<?php

/*
 * This file is part of the Access to Memory (AtoM) software.
 *
 * Access to Memory (AtoM) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Access to Memory (AtoM) is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Access to Memory (AtoM).  If not, see <http://www.gnu.org/licenses/>.
 */

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Global search/replace across archival descriptions.
 *
 * Stock AtoM 2.10.2 ships this action extending SearchAdvancedAction, a class
 * 2.10 no longer has, so the page was a blank fatal on every install, and its
 * template rendered the removed search/advancedSearch partial. It also only ever
 * replaced within the first page of search hits.
 *
 * Rebuilt here as preview-then-confirm over the database column itself: what the
 * preview lists is exactly what gets replaced, with no page limit, and each record
 * is saved through QubitInformationObject so the search index follows.
 */
class SearchGlobalReplaceAction extends sfAction
{
    /** Hard ceiling on one run, so a careless pattern cannot rewrite the catalogue. */
    public const MAX_RECORDS = 500;

    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            QubitAcl::forwardUnauthorized();
        }

        $this->title = $this->context->i18n->__('Global search/replace');
        $this->culture = $this->context->user->getCulture();
        $this->columns = $this->columnChoices();
        $this->collections = $this->collectionChoices();

        $this->column = (string) $request->getParameter('column', 'title');
        $this->pattern = (string) $request->getParameter('pattern', '');
        $this->replacement = (string) $request->getParameter('replacement', '');
        $this->caseSensitive = (bool) $request->getParameter('caseSensitive');
        $this->allowRegex = (bool) $request->getParameter('allowRegex');
        $this->collection = (int) $request->getParameter('collection', 0);
        $this->matches = [];
        $this->error = null;
        $this->form = new sfForm();

        if (!$request->isMethod('post')) {
            return sfView::SUCCESS;
        }

        if (!$this->form->getCSRFToken() || $request->getParameter('_csrf_token') !== $this->form->getCSRFToken()) {
            $this->error = $this->context->i18n->__('The form expired. Please try again.');

            return sfView::SUCCESS;
        }
        if (!isset($this->columns[$this->column])) {
            $this->error = $this->context->i18n->__('Choose a field to replace in.');

            return sfView::SUCCESS;
        }
        if ('' === $this->pattern) {
            $this->error = $this->context->i18n->__('Enter the text to find.');

            return sfView::SUCCESS;
        }
        if ($this->allowRegex && false === @preg_match($this->regex(), '')) {
            $this->error = $this->context->i18n->__('That regular expression is not valid.');

            return sfView::SUCCESS;
        }

        $this->matches = $this->findMatches();
        $this->tooMany = count($this->matches) > self::MAX_RECORDS;

        if ($request->getParameter('confirm') && !$this->tooMany && $this->matches) {
            $changed = 0;
            foreach ($this->matches as $match) {
                $io = QubitInformationObject::getById($match['id']);
                if (null === $io) {
                    continue;
                }
                $current = (string) $this->readColumn($io);
                $updated = $this->replaceIn($current);
                if ($updated !== $current) {
                    $this->writeColumn($io, $updated);
                    $io->save();
                    // save() alone does not reliably reach the index; the stock edit
                    // actions update it explicitly, and so must this.
                    QubitSearch::getInstance()->update($io);
                    ++$changed;
                }
            }

            // Batch mode holds updates until the batch is sent; send it before redirecting.
            $search = QubitSearch::getInstance();
            if (method_exists($search, 'flushBatch')) {
                $search->flushBatch();
            }

            $this->getUser()->setFlash('notice', $this->context->i18n->__('%1% description(s) updated.', ['%1%' => $changed]));
            $this->redirect(['module' => 'search', 'action' => 'globalReplace']);
        }

        return sfView::SUCCESS;
    }

    /** Replaceable text fields: the information_object_i18n columns, plus the identifier. */
    private function columnChoices(): array
    {
        $choices = [];
        $map = new InformationObjectI18nTableMap();
        foreach ($map->getColumns() as $col) {
            if (!$col->isPrimaryKey() && !$col->isForeignKey()) {
                $choices[$col->getPhpName()] = [
                    'label' => sfInflector::humanize(sfInflector::underscore($col->getPhpName())),
                    'table' => 'information_object_i18n',
                    'column' => $col->getName(),
                ];
            }
        }
        $choices['identifier'] = ['label' => $this->context->i18n->__('Identifier'), 'table' => 'information_object', 'column' => 'identifier'];
        unset($choices['culture']);

        return $choices;
    }

    /** Top-level descriptions a run can be limited to. */
    private function collectionChoices(): array
    {
        return DB::table('information_object as io')
            ->join('information_object_i18n as i', function ($j) {
                $j->on('i.id', '=', 'io.id')->where('i.culture', '=', $this->culture);
            })
            ->where('io.parent_id', QubitInformationObject::ROOT_ID)
            ->orderBy('i.title')
            ->pluck('i.title', 'io.id')
            ->all();
    }

    /** Every description whose chosen field contains the pattern, in this culture. */
    private function findMatches(): array
    {
        $def = $this->columns[$this->column];
        $query = DB::table('information_object as io')
            ->join('information_object_i18n as i', function ($j) {
                $j->on('i.id', '=', 'io.id')->where('i.culture', '=', $this->culture);
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'io.id')
            ->where('io.id', '!=', QubitInformationObject::ROOT_ID)
            ->select('io.id', 'i.title', 's.slug', DB::raw(('information_object' === $def['table'] ? 'io.' : 'i.') . $def['column'] . ' as value'));

        if ($this->collection) {
            $root = DB::table('information_object')->where('id', $this->collection)->first(['lft', 'rgt']);
            if ($root) {
                $query->whereBetween('io.lft', [$root->lft, $root->rgt]);
            }
        }

        $matches = [];
        // Narrow in SQL, then decide in PHP with exactly the rule the replacement uses.
        $sqlColumn = ('information_object' === $def['table'] ? 'io.' : 'i.') . $def['column'];
        if (!$this->allowRegex) {
            $query->where($sqlColumn, 'like', '%' . addcslashes($this->pattern, '%_\\') . '%');
        } else {
            $query->whereNotNull($sqlColumn);
        }

        foreach ($query->orderBy('io.lft')->get() as $row) {
            $value = (string) $row->value;
            $updated = $this->replaceIn($value);
            if ($updated !== $value) {
                $matches[] = ['id' => (int) $row->id, 'title' => $row->title, 'slug' => $row->slug, 'before' => $value, 'after' => $updated];
            }
        }

        return $matches;
    }

    private function regex(): string
    {
        return '/' . str_replace('/', '\/', $this->pattern) . '/u' . ($this->caseSensitive ? '' : 'i');
    }

    private function replaceIn(string $value): string
    {
        if ($this->allowRegex) {
            $result = @preg_replace($this->regex(), $this->replacement, $value);

            return null === $result ? $value : $result;
        }

        return $this->caseSensitive
            ? str_replace($this->pattern, $this->replacement, $value)
            : str_ireplace($this->pattern, $this->replacement, $value);
    }

    private function readColumn(QubitInformationObject $io)
    {
        return 'identifier' === $this->column ? $io->identifier : $io->__get($this->column, ['culture' => $this->culture]);
    }

    private function writeColumn(QubitInformationObject $io, string $value): void
    {
        if ('identifier' === $this->column) {
            $io->identifier = $value;
        } else {
            $io->__set($this->column, $value, ['culture' => $this->culture]);
        }
    }
}
