# AtoM AHG Plugins

Custom plugins for Access to Memory (AtoM) 2.10 developed by The Archive and Heritage Group.

## Included Plugins

| Plugin | Description |
|--------|-------------|
| **arAHGThemeB5Plugin** | Bootstrap 5 theme with custom styling and enhanced UI |
| **arDisplayPlugin** | Configurable display profiles, levels of description, layout modes |
| **arSecurityClearancePlugin** | Security classification system with clearance levels |
| **arResearchPlugin** | Researcher registration, reading room bookings, collections |
| **arAccessRequestPlugin** | Access request workflow with approvers |

## Installation

These plugins are installed automatically by the atom-framework installer:
```bash
cd /usr/share/nginx/atom

# Clone both repos
git clone https://github.com/ArchiveHeritageGroup/atom-framework.git
git clone https://github.com/ArchiveHeritageGroup/atom-ahg-plugins.git

# Install
cd atom-framework
composer install
bash bin/install

sudo systemctl restart php8.3-fpm
```

## Plugin Details

### arAHGThemeB5Plugin
- Custom Bootstrap 5 theme
- Enhanced navigation menus
- AHG Settings dashboard
- Custom color scheme

### arDisplayPlugin
- Display Mode Switching (list, card, gallery, hierarchy)
- Extended Levels of Description
- Per-user display preferences
- Configurable display profiles

### arSecurityClearancePlugin
- 5-level security classification (Unclassified → Top Secret)
- User clearance management
- Object classification
- Access logging and audit trails
- Watermark support

### arResearchPlugin
- Researcher registration and approval
- Reading room management
- Booking system
- Research collections
- Citation logging

### arAccessRequestPlugin
- Access request workflow
- Approver management
- Object access grants
- Request tracking

## Theme Switching

After installation, switch themes via **Admin → Themes**:
- arDominionB5Plugin (AtoM default)
- arAHGThemeB5Plugin (custom)

## Configuration

### Enable DAM Tools (optional)
```sql
INSERT INTO ahg_settings (setting_key, setting_value, setting_group) 
VALUES ('dam_tools_enabled', '1', 'general');
```

## Version

v1.0.0 - Base release

## License

GPL-3.0

## Author

The Archive and Heritage Group
