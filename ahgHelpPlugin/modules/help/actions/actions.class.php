<?php

use AtomFramework\Http\Controllers\AhgController;

class helpActions extends AhgController
{
    /**
     * Help center landing page — category cards + search + recent articles.
     */
    public function executeIndex($request)
    {
        $this->loadServices();

        $this->response->setTitle(__('Help Center') . ' - ' . $this->response->getTitle());

        $this->categories = \AhgHelp\Services\HelpArticleService::getCategories();
        $this->recentArticles = \AhgHelp\Services\HelpArticleService::getRecentlyUpdated(5);

        // Category descriptions for cards
        $this->categoryDescriptions = [
            'User Guide' => 'Step-by-step guides for using AtoM Heratio features',
            'User Manual' => 'Comprehensive user manuals',
            'Plugin Reference' => 'Technical reference documentation for each plugin',
            'Technical' => 'Architecture, database, and system documentation',
            'Reference' => 'Functions, workflows, and roadmap documents',
        ];

        // Category icons (Bootstrap Icons class names)
        $this->categoryIcons = [
            'User Guide' => 'bi-book',
            'User Manual' => 'bi-journal-text',
            'Plugin Reference' => 'bi-puzzle',
            'Technical' => 'bi-gear',
            'Reference' => 'bi-file-text',
        ];
    }

    /**
     * Category listing — articles grouped by subcategory.
     */
    public function executeCategory($request)
    {
        $this->loadServices();

        $category = urldecode($request->getParameter('category', ''));
        if (empty($category)) {
            $this->forward404();
        }

        $this->category = $category;
        $this->articles = \AhgHelp\Services\HelpArticleService::getByCategory($category);

        if (empty($this->articles)) {
            $this->forward404();
        }

        $this->response->setTitle($category . ' - ' . __('Help Center') . ' - ' . $this->response->getTitle());

        // Group articles by subcategory
        $this->grouped = [];
        foreach ($this->articles as $article) {
            $sub = $article['subcategory'] ?: 'General';
            $this->grouped[$sub][] = $article;
        }
        ksort($this->grouped);
    }

    /**
     * Single article view with TOC sidebar and prev/next navigation.
     */
    public function executeArticle($request)
    {
        $this->loadServices();

        $slug = $request->getParameter('slug', '');
        if (empty($slug)) {
            $this->forward404();
        }

        $this->article = \AhgHelp\Services\HelpArticleService::getBySlug($slug);
        if (!$this->article) {
            $this->forward404();
        }

        $this->response->setTitle($this->article['title'] . ' - ' . __('Help Center') . ' - ' . $this->response->getTitle());

        // Parse TOC JSON
        $this->toc = [];
        if (!empty($this->article['toc_json'])) {
            $this->toc = json_decode($this->article['toc_json'], true) ?: [];
        }

        // Get prev/next navigation
        $adjacent = \AhgHelp\Services\HelpArticleService::getAdjacentArticles(
            $this->article['id'],
            $this->article['category']
        );
        $this->prevArticle = $adjacent['prev'];
        $this->nextArticle = $adjacent['next'];
    }

    /**
     * Server-side search results page.
     */
    public function executeSearch($request)
    {
        $this->loadServices();

        $this->query = trim($request->getParameter('q', ''));

        $this->response->setTitle(__('Search Help') . ' - ' . $this->response->getTitle());

        $this->articleResults = [];
        $this->sectionResults = [];

        if (mb_strlen($this->query) >= 2) {
            $this->articleResults = \AhgHelp\Services\HelpArticleService::search($this->query, 20);
            $this->sectionResults = \AhgHelp\Services\HelpArticleService::searchSections($this->query, 20);
        }
    }

    /**
     * API: AJAX search endpoint (returns JSON).
     */
    public function executeApiSearch($request)
    {
        $this->loadServices();

        $query = trim($request->getParameter('q', ''));
        $limit = min((int) $request->getParameter('limit', 10), 50);

        $results = [];
        if (mb_strlen($query) >= 2) {
            $articles = \AhgHelp\Services\HelpArticleService::search($query, $limit);
            foreach ($articles as $a) {
                $results[] = [
                    'slug' => $a['slug'],
                    'title' => $a['title'],
                    'category' => $a['category'],
                    'snippet' => $a['snippet'] ?? '',
                ];
            }
        }

        $this->getResponse()->setContentType('application/json');
        $this->getResponse()->setContent(json_encode(['results' => $results]));

        return sfView::NONE;
    }

    /**
     * API: FlexSearch JSON index for client-side instant search.
     */
    public function executeApiSearchIndex($request)
    {
        $this->loadServices();

        // Load search index service
        $pluginDir = sfConfig::get('sf_plugins_dir') . '/ahgHelpPlugin';
        require_once $pluginDir . '/lib/Services/HelpSearchIndexService.php';

        $index = \AhgHelp\Services\HelpSearchIndexService::buildFlexSearchIndex();

        $this->getResponse()->setContentType('application/json');
        $this->getResponse()->setHttpHeader('Cache-Control', 'public, max-age=300');
        $this->getResponse()->setContent(json_encode($index));

        return sfView::NONE;
    }

    /**
     * API: Context map — URL pattern to help article mappings.
     */
    public function executeApiContextMap($request)
    {
        $contextMap = [
            ['pattern' => '/research/dashboard', 'slug' => 'researcher-user-guide', 'title' => 'Researcher User Guide'],
            ['pattern' => '/research/projects', 'slug' => 'research-knowledge-platform-user-guide', 'title' => 'Research Knowledge Platform'],
            ['pattern' => '/research/book', 'slug' => 'researcher-user-guide', 'anchor' => 'reading-room-bookings', 'title' => 'Reading Room Bookings'],
            ['pattern' => '/research/annotations', 'slug' => 'research-knowledge-platform-user-guide', 'anchor' => 'annotations', 'title' => 'Annotations'],
            ['pattern' => '/research/annotation-studio', 'slug' => 'research-knowledge-platform-user-guide', 'anchor' => 'annotation-studio', 'title' => 'Annotation Studio'],
            ['pattern' => '/accession/browse', 'slug' => 'glam-browse-user-guide', 'anchor' => 'accession-browse', 'title' => 'Accession Browse'],
            ['pattern' => '/display/browse', 'slug' => 'glam-browse-user-guide', 'title' => 'GLAM Browse'],
            ['pattern' => '/admin/settings/ahg', 'slug' => 'ahg-settings-user-guide', 'title' => 'AHG Settings'],
            ['pattern' => '/preservation', 'slug' => 'preservation-user-guide', 'title' => 'Digital Preservation'],
            ['pattern' => '/ingest', 'slug' => 'data-ingest-user-guide', 'title' => 'Data Ingest'],
            ['pattern' => '/iiif', 'slug' => 'iiif-integration-user-guide', 'title' => 'IIIF Integration'],
            ['pattern' => '/audit', 'slug' => 'audit-trail-user-guide', 'title' => 'Audit Trail'],
            ['pattern' => '/privacy', 'slug' => 'privacy-user-guide', 'title' => 'Privacy & Compliance'],
        ];

        $this->getResponse()->setContentType('application/json');
        $this->getResponse()->setHttpHeader('Cache-Control', 'public, max-age=600');
        $this->getResponse()->setContent(json_encode(['mappings' => $contextMap]));

        return sfView::NONE;
    }

    /**
     * API: Chatbot endpoint — search-based or AI-powered responses.
     *
     * POST /help/api/chat
     * Body: {message: string, mode: 'search'|'ai', history: [{role, content}]}
     */
    public function executeApiChat($request)
    {
        $this->loadServices();

        $pluginDir = sfConfig::get('sf_plugins_dir') . '/ahgHelpPlugin';
        require_once $pluginDir . '/lib/Services/HelpChatbotService.php';

        // Parse JSON body or form params
        $message = '';
        $mode = 'search';
        $history = [];

        $contentType = $request->getContentType();
        if (strpos($contentType, 'json') !== false) {
            $body = json_decode(file_get_contents('php://input'), true);
            $message = trim($body['message'] ?? '');
            $mode = $body['mode'] ?? 'search';
            $history = $body['history'] ?? [];
        } else {
            $message = trim($request->getParameter('message', ''));
            $mode = $request->getParameter('mode', 'search');
        }

        if (empty($message)) {
            $this->getResponse()->setContentType('application/json');
            $this->getResponse()->setContent(json_encode([
                'error' => 'Message is required',
            ]));

            return sfView::NONE;
        }

        // Validate mode
        if (!in_array($mode, ['search', 'ai'])) {
            $mode = 'search';
        }

        $result = \AhgHelp\Services\HelpChatbotService::chat($message, $mode, $history);

        // Include AI availability status
        $result['ai_available'] = \AhgHelp\Services\HelpChatbotService::isAiAvailable();

        $this->getResponse()->setContentType('application/json');
        $this->getResponse()->setContent(json_encode($result));

        return sfView::NONE;
    }

    /**
     * Load plugin services (lazy loading for Symfony 1.x).
     */
    protected function loadServices()
    {
        $pluginDir = sfConfig::get('sf_plugins_dir') . '/ahgHelpPlugin';
        require_once $pluginDir . '/lib/Services/HelpMarkdownParser.php';
        require_once $pluginDir . '/lib/Services/HelpArticleService.php';
    }
}
