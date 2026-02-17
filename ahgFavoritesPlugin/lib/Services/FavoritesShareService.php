<?php

namespace AtomAhgPlugins\ahgFavoritesPlugin\Services;

require_once dirname(__DIR__).'/Repositories/FolderRepository.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Repositories\FolderRepository;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Favorites Share Service - Folder sharing via token links
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class FavoritesShareService
{
    private FolderRepository $folderRepo;

    public function __construct()
    {
        $this->folderRepo = new FolderRepository();
    }

    /**
     * Share a folder — generates token and returns share URL
     *
     * @param int   $userId   Owner user ID
     * @param int   $folderId Folder to share
     * @param array $options  expires_in_days, shared_via
     *
     * @return array ['token', 'url', 'expires_at'] or error
     */
    public function shareFolder(int $userId, int $folderId, array $options = []): array
    {
        $folder = $this->folderRepo->getById($folderId);
        if (!$folder) {
            return ['success' => false, 'message' => \__('Folder not found.')];
        }
        if ($folder->user_id != $userId) {
            return ['success' => false, 'message' => \__('Access denied.')];
        }

        $token = bin2hex(random_bytes(32));
        $expiresInDays = (int) ($options['expires_in_days'] ?? 30);
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiresInDays} days"));
        $sharedVia = in_array($options['shared_via'] ?? 'link', ['link', 'email', 'direct'])
            ? ($options['shared_via'] ?? 'link')
            : 'link';

        // Update folder with share token
        DB::table('favorites_folder')
            ->where('id', $folderId)
            ->update([
                'share_token' => $token,
                'share_expires_at' => $expiresAt,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        // Insert share tracking record
        DB::table('favorites_share')->insert([
            'folder_id' => $folderId,
            'shared_via' => $sharedVia,
            'token' => $token,
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $baseUrl = '';
        try {
            $baseUrl = \sfConfig::get('app_siteBaseUrl', '');
            if (!$baseUrl) {
                $request = \sfContext::getInstance()->getRequest();
                $baseUrl = $request->getUriPrefix();
            }
        } catch (\Exception $e) {
        }

        return [
            'success' => true,
            'token' => $token,
            'url' => $baseUrl . '/favorites/shared/' . $token,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Send share link emails to recipients
     *
     * @param int    $userId    Sender user ID
     * @param string $shareUrl  The share URL to include
     * @param string $rawEmails Comma/newline-separated email addresses
     * @param string $message   Optional personal message
     * @param int    $folderId  Folder being shared
     *
     * @return array ['sent' => int, 'errors' => string[]]
     */
    public function sendShareEmails(int $userId, string $shareUrl, string $rawEmails, string $message = '', int $folderId = 0): array
    {
        // Parse email addresses (comma, semicolon, newline separated)
        $emails = preg_split('/[\s,;]+/', trim($rawEmails), -1, PREG_SPLIT_NO_EMPTY);
        $emails = array_filter($emails, function ($e) {
            return filter_var(trim($e), FILTER_VALIDATE_EMAIL);
        });
        $emails = array_unique(array_map('trim', $emails));

        if (empty($emails)) {
            return ['sent' => 0, 'errors' => []];
        }

        // Load email service
        $emailServicePath = \sfConfig::get('sf_plugins_dir', '')
            . '/ahgCorePlugin/lib/Services/EmailService.php';
        if (!class_exists('AhgCore\Services\EmailService') && file_exists($emailServicePath)) {
            require_once $emailServicePath;
        }
        if (!class_exists('AhgCore\Services\EmailService') || !\AhgCore\Services\EmailService::isEnabled()) {
            return ['sent' => 0, 'errors' => [\__('Email service is not configured.')]];
        }

        // Get sender name
        $senderName = '';
        try {
            $culture = \sfContext::getInstance()->getUser()->getCulture() ?: 'en';
            $actor = DB::table('actor_i18n')
                ->where('id', $userId)
                ->where('culture', $culture)
                ->value('authorized_form_of_name');
            $senderName = $actor ?: '';
        } catch (\Exception $e) {
        }
        if (!$senderName) {
            $senderName = DB::table('user')->where('id', $userId)->value('username') ?: \__('A user');
        }

        // Get folder name
        $folderName = '';
        if ($folderId) {
            $folderName = DB::table('favorites_folder')->where('id', $folderId)->value('name') ?: '';
        }

        // Get item count
        $itemCount = 0;
        if ($folderId) {
            $itemCount = DB::table('favorites')->where('folder_id', $folderId)->count();
        }

        $sent = 0;
        $errors = [];

        $subject = \__('%1% shared a favorites folder with you', ['%1%' => $senderName]);

        foreach ($emails as $email) {
            $body = '<div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; max-width: 600px; margin: 0 auto;">';
            $body .= '<h2 style="color: #0d6efd;">' . htmlspecialchars($senderName) . ' ' . \__('shared a folder with you') . '</h2>';

            if ($folderName) {
                $body .= '<p style="font-size: 1.1em;"><strong>' . \__('Folder:') . '</strong> ' . htmlspecialchars($folderName);
                if ($itemCount) {
                    $body .= ' <span style="color: #6c757d;">(' . $itemCount . ' ' . \__('items') . ')</span>';
                }
                $body .= '</p>';
            }

            if ($message) {
                $body .= '<div style="background: #f8f9fa; border-left: 4px solid #0d6efd; padding: 12px 16px; margin: 16px 0; border-radius: 4px;">';
                $body .= '<p style="margin: 0; color: #495057;"><em>' . nl2br(htmlspecialchars($message)) . '</em></p>';
                $body .= '</div>';
            }

            $body .= '<p style="margin: 24px 0;">';
            $body .= '<a href="' . htmlspecialchars($shareUrl) . '" style="display: inline-block; background: #0d6efd; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500;">';
            $body .= '<i class="fas fa-external-link-alt"></i> ' . \__('View Shared Folder');
            $body .= '</a></p>';

            $body .= '<p style="color: #6c757d; font-size: 0.9em;">' . \__('Or copy this link:') . '<br>';
            $body .= '<a href="' . htmlspecialchars($shareUrl) . '">' . htmlspecialchars($shareUrl) . '</a></p>';

            $body .= '</div>';

            try {
                $ok = \AhgCore\Services\EmailService::send($email, $subject, $body);
                if ($ok) {
                    $sent++;
                } else {
                    $errors[] = $email;
                }
            } catch (\Exception $e) {
                $errors[] = $email;
                error_log('Share email failed for ' . $email . ': ' . $e->getMessage());
            }
        }

        // Track email recipients in favorites_share
        if ($sent > 0 && $folderId) {
            try {
                $token = DB::table('favorites_folder')->where('id', $folderId)->value('share_token');
                if ($token) {
                    DB::table('favorites_share')
                        ->where('token', $token)
                        ->update([
                            'shared_via' => 'email',
                            'recipients' => implode(', ', $emails),
                        ]);
                }
            } catch (\Exception $e) {
                // recipients column may not exist yet — ignore
            }
        }

        return ['sent' => $sent, 'errors' => $errors];
    }

    /**
     * Get shared folder data by token — validates expiry, tracks access
     *
     * @return array|null Folder + items or null if invalid/expired
     */
    public function getSharedFolder(string $token): ?array
    {
        $folder = DB::table('favorites_folder')
            ->where('share_token', $token)
            ->first();

        if (!$folder) {
            return null;
        }

        // Check expiry
        if ($folder->share_expires_at && strtotime($folder->share_expires_at) < time()) {
            return null;
        }

        // Update access tracking
        DB::table('favorites_share')
            ->where('token', $token)
            ->update([
                'accessed_at' => date('Y-m-d H:i:s'),
                'access_count' => DB::raw('access_count + 1'),
            ]);

        // Get folder items with enrichment
        $culture = 'en';
        try {
            $culture = \sfContext::getInstance()->getUser()->getCulture() ?: 'en';
        } catch (\Exception $e) {
        }

        $favorites = DB::table('favorites')
            ->where('folder_id', $folder->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        $items = [];
        foreach ($favorites as $fav) {
            $objectId = $fav->archival_description_id;

            // Check object exists
            if (!$objectId || !DB::table('object')->where('id', $objectId)->exists()) {
                continue;
            }

            $title = DB::table('information_object_i18n')
                ->where('id', $objectId)
                ->where('culture', $culture)
                ->value('title');
            if (!$title && $culture !== 'en') {
                $title = DB::table('information_object_i18n')
                    ->where('id', $objectId)
                    ->where('culture', 'en')
                    ->value('title');
            }

            $slug = DB::table('slug')->where('object_id', $objectId)->value('slug');

            $items[] = (object) [
                'id' => $fav->id,
                'archival_description_id' => $objectId,
                'title' => $title ?? $fav->archival_description ?? \__('Untitled'),
                'slug' => $slug ?? $fav->slug,
                'reference_code' => $fav->reference_code,
                'notes' => $fav->notes,
                'created_at' => $fav->created_at,
            ];
        }

        // Get owner name
        $ownerName = '';
        $actor = DB::table('actor_i18n')
            ->where('id', $folder->user_id)
            ->where('culture', $culture)
            ->value('authorized_form_of_name');
        if (!$actor && $culture !== 'en') {
            $actor = DB::table('actor_i18n')
                ->where('id', $folder->user_id)
                ->where('culture', 'en')
                ->value('authorized_form_of_name');
        }
        $ownerName = $actor ?? '';

        return [
            'folder' => $folder,
            'items' => $items,
            'owner_name' => $ownerName,
        ];
    }

    /**
     * Revoke sharing for a folder
     */
    public function revokeShare(int $userId, int $folderId): bool
    {
        $folder = $this->folderRepo->getById($folderId);
        if (!$folder || $folder->user_id != $userId) {
            return false;
        }

        // Clear token on folder
        DB::table('favorites_folder')
            ->where('id', $folderId)
            ->update([
                'share_token' => null,
                'share_expires_at' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        // Remove share tracking records
        DB::table('favorites_share')
            ->where('folder_id', $folderId)
            ->delete();

        return true;
    }

    /**
     * Copy items from a shared folder into user's own favourites
     */
    public function copySharedToFavorites(int $userId, string $token): array
    {
        $shared = $this->getSharedFolder($token);
        if (!$shared) {
            return ['success' => false, 'copied' => 0, 'skipped' => 0, 'message' => \__('Shared folder not found or expired.')];
        }

        require_once dirname(__DIR__).'/Services/FavoritesService.php';
        $favService = new FavoritesService();

        $copied = 0;
        $skipped = 0;

        foreach ($shared['items'] as $item) {
            if ($favService->isFavorited($userId, $item->archival_description_id)) {
                $skipped++;
                continue;
            }

            $result = $favService->addToFavorites(
                $userId,
                $item->archival_description_id,
                $item->title,
                $item->slug
            );

            if ($result['success']) {
                $copied++;
            } else {
                $skipped++;
            }
        }

        return [
            'success' => true,
            'copied' => $copied,
            'skipped' => $skipped,
            'message' => \__('Copied %1% items to your favorites.', ['%1%' => $copied]) . ($skipped ? ' ' . \__('%1% already existed.', ['%1%' => $skipped]) : ''),
        ];
    }
}
