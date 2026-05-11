<?php

namespace AhgVersionControl\Listeners;

use AhgVersionControl\Services\SnapshotBuilder;
use AhgVersionControl\Services\VersionContext;
use AhgVersionControl\Services\VersionWriter;

/**
 * SaveListener — hooks `response.filter_content` to capture a version after
 * an IO or actor has been saved through the web UI.
 *
 * Base AtoM does not dispatch model-save events, so we mirror the strategy
 * used by ahgAuditTrailPlugin: examine the just-completed request, decide if
 * it was a save on an entity we version, resolve the entity id, and write.
 *
 * The listener is no-op when:
 *   - VersionContext::isSkipped() is true (bulk imports)
 *   - The request is GET/HEAD or hits a module we don't version
 *   - The request was an autocomplete / AJAX poll
 *   - The action is a no-op view path (show / list / browse / etc.)
 *
 * For each captured save the listener:
 *   1. Resolves the entity id from request params or the response URL.
 *   2. Builds the canonical snapshot via SnapshotBuilder.
 *   3. Writes the version via VersionWriter (concurrency-safe).
 *
 * @phase D
 */
final class SaveListener
{
    /** Modules where a save targets an information_object. */
    private const IO_MODULES = [
        'informationobject',
        'ahgInformationObjectManage',
        'ahgDacsManage',
        'ahgDcManage',
        'ahgModsManage',
        'ahgRadManage',
    ];

    /** Modules where a save targets an actor. */
    private const ACTOR_MODULES = [
        'actor',
        'ahgActorManage',
        'ahgDonorManage',
        'ahgRepositoryManage',
        'ahgRightsHolderManage',
    ];

    /**
     * Action names that should NEVER trigger versioning even on POST.
     * Denylist approach (same as ahgAuditTrailPlugin) — captures any POST to
     * a versioned-entity module unless the action is one of these.
     */
    private const SKIP_ACTIONS = [
        // AJAX autocompletes / polling
        'autocomplete', 'actorAutocomplete', 'repositoryAutocomplete',
        'termAutocomplete', 'objectAutocomplete', 'autocompleteGlam',
        'ajaxStatus', 'apiStatus', 'apiProgress', 'jobStatus',
        'getAnnotation', 'apiRealtime', 'apiCheck', 'health',
        // Read-only POST endpoints
        'search', 'browse', 'list', 'index',
        // Delete is captured by audit but version-on-delete is meaningless
        // (the entity row is gone — no snapshot target). Restore via the
        // audit_log instead of version.
        'delete', 'remove',
    ];

    public function onResponseFilterContent(\sfEvent $event, string $content): string
    {
        try {
            $this->maybeCapture();
        } catch (\Throwable $e) {
            // Never let a versioning failure break a user-facing request.
            error_log('ahgVersionControlPlugin SaveListener error: ' . $e->getMessage());
        }
        return $content;
    }

    private function maybeCapture(): void
    {
        if (VersionContext::isSkipped()) {
            return;
        }
        if (!\sfContext::hasInstance()) {
            return;
        }

        $context = \sfContext::getInstance();
        $request = $context->getRequest();
        $module = $context->getModuleName();
        $action = $context->getActionName();

        if (!$request->isMethod('POST') && !$request->isMethod('PUT')) {
            return;
        }
        if (in_array($action, self::SKIP_ACTIONS, true)) {
            return;
        }

        $entityType = null;
        if (in_array($module, self::IO_MODULES, true)) {
            $entityType = 'information_object';
        } elseif (in_array($module, self::ACTOR_MODULES, true)) {
            $entityType = 'actor';
        }
        if ($entityType === null) {
            return;
        }

        $entityId = $this->resolveEntityId($request, $context, $entityType);
        if ($entityId === null || $entityId <= 0) {
            return;
        }

        $userId = VersionContext::takeUserId() ?? $this->resolveUserId($context);
        $summary = VersionContext::takeSummary() ?? $this->buildDefaultSummary($module, $action);

        $builder = new SnapshotBuilder();
        $writer  = new VersionWriter();

        $snapshot = $entityType === 'information_object'
            ? $builder->buildForInformationObject($entityId)
            : $builder->buildForActor($entityId);

        $writer->write(
            entityType: $entityType,
            entityId: $entityId,
            snapshot: $snapshot,
            changeSummary: $summary,
            userId: $userId,
        );
    }

    /**
     * Try to resolve the entity id from URL params, POST body, or a slug.
     */
    private function resolveEntityId(\sfWebRequest $request, \sfContext $context, string $entityType): ?int
    {
        // Common params: id (numeric) or slug
        $direct = $request->getParameter('id');
        if (is_numeric($direct) && (int) $direct > 0) {
            return (int) $direct;
        }

        $slug = $request->getParameter('slug');
        if (is_string($slug) && $slug !== '') {
            $row = \Illuminate\Database\Capsule\Manager::table('slug')
                ->where('slug', $slug)
                ->first();
            if ($row && !empty($row->object_id)) {
                return (int) $row->object_id;
            }
        }

        // Resolve from response Location header (post-create redirect)
        $response = $context->getResponse();
        if (method_exists($response, 'getHttpHeader')) {
            $location = $response->getHttpHeader('Location');
            if (is_string($location) && preg_match('#/(?:informationobject|actor)/(?:view/)?([^/?#]+)#', $location, $m)) {
                $value = $m[1];
                if (ctype_digit($value)) {
                    return (int) $value;
                }
                $row = \Illuminate\Database\Capsule\Manager::table('slug')
                    ->where('slug', $value)
                    ->first();
                if ($row && !empty($row->object_id)) {
                    return (int) $row->object_id;
                }
            }
        }

        return null;
    }

    private function resolveUserId(\sfContext $context): ?int
    {
        $user = $context->getUser();
        if (!$user || !method_exists($user, 'isAuthenticated') || !$user->isAuthenticated()) {
            return null;
        }
        $id = method_exists($user, 'getUserID') ? $user->getUserID() : null;
        if ($id !== null) {
            return (int) $id;
        }
        $attr = method_exists($user, 'getAttribute') ? $user->getAttribute('user_id') : null;
        return $attr !== null ? (int) $attr : null;
    }

    private function buildDefaultSummary(string $module, string $action): string
    {
        return ucfirst($action) . ' via ' . $module;
    }
}
