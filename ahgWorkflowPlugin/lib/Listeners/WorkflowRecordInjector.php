<?php

declare(strict_types=1);

namespace AhgWorkflowPlugin\Listeners;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Adds a "Workflow" block to an archival description's side menu: "Submit for
 * review" when the record is not in a workflow, or a link to its open task.
 *
 * Why this exists: nothing in the interface started a review for a description.
 * The Standard Review Workflow's trigger is "submit", but no screen submitted; only
 * the researcher and authority-resolution plugins started workflows, in code. The
 * theme's side menu is locked, so the block is added by filtering the response, the
 * same route ahgProvenancePlugin's ProvenanceInjector takes.
 */
class WorkflowRecordInjector
{
    /** AtoM forwards /{slug} to the module of the record's descriptive standard. */
    private const VIEW_MODULES = ['informationobject', 'sfIsadPlugin', 'sfRadPlugin', 'sfDcPlugin', 'sfModsPlugin', 'sfDacsPlugin'];

    private const ANCHOR = '<section class="sidebar-widget ahg-collections-management">';

    private static bool $injected = false;

    public static function filter(\sfEvent $event, $content)
    {
        try {
            return (new self())->inject($event, (string) $content);
        } catch (\Throwable $e) {
            // An extra menu block must never take a record page down.
            return $content;
        }
    }

    private function inject(\sfEvent $event, string $content): string
    {
        if (self::$injected || false === strpos($content, self::ANCHOR) || false !== strpos($content, 'ahg-workflow-block')) {
            return $content;
        }
        $response = $event->getSubject();
        if (false === stripos((string) $response->getContentType(), 'html')) {
            return $content;
        }
        $context = \sfContext::getInstance();
        if (!in_array($context->getModuleName(), self::VIEW_MODULES, true) || 'index' !== $context->getActionName()) {
            return $content;
        }
        $user = $context->getUser();
        if (!$user->isAuthenticated() || !($user->isAdministrator() || $user->hasCredential('editor') || $user->hasCredential('contributor'))) {
            return $content;
        }
        $resource = $context->getActionStack()->getLastEntry()->getActionInstance()->resource ?? null;
        if (!$resource || empty($resource->id) || (int) $resource->id <= 1) {
            return $content;
        }
        $id = (int) $resource->id;

        $task = DB::table('ahg_workflow_task as t')
            ->leftJoin('ahg_workflow_step as s', 's.id', '=', 't.workflow_step_id')
            ->where('t.object_id', $id)->where('t.object_type', 'information_object')
            ->whereNotIn('t.status', ['approved', 'rejected', 'cancelled'])
            ->orderByDesc('t.id')->first(['t.id', 't.status', 's.name as step']);

        $context->getConfiguration()->loadHelpers(['I18N']);
        $routing = $context->getRouting();
        $e = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        if ($task) {
            $item = '<li><a href="' . $e($routing->generate('workflow_view_task', ['id' => $task->id])) . '"><i class="fas fa-clipboard-list fa-fw me-2"></i>'
                . $e(__('In review: %1%', ['%1%' => $task->step ?: 'task']) . ' (' . str_replace('_', ' ', $task->status) . ')') . '</a></li>';
        } else {
            $item = '<li><a href="' . $e($routing->generate('workflow_start', ['object_id' => $id])) . '"><i class="fas fa-paper-plane fa-fw me-2"></i>' . $e(__('Submit for review')) . '</a></li>';
        }
        $item .= '<li><a href="' . $e($routing->generate('workflow_timeline', ['object_id' => $id])) . '"><i class="fas fa-history fa-fw me-2"></i>' . $e(__('Workflow history')) . '</a></li>';

        $block = '<section class="sidebar-widget ahg-workflow-block"><h2 class="h4">' . $e(__('Workflow')) . '</h2><ul class="list-unstyled">' . $item . '</ul></section>' . "\n";
        self::$injected = true;

        return preg_replace('/' . preg_quote(self::ANCHOR, '/') . '/', $block . self::ANCHOR, $content, 1);
    }
}
