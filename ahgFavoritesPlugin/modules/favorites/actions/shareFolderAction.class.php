<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesShareService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesShareService;

/**
 * Share a favorites folder — generates a share link, optionally emails recipients
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesShareFolderAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);

            return;
        }

        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'favorites', 'action' => 'browse']);

            return;
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $folderId = (int) $request->getParameter('id');

        $service = new FavoritesShareService();
        $result = $service->shareFolder($userId, $folderId, [
            'expires_in_days' => (int) $request->getParameter('expires_in_days', 30),
            'shared_via' => $request->getParameter('emails') ? 'email' : 'link',
        ]);

        // Send emails if provided and share was successful
        $emailsSent = 0;
        $emailErrors = [];
        if ($result['success'] && $request->getParameter('emails')) {
            $rawEmails = $request->getParameter('emails', '');
            $message = trim($request->getParameter('message', ''));

            $emailResult = $service->sendShareEmails(
                $userId,
                $result['url'],
                $rawEmails,
                $message,
                $folderId
            );
            $emailsSent = $emailResult['sent'] ?? 0;
            $emailErrors = $emailResult['errors'] ?? [];
        }

        $result['emails_sent'] = $emailsSent;
        $result['email_errors'] = $emailErrors;

        // Return JSON for AJAX requests
        if ($request->isXmlHttpRequest()) {
            $this->getResponse()->setContentType('application/json');

            return $this->renderText(json_encode($result));
        }

        if ($result['success']) {
            $msg = __('Folder shared.');
            if ($emailsSent > 0) {
                $msg .= ' ' . __('Link sent to %1% recipient(s).', ['%1%' => $emailsSent]);
            }
            $this->getUser()->setFlash('notice', $msg);
        } else {
            $this->getUser()->setFlash('error', $result['message']);
        }

        $this->redirect(['module' => 'favorites', 'action' => 'browse', 'folder_id' => $folderId]);
    }
}
