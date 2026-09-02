# AHG Marketplace Plugin

Online art/gallery marketplace with multi-GLAM sector support. Fixed pricing, make-an-offer negotiations, and timed auctions. Multi-currency, seller verification, commission tracking, and payout management.

| | |
|---|---|
| Machine name | `ahgMarketplacePlugin` |
| Version | 1.0.1 |
| Category | ahg |
| Licence | AGPL-3.0-or-later |
| Author | The Archive and Heritage Group |

## Features

- fixed_price
- auctions
- offers
- multi_currency
- seller_verification
- commission_tracking
- payout_management
- sectors

## Requirements

| Component | Version |
|---|---|
| atom framework | `>=2.0.0` |
| atom | `>=2.8` |
| php | `>=8.1` |

## Depends on

- `ahgCorePlugin`

## Optional integrations

These are used when present and are not required:

- `ahgCartPlugin`
- `ahgGalleryPlugin`
- `ahgExhibitionPlugin`
- `ahgFavoritesPlugin`
- `ahgLoanPlugin`
- `ahgHeritagePlugin`
- `ahgLandingPagePlugin`
- `ahgDisplayPlugin`

## Database tables

Creates 16 table(s):

- `marketplace_settings`
- `marketplace_currency`
- `marketplace_category`
- `marketplace_seller`
- `marketplace_listing`
- `marketplace_listing_image`
- `marketplace_auction`
- `marketplace_bid`
- `marketplace_offer`
- `marketplace_transaction`
- `marketplace_payout`
- `marketplace_review`
- `marketplace_enquiry`
- `marketplace_follow`
- `marketplace_collection`
- `marketplace_collection_item`

## Installation

This plugin requires **atom-framework**. It is not optional: the framework
provides `AhgController`, `AtomFramework\*` and the routing and settings
services that this plugin builds on.

```bash
# 1. Fetch into the AtoM plugins directory as a REAL DIRECTORY.
#    A symlink fails the prefix test in pluginsAction.class.php and the
#    plugin is then invisible in the stock admin UI with no error shown.
cd <atom-root>/plugins
git clone --depth 1 --filter=blob:none --sparse \
    https://github.com/ArchiveHeritageGroup/atom-ahg-plugins.git tmp-fetch
cd tmp-fetch && git sparse-checkout set ahgMarketplacePlugin && cd ..
mv tmp-fetch/ahgMarketplacePlugin ./ahgMarketplacePlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgMarketplacePlugin/database/install.sql

# 3. Enable it, then clear the cache and reload PHP-FPM.
cd <atom-root>
php symfony cc
sudo systemctl reload php8.3-fpm
```

**Enabling differs by instance shape.** Check which list governs:

```bash
grep -c 'loadPluginsFromDatabase' <atom-root>/config/ProjectConfiguration.class.php
```

- `0` (stock AtoM): plugins load from the serialised `plugins` row in
  `setting_i18n`. The `atom_plugin` table is inert and the admin screen can
  show a plugin as enabled that does not load.
- `1` or more (AHG): `atom_plugin` is the source of truth.

Verify against whichever list governs, not against the admin screen.

## Licence

AGPL-3.0-or-later. Copyright The Archive and Heritage Digital Commons Group (Pty) Ltd.
