# Research mode (Beginning/Intermediate/Advanced) + guide — cloned from Heratio — 2026-06-15

**Source:** Heratio `packages/ahg-research` (experience_level + levels_guide). **Target:** PSIS `ahgResearchPlugin`. Clone-from-Heratio per standing rule. Benefits RARI too (RARI will run this stack — see [[wits_rari_engagement]]).

## What the feature is
A per-researcher **research mode** (beginning / intermediate / advanced) stored on `research_researcher.experience_level`. A sidebar `<select>` lets the researcher switch mode; the choice **curates the research sidebar** — Beginning shows core essentials, Intermediate adds the working tools, Advanced reveals everything (`$atLeast(rank)` gating, ranks beginning=1/intermediate=2/advanced=3). A side-by-side **guide** (3 step-cards, current mode highlighted) explains the modes.

## Delivered (faithful Symfony port of the Laravel feature)
- `database/experience_level.sql` — `ALTER research_researcher ADD experience_level VARCHAR(20) NOT NULL DEFAULT 'intermediate' AFTER status` (additive, MySQL-8 INSTANT). **Johan runs (DB-protection).**
- `researchActions::executeSaveExperienceLevel` + route `research_save_experience_level` → `POST /research/experience-level`. Auth-gated, validates beginning/intermediate/advanced, `service->updateResearcher(id, ['experience_level'=>...])`, returns JSON `{ok,level}`. (Mirrors Heratio saveExperienceLevel.)
- `_researchSidebar.php` — added: experience_level self-lookup (try/catch → defaults intermediate; column-tolerant so it never breaks pre-ALTER), `$atLeast()` helper, the "Research mode" selector block (with `?` guide link → projects#research-modes), `$atLeast(2)/(3)` gating on items (mapped to Heratio: ≥2 = Team Workspaces, Research Journal, DMP, My Reports, Annotation Studio, Source Assessments, Document Templates, Reproduction Requests; ≥3 = Journal Builder, Where to Publish, Lecture Builder, Validation Queue, Entity Resolution, ODRL Policies), and the CSP-nonced JS (POST on change → reload to re-curate server-side).
- `_levelsGuide.php` — port of `levels_guide.blade.php` (3 mode step-cards, self-looks-up current mode, `id="research-modes"` anchor). Included at top of `projectsSuccess.php`.

## Verified
- All `php -l` clean.
- `updateResearcher` confirmed generic `->update($data)` (no column whitelist) → experience_level persists.
- Column-tolerant pre-ALTER: sidebar self-lookup try/catch → everyone defaults to intermediate; only saving needs the column.

## Activation (Johan)
```bash
cd /usr/share/nginx/archive
PW=$(grep -oP "'password'\s*=>\s*'\K[^']+" config/config.php | head -1)
MYSQL_PWD="$PW" mysql --no-defaults -u root archive < atom-ahg-plugins/ahgResearchPlugin/database/experience_level.sql
sudo rm -rf cache/qubit/prod/* && sudo systemctl restart php8.3-fpm
# verify: /research/projects shows the guide + sidebar "Research mode" selector; switch mode → sidebar items change.
cd atom-ahg-plugins
./bin/release patch "ahgResearchPlugin: research mode (Beginning/Intermediate/Advanced) + guide — cloned from Heratio"
```
