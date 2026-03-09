<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Library Acquisition REST API
 *
 * Handles API requests for orders, order lines, budgets, and batch operations.
 * All endpoints require X-API-Key header authentication.
 *
 * Routes:
 *   /api/library/orders           — List / Create orders
 *   /api/library/orders/:id       — Get / Update / Delete (cancel) order
 *   /api/library/orders/:id/lines — Add line item
 *   /api/library/orders/:id/lines/:line_id          — Update / Delete line item
 *   /api/library/orders/:id/lines/:line_id/receive   — Receive line item
 *   /api/library/budgets          — List / Create budgets
 *   /api/library/batch/isbn-lookup — Batch ISBN lookup
 *   /api/library/batch/capture     — Batch create library items
 *
 * @package    ahgLibraryPlugin
 * @subpackage acquisition
 */
class acquisitionApiAction extends AhgController
{
    /** @var AcquisitionService */
    protected $service;

    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        require_once \sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once \sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/AcquisitionService.php';

        // Authenticate via API key
        $apiKey = $request->getHttpHeader('X-API-Key');
        if (!$this->authenticateApiKey($apiKey)) {
            return $this->jsonResponse(['success' => false, 'error' => 'Unauthorized', 'code' => 'AUTH_REQUIRED'], 401);
        }

        $this->service = \AcquisitionService::getInstance();

        // Parse URI to determine endpoint
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $uri = strtok($uri, '?'); // strip query string
        $method = strtoupper($request->getMethod());

        try {
            return $this->routeRequest($method, $uri, $request);
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 500;
            if ($code < 100 || $code >= 600) {
                $code = 500;
            }

            return $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'INTERNAL_ERROR',
            ], $code);
        }
    }

    // ========================================================================
    // ROUTING
    // ========================================================================

    /**
     * Route the request to the appropriate handler based on URI pattern and HTTP method.
     */
    protected function routeRequest(string $method, string $uri, $request)
    {
        // POST /api/library/batch/isbn-lookup
        if (preg_match('#^/api/library/batch/isbn-lookup$#', $uri)) {
            if ($method !== 'POST') {
                return $this->methodNotAllowed();
            }

            return $this->batchIsbnLookup($request);
        }

        // POST /api/library/batch/capture
        if (preg_match('#^/api/library/batch/capture$#', $uri)) {
            if ($method !== 'POST') {
                return $this->methodNotAllowed();
            }

            return $this->batchCapture($request);
        }

        // /api/library/budgets
        if (preg_match('#^/api/library/budgets$#', $uri)) {
            if ($method === 'GET') {
                return $this->listBudgets($request);
            }
            if ($method === 'POST') {
                return $this->createBudget($request);
            }

            return $this->methodNotAllowed();
        }

        // POST /api/library/orders/:id/lines/:line_id/receive
        if (preg_match('#^/api/library/orders/(\d+)/lines/(\d+)/receive$#', $uri, $m)) {
            if ($method !== 'POST') {
                return $this->methodNotAllowed();
            }

            return $this->receiveLine((int) $m[1], (int) $m[2], $request);
        }

        // /api/library/orders/:id/lines/:line_id
        if (preg_match('#^/api/library/orders/(\d+)/lines/(\d+)$#', $uri, $m)) {
            if ($method === 'PUT') {
                return $this->updateLine((int) $m[1], (int) $m[2], $request);
            }
            if ($method === 'DELETE') {
                return $this->deleteLine((int) $m[1], (int) $m[2]);
            }

            return $this->methodNotAllowed();
        }

        // /api/library/orders/:id/lines
        if (preg_match('#^/api/library/orders/(\d+)/lines$#', $uri, $m)) {
            if ($method !== 'POST') {
                return $this->methodNotAllowed();
            }

            return $this->addLine((int) $m[1], $request);
        }

        // /api/library/orders/:id
        if (preg_match('#^/api/library/orders/(\d+)$#', $uri, $m)) {
            $orderId = (int) $m[1];
            if ($method === 'GET') {
                return $this->getOrder($orderId);
            }
            if ($method === 'PUT') {
                return $this->updateOrder($orderId, $request);
            }
            if ($method === 'DELETE') {
                return $this->cancelOrder($orderId);
            }

            return $this->methodNotAllowed();
        }

        // /api/library/orders
        if (preg_match('#^/api/library/orders$#', $uri)) {
            if ($method === 'GET') {
                return $this->listOrders($request);
            }
            if ($method === 'POST') {
                return $this->createOrder($request);
            }

            return $this->methodNotAllowed();
        }

        return $this->jsonResponse([
            'success' => false,
            'error' => 'Endpoint not found',
            'code' => 'NOT_FOUND',
        ], 404);
    }

    // ========================================================================
    // ORDER HANDLERS
    // ========================================================================

    /**
     * GET /api/library/orders — List orders with pagination and filters.
     */
    protected function listOrders($request)
    {
        $params = [
            'q' => trim($request->getParameter('q', '')),
            'order_status' => $request->getParameter('status', ''),
            'order_type' => $request->getParameter('order_type', ''),
            'page' => max(1, (int) $request->getParameter('page', 1)),
            'limit' => min(100, max(1, (int) $request->getParameter('limit', 25))),
        ];

        $result = $this->service->searchOrders($params);

        return $this->jsonResponse([
            'success' => true,
            'data' => $result['items'],
            'meta' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'pages' => $result['pages'],
            ],
        ]);
    }

    /**
     * GET /api/library/orders/:id — Get single order with line items.
     */
    protected function getOrder(int $orderId)
    {
        $data = $this->service->getOrder($orderId);
        if (!$data) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Order not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'order' => $data['order'],
                'lines' => $data['lines'],
            ],
        ]);
    }

    /**
     * POST /api/library/orders — Create a new order.
     */
    protected function createOrder($request)
    {
        $body = $this->getJsonBody($request);

        $vendorName = trim($body['vendor_name'] ?? '');
        if (empty($vendorName)) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'vendor_name is required',
                'code' => 'VALIDATION_ERROR',
            ], 422);
        }

        $orderId = $this->service->createOrder([
            'vendor_name' => $vendorName,
            'vendor_account' => $body['vendor_account'] ?? null,
            'order_date' => $body['order_date'] ?? date('Y-m-d'),
            'order_type' => $body['order_type'] ?? 'purchase',
            'budget_id' => $body['budget_id'] ?? null,
            'budget_code' => $body['budget_code'] ?? null,
            'currency' => $body['currency'] ?? 'USD',
            'notes' => $body['notes'] ?? null,
        ]);

        $order = $this->service->getOrder($orderId);

        return $this->jsonResponse([
            'success' => true,
            'data' => $order,
        ], 201);
    }

    /**
     * PUT /api/library/orders/:id — Update an existing order.
     */
    protected function updateOrder(int $orderId, $request)
    {
        $existing = DB::table('library_order')->where('id', $orderId)->first();
        if (!$existing) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Order not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        if ($existing->order_status === 'cancelled') {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Cannot update a cancelled order',
                'code' => 'ORDER_CANCELLED',
            ], 409);
        }

        $body = $this->getJsonBody($request);
        $update = ['updated_at' => date('Y-m-d H:i:s')];

        $allowedFields = ['vendor_name', 'vendor_account', 'order_date', 'order_type', 'budget_id', 'currency', 'notes'];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $body)) {
                $update[$field] = $body[$field];
            }
        }

        DB::table('library_order')->where('id', $orderId)->update($update);

        $order = $this->service->getOrder($orderId);

        return $this->jsonResponse([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * DELETE /api/library/orders/:id — Cancel an order (set status=cancelled).
     */
    protected function cancelOrder(int $orderId)
    {
        $existing = DB::table('library_order')->where('id', $orderId)->first();
        if (!$existing) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Order not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        if ($existing->order_status === 'cancelled') {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Order is already cancelled',
                'code' => 'ALREADY_CANCELLED',
            ], 409);
        }

        DB::table('library_order')->where('id', $orderId)->update([
            'order_status' => 'cancelled',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->jsonResponse([
            'success' => true,
            'data' => ['id' => $orderId, 'order_status' => 'cancelled'],
        ]);
    }

    // ========================================================================
    // ORDER LINE HANDLERS
    // ========================================================================

    /**
     * POST /api/library/orders/:id/lines — Add a line item to an order.
     */
    protected function addLine(int $orderId, $request)
    {
        $order = DB::table('library_order')->where('id', $orderId)->first();
        if (!$order) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Order not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        if ($order->order_status === 'cancelled') {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Cannot add lines to a cancelled order',
                'code' => 'ORDER_CANCELLED',
            ], 409);
        }

        $body = $this->getJsonBody($request);

        $title = trim($body['title'] ?? '');
        if (empty($title)) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'title is required',
                'code' => 'VALIDATION_ERROR',
            ], 422);
        }

        $lineId = $this->service->addOrderLine($orderId, [
            'title' => $title,
            'isbn' => $body['isbn'] ?? null,
            'author' => $body['author'] ?? null,
            'publisher' => $body['publisher'] ?? null,
            'quantity' => max(1, (int) ($body['quantity'] ?? 1)),
            'unit_price' => (float) ($body['unit_price'] ?? 0),
            'material_type' => $body['material_type'] ?? null,
            'fund_code' => $body['fund_code'] ?? null,
            'notes' => $body['notes'] ?? null,
        ]);

        $line = DB::table('library_order_line')->where('id', $lineId)->first();

        return $this->jsonResponse([
            'success' => true,
            'data' => $line,
        ], 201);
    }

    /**
     * PUT /api/library/orders/:id/lines/:line_id — Update a line item.
     */
    protected function updateLine(int $orderId, int $lineId, $request)
    {
        $line = DB::table('library_order_line')
            ->where('id', $lineId)
            ->where('order_id', $orderId)
            ->first();

        if (!$line) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Order line not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        $body = $this->getJsonBody($request);
        $update = ['updated_at' => date('Y-m-d H:i:s')];

        $allowedFields = ['title', 'isbn', 'author', 'publisher', 'quantity', 'unit_price', 'material_type', 'fund_code', 'notes'];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $body)) {
                $update[$field] = $body[$field];
            }
        }

        // Recalculate line_total if quantity or unit_price changed
        $quantity = (int) ($update['quantity'] ?? $line->quantity);
        $unitPrice = (float) ($update['unit_price'] ?? $line->unit_price);
        if (array_key_exists('quantity', $update) || array_key_exists('unit_price', $update)) {
            $update['quantity'] = max(1, $quantity);
            $update['unit_price'] = $unitPrice;
            $update['line_total'] = $update['quantity'] * $unitPrice;
        }

        DB::table('library_order_line')->where('id', $lineId)->update($update);

        // Recalculate order total
        $this->recalculateOrderTotal($orderId);

        $updated = DB::table('library_order_line')->where('id', $lineId)->first();

        return $this->jsonResponse([
            'success' => true,
            'data' => $updated,
        ]);
    }

    /**
     * DELETE /api/library/orders/:id/lines/:line_id — Remove a line item.
     */
    protected function deleteLine(int $orderId, int $lineId)
    {
        $line = DB::table('library_order_line')
            ->where('id', $lineId)
            ->where('order_id', $orderId)
            ->first();

        if (!$line) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Order line not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        DB::table('library_order_line')->where('id', $lineId)->delete();

        // Recalculate order total
        $this->recalculateOrderTotal($orderId);

        return $this->jsonResponse([
            'success' => true,
            'data' => ['id' => $lineId, 'deleted' => true],
        ]);
    }

    /**
     * POST /api/library/orders/:id/lines/:line_id/receive — Mark line as received.
     */
    protected function receiveLine(int $orderId, int $lineId, $request)
    {
        $line = DB::table('library_order_line')
            ->where('id', $lineId)
            ->where('order_id', $orderId)
            ->first();

        if (!$line) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Order line not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        $body = $this->getJsonBody($request);
        $quantityReceived = max(1, (int) ($body['quantity_received'] ?? 1));

        $result = $this->service->receiveOrderLine($lineId, $quantityReceived);

        if (!$result['success']) {
            return $this->jsonResponse([
                'success' => false,
                'error' => $result['error'],
                'code' => 'RECEIVE_FAILED',
            ], 400);
        }

        $updated = DB::table('library_order_line')->where('id', $lineId)->first();

        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'line' => $updated,
                'status' => $result['status'],
                'quantity_received' => $result['quantity_received'],
            ],
        ]);
    }

    // ========================================================================
    // BUDGET HANDLERS
    // ========================================================================

    /**
     * GET /api/library/budgets — List budgets with optional fiscal_year filter.
     */
    protected function listBudgets($request)
    {
        $fiscalYear = $request->getParameter('fiscal_year');
        $budgets = $this->service->getBudgets($fiscalYear ?: null);

        return $this->jsonResponse([
            'success' => true,
            'data' => $budgets,
            'meta' => [
                'total' => count($budgets),
            ],
        ]);
    }

    /**
     * POST /api/library/budgets — Create a new budget.
     */
    protected function createBudget($request)
    {
        $body = $this->getJsonBody($request);

        $budgetCode = trim($body['budget_code'] ?? '');
        $fundName = trim($body['fund_name'] ?? '');

        if (empty($budgetCode) || empty($fundName)) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'budget_code and fund_name are required',
                'code' => 'VALIDATION_ERROR',
            ], 422);
        }

        // Check for duplicate budget code in same fiscal year
        $fiscalYear = $body['fiscal_year'] ?? date('Y');
        $exists = DB::table('library_budget')
            ->where('budget_code', $budgetCode)
            ->where('fiscal_year', $fiscalYear)
            ->exists();

        if ($exists) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Budget code already exists for this fiscal year',
                'code' => 'DUPLICATE_BUDGET',
            ], 409);
        }

        $budgetId = $this->service->createBudget([
            'budget_name' => $fundName,
            'budget_code' => $budgetCode,
            'fiscal_year' => $fiscalYear,
            'allocated_amount' => (float) ($body['allocated_amount'] ?? 0),
            'currency' => $body['currency'] ?? 'USD',
            'category' => $body['category'] ?? 'general',
            'notes' => $body['notes'] ?? null,
        ]);

        $budget = $this->service->getBudgetSummary($budgetId);

        return $this->jsonResponse([
            'success' => true,
            'data' => $budget,
        ], 201);
    }

    // ========================================================================
    // BATCH HANDLERS
    // ========================================================================

    /**
     * POST /api/library/batch/isbn-lookup — Batch ISBN lookup.
     */
    protected function batchIsbnLookup($request)
    {
        $body = $this->getJsonBody($request);
        $isbns = $body['isbns'] ?? [];

        if (empty($isbns) || !is_array($isbns)) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'isbns array is required',
                'code' => 'VALIDATION_ERROR',
            ], 422);
        }

        // Cap at 50 ISBNs per batch request
        $isbns = array_slice($isbns, 0, 50);

        require_once \sfConfig::get('sf_root_dir') . '/atom-framework/src/Services/IsbnLookupService.php';
        $isbnService = new \AtomExtensions\Services\IsbnLookupService();

        $results = [];
        foreach ($isbns as $isbn) {
            $isbn = trim((string) $isbn);
            if (empty($isbn)) {
                continue;
            }

            try {
                $data = $isbnService->lookupByIsbn($isbn);
                $results[] = [
                    'isbn' => $isbn,
                    'found' => !empty($data),
                    'data' => $data ?: null,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'isbn' => $isbn,
                    'found' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $this->jsonResponse([
            'success' => true,
            'data' => $results,
            'meta' => [
                'total' => count($results),
                'found' => count(array_filter($results, fn($r) => $r['found'])),
            ],
        ]);
    }

    /**
     * POST /api/library/batch/capture — Batch create library items.
     */
    protected function batchCapture($request)
    {
        $body = $this->getJsonBody($request);
        $items = $body['items'] ?? [];
        $orderId = $body['order_id'] ?? null;

        if (empty($items) || !is_array($items)) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'items array is required',
                'code' => 'VALIDATION_ERROR',
            ], 422);
        }

        // Validate order if provided
        if ($orderId) {
            $order = DB::table('library_order')->where('id', (int) $orderId)->first();
            if (!$order) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Order not found',
                    'code' => 'NOT_FOUND',
                ], 404);
            }
        }

        // Cap at 100 items per batch
        $items = array_slice($items, 0, 100);

        require_once \sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/LibraryService.php';
        $libraryService = \LibraryService::getInstance();

        $created = [];
        $errors = [];

        foreach ($items as $index => $item) {
            $title = trim($item['title'] ?? '');
            if (empty($title)) {
                $errors[] = [
                    'index' => $index,
                    'error' => 'title is required',
                ];
                continue;
            }

            try {
                $itemId = $libraryService->createItem([
                    'title' => $title,
                    'isbn' => $item['isbn'] ?? null,
                    'author' => $item['author'] ?? null,
                    'publisher' => $item['publisher'] ?? null,
                    'publication_date' => $item['publication_date'] ?? null,
                    'edition' => $item['edition'] ?? null,
                    'call_number' => $item['call_number'] ?? null,
                    'material_type' => $item['material_type'] ?? null,
                    'subject' => $item['subject'] ?? null,
                    'language' => $item['language'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ]);

                // Link to order line if order_id provided
                if ($orderId && $itemId) {
                    $this->service->addOrderLine((int) $orderId, [
                        'library_item_id' => $itemId,
                        'title' => $title,
                        'isbn' => $item['isbn'] ?? null,
                        'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                        'unit_price' => (float) ($item['unit_price'] ?? 0),
                    ]);
                }

                $created[] = [
                    'index' => $index,
                    'id' => $itemId,
                    'title' => $title,
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'title' => $title,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'created' => $created,
                'errors' => $errors,
            ],
            'meta' => [
                'total_submitted' => count($items),
                'total_created' => count($created),
                'total_errors' => count($errors),
                'order_id' => $orderId,
            ],
        ]);
    }

    // ========================================================================
    // HELPERS
    // ========================================================================

    /**
     * Authenticate an API key against the ahg_api_key table.
     */
    protected function authenticateApiKey(?string $key): bool
    {
        if (empty($key)) {
            return false;
        }

        $hash = hash('sha256', $key);

        try {
            return DB::table('ahg_api_key')
                ->where('api_key', $hash)
                ->where('is_active', 1)
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', date('Y-m-d H:i:s'));
                })
                ->exists();
        } catch (\Exception $e) {
            // Table may not exist if ahgAPIPlugin is not installed
            return false;
        }
    }

    /**
     * Parse JSON request body.
     */
    protected function getJsonBody($request): array
    {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return [];
        }

        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Render a JSON response with status code.
     */
    protected function jsonResponse(array $data, int $status = 200)
    {
        $this->getResponse()->setStatusCode($status);

        return $this->renderJson($data, $status);
    }

    /**
     * Return a 405 Method Not Allowed response.
     */
    protected function methodNotAllowed()
    {
        return $this->jsonResponse([
            'success' => false,
            'error' => 'Method not allowed',
            'code' => 'METHOD_NOT_ALLOWED',
        ], 405);
    }

    /**
     * Recalculate order total from lines (local helper to avoid protected access issues).
     */
    protected function recalculateOrderTotal(int $orderId): void
    {
        $total = DB::table('library_order_line')
            ->where('order_id', $orderId)
            ->sum('line_total');

        DB::table('library_order')->where('id', $orderId)->update([
            'total_amount' => $total,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
