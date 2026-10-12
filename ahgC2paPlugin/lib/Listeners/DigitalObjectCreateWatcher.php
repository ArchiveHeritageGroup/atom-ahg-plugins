<?php
/**
 * PSIS / AtoM-AHG - auto-provenance when a digital object is created.
 *
 * PSIS twin of Heratio's RecordDigitalObjectProvenance listener. Base AtoM
 * fires no event when a digital object is saved (QubitDigitalObject.postSave
 * and digital_object.post_create are connected to by other plugins but never
 * dispatched), and base files are locked. So instead of hooking the save, this
 * notes the highest digital_object.id when a request that can create one
 * starts (any POST, and every CLI task such as ingest:commit), and at shutdown
 * records signed provenance for each master created since. That covers every
 * upload path - base digitalobject/add, multi-upload, the ingest wizard, CSV
 * import - without knowing about any of them.
 *
 * Idempotent through DigitalObjectProvenanceService (never double-records) and
 * a no-op when no signing key is readable, so c2pa:provenance-backfill can
 * still sign those objects later.
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace AhgC2pa\Listeners;

use Illuminate\Database\Capsule\Manager as DB;
use Throwable;

final class DigitalObjectCreateWatcher
{
    /**
     * ponytail: at most this many masters are signed inline per request (each
     * is a full-file hash plus a c2patool copy). A bigger batch is finished by
     * php symfony c2pa:provenance-backfill; move to a job queue if inline
     * latency on multi-uploads becomes a complaint.
     */
    private const CAP_PER_REQUEST = 20;

    private static bool $armed = false;

    /**
     * context.load_factories listener.
     */
    public static function arm(\sfEvent $event): void
    {
        if (self::$armed) {
            return;
        }

        try {
            if (PHP_SAPI !== 'cli') {
                $context = $event->getSubject();
                $method = ($context instanceof \sfContext) ? (string) $context->getRequest()->getMethod() : '';
                if (strtoupper($method) !== 'POST') {
                    return;
                }
            }

            if (class_exists('\AhgCore\Core\AhgDb')) {
                \AhgCore\Core\AhgDb::init();
            }
            if (!DB::schema()->hasTable('ahg_c2pa_provenance')) {
                return;
            }

            $startMax = (int) (DB::table('digital_object')->max('id') ?? 0);
            self::$armed = true;

            register_shutdown_function(static function () use ($startMax): void {
                self::flush($startMax);
            });
        } catch (Throwable $e) {
            error_log('[c2pa] digital object create watcher not armed: ' . $e->getMessage());
        }
    }

    private static function flush(int $startMax): void
    {
        try {
            $now = (int) (DB::table('digital_object')->max('id') ?? 0);
            if ($now <= $startMax) {
                return;
            }

            require_once dirname(__DIR__) . '/c2pa_bootstrap.php';
            \C2paBootstrap::load();
            \C2paBootstrap::digitalObjectService()->recordCreatedAfter($startMax, self::CAP_PER_REQUEST);
        } catch (Throwable $e) {
            error_log('[c2pa] auto-provenance at shutdown failed: ' . $e->getMessage());
        }
    }
}
