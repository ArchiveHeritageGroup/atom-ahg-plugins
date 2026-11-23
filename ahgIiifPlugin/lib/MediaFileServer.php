<?php

/**
 * Access-controlled delivery of document masters (PDF and friends).
 *
 * Background: base AtoM used to return true for `readMaster` on ANY text media
 * object before both the ACL check and QubitGrantedRight::checkPremis(). That is
 * why a PREMIS rights statement could never restrict a PDF - the bypass ran first.
 * #258 removed it, which correctly closed the hole but left 33 published PDFs
 * returning 404 to the public.
 *
 * This route restores access on purpose rather than by bypass:
 *   1. the description must be readable (QubitAcl 'read')
 *   2. PREMIS granted rights are then honoured - the check the old code skipped
 *   3. only allowlisted document types, resolved through PathGuard
 *
 * So a PDF on a published record loads, and a PDF restricted by a rights statement
 * does not - which was never true before.
 */
class MediaFileServer
{
    /** Extension => acceptable mime types. Both must match. */
    private const ALLOWED = [
        'pdf' => ['application/pdf'],
        'txt' => ['text/plain'],
        'csv' => ['text/csv', 'text/plain'],
        'rtf' => ['application/rtf', 'text/rtf'],
    ];

    private const MIME_OUT = [
        'pdf' => 'application/pdf',
        'txt' => 'text/plain; charset=utf-8',
        'csv' => 'text/csv; charset=utf-8',
        'rtf' => 'application/rtf',
    ];

    /**
     * Authorisation only, no type allowlist - for endpoints that legitimately serve
     * any media type (streaming audio/video as well as documents).
     *
     * The description must be readable, must not be restricted for this user
     * (classification, donor, embargo, ICIP, ODRL), and PREMIS granted rights are
     * honoured. A master also needs readMaster, except audio and video being
     * streamed, which public players rely on. Returns the digital object when
     * the caller may have it, null when they may not.
     *
     * @return \QubitDigitalObject|null
     */
    public static function authorise(int $digitalObjectId, bool $streaming = false)
    {
        $digitalObject = \QubitDigitalObject::getById($digitalObjectId);
        if (!$digitalObject) {
            return null;
        }
        $isMaster = empty($digitalObject->parentId);
        $isAudioVideo = in_array((int) $digitalObject->mediaTypeId, [\QubitTerm::AUDIO_ID, \QubitTerm::VIDEO_ID], true);
        $isText = \QubitTerm::TEXT_ID === (int) $digitalObject->mediaTypeId;

        $resource = $digitalObject->object;

        // Derivatives hang off the master by parent_id and carry object_id NULL, so
        // walk up to find the description this file belongs to.
        $guard = 0;
        while ((!$resource || !isset($resource->id)) && isset($digitalObject->parentId) && $guard++ < 5) {
            $parent = \QubitDigitalObject::getById((int) $digitalObject->parentId);
            if (!$parent) {
                break;
            }
            $digitalObject = $parent;
            $resource = $digitalObject->object;
        }

        if (!$resource || !isset($resource->id)) {
            return null;
        }

        if (!\QubitAcl::check($resource, 'read')) {
            return null;
        }

        if (class_exists('\QubitGrantedRight')) {
            $denyReason = null;
            if (!\QubitGrantedRight::checkPremis($resource->id, 'readMaster', $denyReason)) {
                return null;
            }
        }

        if (self::isWithheld((int) $resource->id)) {
            return null;
        }

        if ($isMaster && !($streaming && $isAudioVideo) && !($isText && self::textMastersArePublic())) {
            // Stock AtoM answers yes to readMaster for every text object, so a document
            // master also needs a signed-in user, as digitalobject/view requires.
            if ($isText && !\sfContext::getInstance()->getUser()->isAuthenticated()) {
                return null;
            }
            if (!\QubitAcl::check($resource, 'readMaster')) {
                return null;
            }
        }

        return \QubitDigitalObject::getById($digitalObjectId);
    }

    /**
     * @return array{path:string,mime:string,name:string}|null null when the caller
     *                                                         must be refused
     */
    public static function resolve(int $digitalObjectId): ?array
    {
        $digitalObject = \QubitDigitalObject::getById($digitalObjectId);
        if (!$digitalObject) {
            return null;
        }

        $name = (string) $digitalObject->name;
        $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        $mime = strtolower((string) $digitalObject->mimeType);

        if (!isset(self::ALLOWED[$ext]) || !in_array($mime, self::ALLOWED[$ext], true)) {
            return null;
        }

        $resource = $digitalObject->object;
        if (!$resource || !isset($resource->id)) {
            return null;
        }

        // 1. Is the description itself readable?
        if (!\QubitAcl::check($resource, 'read')) {
            return null;
        }

        // 2. PREMIS rights. This is the check the removed bypass jumped over, so a
        //    rights statement on a PDF now actually restricts it.
        if (class_exists('\QubitGrantedRight')) {
            $denyReason = null;
            if (!\QubitGrantedRight::checkPremis($resource->id, 'readMaster', $denyReason)) {
                return null;
            }
        }

        $relative = (string) $digitalObject->path . $name;
        $absolute = rtrim((string) \sfConfig::get('sf_web_dir'), '/') . '/' . ltrim($relative, '/');

        $safe = self::guard($absolute);
        if (null === $safe || !is_readable($safe)) {
            return null;
        }

        return ['path' => $safe, 'mime' => self::MIME_OUT[$ext], 'name' => $name];
    }

    /** The site's choice to publish document masters, as digitalobject/view honours it. */
    private static function textMastersArePublic(): bool
    {
        try {
            return \AtomExtensions\Services\AhgSettingsService::getBool('allow_public_text_masters', false);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Fails closed: if the restriction check cannot run, the file is withheld. */
    private static function isWithheld(int $objectId): bool
    {
        try {
            $user = \sfContext::getInstance()->getUser();
            if ($user->isAdministrator()) {
                return false;
            }
            $userId = $user->getAttribute('user_id');
            $filter = \AtomExtensions\Services\Search\SearchAccessFilterService::getInstance();
            if (in_array($objectId, $filter->getRestrictedObjectIds($userId ? (int) $userId : null), true)) {
                return true;
            }

            // The filter only covers full embargoes; these two types also withhold the file.
            // ponytail: embargo exceptions are not honoured here for these types; holders use an administrator until the filter covers them.
            $today = date('Y-m-d');

            return \Illuminate\Database\Capsule\Manager::table('rights_embargo')
                ->where('object_id', $objectId)->where('status', 'active')
                ->whereIn('embargo_type', ['metadata_only', 'digital_object'])
                ->where('start_date', '<=', $today)
                ->where(function ($q) use ($today) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
                })->exists();
        } catch (\Throwable $e) {
            error_log('media.access.check_failed: ' . $e->getMessage());

            return true;
        }
    }

    private static function guard(string $path): ?string
    {
        if (class_exists('\AtomExtensions\Services\PathGuard')) {
            return \AtomExtensions\Services\PathGuard::within($path, \AtomExtensions\Services\PathGuard::defaultRoots());
        }

        $real = realpath($path);
        if (false === $real) {
            return null;
        }

        $root = realpath(rtrim((string) \sfConfig::get('sf_web_dir'), '/') . '/uploads');

        return (false !== $root && str_starts_with($real, $root . DIRECTORY_SEPARATOR)) ? $real : null;
    }
}
