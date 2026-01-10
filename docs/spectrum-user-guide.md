# Spectrum 5.0 Collections Management

## A Guide for Museum Staff

---

## What is Spectrum?

Spectrum helps you keep track of everything that happens to objects in your collection. Think of it as a complete history for each item.

---

## The Dashboard

Find it at: **Admin → Spectrum Dashboard**

```
┌─────────────────────────────────────────────────────────────┐
│                   SPECTRUM DASHBOARD                         │
├───────────────┬───────────────┬───────────────┬─────────────┤
│ 📦 Loans Out  │ 📥 Loans In   │ ⚠️ Overdue    │ 🔍 Checks   │
│     12        │      5        │      3        │    Due: 8   │
├───────────────┴───────────────┴───────────────┴─────────────┤
│                                                              │
│  ALERTS                                                      │
│  • 3 loans are overdue - action needed                       │
│  • 5 condition checks due this week                          │
│  • 2 insurance renewals coming up                            │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

---

## When Objects Arrive

### The Process

```
         ┌──────────────────┐
         │  Object Arrives  │
         └────────┬─────────┘
                  │
                  ▼
         ┌──────────────────┐
         │  Create Entry    │
         │  Record          │
         └────────┬─────────┘
                  │
                  ▼
         ┌──────────────────┐
         │  Check Condition │
         │  & Take Photos   │
         └────────┬─────────┘
                  │
                  ▼
         ┌──────────────────┐
         │  Assign Storage  │
         │  Location        │
         └────────┬─────────┘
                  │
                  ▼
         ┌──────────────────┐
         │  Is it staying   │
         │  permanently?    │
         └────────┬─────────┘
                  │
        ┌─────────┴─────────┐
        │                   │
       YES                  NO
        │                   │
        ▼                   ▼
┌───────────────┐   ┌───────────────┐
│ Create        │   │ Note return   │
│ Acquisition   │   │ date          │
│ Record        │   │               │
└───────────────┘   └───────────────┘
```

### How to Record Entry

1. Find the object in AtoM (or create a new record)
2. Click **Extensions → Spectrum 5.0**
3. Click **New Entry**
4. Fill in the form:

| Question | Your Answer |
|----------|-------------|
| When did it arrive? | Enter the date |
| How did it arrive? | Deposit, loan, purchase, donation, or found |
| Who brought it? | Name and contact details |
| Staying permanently? | Yes = continue to Acquisition |
| If temporary, return date? | When it needs to go back |

---

## Lending Objects to Others

### The Loan Out Process

```
┌──────────────────┐
│ Someone wants    │
│ to borrow        │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐      NO       ┌──────────────┐
│ Can we lend it?  │──────────────▶│ Decline the  │
│ Check condition  │               │ request      │
└────────┬─────────┘               └──────────────┘
         │ YES
         ▼
┌──────────────────┐
│ Create loan      │
│ record           │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Generate loan    │
│ agreement        │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Print & get      │
│ signatures       │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Pack object      │
│ carefully        │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Record dispatch  │
│ details          │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Wait for return  │
│ Dashboard tracks │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Check condition  │
│ when returned    │
└──────────────────┘
```

### Creating a Loan

1. Go to the object
2. Click **Extensions → Spectrum → New Loan Out**
3. Enter the details:

| Field | Example |
|-------|---------|
| Who is borrowing? | National Art Gallery |
| Contact person | Jane Smith |
| Email | jane@gallery.org |
| Purpose | Exhibition: "Modern Art" |
| Start date | 1 March 2026 |
| End date | 30 June 2026 |
| Insurance value | R500,000 |

4. Click **Save**
5. Click **Generate Agreement** to create the paperwork

### When a Loan is Overdue

The dashboard shows overdue items in red. Click on any overdue loan to:
- Send a reminder to the borrower
- Extend the loan dates
- Record the return

---

## Checking Condition

### When to Check

```
┌─────────────────────────────────────────┐
│       ALWAYS CHECK CONDITION:           │
│                                         │
│   ✓  When object first arrives          │
│   ✓  Before lending to anyone           │
│   ✓  When a loan returns                │
│   ✓  After any accident or incident     │
│   ✓  Once a year (routine check)        │
│   ✓  Before photography or display      │
│                                         │
└─────────────────────────────────────────┘
```

### Condition Ratings

| Rating | What it Means |
|--------|---------------|
| ⭐⭐⭐⭐⭐ Excellent | Perfect, no problems at all |
| ⭐⭐⭐⭐ Good | Minor wear, normal for its age |
| ⭐⭐⭐ Fair | Some issues, keep an eye on it |
| ⭐⭐ Poor | Needs treatment, limit handling |
| ⭐ Unacceptable | Serious damage, do not touch |

### Recording a Check

1. Go to the object
2. Click **Extensions → Spectrum → New Condition Check**
3. Fill in:
   - Today's date
   - Your name
   - Overall rating
   - What you can see (describe any damage)
   - Does it need treatment?
   - When to check again?
4. Click **Add Photos** to document what you see

---

## Tracking Locations

### Why This Matters

When someone asks "where is the blue vase?" you need to answer quickly. Good location records save hours of searching.

### Location Format

```
┌─────────────────────────────────────────┐
│           OBJECT LOCATION               │
│                                         │
│   Building:   Main Museum               │
│   Floor:      Ground Floor              │
│   Room:       Storage Room A            │
│   Unit:       Cabinet 12                │
│   Position:   Shelf 3, Left Side        │
│                                         │
└─────────────────────────────────────────┘
```

### Recording Movement

Every time an object moves:

1. Go to the object
2. Click **Extensions → Spectrum → Record Movement**
3. Enter:
   - New location
   - Why it moved (exhibition, photography, storage)
   - Who moved it
   - Condition before and after

---

## Insurance & Valuations

### Recording Value

1. Go to the object
2. Click **Extensions → Spectrum → Add Valuation**
3. Enter:

| Field | Example |
|-------|---------|
| Type | Insurance |
| Value | R250,000 |
| Who valued it? | ABC Valuations |
| Date valued | 15 January 2026 |
| Renewal date | 15 January 2027 |

The dashboard alerts you when renewals are due.

---

## Quick Tips

**Start each day with the Dashboard**
- Check for overdue loans
- See what condition checks are due
- Note any expiring insurance

**Update locations immediately**
- Takes 30 seconds now
- Saves hours of searching later

**Always photograph condition**
- Pictures are worth a thousand words
- Essential for insurance claims

---

*For technical support, contact your system administrator.*
