#!/bin/sh
# link-plugins.sh
#
# Ensure every atom-ahg-plugins/<plugin> has a matching symlink in the
# instance's plugins/ directory. AtoM (Symfony 1.4) discovers plugins ONLY via
# plugins/<name>; the AHG plugins live in atom-ahg-plugins/, so each needs a
# symlink. Enabling a plugin in the atom_plugin table WITHOUT this symlink makes
# Symfony's enablePlugin() throw "The plugin \"X\" does not exist" and FATAL the
# whole site on every request (this took WDB down on 2026-06-08).
#
# Idempotent + safe to run on every deploy (after `git pull`). Creating a
# symlink does NOT enable/load the plugin (loading is driven by
# atom_plugin.is_enabled) - it only makes the plugin discoverable so it CAN be
# enabled without crashing.
#
# Usage:  sudo bin/link-plugins.sh            (sudo so it can chown the links)
# Env:    PLUGIN_LINK_OWNER=www-data:www-data (override the symlink owner)

set -eu

SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
AHG_DIR=$(cd "$SCRIPT_DIR/.." && pwd)        # .../atom-ahg-plugins
ROOT_DIR=$(cd "$AHG_DIR/.." && pwd)          # instance root (parent)
PLUGINS_DIR="$ROOT_DIR/plugins"
OWNER="${PLUGIN_LINK_OWNER:-www-data:www-data}"

if [ ! -d "$PLUGINS_DIR" ]; then
    echo "ERROR: plugins/ dir not found at $PLUGINS_DIR" >&2
    exit 1
fi

created=0
existing=0
skipped=0

for dir in "$AHG_DIR"/*/; do
    name=$(basename "$dir")
    # Only real Symfony plugins (those with a *Configuration.class.php).
    if ! ls "$dir"config/*Configuration.class.php >/dev/null 2>&1; then
        continue
    fi
    link="$PLUGINS_DIR/$name"
    target="$AHG_DIR/$name"

    if [ -L "$link" ]; then
        existing=$((existing + 1))
        continue
    fi
    if [ -e "$link" ]; then
        echo "SKIP (real path, not a symlink): $link"
        skipped=$((skipped + 1))
        continue
    fi

    ln -s "$target" "$link"
    chown -h "$OWNER" "$link" 2>/dev/null || true
    echo "linked: plugins/$name -> $target"
    created=$((created + 1))
done

echo "Done: $created created, $existing already linked, $skipped skipped."

# Report dangling symlinks (plugin removed from atom-ahg-plugins) - do NOT auto-remove.
for link in "$PLUGINS_DIR"/*; do
    [ -L "$link" ] || continue
    case "$(readlink "$link")" in
        *atom-ahg-plugins/*)
            [ -e "$link" ] || echo "WARN dangling symlink (target gone): $link -> $(readlink "$link")"
            ;;
    esac
done
