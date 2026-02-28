
## Rejection with Audit

When an admin rejects a researcher registration:

1. **Data moved to audit table** - All researcher data copied to `research_researcher_audit`
2. **Main record deleted** - Removed from `research_researcher` table
3. **User deactivated** - `user.active` set to 0
4. **Access request updated** - Set to `denied` with reason

This allows:
- The email/user to register again
- Full audit trail of rejected applications
- Clean main table with only active/pending researchers

### Database Tables

#### research_researcher_audit
- Stores rejected/archived researcher records
- Includes `original_id`, `archived_by`, `archived_at`
- Keeps `rejection_reason` for reference
