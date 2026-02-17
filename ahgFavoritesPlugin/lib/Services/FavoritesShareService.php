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
