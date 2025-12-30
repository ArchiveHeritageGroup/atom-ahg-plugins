<?php
/**
 * Data Quality Dashboard Action
 *
 * Displays collection-wide data quality metrics and drilling tools.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgMuseumPlugin
 */

use Illuminate\Database\Capsule\Manager as DB;

class dashboardIndexAction extends sfAction
{
    // Root repository ID
    private const ROOT_REPOSITORY_ID = 3;

    public function execute($request)
    {
        // Check permissions
        if (!$this->context->user->isAuthenticated()) {
            $this->forwardUnauthorized();
        }

        // Get filter parameters
        $this->repositoryId = $request->getParameter('repository');
        $this->parentId = $request->getParameter('parent');

        // Get repository list for filter using Laravel
        $this->repositories = $this->getRepositories();

        // Analyze collection
        $this->analysis = arDataQualityService::analyzeCollection(
            $this->repositoryId,
            $this->parentId,
            1000
        );

        // Get category labels
        $this->categoryLabels = arDataQualityService::getCategoryLabels();

        // Get field definitions
        $this->fieldDefinitions = arDataQualityService::getFieldDefinitions();
        // Handle export request
        if ($request->getParameter('export') === 'csv') {
            $csv = arDataQualityService::exportToCSV($this->repositoryId);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="data_quality_report_' . date('Y-m-d') . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');
            echo $csv;
            exit;
        }
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
        $culture = $this->getUser()->getCulture() ?? 'en';

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