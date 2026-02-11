<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * Missing Field Records Action
 *
 * Lists records missing a specific field for data quality remediation.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgMuseumPlugin
 */

use Illuminate\Database\Capsule\Manager as DB;

class dashboardMissingFieldAction extends AhgController
{
    // Root repository ID
    private const ROOT_REPOSITORY_ID = 3;

    public function execute($request)
    {
        // Check permissions
        if (!$this->getUser()->isAuthenticated()) {
            $this->forwardUnauthorized();
        }

        $this->fieldName = $request->getParameter('field');
        $this->repositoryId = $request->getParameter('repository');

        // Validate field name
        $fieldDefinitions = ahgDataQualityService::getFieldDefinitions();
        if (!isset($fieldDefinitions[$this->fieldName])) {
            $this->forward404('Unknown field');
        }

        $this->fieldDefinition = $fieldDefinitions[$this->fieldName];

        // Get records missing this field
        $this->records = ahgDataQualityService::getRecordsMissingField(
            $this->fieldName,
            $this->repositoryId,
            200
        );

        // Get repositories for filter using Laravel
        $this->repositories = $this->getRepositories();

        // Category labels
        $this->categoryLabels = ahgDataQualityService::getCategoryLabels();
    }

    /**
     * Forward to unauthorized page
     */
    protected function forwardUnauthorized(): void
    {
        $this->forward('admin', 'secure');
    }

    /**
     * Get repositories for filter dropdown using Laravel
     */
    protected function getRepositories(): array
    {
        $culture = $this->culture() ?? 'en';

        $repositories = DB::table('repository as r')
            ->leftJoin('actor_i18n as ai', function ($join) use ($culture) {
                $join->on('r.id', '=', 'ai.id')
                    ->where('ai.culture', '=', $culture);
            })
            ->leftJoin('actor_i18n as ai_en', function ($join) {
                $join->on('r.id', '=', 'ai_en.id')
                    ->where('ai_en.culture', '=', 'en');
            })
            ->where('r.id', '!=', self::ROOT_REPOSITORY_ID)
            ->whereNotNull(DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name)'))
            ->orderBy(DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name)'))
            ->select([
                'r.id',
                DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name) as name'),
            ])
            ->get();

        $result = [];
        foreach ($repositories as $repo) {
            $result[$repo->id] = $repo->name;
        }

        return $result;
    }
}