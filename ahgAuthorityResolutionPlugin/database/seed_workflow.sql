-- ==========================================================================
-- AHG Authority Resolution Engine - workflow seed (Task 12: Assign / Workflow)
-- Plugin: ahgAuthorityResolutionPlugin
--
-- Seeds a minimal "Authority Resolution Review" workflow definition into the
-- ahgWorkflowPlugin tables so that assigning a mention can route it through
-- the existing Workflow plugin. AssignmentService::assign() passes this
-- workflow id explicitly to WorkflowService::startWorkflow(), so the
-- ahg_mention object never has to satisfy getApplicableWorkflow()'s
-- information_object scope lookup.
--
-- Fixed ids 200 / 200 are well clear of the ahgWorkflowPlugin seed range
-- (ids 1, 100, 101) to avoid collisions on a converged DB.
--
-- Copyright (C) 2026 Johan Pieterse
-- Plain Sailing Information Systems
-- Email: johan@plainsailingisystems.co.za
--
-- This file is part of the AHG Authority Resolution Engine plugin for
-- AtoM Heratio. Licensed under the GNU General Public License v3.0 or later,
-- matching the parent atom-ahg-plugins repository.
-- ==========================================================================

-- Workflow definition. scope_type='global', applies_to='ahg_mention'.
INSERT IGNORE INTO `ahg_workflow`
    (`id`, `name`, `description`, `scope_type`, `scope_id`, `trigger_event`,
     `applies_to`, `is_active`, `is_default`, `require_all_steps`,
     `allow_parallel`, `notification_enabled`)
VALUES
    (200, 'Authority Resolution Review',
     'Routes an authority-resolution mention to an archivist for review and linking.',
     'global', NULL, 'submit', 'ahg_mention', 1, 0, 1, 0, 1);

-- Single review step. pool_enabled so the task is claimable.
INSERT IGNORE INTO `ahg_workflow_step`
    (`id`, `workflow_id`, `name`, `description`, `step_order`, `step_type`,
     `action_required`, `pool_enabled`, `is_optional`, `is_active`, `instructions`)
VALUES
    (200, 200, 'Review',
     'Review the mention, evaluate candidate authorities and record a link, park or reject decision.',
     1, 'review', 'approve_reject', 1, 0, 1,
     'Open the mention review screen, weigh the evidence-scored candidates and record a decision.');
