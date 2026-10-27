-- ============================================================================
-- Migration: correct two Local Contexts label addresses (atom-ahg-plugins#227)
-- Date: 2026-10-27
-- Run once on existing installs; new installs get these from install.sql.
-- Changes only the address of the two seeded labels, and only where it still
-- holds the wrong value. Codes, names and label assignments are untouched.
--   TK-C (TK Community Voice): tk-community   -> tk-community-voice (no tk-community label exists)
--   BC-R (BC Research Use):    bc-research    -> bc-research-use
-- ============================================================================

UPDATE rights_tk_label SET uri = 'https://localcontexts.org/label/tk-community-voice/'
 WHERE code = 'TK-C' AND uri = 'https://localcontexts.org/label/tk-community/';

UPDATE rights_tk_label SET uri = 'https://localcontexts.org/label/bc-research-use/'
 WHERE code = 'BC-R' AND uri = 'https://localcontexts.org/label/bc-research/';
