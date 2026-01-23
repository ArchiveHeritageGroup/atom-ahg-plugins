# ahgTranslationPlugin

Standalone AtoM Symfony 1 plugin for on-prem translation via a local MT HTTP endpoint.

## What you get
- `/translation/settings`  (configure endpoint + timeout)
- `/translation/health`    (connectivity check)
- `POST /translation/translate/:id` (translate a specific descriptive field, create draft, optionally apply)
- `POST /translation/apply/:draftId` (apply an existing draft)

A modal UI partial `_translateModal.php` is included that matches your requested screen style.

## Database setup
Run:
`data/install.sql` against your AtoM MySQL database.

Example:
```bash
mysql -u <user> -p <atom_db> < plugins/ahgTranslationPlugin/data/install.sql
```

## Install in AtoM instance
Copy plugin into:
- `/usr/share/nginx/atom/plugins/ahgTranslationPlugin`
- and/or `/usr/share/nginx/atom_psis/plugins/ahgTranslationPlugin`

Fix ownership:
```bash
chown -R www-data:www-data /usr/share/nginx/atom/plugins/ahgTranslationPlugin
```

Clear cache:
```bash
php /usr/share/nginx/atom/symfony cc
```

## Render the modal (temporary hook)
In any template/partial you control, include:
```php
<?php include_partial('ahgTranslation/translateModal', array('objectId' => $resource->id)); ?>
```

## MT endpoint contract
Plugin expects the endpoint to accept:
`{ source, target, text }` JSON and return JSON containing either:
- `{ "translatedText": "..." }` or `{ "translation": "..." }`

Default endpoint: `http://127.0.0.1:5100/translate`

## Notes
- No theme dependencies.
- No atom-framework service requires.
- Uses AtoM's Propel connection for DB access.
