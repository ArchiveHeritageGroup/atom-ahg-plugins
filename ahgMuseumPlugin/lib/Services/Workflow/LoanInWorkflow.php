<?php

declare(strict_types=1);

namespace arMuseumMetadataPlugin\Services\Workflow;

/**
 * Loan In Workflow.
 *
 * Implements Spectrum 5.0 Loans In procedure as a state machine.
 * Tracks borrowed objects from request through receipt, display,
 * and return to lender.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 *
 * @see https://collectionstrust.org.uk/spectrum/procedures/loans-in/
 */
class LoanInWorkflow extends AbstractWorkflow
{
    protected string $initialState = 'request_submitted';

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
        return 'loan_in';
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'Loan In (Spectrum 5.0)';
    }

    /**
     * Define workflow states.
     */
    private function initializeStates(): void
    {
        $this->states = [
            'request_submitted' => [
                'label' => 'Request Submitted',
                'description' => 'Loan request submitted to lender',
                'color' => 'info',
                'icon' => 'paper-plane',
                'phase' => 'request',
            ],
            'awaiting_response' => [
                'label' => 'Awaiting Response',
                'description' => 'Waiting for lender response',
                'color' => 'warning',
                'icon' => 'clock',
                'phase' => 'request',
            ],
            'approved_by_lender' => [
                'label' => 'Approved by Lender',
                'description' => 'Lender has approved the loan',
                'color' => 'success',
                'icon' => 'check',
                'phase' => 'preparation',
            ],
            'declined_by_lender' => [
                'label' => 'Declined by Lender',
                'description' => 'Lender has declined the loan request',
                'color' => 'danger',
                'icon' => 'times',
                'phase' => 'closed',
            ],
            'agreement_drafting' => [
                'label' => 'Agreement Drafting',
                'description' => 'Loan agreement being drafted',
                'color' => 'info',
                'icon' => 'file-alt',
                'phase' => 'preparation',
            ],
            'agreement_review' => [
                'label' => 'Agreement Under Review',
                'description' => 'Agreement being reviewed by lender',
                'color' => 'warning',
                'icon' => 'search',
                'phase' => 'preparation',
            ],
            'agreement_signed' => [
                'label' => 'Agreement Signed',
                'description' => 'Loan agreement signed by both parties',
                'color' => 'success',
                'icon' => 'file-signature',
                'phase' => 'preparation',
            ],
            'insurance_arranged' => [
                'label' => 'Insurance Arranged',
                'description' => 'Insurance coverage arranged',
                'color' => 'success',
                'icon' => 'shield-check',
                'phase' => 'preparation',
            ],
            'facilities_report_pending' => [
                'label' => 'Facilities Report Pending',
                'description' => 'Awaiting facilities report approval',
                'color' => 'warning',
                'icon' => 'building',
                'phase' => 'preparation',
            ],
            'facilities_approved' => [
                'label' => 'Facilities Approved',
                'description' => 'Display/storage facilities approved by lender',
                'color' => 'success',
                'icon' => 'building',
                'phase' => 'preparation',
            ],
            'transport_arranged' => [
                'label' => 'Transport Arranged',
                'description' => 'Transport/courier arranged',
                'color' => 'info',
                'icon' => 'truck',
                'phase' => 'transit',
            ],
            'in_transit_inbound' => [
                'label' => 'In Transit (Inbound)',
                'description' => 'Object in transit from lender',
                'color' => 'primary',
                'icon' => 'shipping-fast',
                'phase' => 'transit',
            ],
            'received' => [
                'label' => 'Received',
                'description' => 'Object received at our institution',
                'color' => 'success',
                'icon' => 'hand-holding',
                'phase' => 'on_loan',
            ],
            'condition_checked' => [
                'label' => 'Condition Checked',
                'description' => 'Condition check completed upon receipt',
                'color' => 'success',
                'icon' => 'clipboard-check',
                'phase' => 'on_loan',
            ],
            'on_display' => [
                'label' => 'On Display',
                'description' => 'Object on display in exhibition',
                'color' => 'primary',
                'icon' => 'image',
                'phase' => 'on_loan',
            ],
            'in_storage' => [
                'label' => 'In Storage',
                'description' => 'Object in temporary storage',
                'color' => 'secondary',
                'icon' => 'warehouse',
                'phase' => 'on_loan',
            ],
            'return_preparation' => [
                'label' => 'Return Preparation',
                'description' => 'Preparing object for return',
                'color' => 'info',
                'icon' => 'undo',
                'phase' => 'return',
            ],
            'return_condition_check' => [
                'label' => 'Return Condition Check',
                'description' => 'Pre-return condition assessment',
                'color' => 'warning',
                'icon' => 'clipboard',
                'phase' => 'return',
            ],
            'return_packed' => [
                'label' => 'Packed for Return',
                'description' => 'Object packed for return transport',
                'color' => 'info',
                'icon' => 'box',
                'phase' => 'return',
            ],
            'in_transit_outbound' => [
                'label' => 'In Transit (Return)',
                'description' => 'Object in transit back to lender',
                'color' => 'primary',
                'icon' => 'shipping-fast',
                'phase' => 'return',
            ],
            'returned_to_lender' => [
                'label' => 'Returned to Lender',
                'description' => 'Object returned and received by lender',
                'color' => 'success',
                'icon' => 'check-circle',
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
            'send_request' => [
                'from' => ['request_submitted'],
                'to' => 'awaiting_response',
                'label' => 'Send Request',
                'icon' => 'paper-plane',
                'color' => 'info',
                'roles' => ['curator', 'registrar', 'administrator'],
            ],
            'lender_approves' => [
                'from' => ['awaiting_response'],
                'to' => 'approved_by_lender',
                'label' => 'Lender Approves',
                'icon' => 'check',
                'color' => 'success',
                'roles' => ['registrar', 'administrator'],
            ],
            'lender_declines' => [
                'from' => ['awaiting_response'],
                'to' => 'declined_by_lender',
                'label' => 'Lender Declines',
                'icon' => 'times',
                'color' => 'danger',
                'roles' => ['registrar', 'administrator'],
            ],

            // Agreement phase
            'draft_agreement' => [
                'from' => ['approved_by_lender'],
                'to' => 'agreement_drafting',
                'label' => 'Draft Agreement',
                'icon' => 'file-alt',
                'color' => 'info',
                'roles' => ['registrar', 'administrator'],
            ],
            'send_for_review' => [
                'from' => ['agreement_drafting'],
                'to' => 'agreement_review',
                'label' => 'Send for Review',
                'icon' => 'paper-plane',
                'color' => 'info',
                'roles' => ['registrar', 'administrator'],
            ],
            'agreement_signed' => [
                'from' => ['agreement_review'],
                'to' => 'agreement_signed',
                'label' => 'Agreement Signed',
                'icon' => 'file-signature',
                'color' => 'success',
                'roles' => ['registrar', 'administrator'],
            ],
            'revise_agreement' => [
                'from' => ['agreement_review'],
                'to' => 'agreement_drafting',
                'label' => 'Revise Agreement',
                'icon' => 'edit',
                'color' => 'warning',
                'roles' => ['registrar', 'administrator'],
            ],

            // Insurance and facilities
            'arrange_insurance' => [
                'from' => ['agreement_signed'],
                'to' => 'insurance_arranged',
                'label' => 'Arrange Insurance',
                'icon' => 'shield',
                'color' => 'success',
                'roles' => ['registrar', 'administrator'],
            ],
            'submit_facilities_report' => [
                'from' => ['insurance_arranged'],
                'to' => 'facilities_report_pending',
                'label' => 'Submit Facilities Report',
                'icon' => 'building',
                'color' => 'info',
                'roles' => ['registrar', 'administrator'],
            ],
            'facilities_approved' => [
                'from' => ['facilities_report_pending'],
                'to' => 'facilities_approved',
                'label' => 'Facilities Approved',
                'icon' => 'check',
                'color' => 'success',
                'roles' => ['registrar', 'administrator'],
            ],

            // Transport
            'arrange_transport' => [
                'from' => ['facilities_approved'],
                'to' => 'transport_arranged',
                'label' => 'Arrange Transport',
                'icon' => 'truck',
                'color' => 'info',
                'roles' => ['registrar', 'administrator'],
            ],
            'object_dispatched' => [
                'from' => ['transport_arranged'],
                'to' => 'in_transit_inbound',
                'label' => 'Object Dispatched',
                'icon' => 'shipping-fast',
                'color' => 'primary',
                'roles' => ['registrar', 'administrator'],
            ],

            // Receipt
            'receive_object' => [
                'from' => ['in_transit_inbound'],
                'to' => 'received',
                'label' => 'Receive Object',
                'icon' => 'hand-holding',
                'color' => 'success',
                'roles' => ['registrar', 'art_handler', 'administrator'],
                'confirm' => true,
                'confirm_message' => 'Confirm object has been received?',
            ],
            'complete_condition_check' => [
                'from' => ['received'],
                'to' => 'condition_checked',
                'label' => 'Complete Condition Check',
                'icon' => 'clipboard-check',
                'color' => 'success',
                'roles' => ['conservator', 'registrar', 'administrator'],
            ],

            // On loan
            'put_on_display' => [
                'from' => ['condition_checked', 'in_storage'],
                'to' => 'on_display',
                'label' => 'Put On Display',
                'icon' => 'image',
                'color' => 'primary',
                'roles' => ['registrar', 'curator', 'administrator'],
            ],
            'move_to_storage' => [
                'from' => ['condition_checked', 'on_display'],
                'to' => 'in_storage',
                'label' => 'Move to Storage',
                'icon' => 'warehouse',
                'color' => 'secondary',
                'roles' => ['registrar', 'administrator'],
            ],

            // Return phase
            'initiate_return' => [
                'from' => ['on_display', 'in_storage'],
                'to' => 'return_preparation',
                'label' => 'Initiate Return',
                'icon' => 'undo',
                'color' => 'info',
                'roles' => ['registrar', 'administrator'],
            ],
            'return_condition_check' => [
                'from' => ['return_preparation'],
                'to' => 'return_condition_check',
                'label' => 'Condition Check',
                'icon' => 'clipboard',
                'color' => 'warning',
                'roles' => ['conservator', 'registrar', 'administrator'],
            ],
            'pack_for_return' => [
                'from' => ['return_condition_check'],
                'to' => 'return_packed',
                'label' => 'Pack for Return',
                'icon' => 'box',
                'color' => 'info',
                'roles' => ['art_handler', 'registrar', 'administrator'],
            ],
            'dispatch_return' => [
                'from' => ['return_packed'],
                'to' => 'in_transit_outbound',
                'label' => 'Dispatch Return',
                'icon' => 'truck',
                'color' => 'primary',
                'roles' => ['registrar', 'administrator'],
            ],
            'confirm_return_receipt' => [
                'from' => ['in_transit_outbound'],
                'to' => 'returned_to_lender',
                'label' => 'Confirm Return Receipt',
                'icon' => 'check-circle',
                'color' => 'success',
                'roles' => ['registrar', 'administrator'],
            ],
            'close_loan' => [
                'from' => ['returned_to_lender'],
                'to' => 'closed',
                'label' => 'Close Loan',
                'icon' => 'archive',
                'color' => 'secondary',
                'roles' => ['registrar', 'administrator'],
                'confirm' => true,
                'confirm_message' => 'Close this loan record?',
            ],

            // Cancellation
            'cancel' => [
                'from' => [
                    'request_submitted', 'awaiting_response', 'approved_by_lender',
                    'agreement_drafting', 'agreement_review', 'agreement_signed',
                    'insurance_arranged', 'facilities_report_pending', 'facilities_approved',
                    'transport_arranged',
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
     * Get progress percentage.
     */
    public function getProgress(string $currentState): int
    {
        $stateOrder = [
            'request_submitted' => 5,
            'awaiting_response' => 10,
            'approved_by_lender' => 15,
            'declined_by_lender' => 100,
            'agreement_drafting' => 20,
            'agreement_review' => 25,
            'agreement_signed' => 30,
            'insurance_arranged' => 35,
            'facilities_report_pending' => 40,
            'facilities_approved' => 45,
            'transport_arranged' => 50,
            'in_transit_inbound' => 55,
            'received' => 60,
            'condition_checked' => 65,
            'on_display' => 70,
            'in_storage' => 70,
            'return_preparation' => 75,
            'return_condition_check' => 80,
            'return_packed' => 85,
            'in_transit_outbound' => 90,
            'returned_to_lender' => 95,
            'closed' => 100,
            'cancelled' => 100,
        ];

        return $stateOrder[$currentState] ?? 0;
    }
}
