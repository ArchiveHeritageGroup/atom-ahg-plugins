# 2026-06-10 — Researcher Copilot (issue #149 strand 2)

## Summary
Built a **Researcher Copilot** into `ahgAIPlugin` — the second implementable strand of tracking issue **#149**, after the AI Cataloguer. It turns the ephemeral #121 collection chatbot (client-side history only) into a researcher's **saved, resumable workspace**. Live + service-verified on PSIS; not yet released.

## What it does
A research page at `/ai/research` with a sessions sidebar and a chat area. Each conversation is persisted per user, so a researcher can leave and resume their line of enquiry, and export a session as a Markdown transcript (with cited records). The grounded Q&A is delegated to the existing `CollectionChatbotService` (RAG over published descriptions); the copilot adds persistence, history, ownership, and export.

## Components (all in ahgAIPlugin)
- `lib/Services/ResearchCopilotService.php` — sessions + messages CRUD, owner-checked; `ask()` loads recent history, runs the assistant, persists both turns, auto-titles from the first question; `exportMarkdown()`.
- Tables `ahg_research_session` + `ahg_research_message` (`install.sql` + `migrate_research_copilot.sql`).
- Routes `/ai/research`, `/ai/research/ask`, `/ai/research/sessions`, `/ai/research/session/:id` (load / `?op=export` / POST `?op=delete|rename`); single-action classes.
- `modules/ai/templates/researchSuccess.php` + `web/js/research-copilot.js` (reuses the #121 chat rendering + session management).
- CLI task `ai:install-research-menu` adds a "Researcher Copilot" nav link under Manage (nested-set, idempotent) — run on PSIS.

## Verification
Service-level (debug=false CLI): create → ask (mode=ai, real 860-char answer + 6 cited sources, session auto-titled) → messages persisted (user/assistant) → list → export Markdown → delete (cascades). **Ownership guard confirmed** — a different user sees zero messages. Routes resolve; site healthy.

Caveat: the **authenticated UI render** was not visually confirmed (no test password; the `ai` module is `is_secure: true`). Template + JS lint clean and the JS reuses the proven #121 pattern — recommend a click-through before release. v1 is for registered researchers (login-gated); public access would need a `security.yml` change.

## Status
Built + service-verified on PSIS. Pending: authenticated UI click-through, release (`./bin/release minor`), per-instance DDL + `ai:install-research-menu`.
