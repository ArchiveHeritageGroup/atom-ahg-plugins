<?php

declare(strict_types=1);

namespace arMuseumMetadataPlugin\Services\Workflow;

/**
 * Object Entry Workflow.
 *
 * Implements Spectrum 5.0 Object Entry procedure as a state machine.
 * Tracks objects arriving at the museum for any reason (acquisition,
 * loan, enquiry, etc.) through receipt, documentation, and disposition.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 *
 * @see https://collectionstrust.org.uk/spectrum/procedures/object-entry/
 */
class ObjectEntryWorkflow extends AbstractWorkflow
{
    protected string $initialState = 'pending_arrival';

    protected array $finalStates = ['accessioned', 'returned', 'disposed', 'cancelled'];

    /** Entry reasons */
    public const ENTRY_REASONS = [
        'acquisition' => 'Acquisition (potential)',
        'loan_in' => 'Loan In',
        'enquiry' => 'Enquiry/Identification',
        'conservation' => 'Conservation Treatment',
        'photography' => 'Photography',
        'research' => 'Research',
        'deposit' => 'Deposit',
        'found' => 'Found in Collection',
        'unknown' => 'Unknown',
    ];

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
        return 'object_entry';
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'Object Entry (Spectrum 5.0)';
    }

    /**
     * Define workflow states.
     */
    private function initializeStates(): void
    {
        $this->states = [
            'pending_arrival' => [
                'label' => 'Pending Arrival',
                'description' => 'Object expected but not yet received',
                'color' => 'info',
                'icon' => 'clock',
            ],
            'received' => [
                'label' => 'Received',
                'description' => 'Object physically received at institution',
                'color' => 'success',
                'icon' => 'hand-holding',
            ],
            'entry_recorded' => [
                'label' => 'Entry Recorded',
                'description' => 'Entry form completed with basic details',
                'color' => 'info',
                'icon' => 'file-alt',
            ],
            'condition_noted' => [
                'label' => 'Condition Noted',
                'description' => 'Condition on arrival documented',
                'color' => 'warning',
                'icon' => 'clipboard',
            ],
            'photographed' => [
                'label' => 'Photographed',
                'description' => 'Entry photographs taken',
                'color' => 'info',
                'icon' => 'camera',
            ],
            'temporary_location_assigned' => [
                'label' => 'Location Assigned',
                'description' => 'Temporary storage location assigned',
                'color' => 'success',
                'icon' => 'map-marker',
            ],
            'under_review' => [
                'label' => 'Under Review',
                'description' => 'Object being reviewed for disposition',
                'color' => 'warning',
                'icon' => 'search',
            ],
            'approved_for_acquisition' => [
                'label' => 'Approved for Acquisition',
                'description' => 'Approved to enter permanent collection',
                'color' => 'success',
                'icon' => 'check',
            ],
            'accessioned' => [
                'label' => 'Accessioned',
                'description' => 'Added to permanent collection',
                'color' => 'primary',
                'icon' => 'archive',
            ],
            'pending_return' => [
                'label' => 'Pending Return',
                'description' => 'Awaiting return to depositor',
                'color' => 'warning',
                'icon' => 'undo',
            ],
            'returned' => [
                'label' => 'Returned',
                'description' => 'Object returned to depositor',
                'color' => 'secondary',
                'icon' => 'undo',
            ],
            'disposed' => [
                'label' => 'Disposed',
                'description' => 'Object disposed of (unclaimed)',
                'color' => 'danger',
                'icon' => 'trash',
            ],
            'cancelled' => [
                'label' => 'Cancelled',
                'description' => 'Entry cancelled (object never arrived)',
                'color' => 'danger',
                'icon' => 'ban',
            ],
        ];
    }

    /**
     * Define workflow transitions.
     */
    private function initializeTransitions(): void
    {
        $this->transitions = [
            // Receipt
            'receive_object' => [
                'from' => ['pending_arrival'],
                'to' => 'received',
                'label' => 'Receive Object',
                'icon' => 'hand-holding',
                'color' => 'success',
                'roles' => ['registrar', 'art_handler', 'administrator'],
            ],
            'record_entry' => [
                'from' => ['received'],
                'to' => 'entry_recorded',
                'label' => 'Record Entry',
                'icon' => 'file-alt',
                'color' => 'info',
                'roles' => ['registrar', 'administrator'],
            ],
            'note_condition' => [
                'from' => ['entry_recorded'],
                'to' => 'condition_noted',
                'label' => 'Note Condition',
                'icon' => 'clipboard',
                'color' => 'warning',
                'roles' => ['registrar', 'conservator', 'administrator'],
            ],
            'photograph' => [
                'from' => ['condition_noted'],
                'to' => 'photographed',
                'label' => 'Photograph Object',
                'icon' => 'camera',
                'color' => 'info',
                'roles' => ['registrar', 'photographer', 'administrator'],
            ],
            'assign_location' => [
                'from' => ['photographed', 'condition_noted'],
                'to' => 'temporary_location_assigned',
                'label' => 'Assign Location',
                'icon' => 'map-marker',
                'color' => 'success',
                'roles' => ['registrar', 'administrator'],
            ],

            // Review
            'submit_for_review' => [
                'from' => ['temporary_location_assigned'],
                'to' => 'under_review',
                'label' => 'Submit for Review',
                'icon' => 'search',
                'color' => 'warning',
                'roles' => ['registrar', 'administrator'],
            ],
            'approve_acquisition' => [
                'from' => ['under_review'],
                'to' => 'approved_for_acquisition',
                'label' => 'Approve for Acquisition',
                'icon' => 'check',
                'color' => 'success',
                'roles' => ['curator', 'director', 'administrator'],
                'confirm' => true,
                'confirm_message' => 'Approve this object for acquisition?',
            ],
            'reject_acquisition' => [
                'from' => ['under_review'],
                'to' => 'pending_return',
                'label' => 'Reject (Return)',
                'icon' => 'times',
                'color' => 'danger',
                'roles' => ['curator', 'director', 'administrator'],
            ],

            // Accession
            'accession' => [
                'from' => ['approved_for_acquisition'],
                'to' => 'accessioned',
                'label' => 'Accession',
                'icon' => 'archive',
                'color' => 'primary',
                'roles' => ['registrar', 'administrator'],
                'confirm' => true,
                'confirm_message' => 'Accession this object into the permanent collection?',
            ],

            // Return
            'initiate_return' => [
                'from' => ['temporary_location_assigned', 'under_review'],
                'to' => 'pending_return',
                'label' => 'Initiate Return',
                'icon' => 'undo',
                'color' => 'warning',
                'roles' => ['registrar', 'administrator'],
            ],
            'complete_return' => [
                'from' => ['pending_return'],
                'to' => 'returned',
                'label' => 'Complete Return',
                'icon' => 'check-circle',
                'color' => 'success',
                'roles' => ['registrar', 'administrator'],
                'confirm' => true,
                'confirm_message' => 'Confirm object has been returned?',
            ],

            // Disposal (unclaimed)
            'dispose' => [
                'from' => ['pending_return'],
                'to' => 'disposed',
                'label' => 'Dispose (Unclaimed)',
                'icon' => 'trash',
                'color' => 'danger',
                'roles' => ['registrar', 'director', 'administrator'],
                'confirm' => true,
                'confirm_message' => 'Dispose of this unclaimed object? This cannot be undone.',
            ],

            // Cancel
            'cancel' => [
                'from' => ['pending_arrival'],
                'to' => 'cancelled',
                'label' => 'Cancel Entry',
                'icon' => 'ban',
                'color' => 'danger',
                'roles' => ['registrar', 'administrator'],
            ],
        ];
    }

    /**
     * Get entry reasons for dropdown.
     */
    public function getEntryReasons(): array
    {
        return self::ENTRY_REASONS;
    }
}
