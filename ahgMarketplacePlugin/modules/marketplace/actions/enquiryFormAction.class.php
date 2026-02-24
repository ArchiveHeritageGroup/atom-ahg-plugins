<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/MarketplaceService.php';
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Repositories/SettingsRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\MarketplaceService;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;
use Illuminate\Database\Capsule\Manager as DB;

class marketplaceEnquiryFormAction extends AhgController
{
    public function execute($request)
    {
        $settingsRepo = new SettingsRepository();
        $guestEnquiriesEnabled = (bool) $settingsRepo->get('guest_enquiries_enabled', true);

        // Auth optional if guest enquiries enabled
        if (!$guestEnquiriesEnabled && !$this->context->user->isAuthenticated()) {
            $this->getUser()->setFlash('error', 'Please log in to send an enquiry.');
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $slug = $request->getParameter('slug');
        if (empty($slug)) {
            $this->redirect(['module' => 'marketplace', 'action' => 'browse']);
        }

        $marketplaceService = new MarketplaceService();

        // Get listing
        $listing = DB::table('marketplace_listing')->where('slug', $slug)->first();
        if (!$listing) {
            $this->forward404();
        }

        // Get primary image
        $images = $marketplaceService->getListingImages($listing->id);
        $primaryImage = null;
        foreach ($images as $img) {
            if ($img->is_primary) {
                $primaryImage = $img;
                break;
            }
        }
        if (!$primaryImage && !empty($images)) {
            $primaryImage = $images[0];
        }

        // Pre-fill name/email if authenticated
        $prefillName = '';
        $prefillEmail = '';
        if ($this->context->user->isAuthenticated()) {
            $userId = (int) $this->context->user->getAttribute('user_id');
            $user = DB::table('user')->where('id', $userId)->first();
            if ($user) {
                $actor = DB::table('actor_i18n')
                    ->where('id', $userId)
                    ->where('culture', 'en')
                    ->first();
                $prefillName = $actor->authorized_form_of_name ?? '';
                $prefillEmail = $user->email ?? '';
            }
        }

        // Handle POST: create enquiry
        if ($request->isMethod('post')) {
            $name = trim($request->getParameter('enquiry_name', ''));
            $email = trim($request->getParameter('enquiry_email', ''));
            $phone = trim($request->getParameter('enquiry_phone', ''));
            $subject = trim($request->getParameter('enquiry_subject', ''));
            $message = trim($request->getParameter('enquiry_message', ''));

            // Validate
            $errors = [];
            if (empty($name)) {
                $errors[] = 'Name is required.';
            }
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'A valid email address is required.';
            }
            if (empty($subject)) {
                $errors[] = 'Subject is required.';
            }
            if (empty($message)) {
                $errors[] = 'Message is required.';
            }

            if (!empty($errors)) {
                $this->getUser()->setFlash('error', implode(' ', $errors));
            } else {
                $enquiryData = [
                    'listing_id' => $listing->id,
                    'user_id' => $this->context->user->isAuthenticated()
                        ? (int) $this->context->user->getAttribute('user_id')
                        : null,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone ?: null,
                    'subject' => $subject,
                    'message' => $message,
                    'status' => 'new',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                $settingsRepo->createEnquiry($enquiryData);

                $this->getUser()->setFlash('notice', 'Your enquiry has been sent successfully. The seller will respond to you by email.');
                $this->redirect(['module' => 'marketplace', 'action' => 'listing', 'slug' => $slug]);
            }
        }

        $this->listing = $listing;
        $this->primaryImage = $primaryImage;
        $this->prefillName = $prefillName;
        $this->prefillEmail = $prefillEmail;
        $this->guestEnquiriesEnabled = $guestEnquiriesEnabled;
    }
}
