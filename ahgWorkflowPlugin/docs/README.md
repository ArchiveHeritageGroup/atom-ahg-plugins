# ahgWorkflowPlugin

Configurable approval workflow system for AtoM archival submissions.

## Features

- **Configurable Workflows**: Define multi-step approval processes
- **Per-Repository/Collection Scope**: Apply workflows to specific contexts
- **Role-Based Access**: Integrate with AtoM roles and security clearance levels
- **Task Pool**: Multiple users can claim tasks from a shared pool
- **Email Notifications**: Automatic notifications on task events
- **Complete Audit Trail**: Full history of all workflow actions
- **CLI Commands**: Cron support for processing and status reporting

## Installation

1. Enable the plugin:
   ```bash
   php bin/atom extension:enable ahgWorkflowPlugin
   ```

2. Run database migration:
   ```bash
   mysql -u root archive < plugins/ahgWorkflowPlugin/database/install.sql
   ```

3. Clear cache:
   ```bash
   php symfony cc
   ```

## Usage

### Web Interface

Access the workflow dashboard at: `/workflow`

- **Dashboard**: View statistics and recent activity
- **My Tasks**: See tasks assigned to you
- **Task Pool**: Claim available tasks
- **Admin**: Configure workflows (administrators only)

### CLI Commands

**Process pending operations (for cron):**
```bash
# Run all processing
php symfony workflow:process

# Send notifications only
php symfony workflow:process --notifications

# Escalate overdue tasks
php symfony workflow:process --escalate

# Archive old completed tasks
php symfony workflow:process --cleanup --days=90
```

**View status:**
```bash
# Show summary
php symfony workflow:status

# Show pending tasks
php symfony workflow:status --pending

# Show overdue tasks
php symfony workflow:status --overdue

# Filter by user
php symfony workflow:status --user=5
```

### Cron Setup

Add to crontab for automated processing:
```bash
# Process workflows every 15 minutes
*/15 * * * * cd /usr/share/nginx/archive && php symfony workflow:process >> /var/log/atom-workflow.log 2>&1

# Weekly cleanup of old tasks
0 2 * * 0 cd /usr/share/nginx/archive && php symfony workflow:process --cleanup --days=90 >> /var/log/atom-workflow.log 2>&1
```

## Database Tables

- `ahg_workflow`: Workflow definitions
- `ahg_workflow_step`: Steps within workflows
- `ahg_workflow_task`: Active tasks
- `ahg_workflow_history`: Audit trail
- `ahg_workflow_notification`: Email queue

## Integration

### Security Clearance

The plugin integrates with `ahgSecurityClearancePlugin` to:
- Require specific clearance levels for workflow steps
- Filter task pool by user clearance
- Enforce access control on sensitive items

### Starting a Workflow

Programmatically start a workflow:
```php
require_once sfConfig::get('sf_root_dir') . '/plugins/ahgWorkflowPlugin/lib/Services/WorkflowService.php';
$service = new WorkflowService();
$taskId = $service->startWorkflow($objectId, $userId);
```

Or via URL: `/workflow/start/{object_id}`

## License

GPL-3.0 - The Archive and Heritage Group
