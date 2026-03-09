<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Batch capture of library items from ISBN lookup, optionally linked to a Purchase Order.
 */
class acquisitionBatchCaptureAction extends AhgController
{
    public function execute($request)
    {
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        $this->notice = $this->getUser()->getFlash('notice');
        $this->error  = $this->getUser()->getFlash('error');

        // Load open orders for the PO dropdown
        $this->orders = [];
        try {
            $this->orders = DB::table('library_order')
                ->whereIn('status', ['draft', 'submitted', 'approved', 'ordered'])
                ->orderBy('order_date', 'desc')
                ->get();
        } catch (\Exception $e) {
            // Table may not exist on fresh installs
        }

        // Selected order
        $this->selectedOrderId = (int) $request->getParameter('order_id', 0);
        $this->selectedOrder   = null;
        $this->orderLines      = [];

        if ($this->selectedOrderId) {
            $this->selectedOrder = DB::table('library_order')
                ->where('id', $this->selectedOrderId)
                ->first();

            if ($this->selectedOrder) {
                $this->orderLines = DB::table('library_order_line')
                    ->where('order_id', $this->selectedOrderId)
                    ->get()
                    ->toArray();
            }
        }

        // Initialise lookup results for template
        $this->lookupResults = [];
        $this->lookupErrors  = [];
        $this->rawIsbns      = '';

        if ('POST' !== $request->getMethod()) {
            return;
        }

        $actionType = $request->getParameter('action_type', '');

        if ('lookup' === $actionType) {
            $this->handleLookup($request);
        } elseif ('save' === $actionType) {
            $this->handleSave($request);
        }
    }

    // ---------------------------------------------------------------
    //  ISBN Lookup
    // ---------------------------------------------------------------

    protected function handleLookup($request): void
    {
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Services/IsbnLookupService.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Services/GlamIdentifierService.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Services/HttpClientService.php';

        $raw = trim($request->getParameter('isbns', ''));
        $this->rawIsbns = $raw;

        if (empty($raw)) {
            $this->error = __('Please enter at least one ISBN.');
            return;
        }

        $lines = array_filter(array_map('trim', preg_split('/[\r\n]+/', $raw)));

        $service = new \AtomFramework\Services\IsbnLookupService();
        $results = [];
        $errors  = [];

        foreach ($lines as $isbn) {
            try {
                $data = $service->lookupByIsbn($isbn);
                if ($data) {
                    $mapped = $service->mapToLibraryFields($data);
                    $mapped['_raw'] = $data;
                    $mapped['isbn_input'] = $isbn;
                    $results[] = $mapped;
                } else {
                    $errors[] = __('No results found for ISBN: %1%', ['%1%' => $isbn]);
                }
            } catch (\Exception $e) {
                $errors[] = __('Error looking up ISBN %1%: %2%', ['%1%' => $isbn, '%2%' => $e->getMessage()]);
            }
        }

        $this->lookupResults = $results;
        $this->lookupErrors  = $errors;
    }

    // ---------------------------------------------------------------
    //  Save items
    // ---------------------------------------------------------------

    protected function handleSave($request): void
    {
        $items   = $request->getParameter('items', []);
        $orderId = (int) $request->getParameter('order_id', 0);

        if (empty($items) || !is_array($items)) {
            $this->getUser()->setFlash('error', __('No items selected for saving.'));
            $this->redirect(['module' => 'acquisition', 'action' => 'batchCapture', 'order_id' => $orderId ?: null]);
            return;
        }

        // Resolve "Item" level of description
        $itemTermId = DB::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', 34)
            ->where('term_i18n.name', 'Item')
            ->where('term_i18n.culture', 'en')
            ->value('term.id');

        if (!$itemTermId) {
            $itemTermId = 242; // fallback
        }

        $savedCount = 0;
        $errorCount = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($items as $item) {
            if (empty($item['include'])) {
                continue;
            }

            try {
                DB::connection()->beginTransaction();

                $title = trim($item['title'] ?? '');
                if (empty($title)) {
                    $errorCount++;
                    DB::connection()->rollBack();
                    continue;
                }

                // 1. Create object row
                $objectId = DB::table('object')->insertGetId([
                    'class_name'    => 'QubitInformationObject',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                    'serial_number' => 0,
                ]);

                // 2. Create information_object row (parent_id=1 = root)
                DB::table('information_object')->insert([
                    'id'                       => $objectId,
                    'parent_id'                => 1,
                    'level_of_description_id'  => $itemTermId,
                    'source_culture'           => 'en',
                    'lft'                      => 0,
                    'rgt'                      => 0,
                ]);

                // 3. Create information_object_i18n row
                DB::table('information_object_i18n')->insert([
                    'id'                 => $objectId,
                    'culture'            => 'en',
                    'title'              => mb_substr($title, 0, 1024),
                    'scope_and_content'  => trim($item['description'] ?? '') ?: null,
                    'extent_and_medium'  => trim($item['pages'] ?? '') ? ($item['pages'] . ' pages') : null,
                ]);

                // 4. Create status row (publication = Published)
                DB::table('status')->insert([
                    'object_id'     => $objectId,
                    'type_id'       => 158,
                    'status_id'     => 160,
                    'serial_number' => 0,
                ]);

                // 5. Create library_item
                $isbn = trim($item['isbn'] ?? '');
                $libraryItemId = DB::table('library_item')->insertGetId([
                    'information_object_id' => $objectId,
                    'material_type'         => 'monograph',
                    'subtitle'              => trim($item['subtitle'] ?? '') ?: null,
                    'isbn'                  => $isbn ?: null,
                    'publisher'             => trim($item['publisher'] ?? '') ?: null,
                    'publication_place'     => trim($item['place_of_publication'] ?? '') ?: null,
                    'publication_date'      => trim($item['year'] ?? '') ?: null,
                    'pagination'            => trim($item['pages'] ?? '') ?: null,
                    'cover_url'             => trim($item['cover_url'] ?? '') ?: null,
                    'lccn'                  => trim($item['lccn'] ?? '') ?: null,
                    'oclc_number'           => trim($item['oclc_number'] ?? '') ?: null,
                    'language'              => trim($item['language'] ?? '') ?: null,
                    'summary'               => trim($item['description'] ?? '') ?: null,
                    'total_copies'          => 1,
                    'available_copies'      => 1,
                    'circulation_status'    => 'available',
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ]);

                // 6. Create one library_copy
                DB::table('library_copy')->insert([
                    'library_item_id'    => $libraryItemId,
                    'copy_number'        => 1,
                    'status'             => 'available',
                    'acquisition_method' => $orderId ? 'purchase' : null,
                    'acquisition_date'   => date('Y-m-d'),
                ]);

                // 7. Create library_item_creator rows
                $authors = trim($item['author'] ?? '');
                if (!empty($authors)) {
                    $authorList = array_filter(array_map('trim', preg_split('/[;,]/', $authors)));
                    foreach ($authorList as $idx => $authorName) {
                        DB::table('library_item_creator')->insert([
                            'library_item_id' => $libraryItemId,
                            'name'            => mb_substr($authorName, 0, 500),
                            'role'            => 'author',
                            'sort_order'      => $idx,
                        ]);
                    }
                }

                // 8. Create library_item_subject rows
                $subjects = trim($item['subjects'] ?? '');
                if (!empty($subjects)) {
                    $subjectList = array_filter(array_map('trim', preg_split('/[;]/', $subjects)));
                    foreach ($subjectList as $subjectHeading) {
                        DB::table('library_item_subject')->insert([
                            'library_item_id' => $libraryItemId,
                            'heading'         => mb_substr($subjectHeading, 0, 500),
                            'subject_type'    => 'topic',
                        ]);
                    }
                }

                // 9. Optionally link to order line
                if ($orderId) {
                    DB::table('library_order_line')
                        ->where('order_id', $orderId)
                        ->where('isbn', $isbn)
                        ->whereNull('library_item_id')
                        ->limit(1)
                        ->update([
                            'library_item_id'   => $libraryItemId,
                            'status'            => 'received',
                            'quantity_received'  => 1,
                            'received_date'     => date('Y-m-d'),
                        ]);
                }

                DB::connection()->commit();
                $savedCount++;
            } catch (\Exception $e) {
                DB::connection()->rollBack();
                $errorCount++;
            }
        }

        // Rebuild nested set after batch insert
        if ($savedCount > 0) {
            try {
                exec('php ' . escapeshellarg(sfConfig::get('sf_root_dir') . '/symfony') . ' propel:build-nested-set 2>&1', $output, $rc);
            } catch (\Exception $e) {
                // Non-fatal — nested set will be rebuilt on next cache clear
            }
        }

        $msg = __('%1% item(s) created successfully.', ['%1%' => $savedCount]);
        if ($errorCount > 0) {
            $msg .= ' ' . __('%1% item(s) failed.', ['%1%' => $errorCount]);
        }

        $this->getUser()->setFlash('notice', $msg);
        $this->redirect(['module' => 'acquisition', 'action' => 'batchCapture', 'order_id' => $orderId ?: null]);
    }
}
