<?php

declare(strict_types=1);

namespace arMuseumMetadataPlugin\Services\Getty;

use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;

/**
 * Getty Service Factory.
 *
 * Factory for creating Getty vocabulary service instances with proper
 * dependency injection. Provides singleton instances where appropriate.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class GettyServiceFactory
{
    private static ?GettyCacheService $cache = null;
    private static ?GettySparqlService $sparql = null;
    private static ?AatService $aat = null;
    private static ?TgnService $tgn = null;
    private static ?UlanService $ulan = null;
    private static ?GettyLinkRepository $repository = null;
    private static ?GettyLinkingService $linking = null;

    private function __construct()
    {
        // Private constructor - use static methods
    }

    /**
     * Create or get cache service instance.
     */
    public static function getCache(
        ?string $cacheDir = null,
        ?LoggerInterface $logger = null
    ): GettyCacheService {
        if (!self::$cache) {
            self::$cache = new GettyCacheService(
                $cacheDir ?? '/tmp/getty_cache',
                $logger,
                true
            );
        }

        return self::$cache;
    }

    /**
     * Create or get SPARQL service instance.
     */
    public static function getSparql(
        ?LoggerInterface $logger = null,
        int $timeout = 30
    ): GettySparqlService {
        if (!self::$sparql) {
            $cache = self::getCache(null, $logger);
            self::$sparql = new GettySparqlService($logger, $timeout, $cache);
        }

        return self::$sparql;
    }

    /**
     * Create or get AAT service instance.
     */
    public static function getAat(?LoggerInterface $logger = null): AatService
    {
        if (!self::$aat) {
            $sparql = self::getSparql($logger);
            self::$aat = new AatService($sparql, $logger);
        }

        return self::$aat;
    }

    /**
     * Create or get TGN service instance.
     */
    public static function getTgn(?LoggerInterface $logger = null): TgnService
    {
        if (!self::$tgn) {
            $sparql = self::getSparql($logger);
            self::$tgn = new TgnService($sparql, $logger);
        }

        return self::$tgn;
    }

    /**
     * Create or get ULAN service instance.
     */
    public static function getUlan(?LoggerInterface $logger = null): UlanService
    {
        if (!self::$ulan) {
            $sparql = self::getSparql($logger);
            self::$ulan = new UlanService($sparql, $logger);
        }

        return self::$ulan;
    }

    /**
     * Create or get link repository instance.
     */
    public static function getRepository(
        ConnectionInterface $db,
        ?LoggerInterface $logger = null
    ): GettyLinkRepository {
        if (!self::$repository) {
            self::$repository = new GettyLinkRepository($db, $logger);
        }

        return self::$repository;
    }

    /**
     * Create or get linking service instance.
     */
    public static function getLinking(
        ConnectionInterface $db,
        ?LoggerInterface $logger = null
    ): GettyLinkingService {
        if (!self::$linking) {
            self::$linking = new GettyLinkingService(
                $db,
                self::getRepository($db, $logger),
                self::getAat($logger),
                self::getTgn($logger),
                self::getUlan($logger),
                $logger
            );
        }

        return self::$linking;
    }

    /**
     * Create all services at once.
     *
     * @return array Associative array of all service instances
     */
    public static function createAll(
        ConnectionInterface $db,
        ?LoggerInterface $logger = null,
        ?string $cacheDir = null
    ): array {
        return [
            'cache' => self::getCache($cacheDir, $logger),
            'sparql' => self::getSparql($logger),
            'aat' => self::getAat($logger),
            'tgn' => self::getTgn($logger),
            'ulan' => self::getUlan($logger),
            'repository' => self::getRepository($db, $logger),
            'linking' => self::getLinking($db, $logger),
        ];
    }

    /**
     * Reset all singleton instances (useful for testing).
     */
    public static function reset(): void
    {
        self::$cache = null;
        self::$sparql = null;
        self::$aat = null;
        self::$tgn = null;
        self::$ulan = null;
        self::$repository = null;
        self::$linking = null;
    }
}
