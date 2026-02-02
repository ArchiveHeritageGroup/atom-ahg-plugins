<?php

/**
 * SeatService - Manages reading room seat assignments
 *
 * Handles seat configuration, assignment to bookings, availability checking,
 * and real-time occupancy tracking.
 *
 * @package    ahgResearchPlugin
 * @subpackage Services
 */

use Illuminate\Database\Capsule\Manager as DB;

class SeatService
{
    /**
     * Get all seats for a reading room
     *
     * @param int  $roomId   Reading room ID
     * @param bool $onlyActive Only return active seats
     * @return array
     */
    public function getSeatsForRoom(int $roomId, bool $onlyActive = true): array
    {
        $query = DB::table('research_reading_room_seat')
            ->where('reading_room_id', $roomId)
            ->orderBy('sort_order')
            ->orderBy('seat_number');

        if ($onlyActive) {
            $query->where('is_active', 1);
        }

        return $query->get()->toArray();
    }

    /**
     * Get a single seat by ID
     *
     * @param int $seatId
     * @return object|null
     */
    public function getSeat(int $seatId): ?object
    {
        return DB::table('research_reading_room_seat')
            ->where('id', $seatId)
            ->first();
    }

    /**
     * Create a new seat
     *
     * @param array $data Seat data
     * @return int New seat ID
     */
    public function createSeat(array $data): int
    {
        $insertData = [
            'reading_room_id' => $data['reading_room_id'],
            'seat_number' => $data['seat_number'],
            'seat_label' => $data['seat_label'] ?? null,
            'seat_type' => $data['seat_type'] ?? 'standard',
            'has_power' => $data['has_power'] ?? 1,
            'has_lamp' => $data['has_lamp'] ?? 1,
            'has_computer' => $data['has_computer'] ?? 0,
            'has_magnifier' => $data['has_magnifier'] ?? 0,
            'position_x' => $data['position_x'] ?? null,
            'position_y' => $data['position_y'] ?? null,
            'zone' => $data['zone'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'sort_order' => $data['sort_order'] ?? 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        return DB::table('research_reading_room_seat')->insertGetId($insertData);
    }

    /**
     * Update a seat
     *
     * @param int   $seatId
     * @param array $data
     * @return bool
     */
    public function updateSeat(int $seatId, array $data): bool
    {
        $updateData = array_filter([
            'seat_number' => $data['seat_number'] ?? null,
            'seat_label' => $data['seat_label'] ?? null,
            'seat_type' => $data['seat_type'] ?? null,
            'has_power' => $data['has_power'] ?? null,
            'has_lamp' => $data['has_lamp'] ?? null,
            'has_computer' => $data['has_computer'] ?? null,
            'has_magnifier' => $data['has_magnifier'] ?? null,
            'position_x' => $data['position_x'] ?? null,
            'position_y' => $data['position_y'] ?? null,
            'zone' => $data['zone'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? null,
            'sort_order' => $data['sort_order'] ?? null,
        ], function ($v) {
            return $v !== null;
        });

        if (empty($updateData)) {
            return true;
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        return DB::table('research_reading_room_seat')
            ->where('id', $seatId)
            ->update($updateData) >= 0;
    }

    /**
     * Delete a seat (soft delete by deactivating)
     *
     * @param int $seatId
     * @return bool
     */
    public function deleteSeat(int $seatId): bool
    {
        return DB::table('research_reading_room_seat')
            ->where('id', $seatId)
            ->update([
                'is_active' => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Get available seats for a room on a specific date/time
     *
     * @param int    $roomId
     * @param string $date      Format: Y-m-d
     * @param string $startTime Format: H:i:s
     * @param string $endTime   Format: H:i:s
     * @param string $seatType  Optional seat type filter
     * @return array
     */
    public function getAvailableSeats(
        int $roomId,
        string $date,
        string $startTime,
        string $endTime,
        ?string $seatType = null
    ): array {
        // Get all active seats for the room
        $query = DB::table('research_reading_room_seat as s')
            ->where('s.reading_room_id', $roomId)
            ->where('s.is_active', 1);

        if ($seatType) {
            $query->where('s.seat_type', $seatType);
        }

        // Exclude seats that are already assigned during the requested time
        $occupiedSeatIds = DB::table('research_seat_assignment as sa')
            ->join('research_booking as b', 'sa.booking_id', '=', 'b.id')
            ->where('b.booking_date', $date)
            ->where('b.reading_room_id', $roomId)
            ->whereIn('sa.status', ['assigned', 'occupied'])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($q2) use ($startTime, $endTime) {
                    $q2->where('b.start_time', '<', $endTime)
                        ->where('b.end_time', '>', $startTime);
                });
            })
            ->pluck('sa.seat_id')
            ->toArray();

        if (!empty($occupiedSeatIds)) {
            $query->whereNotIn('s.id', $occupiedSeatIds);
        }

        return $query->orderBy('s.sort_order')
            ->orderBy('s.seat_number')
            ->get()
            ->toArray();
    }

    /**
     * Assign a seat to a booking
     *
     * @param int      $bookingId
     * @param int      $seatId
     * @param int|null $assignedBy User ID
     * @return int Assignment ID
     * @throws Exception If seat is not available
     */
    public function assignSeat(int $bookingId, int $seatId, ?int $assignedBy = null): int
    {
        // Get booking details
        $booking = DB::table('research_booking')
            ->where('id', $bookingId)
            ->first();

        if (!$booking) {
            throw new Exception('Booking not found');
        }

        // Check if seat is available for this time slot
        $available = $this->getAvailableSeats(
            $booking->reading_room_id,
            $booking->booking_date,
            $booking->start_time,
            $booking->end_time
        );

        $availableIds = array_column($available, 'id');
        if (!in_array($seatId, $availableIds)) {
            throw new Exception('Seat is not available for the requested time slot');
        }

        // Create the assignment
        $assignmentId = DB::table('research_seat_assignment')->insertGetId([
            'booking_id' => $bookingId,
            'seat_id' => $seatId,
            'assigned_at' => date('Y-m-d H:i:s'),
            'assigned_by' => $assignedBy,
            'status' => 'assigned',
        ]);

        // Update booking with seat_id
        DB::table('research_booking')
            ->where('id', $bookingId)
            ->update(['seat_id' => $seatId]);

        return $assignmentId;
    }

    /**
     * Release a seat assignment
     *
     * @param int      $bookingId
     * @param int|null $releasedBy User ID
     * @return bool
     */
    public function releaseSeat(int $bookingId, ?int $releasedBy = null): bool
    {
        $updated = DB::table('research_seat_assignment')
            ->where('booking_id', $bookingId)
            ->whereIn('status', ['assigned', 'occupied'])
            ->update([
                'released_at' => date('Y-m-d H:i:s'),
                'released_by' => $releasedBy,
                'status' => 'released',
            ]);

        // Clear seat_id from booking
        DB::table('research_booking')
            ->where('id', $bookingId)
            ->update(['seat_id' => null]);

        return $updated > 0;
    }

    /**
     * Mark seat as occupied (researcher checked in)
     *
     * @param int $bookingId
     * @return bool
     */
    public function markSeatOccupied(int $bookingId): bool
    {
        return DB::table('research_seat_assignment')
            ->where('booking_id', $bookingId)
            ->where('status', 'assigned')
            ->update(['status' => 'occupied']) > 0;
    }

    /**
     * Get current seat assignment for a booking
     *
     * @param int $bookingId
     * @return object|null
     */
    public function getSeatAssignment(int $bookingId): ?object
    {
        return DB::table('research_seat_assignment as sa')
            ->join('research_reading_room_seat as s', 'sa.seat_id', '=', 's.id')
            ->where('sa.booking_id', $bookingId)
            ->whereIn('sa.status', ['assigned', 'occupied'])
            ->select('sa.*', 's.seat_number', 's.seat_label', 's.seat_type', 's.zone')
            ->first();
    }

    /**
     * Get real-time occupancy for a reading room
     *
     * @param int    $roomId
     * @param string $date Optional, defaults to today
     * @return array
     */
    public function getRoomOccupancy(int $roomId, ?string $date = null): array
    {
        $date = $date ?? date('Y-m-d');
        $currentTime = date('H:i:s');

        // Get all seats
        $allSeats = $this->getSeatsForRoom($roomId);
        $totalSeats = count($allSeats);

        // Get currently occupied seats
        $occupiedSeats = DB::table('research_seat_assignment as sa')
            ->join('research_booking as b', 'sa.booking_id', '=', 'b.id')
            ->where('b.reading_room_id', $roomId)
            ->where('b.booking_date', $date)
            ->where('b.start_time', '<=', $currentTime)
            ->where('b.end_time', '>=', $currentTime)
            ->whereIn('sa.status', ['assigned', 'occupied'])
            ->count();

        // Get upcoming assignments for today
        $upcomingAssignments = DB::table('research_seat_assignment as sa')
            ->join('research_booking as b', 'sa.booking_id', '=', 'b.id')
            ->where('b.reading_room_id', $roomId)
            ->where('b.booking_date', $date)
            ->where('b.start_time', '>', $currentTime)
            ->whereIn('sa.status', ['assigned'])
            ->count();

        return [
            'room_id' => $roomId,
            'date' => $date,
            'current_time' => $currentTime,
            'total_seats' => $totalSeats,
            'occupied_seats' => $occupiedSeats,
            'available_seats' => $totalSeats - $occupiedSeats,
            'upcoming_bookings' => $upcomingAssignments,
            'occupancy_percentage' => $totalSeats > 0 ? round(($occupiedSeats / $totalSeats) * 100, 1) : 0,
        ];
    }

    /**
     * Get seat map data for visualization
     *
     * @param int    $roomId
     * @param string $date
     * @param string $time
     * @return array
     */
    public function getSeatMapData(int $roomId, string $date, string $time): array
    {
        $seats = $this->getSeatsForRoom($roomId);
        $result = [];

        foreach ($seats as $seat) {
            // Check if seat is occupied at the given time
            $assignment = DB::table('research_seat_assignment as sa')
                ->join('research_booking as b', 'sa.booking_id', '=', 'b.id')
                ->where('sa.seat_id', $seat->id)
                ->where('b.booking_date', $date)
                ->where('b.start_time', '<=', $time)
                ->where('b.end_time', '>=', $time)
                ->whereIn('sa.status', ['assigned', 'occupied'])
                ->select('sa.*', 'b.researcher_id')
                ->first();

            $result[] = [
                'id' => $seat->id,
                'seat_number' => $seat->seat_number,
                'seat_label' => $seat->seat_label,
                'seat_type' => $seat->seat_type,
                'zone' => $seat->zone,
                'position_x' => $seat->position_x,
                'position_y' => $seat->position_y,
                'has_power' => (bool) $seat->has_power,
                'has_lamp' => (bool) $seat->has_lamp,
                'has_computer' => (bool) $seat->has_computer,
                'has_magnifier' => (bool) $seat->has_magnifier,
                'status' => $assignment ? $assignment->status : 'available',
                'booking_id' => $assignment->booking_id ?? null,
                'researcher_id' => $assignment->researcher_id ?? null,
            ];
        }

        return $result;
    }

    /**
     * Auto-assign seat to a booking based on preferences
     *
     * @param int         $bookingId
     * @param array       $preferences Optional preferences (seat_type, zone, etc.)
     * @param int|null    $assignedBy
     * @return int|null Assignment ID or null if no seats available
     */
    public function autoAssignSeat(int $bookingId, array $preferences = [], ?int $assignedBy = null): ?int
    {
        $booking = DB::table('research_booking')
            ->where('id', $bookingId)
            ->first();

        if (!$booking) {
            return null;
        }

        // Get available seats
        $available = $this->getAvailableSeats(
            $booking->reading_room_id,
            $booking->booking_date,
            $booking->start_time,
            $booking->end_time,
            $preferences['seat_type'] ?? null
        );

        if (empty($available)) {
            return null;
        }

        // Filter by zone if specified
        if (!empty($preferences['zone'])) {
            $available = array_filter($available, function ($seat) use ($preferences) {
                return $seat->zone === $preferences['zone'];
            });
        }

        // Filter by amenities if specified
        if (!empty($preferences['needs_power'])) {
            $available = array_filter($available, function ($seat) {
                return $seat->has_power;
            });
        }

        if (!empty($preferences['needs_computer'])) {
            $available = array_filter($available, function ($seat) {
                return $seat->has_computer;
            });
        }

        if (empty($available)) {
            // Fall back to any available seat
            $available = $this->getAvailableSeats(
                $booking->reading_room_id,
                $booking->booking_date,
                $booking->start_time,
                $booking->end_time
            );
        }

        if (empty($available)) {
            return null;
        }

        // Pick the first available seat
        $seat = reset($available);

        try {
            return $this->assignSeat($bookingId, $seat->id, $assignedBy);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Bulk create seats for a room
     *
     * @param int    $roomId
     * @param string $pattern Pattern like "A1-A10" or "1-20"
     * @param string $seatType
     * @param string $zone
     * @return int Number of seats created
     */
    public function bulkCreateSeats(
        int $roomId,
        string $pattern,
        string $seatType = 'standard',
        ?string $zone = null
    ): int {
        $seats = $this->parsePattern($pattern);
        $created = 0;

        foreach ($seats as $index => $seatNumber) {
            try {
                $this->createSeat([
                    'reading_room_id' => $roomId,
                    'seat_number' => $seatNumber,
                    'seat_type' => $seatType,
                    'zone' => $zone,
                    'sort_order' => $index,
                ]);
                $created++;
            } catch (Exception $e) {
                // Skip duplicates
                continue;
            }
        }

        return $created;
    }

    /**
     * Parse seat pattern like "A1-A10" or "1-20"
     *
     * @param string $pattern
     * @return array
     */
    private function parsePattern(string $pattern): array
    {
        $seats = [];

        if (preg_match('/^([A-Za-z]*)(\d+)-\1?(\d+)$/', $pattern, $matches)) {
            $prefix = $matches[1];
            $start = (int) $matches[2];
            $end = (int) $matches[3];

            for ($i = $start; $i <= $end; $i++) {
                $seats[] = $prefix . $i;
            }
        } else {
            // Single seat or comma-separated
            $seats = array_map('trim', explode(',', $pattern));
        }

        return $seats;
    }

    /**
     * Get seat statistics for a room
     *
     * @param int    $roomId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getSeatStatistics(int $roomId, string $startDate, string $endDate): array
    {
        // Total assignments
        $totalAssignments = DB::table('research_seat_assignment as sa')
            ->join('research_booking as b', 'sa.booking_id', '=', 'b.id')
            ->where('b.reading_room_id', $roomId)
            ->whereBetween('b.booking_date', [$startDate, $endDate])
            ->count();

        // Most used seats
        $mostUsedSeats = DB::table('research_seat_assignment as sa')
            ->join('research_booking as b', 'sa.booking_id', '=', 'b.id')
            ->join('research_reading_room_seat as s', 'sa.seat_id', '=', 's.id')
            ->where('b.reading_room_id', $roomId)
            ->whereBetween('b.booking_date', [$startDate, $endDate])
            ->select('s.id', 's.seat_number', 's.seat_label', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('s.id', 's.seat_number', 's.seat_label')
            ->orderByDesc('usage_count')
            ->limit(10)
            ->get()
            ->toArray();

        // Usage by seat type
        $usageByType = DB::table('research_seat_assignment as sa')
            ->join('research_booking as b', 'sa.booking_id', '=', 'b.id')
            ->join('research_reading_room_seat as s', 'sa.seat_id', '=', 's.id')
            ->where('b.reading_room_id', $roomId)
            ->whereBetween('b.booking_date', [$startDate, $endDate])
            ->select('s.seat_type', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('s.seat_type')
            ->get()
            ->toArray();

        // Average occupancy by day
        $dailyOccupancy = DB::table('research_seat_assignment as sa')
            ->join('research_booking as b', 'sa.booking_id', '=', 'b.id')
            ->where('b.reading_room_id', $roomId)
            ->whereBetween('b.booking_date', [$startDate, $endDate])
            ->select('b.booking_date', DB::raw('COUNT(*) as bookings'))
            ->groupBy('b.booking_date')
            ->orderBy('b.booking_date')
            ->get()
            ->toArray();

        return [
            'total_assignments' => $totalAssignments,
            'most_used_seats' => $mostUsedSeats,
            'usage_by_type' => $usageByType,
            'daily_occupancy' => $dailyOccupancy,
        ];
    }
}
