<?php
/**
 * Reports Menu Component
 */

use Illuminate\Database\Capsule\Manager as DB;

class reportsMenuComponent extends sfComponent
{
    public function execute($request)
    {
        // Menu structure
        $this->menuItems = [
            'reports' => [
                'label' => 'Reports',
                'icon' => 'fa-file-alt',
                'items' => [
                    ['label' => 'Archival Description Report', 'url' => 'reports/descriptions', 'icon' => 'fa-archive'],
                    ['label' => 'Authority Record Report', 'url' => 'reports/authorities', 'icon' => 'fa-users'],
                    ['label' => 'Repository Report', 'url' => 'reports/repositories', 'icon' => 'fa-building'],
                    ['label' => 'Accession Report', 'url' => 'reports/accessions', 'icon' => 'fa-inbox'],
                    ['label' => 'Physical Storage Report', 'url' => 'reports/storage', 'icon' => 'fa-boxes'],
                    ['label' => 'Recent Updates', 'url' => 'reports/recent', 'icon' => 'fa-clock'],
                    ['label' => 'User Activity Report', 'url' => 'reports/activity', 'icon' => 'fa-user-clock']
                ]
            ],
            'audit' => [
                'label' => 'Audit',
                'icon' => 'fa-clipboard-check',
                'items' => [
                    ['label' => 'Description Audit', 'url' => 'reports/audit/descriptions', 'icon' => 'fa-search'],
                    ['label' => 'Authority Audit', 'url' => 'reports/audit/authorities', 'icon' => 'fa-user-check'],
                    ['label' => 'Repository Audit', 'url' => 'reports/audit/repositories', 'icon' => 'fa-building'],
                    ['label' => 'Permissions Audit', 'url' => 'reports/audit/permissions', 'icon' => 'fa-key'],
                    ['label' => 'Data Quality Audit', 'url' => 'dashboard/quality', 'icon' => 'fa-chart-line']
                ]
            ],
            'dashboards' => [
                'label' => 'Dashboards',
                'icon' => 'fa-tachometer-alt',
                'items' => [
                    ['label' => 'System Overview', 'url' => 'reports/dashboard', 'icon' => 'fa-desktop'],
                    ['label' => 'Collections Management', 'url' => 'spectrum/dashboard', 'icon' => 'fa-layer-group'],
                    ['label' => 'Data Quality', 'url' => 'dashboard/quality', 'icon' => 'fa-chart-bar'],
                    ['label' => 'GRAP 103 Dashboard', 'url' => 'spectrum/grap', 'icon' => 'fa-balance-scale'],
                    ['label' => 'Digital Assets', 'url' => 'reports/digital', 'icon' => 'fa-images']
                ]
            ],
            'export' => [
                'label' => 'Export',
                'icon' => 'fa-download',
                'items' => [
                    ['label' => 'Full Archival Export', 'url' => 'export/archival', 'icon' => 'fa-file-export'],
                    // Sector exports - implemented in each sector plugin
                    // ['label' => 'Archive Export (EAD/CSV)', 'url' => 'export/archival', 'icon' => 'fa-archive'],
                    // ['label' => 'Library Export (MARC)', 'url' => 'ahgLibraryPlugin/export', 'icon' => 'fa-book'],
                    // ['label' => 'Museum Export (CCO)', 'url' => 'sfMuseumPlugin/export', 'icon' => 'fa-university'],
                    // ['label' => 'DAM Export (IPTC)', 'url' => 'ahgDAMPlugin/export', 'icon' => 'fa-images'],
                    ['label' => 'CIDOC-CRM Export', 'url' => 'cidoc/export', 'icon' => 'fa-project-diagram'],
                    ['label' => 'Authority Export (EAC)', 'url' => 'export/authorities', 'icon' => 'fa-users'],
                    ['label' => 'GRAP 103 Report', 'url' => 'export/grap', 'icon' => 'fa-file-invoice-dollar']
                ]
            ],
            'import' => [
                'label' => 'Import',
                'icon' => 'fa-upload',
                'items' => [
                    ['label' => 'CSV Import', 'url' => 'import/csv', 'icon' => 'fa-file-csv'],
                    ['label' => 'XML Import (EAD)', 'url' => 'import/ead', 'icon' => 'fa-file-code'],
                    ['label' => 'Import Package', 'url' => 'import/package', 'icon' => 'fa-file-archive'],
                    ['label' => 'Authority Import', 'url' => 'import/authorities', 'icon' => 'fa-users']
                ]
            ]
        ];

        // Get pending counts for badges
        $this->pendingCounts = $this->getPendingCounts();
    }

    protected function getPendingCounts()
    {
        return [
            'drafts' => DB::table('information_object')
                ->where('id', '!=', 1)
                ->where('publication_status_id', 159)
                ->count(),
            'recentUpdates' => DB::table('information_object')
                ->where('id', '!=', 1)
                ->where('updated_at', '>=', date('Y-m-d', strtotime('-24 hours')))
                ->count()
        ];
    }
}
