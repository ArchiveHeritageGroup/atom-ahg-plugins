<?php

/**
 * Configurable interface labels.
 *
 * AtoM's ISAD(G) element names describe a documentary record - "Scope and
 * content", "Extent and medium", "Archival history". For a sector that is not
 * archives those names are wrong in a way that matters: an archaeologist reads
 * "Extent and medium" and expects material and dimensions, and "Archival
 * history" where they mean site history.
 *
 * AtoM already stores 27 entity nouns as settings in the `ui_label` scope,
 * surfaced as `app_ui_label_<key>` and editable at Admin > Interface labels.
 * This extends the same mechanism to element labels, so relabelling stays a
 * configuration change rather than a template edit - no fork, nothing to redo
 * on upgrade, and an administrator can do it without a release.
 *
 * Unset labels fall through to the standard ISAD wording, so an install that
 * configures nothing behaves exactly as before.
 */

/**
 * Return the configured label for $key, or the translated $default.
 *
 * @param string $key     ui_label setting name, e.g. 'isad_scope_and_content'
 * @param string $default the ISAD wording to fall back to
 */
function ahg_label(string $key, string $default): string
{
    static $cache = [];

    if (!array_key_exists($key, $cache)) {
        $value = sfConfig::get('app_ui_label_' . $key);
        // Treat an empty setting as unset - a blank label is never intended,
        // and would otherwise render a heading with no text.
        $cache[$key] = (null !== $value && '' !== trim((string) $value))
            ? (string) $value
            : null;
    }

    return $cache[$key] ?? __($default);
}
