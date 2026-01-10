# ahgResearchPlugin - Technical Documentation

**Version:** 1.0.0  
**Category:** Public Services  
**Dependencies:** atom-framework, ahgSecurityClearancePlugin

---

## Overview

Researcher registration, reading room booking, workspace management, and citation generation for archival research services.

---

## Database Schema

### ERD Diagram

```
┌─────────────────────────────────────────┐
│         research_researcher             │
├─────────────────────────────────────────┤
│ PK id INT                              │
│ FK user_id INT                         │──────┐
│                                         │      │
│ -- PERSONAL --                          │      │
│    first_name VARCHAR                   │      │
│    last_name VARCHAR                    │      │
│    email VARCHAR                        │      │
│    phone VARCHAR                        │      │
│    institution VARCHAR                  │      │
│    position VARCHAR                     │      │
│    department VARCHAR                   │      │
│                                         │      │
│ -- IDENTIFICATION --                    │      │
│    id_type ENUM                         │      │
│    id_number VARCHAR                    │      │
│    id_verified TINYINT                  │      │
│    id_verified_by INT                   │      │
│    id_verified_at TIMESTAMP             │      │
│                                         │      │
│ -- RESEARCH --                          │      │
│    research_topic TEXT                  │      │
│    research_purpose ENUM                │      │
│    research_period VARCHAR              │      │
│    supervisor_name VARCHAR              │      │
│    supervisor_email VARCHAR             │      │
│    reference_letter VARCHAR             │      │
│                                         │      │
│ -- STATUS --                            │      │
│    status ENUM                          │      │
│    approved_by INT                      │      │
│    approved_at TIMESTAMP                │      │
│    card_number VARCHAR                  │      │
│    card_issued_at TIMESTAMP             │      │
│    card_expires_at TIMESTAMP            │      │
│                                         │      │
│ -- AGREEMENTS --                        │      │
│    terms_accepted TINYINT               │      │
│    terms_accepted_at TIMESTAMP          │      │
│    photo_consent TINYINT                │      │
│    publication_consent TINYINT          │      │
│                                         │      │
│    notes TEXT                           │      │
│    created_at TIMESTAMP                 │      │
│    updated_at TIMESTAMP                 │      │
└─────────────────────────────────────────┘      │
              │                                   │
              │ 1:N                               │
              ▼                                   │
┌─────────────────────────────────────────┐      │
│          research_booking               │      │
├─────────────────────────────────────────┤      │
│ PK id INT                              │      │
│ FK researcher_id INT                   │──────┤
│ FK reading_room_id INT                 │      │
│                                         │      │
│    booking_date DATE                    │      │
│    start_time TIME                      │      │
│    end_time TIME                        │      │
│    purpose TEXT                         │      │
│    materials_requested JSON             │      │
│    status ENUM                          │      │
│    checked_in_at TIMESTAMP              │      │
│    checked_out_at TIMESTAMP             │      │
│    desk_number VARCHAR                  │      │
│    notes TEXT                           │      │
│    cancelled_at TIMESTAMP               │      │
│    cancellation_reason TEXT             │      │
│    created_at TIMESTAMP                 │      │
│    updated_at TIMESTAMP                 │      │
└─────────────────────────────────────────┘      │
              │                                   │
              │ N:1                               │
              ▼                                   │
┌─────────────────────────────────────────┐      │
│         research_reading_room           │      │
├─────────────────────────────────────────┤      │
│ PK id INT                              │      │
│    name VARCHAR                         │      │
│    description TEXT                     │      │
│    capacity INT                         │      │
│    location VARCHAR                     │      │
│    operating_hours JSON                 │      │
│    rules TEXT                           │      │
│    amenities JSON                       │      │
│    is_active TINYINT                    │      │
│    created_at TIMESTAMP                 │      │
└─────────────────────────────────────────┘      │
                                                  │
┌─────────────────────────────────────────┐      │
│        research_workspace               │      │
├─────────────────────────────────────────┤      │
│ PK id INT                              │      │
│ FK researcher_id INT                   │──────┤
│    name VARCHAR                         │      │
│    description TEXT                     │      │
│    is_default TINYINT                   │      │
│    saved_searches JSON                  │      │
│    saved_records JSON                   │      │
│    notes TEXT                           │      │
│    created_at TIMESTAMP                 │      │
│    updated_at TIMESTAMP                 │      │
└─────────────────────────────────────────┘      │
                                                  │
┌─────────────────────────────────────────┐      │
│        research_citation_log            │      │
├─────────────────────────────────────────┤      │
│ PK id INT                              │      │
│ FK researcher_id INT                   │──────┘
│ FK object_id INT                       │
│    citation_style VARCHAR               │
│    citation_text TEXT                   │
│    created_at TIMESTAMP                 │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│        research_annotation              │
├─────────────────────────────────────────┤
│ PK id INT                              │
│ FK researcher_id INT                   │
│ FK object_id INT                       │
│    annotation_type ENUM                 │
│    title VARCHAR                        │
│    content TEXT                         │
│    target_selector TEXT                 │
│    tags VARCHAR                         │
│    is_private TINYINT                   │
│    created_at TIMESTAMP                 │
│    updated_at TIMESTAMP                 │
└─────────────────────────────────────────┘
```

---

## Research Purpose Types

| Purpose | Description |
|---------|-------------|
| academic | University/college research |
| professional | Work-related research |
| personal | Genealogy, personal interest |
| journalistic | Media/press research |
| legal | Legal proceedings |
| government | Official government use |

---

## Booking Statuses

| Status | Description |
|--------|-------------|
| pending | Awaiting approval |
| approved | Confirmed booking |
| checked_in | Researcher arrived |
| completed | Visit finished |
| cancelled | Booking cancelled |
| no_show | Researcher didn't arrive |

---

## Service Methods

### ResearcherService

```php
namespace ahgResearchPlugin\Service;

class ResearcherService
{
    // Registration
    public function register(array $data): int
    public function updateProfile(int $id, array $data): bool
    public function getResearcher(int $id): ?array
    public function getResearcherByUser(int $userId): ?array
    public function verifyIdentity(int $id, int $verifiedBy): bool
    public function approveResearcher(int $id, int $approvedBy): bool
    public function issueCard(int $id, string $cardNumber, DateTime $expires): bool
    
    // Bookings
    public function createBooking(int $researcherId, array $data): int
    public function updateBooking(int $id, array $data): bool
    public function cancelBooking(int $id, string $reason): bool
    public function checkIn(int $bookingId, string $deskNumber): bool
    public function checkOut(int $bookingId): bool
    public function getBookings(int $researcherId): Collection
    public function getBookingsForDate(DateTime $date, int $roomId): Collection
    public function getAvailableSlots(DateTime $date, int $roomId): array
    
    // Workspace
    public function createWorkspace(int $researcherId, array $data): int
    public function addToWorkspace(int $workspaceId, int $objectId): bool
    public function removeFromWorkspace(int $workspaceId, int $objectId): bool
    public function getWorkspaces(int $researcherId): Collection
    
    // Citations
    public function generateCitation(int $objectId, string $style): string
    public function logCitation(int $researcherId, int $objectId, string $style, string $text): int
    public function getCitationHistory(int $researcherId): Collection
    
    // Annotations
    public function createAnnotation(int $researcherId, int $objectId, array $data): int
    public function getAnnotations(int $researcherId, ?int $objectId = null): Collection
}
```

---

## Citation Styles Supported

| Style | Format |
|-------|--------|
| APA | American Psychological Association |
| MLA | Modern Language Association |
| Chicago | Chicago Manual of Style |
| Harvard | Harvard Referencing |
| Turabian | Turabian Style |
| MHRA | Modern Humanities Research Association |

---

*Part of the AtoM AHG Framework*
