<?php

/**
 * EquipmentService - Manages reading room equipment bookings
 *
 * Handles equipment configuration, booking, availability checking,
 * checkout/return tracking, and maintenance scheduling.
 *
 * @package    ahgResearchPlugin
 * @subpackage Services
 */

use Illuminate\Database\Capsule\Manager as DB;

class EquipmentService
{
    /**
     * Get all equipment for a reading room
     *
     * @param int  $roomId        Reading room ID
     * @param bool $onlyAvailable Only return available equipment
     * @return array
     */
    public function getEquipmentForRoom(int $roomId, bool $onlyAvailable = false): array
    {
        $query = DB::table('research_equipment')
            ->where('reading_room_id', $roomId);

        if ($onlyAvailable) {
            $query->where('is_available', 1);
        }

        return $query->orderBy('equipment_type')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Get equipment by ID
     *
     * @param int $equipmentId
     * @return object|null
     */
    public function getEquipment(int $equipmentId): ?object
    {
        return DB::table('research_equipment')
            ->where('id', $equipmentId)
            ->first();
    }

    /**
     * Create new equipment
     *
     * @param array $data Equipment data
     * @return int New equipment ID
     */
    public function createEquipment(array $data): int
    {
        return DB::table('research_equipment')->insertGetId([
            'reading_room_id' => $data['reading_room_id'],
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'equipment_type' => $data['equipment_type'],
            'brand' => $data['brand'] ?? null,
            'model' => $data['model'] ?? null,
            'serial_number' => $data['serial_number'] ?? null,
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'requires_training' => $data['requires_training'] ?? 0,
            'max_booking_hours' => $data['max_booking_hours'] ?? 4,
            'booking_increment_minutes' => $data['booking_increment_minutes'] ?? 30,
            'condition_status' => $data['condition_status'] ?? 'good',
            'last_maintenance_date' => $data['last_maintenance_date'] ?? null,
            'next_maintenance_date' => $data['next_maintenance_date'] ?? null,
            'is_available' => $data['is_available'] ?? 1,
            'notes' => $data['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update equipment
     *
     * @param int   $equipmentId
     * @param array $data
     * @return bool
     */
    public function updateEquipment(int $equipmentId, array $data): bool
    {
        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'code' => $data['code'] ?? null,
            'equipment_type' => $data['equipment_type'] ?? null,
            'brand' => $data['brand'] ?? null,
            'model' => $data['model'] ?? null,
            'serial_number' => $data['serial_number'] ?? null,
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'requires_training' => $data['requires_training'] ?? null,
            'max_booking_hours' => $data['max_booking_hours'] ?? null,
            'booking_increment_minutes' => $data['booking_increment_minutes'] ?? null,
            'condition_status' => $data['condition_status'] ?? null,
            'last_maintenance_date' => $data['last_maintenance_date'] ?? null,
            'next_maintenance_date' => $data['next_maintenance_date'] ?? null,
            'is_available' => $data['is_available'] ?? null,
            'notes' => $data['notes'] ?? null,
        ], function ($v) {
            return $v !== null;
        });

        if (empty($updateData)) {
            return true;
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        return DB::table('research_equipment')
            ->where('id', $equipmentId)
            ->update($updateData) >= 0;
    }

    /**
     * Delete equipment (soft delete by marking unavailable)
     *
     * @param int $equipmentId
     * @return bool
     */
    public function deleteEquipment(int $equipmentId): bool
    {
        return DB::table('research_equipment')
            ->where('id', $equipmentId)
            ->update([
                'is_available' => 0,
                'notes' => DB::raw("CONCAT(IFNULL(notes, ''), '\n[Deactivated: " . date('Y-m-d H:i:s') . "]')"),
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Get available equipment for a time slot
     *
     * @param int    $roomId
     * @param string $date
     * @param string $startTime
     * @param string $endTime
     * @param string $equipmentType Optional type filter
     * @return array
     */
    public function getAvailableEquipment(
        int $roomId,
        string $date,
        string $startTime,
        string $endTime,
        ?string $equipmentType = null
    ): array {
        $query = DB::table('research_equipment as e')
            ->where('e.reading_room_id', $roomId)
            ->where('e.is_available', 1);

        if ($equipmentType) {
            $query->where('e.equipment_type', $equipmentType);
        }

        // Exclude equipment that's already booked during the time slot
        $bookedEquipmentIds = DB::table('research_equipment_booking')
            ->where('booking_date', $date)
            ->whereIn('status', ['reserved', 'in_use'])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($q2) use ($startTime, $endTime) {
                    $q2->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                });
            })
            ->pluck('equipment_id')
            ->toArray();

        if (!empty($bookedEquipmentIds)) {
            $query->whereNotIn('e.id', $bookedEquipmentIds);
        }

        return $query->orderBy('e.name')
            ->get()
            ->toArray();
    }

    /**
     * Create an equipment booking
     *
     * @param array $data Booking data
     * @return int Booking ID
     * @throws Exception If equipment is not available
     */
    public function createBooking(array $data): int
    {
        // Check availability
        $available = $this->getAvailableEquipment(
            $data['reading_room_id'] ?? $this->getEquipment($data['equipment_id'])->reading_room_id,
            $data['booking_date'],
            $data['start_time'],
            $data['end_time']
        );

        $availableIds = array_column($available, 'id');
        if (!in_array($data['equipment_id'], $availableIds)) {
            throw new Exception('Equipment is not available for the requested time slot');
        }

        return DB::table('research_equipment_booking')->insertGetId([
            'booking_id' => $data['booking_id'] ?? null,
            'researcher_id' => $data['researcher_id'],
            'equipment_id' => $data['equipment_id'],
            'booking_date' => $data['booking_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'purpose' => $data['purpose'] ?? null,
            'status' => 'reserved',
            'notes' => $data['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get equipment booking by ID
     *
     * @param int $bookingId
     * @return object|null
     */
    public function getBooking(int $bookingId): ?object
    {
        return DB::table('research_equipment_booking as eb')
            ->join('research_equipment as e', 'eb.equipment_id', '=', 'e.id')
            ->where('eb.id', $bookingId)
            ->select('eb.*', 'e.name as equipment_name', 'e.equipment_type', 'e.location')
            ->first();
    }

    /**
     * Get equipment bookings for a researcher
     *
     * @param int    $researcherId
     * @param string $status Optional status filter
     * @return array
     */
    public function getResearcherBookings(int $researcherId, ?string $status = null): array
    {
        $query = DB::table('research_equipment_booking as eb')
            ->join('research_equipment as e', 'eb.equipment_id', '=', 'e.id')
            ->where('eb.researcher_id', $researcherId);

        if ($status) {
            $query->where('eb.status', $status);
        }

        return $query->select('eb.*', 'e.name as equipment_name', 'e.equipment_type', 'e.location')
            ->orderByDesc('eb.booking_date')
            ->orderByDesc('eb.start_time')
            ->get()
            ->toArray();
    }

    /**
     * Get equipment bookings linked to a reading room booking
     *
     * @param int $roomBookingId
     * @return array
     */
    public function getBookingsForRoomBooking(int $roomBookingId): array
    {
        return DB::table('research_equipment_booking as eb')
            ->join('research_equipment as e', 'eb.equipment_id', '=', 'e.id')
            ->where('eb.booking_id', $roomBookingId)
            ->select('eb.*', 'e.name as equipment_name', 'e.equipment_type', 'e.location')
            ->get()
            ->toArray();
    }

    /**
     * Check out equipment (start using)
     *
     * @param int      $bookingId     Equipment booking ID
     * @param int|null $checkedOutBy  Staff user ID
     * @return bool
     */
    public function checkOut(int $bookingId, ?int $checkedOutBy = null): bool
    {
        return DB::table('research_equipment_booking')
            ->where('id', $bookingId)
            ->where('status', 'reserved')
            ->update([
                'status' => 'in_use',
                'checked_out_at' => date('Y-m-d H:i:s'),
                'checked_out_by' => $checkedOutBy,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Return equipment
     *
     * @param int         $bookingId
     * @param string|null $conditionOnReturn
     * @param string|null $returnNotes
     * @param int|null    $returnedBy Staff user ID
     * @return bool
     */
    public function returnEquipment(
        int $bookingId,
        ?string $conditionOnReturn = null,
        ?string $returnNotes = null,
        ?int $returnedBy = null
    ): bool {
        $updated = DB::table('research_equipment_booking')
            ->where('id', $bookingId)
            ->whereIn('status', ['reserved', 'in_use'])
            ->update([
                'status' => 'returned',
                'returned_at' => date('Y-m-d H:i:s'),
                'returned_by' => $returnedBy,
                'condition_on_return' => $conditionOnReturn,
                'return_notes' => $returnNotes,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;

        // If equipment was returned damaged, mark it for repair
        if ($updated && $conditionOnReturn === 'damaged') {
            $booking = $this->getBooking($bookingId);
            if ($booking) {
                DB::table('research_equipment')
                    ->where('id', $booking->equipment_id)
                    ->update([
                        'condition_status' => 'needs_repair',
                        'is_available' => 0,
                        'notes' => DB::raw("CONCAT(IFNULL(notes, ''), '\n[Damaged on " . date('Y-m-d') . ": " . ($returnNotes ?? 'No details') . "]')"),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }
        }

        return $updated;
    }

    /**
     * Cancel an equipment booking
     *
     * @param int $bookingId
     * @return bool
     */
    public function cancelBooking(int $bookingId): bool
    {
        return DB::table('research_equipment_booking')
            ->where('id', $bookingId)
            ->whereIn('status', ['reserved'])
            ->update([
                'status' => 'cancelled',
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Mark booking as no-show
     *
     * @param int $bookingId
     * @return bool
     */
    public function markNoShow(int $bookingId): bool
    {
        return DB::table('research_equipment_booking')
            ->where('id', $bookingId)
            ->where('status', 'reserved')
            ->update([
                'status' => 'no_show',
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Get equipment types with counts
     *
     * @param int $roomId
     * @return array
     */
    public function getEquipmentTypeCounts(int $roomId): array
    {
        return DB::table('research_equipment')
            ->where('reading_room_id', $roomId)
            ->where('is_available', 1)
            ->select('equipment_type', DB::raw('COUNT(*) as count'))
            ->groupBy('equipment_type')
            ->orderBy('equipment_type')
            ->get()
            ->toArray();
    }

    /**
     * Get equipment needing maintenance
     *
     * @param int $daysAhead Days to look ahead for scheduled maintenance
     * @return array
     */
    public function getEquipmentNeedingMaintenance(int $daysAhead = 30): array
    {
        $cutoffDate = date('Y-m-d', strtotime("+{$daysAhead} days"));

        return DB::table('research_equipment as e')
            ->join('research_reading_room as r', 'e.reading_room_id', '=', 'r.id')
            ->where(function ($q) use ($cutoffDate) {
                $q->where('e.condition_status', 'needs_repair')
                    ->orWhere('e.next_maintenance_date', '<=', $cutoffDate);
            })
            ->select('e.*', 'r.name as room_name')
            ->orderBy('e.next_maintenance_date')
            ->get()
            ->toArray();
    }

    /**
     * Log maintenance performed on equipment
     *
     * @param int    $equipmentId
     * @param string $description
     * @param string $newCondition
     * @param string $nextMaintenanceDate
     * @return bool
     */
    public function logMaintenance(
        int $equipmentId,
        string $description,
        string $newCondition = 'good',
        ?string $nextMaintenanceDate = null
    ): bool {
        return DB::table('research_equipment')
            ->where('id', $equipmentId)
            ->update([
                'condition_status' => $newCondition,
                'is_available' => $newCondition !== 'out_of_service' ? 1 : 0,
                'last_maintenance_date' => date('Y-m-d'),
                'next_maintenance_date' => $nextMaintenanceDate,
                'notes' => DB::raw("CONCAT(IFNULL(notes, ''), '\n[Maintenance " . date('Y-m-d') . ": " . addslashes($description) . "]')"),
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Get equipment usage statistics
     *
     * @param int    $roomId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getUsageStatistics(int $roomId, string $startDate, string $endDate): array
    {
        // Total bookings
        $totalBookings = DB::table('research_equipment_booking as eb')
            ->join('research_equipment as e', 'eb.equipment_id', '=', 'e.id')
            ->where('e.reading_room_id', $roomId)
            ->whereBetween('eb.booking_date', [$startDate, $endDate])
            ->count();

        // Bookings by status
        $byStatus = DB::table('research_equipment_booking as eb')
            ->join('research_equipment as e', 'eb.equipment_id', '=', 'e.id')
            ->where('e.reading_room_id', $roomId)
            ->whereBetween('eb.booking_date', [$startDate, $endDate])
            ->select('eb.status', DB::raw('COUNT(*) as count'))
            ->groupBy('eb.status')
            ->get()
            ->toArray();

        // Most used equipment
        $mostUsed = DB::table('research_equipment_booking as eb')
            ->join('research_equipment as e', 'eb.equipment_id', '=', 'e.id')
            ->where('e.reading_room_id', $roomId)
            ->whereBetween('eb.booking_date', [$startDate, $endDate])
            ->whereIn('eb.status', ['in_use', 'returned'])
            ->select('e.id', 'e.name', 'e.equipment_type', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('e.id', 'e.name', 'e.equipment_type')
            ->orderByDesc('usage_count')
            ->limit(10)
            ->get()
            ->toArray();

        // Usage by type
        $byType = DB::table('research_equipment_booking as eb')
            ->join('research_equipment as e', 'eb.equipment_id', '=', 'e.id')
            ->where('e.reading_room_id', $roomId)
            ->whereBetween('eb.booking_date', [$startDate, $endDate])
            ->whereIn('eb.status', ['in_use', 'returned'])
            ->select('e.equipment_type', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('e.equipment_type')
            ->orderByDesc('usage_count')
            ->get()
            ->toArray();

        return [
            'total_bookings' => $totalBookings,
            'by_status' => $byStatus,
            'most_used' => $mostUsed,
            'by_type' => $byType,
        ];
    }

    /**
     * Get today's equipment schedule for a room
     *
     * @param int    $roomId
     * @param string $date Optional, defaults to today
     * @return array
     */
    public function getDailySchedule(int $roomId, ?string $date = null): array
    {
        $date = $date ?? date('Y-m-d');

        return DB::table('research_equipment_booking as eb')
            ->join('research_equipment as e', 'eb.equipment_id', '=', 'e.id')
            ->join('research_researcher as r', 'eb.researcher_id', '=', 'r.id')
            ->where('e.reading_room_id', $roomId)
            ->where('eb.booking_date', $date)
            ->whereIn('eb.status', ['reserved', 'in_use'])
            ->select(
                'eb.*',
                'e.name as equipment_name',
                'e.equipment_type',
                'e.location',
                'r.first_name',
                'r.last_name',
                'r.email'
            )
            ->orderBy('eb.start_time')
            ->orderBy('e.name')
            ->get()
            ->toArray();
    }
}
