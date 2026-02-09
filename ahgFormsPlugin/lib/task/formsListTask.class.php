<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI task to list form templates.
 */
class formsListTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('type', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter by form type (information_object, accession, actor)'),
            new sfCommandOption('assignments', null, sfCommandOption::PARAMETER_NONE, 'Show assignments'),
            new sfCommandOption('fields', null, sfCommandOption::PARAMETER_OPTIONAL, 'Show fields for specific template ID'),
        ]);

        $this->namespace = 'forms';
        $this->name = 'list';
        $this->briefDescription = 'List form templates';
        $this->detailedDescription = <<<EOF
List all form templates in the system.

Examples:
  php symfony forms:list                           # List all templates
  php symfony forms:list --type=information_object # Filter by type
  php symfony forms:list --assignments             # Show template assignments
  php symfony forms:list --fields=1                # Show fields for template ID 1
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }

        if ($options['fields']) {
            $this->showTemplateFields((int) $options['fields']);

            return;
        }

        if ($options['assignments']) {
            $this->showAssignments();

            return;
        }

        $this->showTemplates($options['type']);
    }

    protected function showTemplates(?string $type): void
    {
        $this->logSection('forms', '=== Form Templates ===');

        $query = DB::table('ahg_form_template')
            ->orderBy('form_type')
            ->orderBy('name');

        if ($type) {
            $query->where('form_type', $type);
        }

        $templates = $query->get();

        if ($templates->isEmpty()) {
            $this->logSection('forms', 'No templates found');

            return;
        }

        $this->logSection('forms', "Found {$templates->count()} templates:");
        echo "\n";

        $currentType = null;
        foreach ($templates as $t) {
            if ($currentType !== $t->form_type) {
                $currentType = $t->form_type;
                $this->logSection('forms', strtoupper($currentType) . ':');
            }

            $flags = [];
            if ($t->is_default) {
                $flags[] = 'DEFAULT';
            }
            if ($t->is_system) {
                $flags[] = 'SYSTEM';
            }
            if (!$t->is_active) {
                $flags[] = 'INACTIVE';
            }
            $flagStr = $flags ? ' [' . implode(', ', $flags) . ']' : '';

            $fieldCount = DB::table('ahg_form_field')
                ->where('template_id', $t->id)
                ->count();

            $this->logSection('forms', "  #{$t->id}: {$t->name}{$flagStr}");
            $this->logSection('forms', "      Fields: {$fieldCount} | Version: {$t->version}");
            if ($t->description) {
                $this->logSection('forms', "      {$t->description}");
            }
        }
    }

    protected function showTemplateFields(int $templateId): void
    {
        $template = DB::table('ahg_form_template')
            ->where('id', $templateId)
            ->first();

        if (!$template) {
            $this->logSection('forms', "Template #{$templateId} not found", null, 'ERROR');

            return;
        }

        $this->logSection('forms', "=== Fields for: {$template->name} ===");

        $fields = DB::table('ahg_form_field')
            ->where('template_id', $templateId)
            ->orderBy('sort_order')
            ->get();

        if ($fields->isEmpty()) {
            $this->logSection('forms', 'No fields defined');

            return;
        }

        $currentSection = null;
        $currentTab = null;

        foreach ($fields as $f) {
            if ($f->tab_name !== $currentTab) {
                $currentTab = $f->tab_name;
                if ($currentTab) {
                    echo "\n";
                    $this->logSection('forms', "TAB: {$currentTab}");
                }
            }

            if ($f->section_name !== $currentSection) {
                $currentSection = $f->section_name;
                if ($currentSection) {
                    $this->logSection('forms', "  Section: {$currentSection}");
                }
            }

            $flags = [];
            if ($f->is_required) {
                $flags[] = 'required';
            }
            if ($f->is_repeatable) {
                $flags[] = 'repeatable';
            }
            if ($f->is_hidden) {
                $flags[] = 'hidden';
            }
            $flagStr = $flags ? ' (' . implode(', ', $flags) . ')' : '';

            $this->logSection('forms', "    {$f->sort_order}. [{$f->field_type}] {$f->field_name}: {$f->label}{$flagStr}");

            // Show mapping if exists
            $mapping = DB::table('ahg_form_field_mapping')
                ->where('field_id', $f->id)
                ->first();

            if ($mapping) {
                $this->logSection('forms', "       -> {$mapping->target_table}.{$mapping->target_column}");
            }
        }
    }

    protected function showAssignments(): void
    {
        $this->logSection('forms', '=== Form Assignments ===');

        $assignments = DB::table('ahg_form_assignment as fa')
            ->join('ahg_form_template as ft', 'ft.id', '=', 'fa.template_id')
            ->leftJoin('repository_i18n as ri', function ($join) {
                $join->on('fa.repository_id', '=', 'ri.id')
                    ->where('ri.culture', '=', 'en');
            })
            ->leftJoin('term_i18n as ti', function ($join) {
                $join->on('fa.level_of_description_id', '=', 'ti.id')
                    ->where('ti.culture', '=', 'en');
            })
            ->select([
                'fa.*',
                'ft.name as template_name',
                'ft.form_type',
                'ri.authorized_form_of_name as repository_name',
                'ti.name as level_name',
            ])
            ->orderBy('fa.priority', 'desc')
            ->get();

        if ($assignments->isEmpty()) {
            $this->logSection('forms', 'No assignments found');

            return;
        }

        foreach ($assignments as $a) {
            $status = $a->is_active ? 'ACTIVE' : 'INACTIVE';
            $this->logSection('forms', "#{$a->id}: {$a->template_name} [{$status}]");

            $conditions = [];
            if ($a->repository_name) {
                $conditions[] = "Repository: {$a->repository_name}";
            }
            if ($a->level_name) {
                $conditions[] = "Level: {$a->level_name}";
            }
            if ($a->collection_id) {
                $conditions[] = "Collection ID: {$a->collection_id}";
            }
            if (empty($conditions)) {
                $conditions[] = 'All (default)';
            }

            $this->logSection('forms', '  Applies to: ' . implode(', ', $conditions));
            $this->logSection('forms', "  Priority: {$a->priority} | Inherit: " . ($a->inherit_to_children ? 'Yes' : 'No'));
            echo "\n";
        }
    }
}
