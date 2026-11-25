<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "view_tracker_geoip" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\ViewTrackerGeoip\EventListener;

use B13\ViewTracker\Event\EnrichTrackingDataEvent;
use B13\ViewTrackerGeoip\Service\CountryLookup;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Http\NormalizedParams;

/**
 * Runs after b13/view-tracker-cdn-country, so a country header set by the
 * CDN wins and no lookup is needed.
 */
#[AsEventListener(
    identifier: 'b13-view-tracker-geoip/country',
    after: 'b13-view-tracker-cdn-country/country',
)]
final class GeoIpCountryListener
{
    private bool $failureLogged = false;

    public function __construct(
        private readonly CountryLookup $countryLookup,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(EnrichTrackingDataEvent $event): void
    {
        if ($event->trackingData->country !== '') {
            return;
        }

        $ip = $this->getClientIp($event->request);
        if ($ip === null) {
            return;
        }

        try {
            $code = $this->countryLookup->lookup($ip);
        } catch (\Throwable $e) {
            // Log once per process, not once per view. The IP address is
            // deliberately not part of the message.
            if (!$this->failureLogged) {
                $this->logger->warning('GeoIP country lookup failed: {class}: {message}', [
                    'class' => $e::class,
                    'message' => $e->getMessage(),
                ]);
                $this->failureLogged = true;
            }
            return;
        }

        if (is_string($code) && preg_match('/^[A-Z]{2}$/', $code) === 1) {
            $event->trackingData->country = $code;
        }
    }

    /**
     * The visitor's address as resolved by TYPO3, which honors the reverse
     * proxy settings (reverseProxyIP, reverseProxyHeaderMultiValue).
     * Private and reserved addresses have no country.
     */
    private function getClientIp(ServerRequestInterface $request): ?string
    {
        $normalizedParams = $request->getAttribute('normalizedParams');
        $ip = $normalizedParams instanceof NormalizedParams
            ? $normalizedParams->getRemoteAddress()
            : (string)($request->getServerParams()['REMOTE_ADDR'] ?? '');

        $valid = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        return $valid === false ? null : $valid;
    }
}
