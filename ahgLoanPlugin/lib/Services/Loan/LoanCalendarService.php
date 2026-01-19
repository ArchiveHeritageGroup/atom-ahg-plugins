<?php

declare(strict_types=1);

namespace AhgLoan\Services\Loan;

use Illuminate\Database\ConnectionInterface;

/**
 * Loan Calendar Service.
 *
 * Provides calendar and timeline views for loan scheduling.
 * Supports FullCalendar.js compatible event output.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class LoanCalendarService
{
    /** Event types */
    public const EVENT_TYPES = [
        'loan_period' => ['color' => '#0d6efd', 'label' => 'Loan Period'],
        'start_date' => ['color' => '#198754', 'label' => 'Loan Start'],
        'end_date' => ['color' => '#dc3545', 'label' => 'Loan End'],
        'pickup' => ['color' => '#6610f2', 'label' => 'Pickup'],
        'delivery' => ['color' => '#fd7e14', 'label' => 'Delivery'],
        'condition_check' => ['color' => '#20c997', 'label' => 'Condition Check'],
        'reminder' => ['color' => '#ffc107', 'label' => 'Reminder'],
    ];

    /** Color schemes for loan types */
    public const LOAN_COLORS = [
        'out' => ['background' => '#fff3cd', 'border' => '#ffc107', 'text' => '#856404'],
        'in' => ['background' => '#cff4fc', 'border' => '#0dcaf0', 'text' => '#055160'],
    ];

    private ConnectionInterface $db;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Get calendar events for a date range.
     *
     * @param string      $startDate Start date (Y-m-d)
     * @param string      $endDate   End date (Y-m-d)
     * @param string|null $loanType  Filter by loan type (out/in)
     *
     * @return array FullCalendar compatible events
     */
    public function getCalendarEvents(string $startDate, string $endDate, ?string $loanType = null): array
    {
        $events = [];

        // Get loans in date range
        $query = $this->db->table('loan as l')
            ->leftJoin('workflow_instance as wi', function ($join) {
                $join->on('wi.entity_id', '=', 'l.id')
                    ->where('wi.entity_type', '=', 'loan');
            })
            ->where(function ($q) use ($startDate, $endDate) {
                // Loan period overlaps with date range
                $q->where(function ($q2) use ($startDate, $endDate) {
                    $q2->where('l.start_date', '<=', $endDate)
                        ->where('l.end_date', '>=', $startDate);
                });
            });

        if ($loanType) {
            $query->where('l.loan_type', $loanType);
        }

        $loans = $query->select('l.*', 'wi.current_state')
            ->get();

        foreach ($loans as $loan) {
            $colors = self::LOAN_COLORS[$loan->loan_type] ?? self::LOAN_COLORS['out'];

            // Main loan period event
            if ($loan->start_date && $loan->end_date) {
                $events[] = [
                    'id' => 'loan_'.$loan->id,
                    'title' => $loan->loan_number.' - '.$loan->partner_institution,
                    'start' => $loan->start_date,
                    'end' => date('Y-m-d', strtotime($loan->end_date.' +1 day')), // FullCalendar end is exclusive
                    'backgroundColor' => $colors['background'],
                    'borderColor' => $colors['border'],
                    'textColor' => $colors['text'],
                    'extendedProps' => [
                        'loan_id' => $loan->id,
                        'loan_number' => $loan->loan_number,
                        'loan_type' => $loan->loan_type,
                        'partner' => $loan->partner_institution,
                        'status' => $loan->current_state,
                        'event_type' => 'loan_period',
                    ],
                    'url' => '/loan/show?id='.$loan->id,
                ];
            }

            // Start date marker
            if ($loan->start_date >= $startDate && $loan->start_date <= $endDate) {
                $events[] = [
                    'id' => 'start_'.$loan->id,
                    'title' => ('out' === $loan->loan_type ? '📤 ' : '📥 ').'Start: '.$loan->loan_number,
                    'start' => $loan->start_date,
                    'allDay' => true,
                    'backgroundColor' => self::EVENT_TYPES['start_date']['color'],
                    'display' => 'list-item',
                    'extendedProps' => [
                        'loan_id' => $loan->id,
                        'event_type' => 'start_date',
                    ],
                ];
            }

            // End date marker
            if ($loan->end_date >= $startDate && $loan->end_date <= $endDate) {
                $isOverdue = !$loan->return_date && strtotime($loan->end_date) < strtotime('today');
                $events[] = [
                    'id' => 'end_'.$loan->id,
                    'title' => ($isOverdue ? '⚠️ OVERDUE: ' : '🏁 Due: ').$loan->loan_number,
                    'start' => $loan->end_date,
                    'allDay' => true,
                    'backgroundColor' => $isOverdue ? '#dc3545' : self::EVENT_TYPES['end_date']['color'],
                    'display' => 'list-item',
                    'extendedProps' => [
                        'loan_id' => $loan->id,
                        'event_type' => 'end_date',
                        'is_overdue' => $isOverdue,
                    ],
                ];
            }
        }

        // Add shipment events
        $shipments = $this->db->table('loan_shipment as s')
            ->leftJoin('loan as l', 'l.id', '=', 's.loan_id')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('s.scheduled_pickup', [$startDate, $endDate])
                    ->orWhereBetween('s.scheduled_delivery', [$startDate, $endDate]);
            })
            ->select('s.*', 'l.loan_number', 'l.partner_institution')
            ->get();

        foreach ($shipments as $shipment) {
            if ($shipment->scheduled_pickup) {
                $events[] = [
                    'id' => 'pickup_'.$shipment->id,
                    'title' => '🚚 Pickup: '.$shipment->loan_number,
                    'start' => date('Y-m-d', strtotime($shipment->scheduled_pickup)),
                    'allDay' => true,
                    'backgroundColor' => self::EVENT_TYPES['pickup']['color'],
                    'extendedProps' => [
                        'loan_id' => $shipment->loan_id,
                        'shipment_id' => $shipment->id,
                        'event_type' => 'pickup',
                    ],
                ];
            }

            if ($shipment->scheduled_delivery) {
                $events[] = [
                    'id' => 'delivery_'.$shipment->id,
                    'title' => '📦 Delivery: '.$shipment->loan_number,
                    'start' => date('Y-m-d', strtotime($shipment->scheduled_delivery)),
                    'allDay' => true,
                    'backgroundColor' => self::EVENT_TYPES['delivery']['color'],
                    'extendedProps' => [
                        'loan_id' => $shipment->loan_id,
                        'shipment_id' => $shipment->id,
                        'event_type' => 'delivery',
                    ],
                ];
            }
        }

        return $events;
    }

    /**
     * Get timeline data for Gantt-style display.
     *
     * @param string      $startDate Start date
     * @param string      $endDate   End date
     * @param string|null $loanType  Filter by type
     *
     * @return array Timeline data
     */
    public function getTimelineData(string $startDate, string $endDate, ?string $loanType = null): array
    {
        $query = $this->db->table('loan as l')
            ->leftJoin('workflow_instance as wi', function ($join) {
                $join->on('wi.entity_id', '=', 'l.id')
                    ->where('wi.entity_type', '=', 'loan');
            })
            ->whereNotNull('l.start_date')
            ->whereNotNull('l.end_date')
            ->where('l.start_date', '<=', $endDate)
            ->where('l.end_date', '>=', $startDate);

        if ($loanType) {
            $query->where('l.loan_type', $loanType);
        }

        $loans = $query->select('l.*', 'wi.current_state', 'wi.is_complete')
            ->orderBy('l.start_date')
            ->get();

        $timeline = [];

        foreach ($loans as $loan) {
            $startTs = strtotime($loan->start_date);
            $endTs = strtotime($loan->end_date);
            $durationDays = (int) (($endTs - $startTs) / 86400);
            $isOverdue = !$loan->return_date && $endTs < strtotime('today');

            $timeline[] = [
                'id' => $loan->id,
                'loan_number' => $loan->loan_number,
                'title' => $loan->title,
                'loan_type' => $loan->loan_type,
                'partner' => $loan->partner_institution,
                'start_date' => $loan->start_date,
                'end_date' => $loan->end_date,
                'return_date' => $loan->return_date,
                'duration_days' => $durationDays,
                'status' => $loan->current_state,
                'is_complete' => (bool) $loan->is_complete,
                'is_overdue' => $isOverdue,
                'progress' => $this->calculateProgress($loan),
            ];
        }

        return $timeline;
    }

    /**
     * Get monthly summary.
     *
     * @param int $year  Year
     * @param int $month Month (1-12)
     *
     * @return array Monthly statistics
     */
    public function getMonthlySummary(int $year, int $month): array
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => date('F', strtotime($startDate)),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'loans_starting' => $this->db->table('loan')
                ->whereBetween('start_date', [$startDate, $endDate])
                ->count(),
            'loans_ending' => $this->db->table('loan')
                ->whereBetween('end_date', [$startDate, $endDate])
                ->count(),
            'loans_active' => $this->db->table('loan as l')
                ->leftJoin('workflow_instance as wi', function ($join) {
                    $join->on('wi.entity_id', '=', 'l.id')
                        ->where('wi.entity_type', '=', 'loan');
                })
                ->where('l.start_date', '<=', $endDate)
                ->where('l.end_date', '>=', $startDate)
                ->where('wi.is_complete', false)
                ->count(),
            'shipments_scheduled' => $this->db->table('loan_shipment')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('scheduled_pickup', [$startDate, $endDate])
                        ->orWhereBetween('scheduled_delivery', [$startDate, $endDate]);
                })
                ->count(),
            'overdue_at_month_end' => $this->db->table('loan as l')
                ->leftJoin('workflow_instance as wi', function ($join) {
                    $join->on('wi.entity_id', '=', 'l.id')
                        ->where('wi.entity_type', '=', 'loan');
                })
                ->where('l.end_date', '<', $endDate)
                ->whereNull('l.return_date')
                ->where('wi.is_complete', false)
                ->count(),
        ];
    }

    /**
     * Get availability for an object.
     *
     * Check when an object is available for loan.
     *
     * @param int    $objectId  Information object ID
     * @param string $startDate Start of check period
     * @param string $endDate   End of check period
     *
     * @return array Availability periods
     */
    public function getObjectAvailability(int $objectId, string $startDate, string $endDate): array
    {
        // Get loans that include this object
        $loans = $this->db->table('loan as l')
            ->join('loan_object as lo', 'lo.loan_id', '=', 'l.id')
            ->leftJoin('workflow_instance as wi', function ($join) {
                $join->on('wi.entity_id', '=', 'l.id')
                    ->where('wi.entity_type', '=', 'loan');
            })
            ->where('lo.information_object_id', $objectId)
            ->where('l.start_date', '<=', $endDate)
            ->where('l.end_date', '>=', $startDate)
            ->whereNotIn('wi.current_state', ['cancelled', 'rejected', 'closed'])
            ->select('l.id', 'l.loan_number', 'l.start_date', 'l.end_date', 'l.partner_institution')
            ->orderBy('l.start_date')
            ->get();

        $unavailablePeriods = [];
        foreach ($loans as $loan) {
            $unavailablePeriods[] = [
                'start' => $loan->start_date,
                'end' => $loan->end_date,
                'reason' => 'On loan to '.$loan->partner_institution,
                'loan_id' => $loan->id,
                'loan_number' => $loan->loan_number,
            ];
        }

        // Calculate available periods
        $availablePeriods = [];
        $currentStart = $startDate;

        usort($unavailablePeriods, fn ($a, $b) => strcmp($a['start'], $b['start']));

        foreach ($unavailablePeriods as $unavailable) {
            if ($currentStart < $unavailable['start']) {
                $availablePeriods[] = [
                    'start' => $currentStart,
                    'end' => date('Y-m-d', strtotime($unavailable['start'].' -1 day')),
                ];
            }
            $currentStart = date('Y-m-d', strtotime($unavailable['end'].' +1 day'));
        }

        if ($currentStart <= $endDate) {
            $availablePeriods[] = [
                'start' => $currentStart,
                'end' => $endDate,
            ];
        }

        return [
            'object_id' => $objectId,
            'check_period' => ['start' => $startDate, 'end' => $endDate],
            'available_periods' => $availablePeriods,
            'unavailable_periods' => $unavailablePeriods,
            'is_available_now' => $this->isObjectAvailableNow($objectId),
        ];
    }

    /**
     * Check if object is currently available.
     */
    private function isObjectAvailableNow(int $objectId): bool
    {
        $today = date('Y-m-d');

        return !$this->db->table('loan as l')
            ->join('loan_object as lo', 'lo.loan_id', '=', 'l.id')
            ->leftJoin('workflow_instance as wi', function ($join) {
                $join->on('wi.entity_id', '=', 'l.id')
                    ->where('wi.entity_type', '=', 'loan');
            })
            ->where('lo.information_object_id', $objectId)
            ->where('l.start_date', '<=', $today)
            ->where('l.end_date', '>=', $today)
            ->whereNotIn('wi.current_state', ['cancelled', 'rejected', 'closed'])
            ->exists();
    }

    /**
     * Calculate loan progress percentage.
     */
    private function calculateProgress(object $loan): int
    {
        if (!$loan->start_date || !$loan->end_date) {
            return 0;
        }

        $startTs = strtotime($loan->start_date);
        $endTs = strtotime($loan->end_date);
        $nowTs = time();

        if ($loan->return_date) {
            return 100;
        }

        if ($nowTs < $startTs) {
            return 0;
        }

        if ($nowTs >= $endTs) {
            return 100;
        }

        $totalDuration = $endTs - $startTs;
        $elapsed = $nowTs - $startTs;

        return (int) round(($elapsed / $totalDuration) * 100);
    }

    /**
     * Get upcoming events.
     *
     * @param int $days Number of days to look ahead
     *
     * @return array Upcoming events
     */
    public function getUpcomingEvents(int $days = 14): array
    {
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime("+{$days} days"));

        $events = [];

        // Loans starting
        $starting = $this->db->table('loan')
            ->whereBetween('start_date', [$startDate, $endDate])
            ->orderBy('start_date')
            ->get();

        foreach ($starting as $loan) {
            $events[] = [
                'date' => $loan->start_date,
                'type' => 'loan_start',
                'icon' => '📤',
                'title' => 'Loan starts: '.$loan->loan_number,
                'description' => $loan->partner_institution,
                'loan_id' => $loan->id,
            ];
        }

        // Loans ending
        $ending = $this->db->table('loan as l')
            ->leftJoin('workflow_instance as wi', function ($join) {
                $join->on('wi.entity_id', '=', 'l.id')
                    ->where('wi.entity_type', '=', 'loan');
            })
            ->whereBetween('l.end_date', [$startDate, $endDate])
            ->whereNull('l.return_date')
            ->where('wi.is_complete', false)
            ->select('l.*')
            ->orderBy('l.end_date')
            ->get();

        foreach ($ending as $loan) {
            $events[] = [
                'date' => $loan->end_date,
                'type' => 'loan_end',
                'icon' => '🏁',
                'title' => 'Loan due: '.$loan->loan_number,
                'description' => $loan->partner_institution,
                'loan_id' => $loan->id,
            ];
        }

        // Scheduled shipments
        $shipments = $this->db->table('loan_shipment as s')
            ->leftJoin('loan as l', 'l.id', '=', 's.loan_id')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('s.scheduled_pickup', [$startDate, $endDate])
                    ->orWhereBetween('s.scheduled_delivery', [$startDate, $endDate]);
            })
            ->whereIn('s.status', ['planned', 'picked_up', 'in_transit'])
            ->select('s.*', 'l.loan_number')
            ->get();

        foreach ($shipments as $shipment) {
            if ($shipment->scheduled_pickup && $shipment->scheduled_pickup >= $startDate && $shipment->scheduled_pickup <= $endDate) {
                $events[] = [
                    'date' => date('Y-m-d', strtotime($shipment->scheduled_pickup)),
                    'type' => 'pickup',
                    'icon' => '🚚',
                    'title' => 'Pickup: '.$shipment->loan_number,
                    'description' => $shipment->origin_address,
                    'loan_id' => $shipment->loan_id,
                    'shipment_id' => $shipment->id,
                ];
            }

            if ($shipment->scheduled_delivery && $shipment->scheduled_delivery >= $startDate && $shipment->scheduled_delivery <= $endDate) {
                $events[] = [
                    'date' => date('Y-m-d', strtotime($shipment->scheduled_delivery)),
                    'type' => 'delivery',
                    'icon' => '📦',
                    'title' => 'Delivery: '.$shipment->loan_number,
                    'description' => $shipment->destination_address,
                    'loan_id' => $shipment->loan_id,
                    'shipment_id' => $shipment->id,
                ];
            }
        }

        // Sort by date
        usort($events, fn ($a, $b) => strcmp($a['date'], $b['date']));

        return $events;
    }

    /**
     * Export calendar to iCal format.
     *
     * @param string $startDate Start date
     * @param string $endDate   End date
     *
     * @return string iCal formatted string
     */
    public function exportToIcal(string $startDate, string $endDate): string
    {
        $events = $this->getCalendarEvents($startDate, $endDate);

        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//AtoM AHG Framework//Loan Calendar//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";

        foreach ($events as $event) {
            $uid = md5($event['id'].time());
            $start = str_replace('-', '', $event['start']);
            $end = isset($event['end']) ? str_replace('-', '', $event['end']) : $start;

            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= "UID:{$uid}\r\n";
            $ical .= "DTSTART;VALUE=DATE:{$start}\r\n";
            $ical .= "DTEND;VALUE=DATE:{$end}\r\n";
            $ical .= 'SUMMARY:'.str_replace("\n", '\\n', $event['title'])."\r\n";
            $ical .= "END:VEVENT\r\n";
        }

        $ical .= "END:VCALENDAR\r\n";

        return $ical;
    }
}
