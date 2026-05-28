<?php

declare(strict_types=1);

/**
 * KBART Vendor Management Actions
 *
 * @package    ahgLibraryPlugin
 * @subpackage kbartVendor
 */

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

class kbartVendorActions extends AhgController
{
    protected KbartRemoteService $kbartService;

    public function preExecute(): void
    {
        $this->kbartService = new KbartRemoteService();
    }

    /**
     * List all vendors with stats.
     */
    public function executeIndex($request)
    {
        $this->vendors = DB::table('library_kbart_vendor')
            ->orderBy('name')
            ->get()
            ->all();
    }

    /**
     * Add a new vendor (POST handler).
     */
    public function executeAdd($request)
    {
        if ($request->getMethod() !== 'POST') {
            $this->forward404();
        }

        $name = trim($request->getParameter('name', ''));
        $feedUrl = trim($request->getParameter('feed_url', ''));
        $active = $request->getParameter('active', '1') === '1' ? 1 : 0;

        if ($name === '' || $feedUrl === '') {
            $this->getUser()->setFlash('error', 'Name and feed URL are required.');
            $this->redirect('@kbart_vendor_index');
        }

        if (!filter_var($feedUrl, FILTER_VALIDATE_URL)) {
            $this->getUser()->setFlash('error', 'Invalid feed URL format.');
            $this->redirect('@kbart_vendor_index');
        }

        // Check for duplicate URL
        $exists = DB::table('library_kbart_vendor')
            ->where('feed_url', $feedUrl)
            ->exists();

        if ($exists) {
            $this->getUser()->setFlash('error', 'A vendor with this feed URL already exists.');
            $this->redirect('@kbart_vendor_index');
        }

        $now = date('Y-m-d H:i:s');

        $vendorId = DB::table('library_kbart_vendor')->insertGetId([
            'name' => $name,
            'feed_url' => $feedUrl,
            'active' => $active,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->getUser()->setFlash('notice', "Vendor '{$name}' added successfully.");
        $this->redirect('@kbart_vendor_index');
    }

    /**
     * Edit an existing vendor (POST handler).
     */
    public function executeEdit($request)
    {
        if ($request->getMethod() !== 'POST') {
            $this->forward404();
        }

        $id = (int) $request->getParameter('id');
        $name = trim($request->getParameter('name', ''));
        $feedUrl = trim($request->getParameter('feed_url', ''));
        $active = $request->getParameter('active', '1') === '1' ? 1 : 0;

        if ($name === '' || $feedUrl === '' || $id <= 0) {
            $this->getUser()->setFlash('error', 'Name, feed URL, and valid ID are required.');
            $this->redirect('@kbart_vendor_index');
        }

        if (!filter_var($feedUrl, FILTER_VALIDATE_URL)) {
            $this->getUser()->setFlash('error', 'Invalid feed URL format.');
            $this->redirect('@kbart_vendor_index');
        }

        // Check for duplicate URL (excluding current vendor)
        $exists = DB::table('library_kbart_vendor')
            ->where('feed_url', $feedUrl)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            $this->getUser()->setFlash('error', 'Another vendor with this feed URL already exists.');
            $this->redirect('@kbart_vendor_index');
        }

        DB::table('library_kbart_vendor')
            ->where('id', $id)
            ->update([
                'name' => $name,
                'feed_url' => $feedUrl,
                'active' => $active,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $this->getUser()->setFlash('notice', "Vendor '{$name}' updated successfully.");
        $this->redirect('@kbart_vendor_index');
    }

    /**
     * Toggle active status for a vendor.
     */
    public function executeToggle($request)
    {
        $id = (int) $request->getParameter('id');

        if ($id <= 0) {
            $this->getUser()->setFlash('error', 'Invalid vendor ID.');
            $this->redirect('@kbart_vendor_index');
        }

        $vendor = $this->kbartService->getVendor($id);
        if (!$vendor) {
            $this->getUser()->setFlash('error', 'Vendor not found.');
            $this->redirect('@kbart_vendor_index');
        }

        $newActive = $vendor->active ? 0 : 1;

        DB::table('library_kbart_vendor')
            ->where('id', $id)
            ->update([
                'active' => $newActive,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $status = $newActive ? 'enabled' : 'disabled';
        $this->getUser()->setFlash('notice', "Vendor '{$vendor->name}' is now {$status}.");
        $this->redirect('@kbart_vendor_index');
    }

    /**
     * Delete a vendor.
     */
    public function executeDelete($request)
    {
        $id = (int) $request->getParameter('id');

        if ($id <= 0) {
            $this->getUser()->setFlash('error', 'Invalid vendor ID.');
            $this->redirect('@kbart_vendor_index');
        }

        $vendor = $this->kbartService->getVendor($id);
        if (!$vendor) {
            $this->getUser()->setFlash('error', 'Vendor not found.');
            $this->redirect('@kbart_vendor_index');
        }

        $name = $vendor->name;

        // Delete import logs first (FK cascade should handle it, but be explicit)
        DB::table('library_kbart_import_log')->where('vendor_id', $id)->delete();

        DB::table('library_kbart_vendor')->where('id', $id)->delete();

        $this->getUser()->setFlash('notice', "Vendor '{$name}' deleted.");
        $this->redirect('@kbart_vendor_index');
    }

    /**
     * Fetch a vendor's KBART feed now.
     */
    public function executeFetch($request)
    {
        $id = (int) $request->getParameter('id');

        if ($id <= 0) {
            $this->getUser()->setFlash('error', 'Invalid vendor ID.');
            $this->redirect('@kbart_vendor_index');
        }

        $vendor = $this->kbartService->getVendor($id);
        if (!$vendor) {
            $this->getUser()->setFlash('error', 'Vendor not found.');
            $this->redirect('@kbart_vendor_index');
        }

        $result = $this->kbartService->fetchVendor($id);

        if ($result['success']) {
            $stats = $result['stats'];
            $msg = sprintf(
                "Fetched %d rows for '%s' (new: %d, removed: %d).",
                $stats['row_count'],
                $vendor->name,
                $stats['new_count'],
                $stats['removed_count']
            );
            $this->getUser()->setFlash('notice', $msg);
        } else {
            $this->getUser()->setFlash('error', "Fetch failed for '{$vendor->name}': " . ($result['error'] ?? 'Unknown error'));
        }

        $this->redirect('@kbart_vendor_index');
    }

    /**
     * Show import log for a vendor.
     */
    public function executeImportLog($request)
    {
        $id = (int) $request->getParameter('id');

        if ($id <= 0) {
            $this->forward404();
        }

        $this->vendor = $this->kbartService->getVendor($id);
        if (!$this->vendor) {
            $this->forward404();
        }

        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $this->logs = DB::table('library_kbart_import_log')
            ->where('vendor_id', $id)
            ->orderBy('fetched_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->all();

        $this->totalLogs = DB::table('library_kbart_import_log')
            ->where('vendor_id', $id)
            ->count();

        $this->page = $page;
        $this->totalPages = (int) ceil($this->totalLogs / $limit);
    }
}