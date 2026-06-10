<?php

/**
 * ImageArService (#147) — resolve the best display image for a digital object.
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * Licensed under the GNU Affero General Public License v3.0 or later.
 */

use Illuminate\Database\Capsule\Manager as DB;

class ImageArService
{
    /** Resolve an information object by slug → reference/master image + title. */
    public function resolveBySlug(string $slug): ?array
    {
        $ioId = DB::table('slug')->where('slug', $slug)->value('object_id');
        return $ioId ? $this->resolveById((int) $ioId) : null;
    }

    public function resolveById(int $ioId): ?array
    {
        // Master digital object is linked directly to the information object.
        $master = DB::table('digital_object')->where('object_id', $ioId)->first();
        if (!$master) {
            return null;
        }

        // Prefer the reference derivative (web-sized); fall back to master.
        $refUsage = defined('\\QubitTerm::REFERENCE_ID') ? \QubitTerm::REFERENCE_ID : 142;
        $ref = DB::table('digital_object')
            ->where('parent_id', $master->id)
            ->where('usage_id', $refUsage)
            ->first();
        $pick = $ref ?: $master;

        if (empty($pick->name) || empty($pick->path)) {
            return null;
        }
        // Only still images make sense in 2D-image AR.
        if (!empty($pick->media_type_id) && defined('\\QubitTerm::IMAGE_ID')
            && (int) $master->media_type_id !== (int) \QubitTerm::IMAGE_ID) {
            // best-effort: still allow, but flag
        }

        $title = DB::table('information_object_i18n')
            ->where('id', $ioId)->value('title');

        return [
            'io_id' => $ioId,
            'title' => $title ?: ('Object #'.$ioId),
            'image_url' => '/'.ltrim($pick->path, '/').$pick->name,
            'mime' => $pick->mime_type ?? null,
        ];
    }
}
