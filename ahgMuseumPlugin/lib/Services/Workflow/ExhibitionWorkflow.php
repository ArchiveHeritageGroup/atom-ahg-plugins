<?php

declare(strict_types=1);

namespace arMuseumMetadataPlugin\Services\Workflow;

use arMuseumMetadataPlugin\Contracts\WorkflowInterface;

/**
 * Exhibition Workflow.
 *
 * State machine for exhibition lifecycle management.
 * Tracks exhibition from concept through closing and archival.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ExhibitionWorkflow extends AbstractWorkflow implements WorkflowInterface
{
    public function getIdentifier(): string
    {
        return 'exhibition';
    }

    public function getName(): string
    {
        return 'Exhibition Lifecycle';
    }

    public function getDescription(): string
    {
        return 'Manages the complete lifecycle of an exhibition from concept to archive.';
    }

    protected function defineStates(): array
    {
        return [
            'concept' => [
                'label' => 'Concept',
                'description' => 'Initial concept and idea development',
                'color' => '#9e9e9e',
                'icon' => 'lightbulb-o',
                'tasks' => [
                    'Define exhibition theme and narrative',
                    'Identify target audience',
                    'Preliminary budget estimate',
                    'Initial object selection',
                ],
            ],
            'planning' => [
                'label' => 'Planning',
                'description' => 'Detailed planning and preparation',
                'color' => '#2196f3',
                'icon' => 'calendar',
                'tasks' => [
                    'Finalize object list',
                    'Submit loan requests',
                    'Confirm budget',
                    'Develop design brief',
                    'Create timeline and milestones',
                    'Assign team responsibilities',
                ],
            ],
            'preparation' => [
                'label' => 'Preparation',
                'description' => 'Physical and content preparation',
                'color' => '#ff9800',
                'icon' => 'wrench',
                'tasks' => [
                    'Design exhibition layout',
                    'Write interpretive text',
                    'Commission mounts and cases',
                    'Plan lighting',
                    'Prepare marketing materials',
                    'Coordinate loans',
                ],
            ],
            'installation' => [
                'label' => 'Installation',
                'description' => 'Physical installation of exhibition',
                'color' => '#9c27b0',
                'icon' => 'cubes',
                'tasks' => [
                    'Install display furniture',
                    'Set lighting',
                    'Install objects',
                    'Complete condition reports',
                    'Install labels and graphics',
                    'Test AV equipment',
                    'Security walkthrough',
                ],
            ],
            'open' => [
                'label' => 'Open',
                'description' => 'Exhibition is open to visitors',
                'color' => '#4caf50',
                'icon' => 'check-circle',
                'tasks' => [
                    'Monitor visitor numbers',
                    'Conduct scheduled events',
                    'Environmental monitoring',
                    'Regular condition checks',
                    'Gather visitor feedback',
                ],
            ],
            'closing' => [
                'label' => 'Closing',
                'description' => 'Preparing for closure',
                'color' => '#ff5722',
                'icon' => 'clock-o',
                'tasks' => [
                    'Final visitor statistics',
                    'Post-exhibition photography',
                    'Final condition reports',
                    'Coordinate loan returns',
                    'Plan deinstallation',
                ],
            ],
            'closed' => [
                'label' => 'Closed',
                'description' => 'Exhibition closed, deinstallation complete',
                'color' => '#795548',
                'icon' => 'archive',
                'tasks' => [
                    'Complete deinstallation',
                    'Return all loans',
                    'Archive documentation',
                    'Final budget reconciliation',
                    'Team debrief',
                ],
            ],
            'archived' => [
                'label' => 'Archived',
                'description' => 'Exhibition fully archived',
                'color' => '#607d8b',
                'icon' => 'folder-open',
                'tasks' => [],
                'final' => true,
            ],
            'canceled' => [
                'label' => 'Canceled',
                'description' => 'Exhibition was canceled',
                'color' => '#f44336',
                'icon' => 'times-circle',
                'tasks' => [
                    'Notify stakeholders',
                    'Cancel loans',
                    'Return deposits',
                    'Archive planning documents',
                ],
                'final' => true,
            ],
        ];
    }

    protected function defineTransitions(): array
    {
        return [
            'start_planning' => [
                'from' => ['concept'],
                'to' => 'planning',
                'label' => 'Start Planning',
                'description' => 'Move concept to active planning phase',
                'icon' => 'play',
                'requires' => ['has_title', 'has_dates'],
            ],
            'begin_preparation' => [
                'from' => ['planning'],
                'to' => 'preparation',
                'label' => 'Begin Preparation',
                'description' => 'Start physical and content preparation',
                'icon' => 'forward',
                'requires' => ['planning_complete', 'budget_approved'],
            ],
            'start_installation' => [
                'from' => ['preparation'],
                'to' => 'installation',
                'label' => 'Start Installation',
                'description' => 'Begin physical installation',
                'icon' => 'play-circle',
                'requires' => ['preparation_complete'],
            ],
            'open_exhibition' => [
                'from' => ['installation'],
                'to' => 'open',
                'label' => 'Open Exhibition',
                'description' => 'Open exhibition to visitors',
                'icon' => 'door-open',
                'requires' => ['installation_complete', 'final_walkthrough'],
                'triggers' => ['send_opening_notification'],
            ],
            'start_closing' => [
                'from' => ['open'],
                'to' => 'closing',
                'label' => 'Start Closing',
                'description' => 'Begin closing procedures',
                'icon' => 'hourglass-half',
            ],
            'close_exhibition' => [
                'from' => ['closing'],
                'to' => 'closed',
                'label' => 'Close Exhibition',
                'description' => 'Mark exhibition as closed',
                'icon' => 'lock',
                'requires' => ['final_reports_complete'],
            ],
            'reopen_exhibition' => [
                'from' => ['closing'],
                'to' => 'open',
                'label' => 'Reopen Exhibition',
                'description' => 'Reopen exhibition (extension)',
                'icon' => 'undo',
            ],
            'archive_exhibition' => [
                'from' => ['closed'],
                'to' => 'archived',
                'label' => 'Archive Exhibition',
                'description' => 'Move to archive',
                'icon' => 'archive',
                'requires' => ['all_objects_returned', 'documentation_complete'],
            ],
            'cancel_exhibition' => [
                'from' => ['concept', 'planning', 'preparation', 'installation'],
                'to' => 'canceled',
                'label' => 'Cancel Exhibition',
                'description' => 'Cancel the exhibition',
                'icon' => 'times',
                'requires_comment' => true,
                'triggers' => ['send_cancellation_notification'],
            ],
            'revive_exhibition' => [
                'from' => ['canceled'],
                'to' => 'concept',
                'label' => 'Revive Exhibition',
                'description' => 'Restart canceled exhibition',
                'icon' => 'refresh',
            ],
            'return_to_planning' => [
                'from' => ['preparation'],
                'to' => 'planning',
                'label' => 'Return to Planning',
                'description' => 'Go back to planning phase',
                'icon' => 'arrow-left',
            ],
            'return_to_preparation' => [
                'from' => ['installation'],
                'to' => 'preparation',
                'label' => 'Return to Preparation',
                'description' => 'Go back to preparation phase',
                'icon' => 'arrow-left',
            ],
            'return_to_concept' => [
                'from' => ['planning'],
                'to' => 'concept',
                'label' => 'Return to Concept',
                'description' => 'Go back to concept phase',
                'icon' => 'arrow-left',
            ],
        ];
    }

    public function getInitialState(): string
    {
        return 'concept';
    }

    public function isFinalState(string $state): bool
    {
        return in_array($state, ['archived', 'canceled']);
    }

    /**
     * Get progress percentage for state.
     */
    public function getProgress(string $state): int
    {
        $progressMap = [
            'concept' => 10,
            'planning' => 25,
            'preparation' => 50,
            'installation' => 75,
            'open' => 90,
            'closing' => 95,
            'closed' => 98,
            'archived' => 100,
            'canceled' => 0,
        ];

        return $progressMap[$state] ?? 0;
    }

    /**
     * Get recommended checklist type for state.
     */
    public function getChecklistTypeForState(string $state): ?string
    {
        $checklistMap = [
            'planning' => 'planning',
            'preparation' => 'preparation',
            'installation' => 'installation',
            'open' => 'during',
            'closing' => 'closing',
            'closed' => 'deinstallation',
        ];

        return $checklistMap[$state] ?? null;
    }

    /**
     * Validate transition requirements.
     */
    public function validateTransitionRequirements(string $transition, array $context): array
    {
        $errors = [];
        $transitionDef = $this->getTransitions()[$transition] ?? null;

        if (!$transitionDef) {
            return ['Unknown transition: '.$transition];
        }

        $requirements = $transitionDef['requires'] ?? [];

        foreach ($requirements as $requirement) {
            switch ($requirement) {
                case 'has_title':
                    if (empty($context['exhibition']['title'])) {
                        $errors[] = 'Exhibition must have a title';
                    }
                    break;

                case 'has_dates':
                    if (empty($context['exhibition']['opening_date'])) {
                        $errors[] = 'Opening date must be set';
                    }
                    break;

                case 'planning_complete':
                    // Check planning checklist
                    if (!$this->isChecklistComplete($context, 'planning')) {
                        $errors[] = 'Planning checklist must be completed';
                    }
                    break;

                case 'budget_approved':
                    if (empty($context['exhibition']['budget_amount'])) {
                        $errors[] = 'Budget must be set and approved';
                    }
                    break;

                case 'preparation_complete':
                    if (!$this->isChecklistComplete($context, 'preparation')) {
                        $errors[] = 'Preparation checklist must be completed';
                    }
                    break;

                case 'installation_complete':
                    if (!$this->isChecklistComplete($context, 'installation')) {
                        $errors[] = 'Installation checklist must be completed';
                    }
                    break;

                case 'final_walkthrough':
                    // Should have a final walkthrough noted
                    break;

                case 'final_reports_complete':
                    if (!$this->isChecklistComplete($context, 'closing')) {
                        $errors[] = 'Closing checklist must be completed';
                    }
                    break;

                case 'all_objects_returned':
                    $unreturned = $context['objects_not_returned'] ?? 0;
                    if ($unreturned > 0) {
                        $errors[] = "{$unreturned} objects have not been returned";
                    }
                    break;

                case 'documentation_complete':
                    // Check that key documentation exists
                    break;
            }
        }

        return $errors;
    }

    /**
     * Check if a checklist type is complete.
     */
    private function isChecklistComplete(array $context, string $checklistType): bool
    {
        $checklists = $context['checklists'] ?? [];

        foreach ($checklists as $checklist) {
            if ($checklist['checklist_type'] === $checklistType) {
                return 'completed' === $checklist['status'];
            }
        }

        return false; // No checklist found means not complete
    }

    /**
     * Get notification recipients for state.
     */
    public function getNotificationRecipients(string $state): array
    {
        $recipientMap = [
            'planning' => ['curator', 'registrar'],
            'preparation' => ['curator', 'designer', 'registrar'],
            'installation' => ['curator', 'designer', 'registrar', 'preparators'],
            'open' => ['curator', 'marketing', 'visitor_services'],
            'closing' => ['curator', 'registrar', 'preparators'],
            'closed' => ['curator', 'registrar', 'administration'],
            'canceled' => ['all_stakeholders'],
        ];

        return $recipientMap[$state] ?? [];
    }
}
