# ahgChatbotPlugin - Ask the Archive

A public assistant that answers questions from the published catalogue and links every answer to the records it came from.

The plugin is self-contained. It touches no base AtoM file and does not depend on the AHG theme or on ahgAIPlugin. It needs atom-framework v2.18.47 or later (the AI gateway client, `SearchAccessFilterService` and `AhgSettingsService`) and ahgCorePlugin, whose fetch wrapper adds the CSRF header.

## What it does

- A launcher button on every HTML page, injected by the plugin itself. It opens a chat panel. On a description page, a question like "who made this?" is taken to mean that record.
- Retrieval comes from three paths, merged:
  - semantic search over descriptions: Qdrant, with embeddings through the AI gateway;
  - semantic search over **digital object text** - the text AtoM extracts from PDFs and OCR'd scans (the "transcript" property), split into overlapping passages of about 1,000 characters in a second collection, `{collection}_text`. Matching passages are given to the model with their record, only after the record has passed the visibility check;
  - MySQL full-text on title and scope, with a prefix retry so plurals still match. (Transcripts have no MySQL full-text index, so without Qdrant they are not searched.)
- **Visibility is decided on every hit when it is used, never trusted from an index.** A record must be published and not restricted (security classification, donor agreement, full embargo, an active ICIP access restriction or access-blocking cultural notice inherited from ancestors, or an ODRL "use" prohibition). If the check cannot run, nothing is returned.
- Generation goes through the AHG AI gateway only. No provider setting can send it elsewhere.
- Limits are checked before any model call:
  - questions longer than 1000 characters are refused;
  - each IP may ask 10 a minute and 100 a day (APCu);
  - the installation has a daily cap (`chatbot_daily_cap`).
- Privacy (POPIA):
  - e-mail addresses, phone numbers and SA ID numbers are masked before a question is logged;
  - no IP address is stored;
  - logs are deleted after `chatbot_retention_days`.
- Questions about visiting, opening times, contacts and services are answered from the institution's own public information: the repository records (opening times, access, services, and the repository's contacts, primary first) and the static pages in `chatbot_info_pages`. Contact details of donors, rights holders, authority records and users are never read.
- A second tab, **Help using the site**, answers how-to questions from the help articles of ahgHelpPlugin. It reads them only, and the tab appears only when those articles are installed. Both audiences get an allow-list of categories, so internal material (Technical, Plugin Reference, Reference) never reaches the assistant:
  - visitors and researchers: `chatbot_help_categories_public`;
  - editors, contributors and administrators: `chatbot_help_categories_staff`.
- Each answer has a "Was this helpful?" rating. The admin page (`/chatbot/admin`) shows usage and lists the questions that found no records or were rated unhelpful, which shows what visitors look for and cannot find.
- Other plugins can open the panel with `window.AhgChatbot.open('question', 'collection' | 'help')`, for example a voice "ask" command.

## Install

```bash
cd /usr/share/nginx/archive/atom-framework
php bin/atom extension:install ahgChatbotPlugin   # links it, loads database/install.sql, enables it
cd .. && php symfony cc
```

Then build the index: descriptions, then digital object text (`--skip-text` to leave the text out). If ahgAIPlugin has already built `{db}_io_nomic`, that index is used as it is; the text collection is created on first run.

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
| chatbot_info_pages | contact,about,accessibility | Static pages used for visiting and contact questions |
| chatbot_help_categories_public | Public Access,Browse & Search,Research,Viewers & Media | Help categories for visitors and researchers |
| chatbot_help_categories_staff | (public list plus User Guide, User Manual, Admin & Settings, Collection Mgmt, Import/Export, Rights, Compliance, Exhibitions, GLAM Sectors, Labels & Forms, AI & Automation) | Help categories for editors, contributors, administrators |
| chatbot_text_collection | {collection}_text | Qdrant collection for digital object text passages |
| chatbot_qdrant_url | http://localhost:6333 | Vector store |
| chatbot_vector_collection | {db}_io_nomic | Qdrant collection |

The gateway API key comes from the framework's gateway settings (`AiGatewayClient::fromSettings()`).

## Check

```bash
php atom-ahg-plugins/ahgChatbotPlugin/testing/visibility-check.php
```

This is read only. It confirms that drafts, restricted records and missing ids never pass the filter, that every hit is published, and that masking and the length limit work. Run it before every release of this plugin.
