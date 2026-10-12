<?php
/**
 * PSIS / AtoM-AHG - "Content Credentials - Verify" badge on record pages.
 *
 * PSIS twin of Heratio's InjectContentCredentialsBadge middleware. Runs as a
 * response.filter_content listener so neither base AtoM nor the locked theme /
 * sector templates are edited: on an HTML GET 200 record view whose record has
 * signed content credentials, it adds a small badge linking to the public
 * /c2pa/record/:id verification page. On archival-description pages the badge
 * joins the shared record action bar; on sector pages (museum, library, DAM,
 * gallery) it goes directly under the record title.
 *
 * One indexed lookup on pages that qualify; never breaks a page.
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace AhgC2pa\Listeners;

use Illuminate\Database\Capsule\Manager as DB;
use Throwable;

final class ContentCredentialsBadge
{
    private const MARKER = 'ahg-c2pa-badge';

    /** Description views RecordActionBar handles. */
    private const DESCRIPTION_MODULES = ['informationobject', 'sfIsadPlugin', 'sfRadPlugin', 'sfDcPlugin', 'sfModsPlugin', 'sfDacsPlugin'];

    /** Sector show pages, which RecordActionBar does not cover. */
    private const SECTOR_MODULES = ['museum', 'cco', 'library', 'dam', 'gallery'];

    public static function filter(\sfEvent $event, $content)
    {
        try {
            $html = (string) $content;
            if ($html === '' || str_contains($html, self::MARKER)) {
                return $content;
            }

            $context = \sfContext::getInstance();
            $module = (string) $context->getModuleName();
            $isDescription = in_array($module, self::DESCRIPTION_MODULES, true);

            if ($isDescription && class_exists('\AtomFramework\Views\RecordActionBar')) {
                return \AtomFramework\Views\RecordActionBar::render(
                    $event,
                    $content,
                    self::MARKER,
                    static fn (string $slug): string => self::badgeForSlug($slug)
                );
            }

            if (!in_array($module, self::SECTOR_MODULES, true) || !self::isPlainView($event, $context)) {
                return $content;
            }

            $context->getConfiguration()->loadHelpers(['I18N', 'Url']);
            $badge = self::badgeForSlug((string) $context->getRequest()->getParameter('slug', ''));
            if ($badge === '') {
                return $content;
            }

            $column = strpos($html, 'id="main-column"');
            $heading = $column === false ? false : stripos($html, '</h1>', $column);
            if ($heading === false) {
                return $content;
            }

            return substr_replace($html, "\n" . '<div class="mb-3">' . $badge . '</div>' . "\n", $heading + 5, 0);
        } catch (Throwable $e) {
            return $content;
        }
    }

    private static function isPlainView(\sfEvent $event, \sfContext $context): bool
    {
        $response = $event->getSubject();
        if (!$response instanceof \sfWebResponse || $response->getStatusCode() !== 200) {
            return false;
        }
        if (stripos((string) $response->getContentType(), 'text/html') === false) {
            return false;
        }
        if (strtoupper((string) $context->getRequest()->getMethod()) !== 'GET') {
            return false;
        }
        $action = strtolower((string) $context->getActionName());
        foreach (['edit', 'add', 'create', 'update', 'new', 'delete', 'browse', 'report', 'dashboard'] as $verb) {
            if (str_starts_with($action, $verb)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Badge HTML for the record behind $slug, or '' when it carries no signed
     * content credentials.
     */
    private static function badgeForSlug(string $slug): string
    {
        if ($slug === '') {
            return '';
        }

        $ioId = (int) (DB::table('slug as s')
            ->join('information_object as io', 'io.id', '=', 's.object_id')
            ->where('s.slug', $slug)
            ->value('io.id') ?? 0);
        if ($ioId <= 0 || !self::hasSignedCredentials($ioId)) {
            return '';
        }

        $href = htmlspecialchars(\url_for('@ahg_c2pa_record?id=' . $ioId), ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars((string) __('Content Credentials'), ENT_QUOTES, 'UTF-8');
        $verify = htmlspecialchars((string) __('Verify'), ENT_QUOTES, 'UTF-8');
        $title = htmlspecialchars((string) __('This record carries signed Content Credentials. Click to verify its authenticity.'), ENT_QUOTES, 'UTF-8');

        return '<a id="' . self::MARKER . '" href="' . $href . '" class="btn btn-sm btn-outline-success ' . self::MARKER . '"'
            . ' title="' . $title . '" data-bs-toggle="tooltip" data-io="' . $ioId . '">'
            . '<i class="fas fa-shield-alt me-1" aria-hidden="true"></i>' . $label
            . ' <span class="opacity-75">&middot; ' . $verify . '</span></a>';
    }

    private static function hasSignedCredentials(int $ioId): bool
    {
        return DB::schema()->hasTable('ahg_c2pa_provenance')
            && DB::table('ahg_c2pa_provenance')->where('information_object_id', $ioId)->whereNotNull('manifest_id')->exists();
    }
}
