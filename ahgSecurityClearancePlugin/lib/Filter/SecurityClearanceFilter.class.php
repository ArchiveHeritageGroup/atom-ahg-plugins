<?php
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Security Clearance Access Filter.
 *
 * Checks security clearance on information object access.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class SecurityClearanceFilter extends sfFilter
{
    public function execute($filterChain): void
    {
        // Only check on first call
        if ($this->isFirstCall()) {
            $this->checkSecurityClearance();
        }

        $filterChain->execute();
    }

    /**
     * Check security clearance for current request.
     */
    private function checkSecurityClearance(): void
    {
        $context = $this->getContext();
        $request = $context->getRequest();
        $user = $context->getUser();

        // Skip if not authenticated
        if (!$user->isAuthenticated()) {
            return;
        }

        // Get current module/action
        $module = $request->getParameter('module');
        $action = $request->getParameter('action');

        // Only check information object views
        if (!in_array($module, ['informationobject', 'sfIsadPlugin', 'sfRadPlugin', 'sfDcPlugin', 'ahgMuseumPlugin'])) {
            return;
        }

        // Only check view actions
        if (!in_array($action, ['index', 'view', 'show'])) {
            return;
        }

        // Get resource
        try {
            $resource = DB::table("information_object as io")->join("slug", "slug.object_id", "=", "io.id")->where("slug.slug", $request->getParameter("slug"))->select("io.*", "slug.slug")->first();
        } catch (Exception $e) {
            return;
        }

        if (!$resource) {
            return;
        }

        $userId = $user->getAttribute('user_id');
        if (!$userId) {
            return;
        }

        // Load service
        require_once sfConfig::get('sf_plugins_dir').'/ahgSecurityClearancePlugin/lib/Services/SecurityClearanceService.php';

        // Check access
        $accessResult = SecurityClearanceService::canAccess($userId, $resource->id, 'view');

        if (!$accessResult['allowed']) {
            // Log denial
            $classification = SecurityClearanceService::getEffectiveClassification($resource->id);
            SecurityClearanceService::logAccess(
                $userId,
                $resource->id,
                $classification ? $classification->id : null,
                null,
                'view',
                false,
                $accessResult['reason']
            );

            // Redirect appropriately
            if (!empty($accessResult['requires_2fa'])) {
                $returnUrl = $request->getUri();
                $context->getController()->redirect('/security/2fa?return='.urlencode($returnUrl));
            } elseif (!empty($accessResult['requires_request'])) {
                $context->getController()->redirect('/security/request/'.$resource->id);
            } else {
                // Generic access denied
                $context->getController()->forward('ahgSecurityClearance', 'denied');
            }
        } else {
            // Log access
            $classification = SecurityClearanceService::getEffectiveClassification($resource->id);
            if ($classification) {
                SecurityClearanceService::logAccess(
                    $userId,
                    $resource->id,
                    $classification->id,
                    null,
                    'view',
                    true
                );
            }
        }
    }
}
