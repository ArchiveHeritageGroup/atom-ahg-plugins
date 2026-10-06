# ahgChatbotPlugin - Ask the Archive

A public assistant that answers questions from the published catalogue and links every answer to the records it came from.

The plugin is self-contained. It touches no base AtoM file and does not depend on the AHG theme or on ahgAIPlugin. It needs only atom-framework (the AI gateway client, `SearchAccessFilterService` and `AhgSettingsService`) and ahgCorePlugin, whose fetch wrapper adds the CSRF header.

## What it does

- A launcher button on every HTML page, injected by the plugin itself. It opens a chat panel. On a description page, a question like "who made this?" is taken to mean that record.
- Retrieval comes from two paths, merged:
  - semantic search: Qdrant, with embeddings through the AHG AI gateway;
  - MySQL full-text on title and scope, with a prefix retry so plurals still match.
- **Visibility is decided on every hit when it is used, never trusted from an index.** A record must be published and not restricted (security classification, donor agreement, full embargo). If the check cannot run, nothing is returned.
- Generation goes through the AHG AI gateway only. No provider setting can send it elsewhere.
- Limits are checked before any model call:
  - questions longer than 1000 characters are refused;
  - each IP may ask 10 a minute and 100 a day (APCu);
  - the installation has a daily cap (`chatbot_daily_cap`).
- Privacy (POPIA):
  - e-mail addresses, phone numbers and SA ID numbers are masked before a question is logged;
  - no IP address is stored;
  - logs are deleted after `chatbot_retention_days`.
- Each answer has a "Was this helpful?" rating. The admin page (`/chatbot/admin`) shows usage and lists the questions that found no records or were rated unhelpful, which shows what visitors look for and cannot find.
- Other plugins can open the panel with `window.AhgChatbot.open('question')`, for example a voice "ask" command.

## Install

```bash
cd /usr/share/nginx/archive
ln -s ../atom-ahg-plugins/ahgChatbotPlugin plugins/ahgChatbotPlugin
mysql archive < atom-ahg-plugins/ahgChatbotPlugin/database/install.sql
php bin/atom extension:enable ahgChatbotPlugin
rm -rf cache/* && php symfony cc
```

Then build the index. If ahgAIPlugin has already built `{db}_io_nomic`, that index is used as it is.

```bash
php symfony chatbot:index --prune
```

## Cron

```cron
# nightly: index new or changed descriptions, drop unpublished ones
30 2 * * * www-data cd /usr/share/nginx/archive && php symfony chatbot:index --prune
# daily: delete conversations past the retention period
45 2 * * * www-data cd /usr/share/nginx/archive && php symfony chatbot:purge-logs
```

## Settings (`ahg_settings`, group `chatbot`)

| Key | Default | Meaning |
| --- | --- | --- |
| chatbot_enabled | 1 | Master switch |
| chatbot_public | 1 | 0 = logged-in users only |
| chatbot_daily_cap | 1000 | Questions per day for the whole installation |
| chatbot_retention_days | 30 | Days a conversation is kept |
| chatbot_model | (gateway default) | Chat model on the gateway, e.g. qwen3:8b |
| chatbot_button_label | Ask the archive | Launcher text |
| chatbot_notice | (AI notice) | Shown under the conversation |
| chatbot_qdrant_url | http://localhost:6333 | Vector store |
| chatbot_vector_collection | {db}_io_nomic | Qdrant collection |

The gateway API key comes from the framework's gateway settings (`AiGatewayClient::fromSettings()`).

## Check

```bash
php atom-ahg-plugins/ahgChatbotPlugin/testing/visibility-check.php
```

This is read only. It confirms that drafts, restricted records and missing ids never pass the filter, that every hit is published, and that masking and the length limit work. Run it before every release of this plugin.
