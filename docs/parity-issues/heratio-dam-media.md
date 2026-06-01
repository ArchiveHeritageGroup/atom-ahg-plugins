Heratio-side parity gaps from the 2026-05-31 two-way audit (PSIS/AtoM has these; Heratio does not).

**Domain:** dam-media

### Features to add to Heratio (present in PSIS/AtoM)
- **[high]** Rights-based derivative rules (redaction, resize, format conversion) — _PSIS plugin: ahgDAMPlugin_: AtoM rights_derivative_rule/rights_derivative_log tables enforce per-role access with conditional watermark/redaction/resize; Heratio DamService lacks equivalent

Mirror the PSIS/AtoM implementation under `/usr/share/nginx/archive/atom-ahg-plugins/`.