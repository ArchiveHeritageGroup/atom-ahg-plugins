<?php

declare(strict_types=1);

namespace AhgLoan\Services\Workflow;

/**
 * Loan Out Workflow.
 *
 * Implements Spectrum 5.0 Loans Out procedure as a state machine.
 * Tracks objects from loan request through dispatch, display,
 * and return.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 *
 * @see https://collectionstrust.org.uk/spectrum/procedures/loans-out/
 */
class LoanOutWorkflow extends AbstractWorkflow
{
    protected string $initialState = 'request_received';

    protected array $finalStates = ['closed', 'cancelled'];

    public function __construct()
    {
        $this->initializeStates();
        $this->initializeTransitions();
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier(): string
    {
        return 'loan_out';
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'Loan Out (Spectrum 5.0)';
    }

    /**
     * Define workflow states.
     */
    private function initializeStates(): void
    {
        $this->states = [
            'request_received' => [
                'label' => 'Request Received',
                'description' => 'Loan request received from borrower',
                'color' => 'info',
                'icon' => 'inbox',
                'phase' => 'request',
            ],
            'under_review' => [
                'label' => 'Under Review',
                'description' => 'Request being reviewed by curatorial staff',
                'color' => 'warning',
                'icon' => 'search',
                'phase' => 'request',
            ],
            'approved' => [
                'label' => 'Approved',
                'description' => 'Loan request approved',
                'color' => 'success',
                'icon' => 'check',
                'phase' => 'preparation',
            ],
            'rejected' => [
                'label' => 'Rejected',
                'description' => 'Loan request declined',
                'color' => 'danger',
                'icon' => 'times',
                'phase' => 'closed',
            ],
            'agreement_pending' => [
                'label' => 'Agreement Pending',
                'description' => 'Awaiting signed loan agreement',
                'color' => 'warning',
                'icon' => 'file-text',
                'phase' => 'preparation',
            ],
            'agreement_signed' => [
                'label' => 'Agreement Signed',
                'description' => 'Loan agreement signed by both parties',
                'color' => 'success',
                'icon' => 'file-signature',
                'phase' => 'preparation',
            ],
            'insurance_pending' => [
                'label' => 'Insurance Pending',
                'description' => 'Awaiting insurance confirmation',
                'color' => 'warning',
                'icon' => 'shield',
                'phase' => 'preparation',
            ],
            'insurance_confirmed' => [
                'label' => 'Insurance Confirmed',
                'description' => 'Insurance coverage confirmed',
                'color' => 'success',
                'icon' => 'shield-check',
                'phase' => 'preparation',
            ],
            'condition_check' => [
                'label' => 'Condition Check',
                'description' => 'Pre-loan condition assessment in progress',
                'color' => 'info',
                'icon' => 'clipboard-check',
                'phase' => 'preparation',
            ],
            'condition_complete' => [
                'label' => 'Condition Complete',
                'description' => 'Condition report completed and signed',
                'color' => 'success',
                'icon' => 'clipboard-check',
                'phase' => 'preparation',
            ],
            'packing' => [
                'label' => 'Packing',
                'description' => 'Object being packed for transport',
                'color' => 'info',
                'icon' => 'box',
                'phase' => 'dispatch',
            ],
            'packed' => [
                'label' => 'Packed',
                'description' => 'Object packed and ready for dispatch',
                'color' => 'success',
                'icon' => 'box-check',
                'phase' => 'dispatch',
            ],
            'courier_arranged' => [
                'label' => 'Courier Arranged',
                'description' => 'Transport/courier arrangements confirmed',
                'color' => 'info',
                'icon' => 'truck',
                'phase' => 'dispatch',
            ],
            'dispatched' => [
                'label' => 'Dispatched',
                'description' => 'Object dispatched to borrower',
                'color' => 'primary',
                'icon' => 'truck-loading',
                'phase' => 'transit',
            ],
            'in_transit' => [
                'label' => 'In Transit',
                'description' => 'Object in transit to borrower',
                'color' => 'info',
                'icon' => 'shipping-fast',
                'phase' => 'transit',
            ],
            'received_by_borrower' => [
                'label' => 'Received by Borrower',
                'description' => 'Borrower has received and inspected object',
                'color' => 'success',
                'icon' => 'hand-holding',
                'phase' => 'on_loan',
            ],
            'on_display' => [
                'label' => 'On Display',
                'description' => 'Object on display at borrower venue',
                'color' => 'primary',
                'icon' => 'image',
                'phase' => 'on_loan',
            ],
            'in_storage_borrower' => [
                'label' => 'In Storage (Borrower)',
                'description' => 'Object in storage at borrower location',
                'color' => 'secondary',
                'icon' => 'warehouse',
                'phase' => 'on_loan',
            ],
            'return_initiated' => [
                'label' => 'Return Initiated',
                'description' => 'Return process started',
                'color' => 'info',
                'icon' => 'undo',
                'phase' => 'return',
            ],
            'return_condition_check' => [
                'label' => 'Return Condition Check',
                'description' => 'Post-loan condition assessment at borrower',
                'color' => 'warning',
                'icon' => 'clipboard',
                'phase' => 'return',
            ],
            'return_packed' => [
                'label' => 'Return Packed',
                'description' => 'Object packed for return transport',
                'color' => 'info',
                'icon' => 'box',
                'phase' => 'return',
            ],
            'return_in_transit' => [
                'label' => 'Return In Transit',
                'description' => 'Object in transit back to lender',
                'color' => 'info',
                'icon' => 'shipping-fast',
                'phase' => 'return',
            ],
            'returned' => [
                'label' => 'Returned',
                'description' => 'Object returned and received by lender',
                'color' => 'success',
                'icon' => 'check-circle',
                'phase' => 'return',
            ],
            'return_condition_verified' => [
                'label' => 'Return Condition Verified',
                'description' => 'Post-return condition verified by lender',
                'color' => 'success',
                'icon' => 'clipboard-check',
                'phase' => 'return',
            ],
            'closed' => [
                'label' => 'Closed',
                'description' => 'Loan completed and closed',
                'color' => 'secondary',
                'icon' => 'archive',
                'phase' => 'closed',
            ],
            'cancelled' => [
                'label' => 'Cancelled',
                'description' => 'Loan cancelled',
                'color' => 'danger',
                'icon' => 'ban',
                'phase' => 'closed',
            ],
        ];
    }

    /**
     * Define workflow transitions.
     */
    private function initializeTransitions(): void
    {
        $this->transitions = [
            // Request phase
            'start_review' => [
                'from' => ['request_received'],
                'to' => 'under_review',
                'label' => 'Start Review',
                'icon' => 'search',
                'color' => 'info',
                'roles' => ['curator', 'registrar', 'administrator'],
            ],
            'approve' => [
                'from' => ['under_review'],
                'to' => 'approved',
                'label' => 'Approve Request',
                'icon' => 'check',
                'color' => 'success',
                'roles' => ['curator', 'director', 'administrator'],
                'confirm' => true,
                'confirm_message' => 'Approve this loan request?',
            ],
            'reject' => [
                'from' => ['under_review', 'request_received'],
                'to' => 'rejected',
                'label' => 'Reject Request',
                'icon' => 'times',
                'color' => 'danger',
                'roles' => ['curator', 'director', 'administrator'],
                'confirm' => true,
                'confirm_message' => 'Reject this loan request? This action cannot be undone.',
            ],
            'request_more_info' => [
                'from' => ['under_review'],
                'to' => 'request_received',
                'label' => 'Request More Info',
                'icon' => 'question-circle',
                'color' => 'warning',
                'roles' => ['curator', 'registrar', 'administrator'],
            ],

            // Agreement phase
            'send_agreement' => [
                'from' => ['approved'],
                'to' => 'agreement_pending',
                'label' => 'Send Agreement',
                'icon' => 'paper-plane',
                'color' => 'info',
                'roles' => ['registrar', 'administrator'],
            ],
            'agreement_received' => [
                'from' => ['agreement_pending'],
                'to' => 'agreement_signed',
                'label' => 'Agreement Signed',
                'icon' => 'file-signature',
                'color' => 'success',
                'roles' => ['registrar', 'administrator'],
            ],

            // Insurance phase
            'request_insurance' => [
                'from' => ['agreement_signed'],
                'to' => 'insurance_pending',
                'label' => 'Request Insurance',
                'icon' => 'shield',
                'color' => 'info',
                'roles' => ['registrar', 'administrator'],
            ],
            'confirm_insurance' => [
                'from' => ['insurance_pending'],
                'to' => 'insurance_confirmed',
                'label' => 'Confirm Insurance',
                'icon' => 'shield-check',
                'color' => 'success',
                'roles' => ['registrar', 'administrator'],
            ],

            // Condition check phase
            'start_condition_check' => [
                'from' => ['insurance_confirmed'],
                'to' => 'condition_check',
                'label' => 'Start Condition Check',
                'icon' => 'clipboard',
                'color' => 'info',
                'roles' => ['conservator', 'registrar', 'administrator'],
            ],
            'complete_condition_check' => [
                'from' => ['condition_check'],
                'to' => 'condition_complete',
                'label' => 'Complete Condition Check',
                'icon' => 'clipboard-check',
                'color' => 'success',
                'roles' => ['conservator', 'registrar', 'administrator'],
            ],

            // Packing phase
            'start_packing' => [
                'from' => ['condition_complete'],
                'to' => 'packing',
                'label' => 'Start Packing',
                'icon' => 'box-open',
                'color' => 'info',
                'roles' => ['art_handler', 'registrar', 'administrator'],
            ],
            'complete_packing' => [
                'from' => ['packing'],
                'to' => 'packed',
                'label' => 'Complete Packing',
                'icon' => 'box',
                'color' => 'success',
                'roles' => ['art_handler', 'registrar', 'administrator'],
            ],

            // Dispatch phase
            'arrange_courier' => [
                'from' => ['packed'],
                'to' => 'courier_arranged',
                'label' => 'Arrange Courier',
                'icon' => 'truck',
                'color' => 'info',
                'roles' => ['registrar', 'administrator'],
            ],
            'dispatch' => [
                'from' => ['courier_arranged'],
                'to' => 'dispatched',
                'label' => 'Dispatch',
                'icon' => 'truck-loading',
                'color' => 'primary',
                'roles' => ['registrar', 'art_handler', 'administrator'],
                'confirm' => true,
                'confirm_message' => 'Confirm object has been dispatched?',
            ],
            'in_transit' => [
                'from' => ['dispatched'],
                'to' => 'in_transit',
                'label' => 'Mark In Transit',
                'icon' => 'shipping-fast',
                'color' => 'info',
                'roles' => ['registrar', 'administrator'],
            ],

            // Receipt by borrower
            'confirm_receipt' => [
                'from' => ['in_transit', 'dispatched'],
                'to' => 'received_by_borrower',
                'label' => 'Confirm Receipt',
                'icon' => 'hand-holding',
                'color' => 'success',
                'roles' => ['registrar', 'administrator'],
            ],

            // On loan phase
            'put_on_display' => [
                'from' => ['received_by_borrower', 'in_storage_borrower'],
                'to' => 'on_display',
                'label' => 'Put On Display',
                'icon' => 'image',
                'color' => 'primary',
                'roles' => ['registrar', 'administrator'],
            ],
            'move_to_storage' => [
                'from' => ['received_by_borrower', 'on_display'],
                'to' => 'in_storage_borrower',
                'label' => 'Move to Storage',
                'icon' => 'warehouse',
                'color' => 'secondary',
                'roles' => ['registrar', 'administrator'],
            ],

            // Return phase
            'initiate_return' => [
                'from' => ['on_display', 'in_storage_borrower', 'received_by_borrower'],
                'to' => 'return_initiated',
                'label' => 'Initiate Return',
                'icon' => 'undo',
                'color' => 'info',
                'roles' => ['registrar', 'administrator'],
            ],
            'return_condition_check' => [
                'from' => ['return_initiated'],
                'to' => 'return_condition_check',
                'label' => 'Start Return Condition Check',
                'icon' => 'clipboard',
                'color' => 'warning',
                'roles' => ['registrar', 'administrator'],
            ],
            'pack_for_return' => [
                'from' => ['return_condition_check'],
                'to' => 'return_packed',
                'label' => 'Pack for Return',
                'icon' => 'box',
                'color' => 'info',
                'roles' => ['registrar', 'administrator'],
            ],
            'dispatch_return' => [
                'from' => ['return_packed'],
                'to' => 'return_in_transit',
                'label' => 'Dispatch Return',
                'icon' => 'truck',
                'color' => 'primary',
                'roles' => ['registrar', 'administrator'],
            ],
            'receive_return' => [
                'from' => ['return_in_transit'],
                'to' => 'returned',
                'label' => 'Receive Return',
                'icon' => 'check-circle',
                'color' => 'success',
                'roles' => ['registrar', 'art_handler', 'administrator'],
            ],
            'verify_return_condition' => [
                'from' => ['returned'],
                'to' => 'return_condition_verified',
                'label' => 'Verify Return Condition',
                'icon' => 'clipboard-check',
                'color' => 'success',
                'roles' => ['conservator', 'registrar', 'administrator'],
            ],
            'close_loan' => [
                'from' => ['return_condition_verified'],
                'to' => 'closed',
                'label' => 'Close Loan',
                'icon' => 'archive',
                'color' => 'secondary',
                'roles' => ['registrar', 'administrator'],
                'confirm' => true,
                'confirm_message' => 'Close this loan record?',
            ],

            // Cancellation (from multiple states)
            'cancel' => [
                'from' => [
                    'request_received', 'under_review', 'approved',
                    'agreement_pending', 'agreement_signed',
                    'insurance_pending', 'insurance_confirmed',
                    'condition_check', 'condition_complete',
                    'packing', 'packed', 'courier_arranged',
                ],
                'to' => 'cancelled',
                'label' => 'Cancel Loan',
                'icon' => 'ban',
                'color' => 'danger',
                'roles' => ['registrar', 'director', 'administrator'],
                'confirm' => true,
                'confirm_message' => 'Cancel this loan? This action cannot be undone.',
            ],
        ];
    }

    /**
     * Get states grouped by phase.
     *
     * @return array States grouped by phase
     */
    public function getStatesByPhase(): array
    {
        $grouped = [];

        foreach ($this->states as $state => $definition) {
            $phase = $definition['phase'] ?? 'other';
            if (!isset($grouped[$phase])) {
                $grouped[$phase] = [];
            }
            $grouped[$phase][$state] = $definition;
        }

        return $grouped;
    }

    /**
     * Get phase labels.
     */
    public function getPhaseLabels(): array
    {
        return [
            'request' => 'Request & Approval',
            'preparation' => 'Preparation',
            'dispatch' => 'Dispatch',
            'transit' => 'Transit',
            'on_loan' => 'On Loan',
            'return' => 'Return',
            'closed' => 'Closed',
        ];
    }

    /**
     * Calculate workflow progress percentage.
     *
     * @param string $currentState Current state
     *
     * @return int Progress 0-100
     */
    public function getProgress(string $currentState): int
    {
        $stateOrder = [
            'request_received' => 5,
            'under_review' => 10,
            'approved' => 15,
            'rejected' => 100,
            'agreement_pending' => 20,
            'agreement_signed' => 25,
            'insurance_pending' => 30,
            'insurance_confirmed' => 35,
            'condition_check' => 40,
            'condition_complete' => 45,
            'packing' => 50,
            'packed' => 55,
            'courier_arranged' => 60,
            'dispatched' => 65,
            'in_transit' => 70,
            'received_by_borrower' => 75,
            'on_display' => 80,
            'in_storage_borrower' => 80,
            'return_initiated' => 82,
            'return_condition_check' => 85,
            'return_packed' => 88,
            'return_in_transit' => 90,
            'returned' => 95,
            'return_condition_verified' => 98,
            'closed' => 100,
            'cancelled' => 100,
        ];

        return $stateOrder[$currentState] ?? 0;
    }
}
