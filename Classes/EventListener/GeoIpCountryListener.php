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

use B13\Magnets\IpLocation;
use B13\ViewTracker\Event\EnrichTrackingDataEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

#[AsEventListener]
final class GeoIpCountryListener
{
    public function __invoke(EnrichTrackingDataEvent $event): void
    {
        if ($event->trackingData->country !== '') {
            return;
        }

        $ip = $event->request->getServerParams()['REMOTE_ADDR'] ?? '';
        if ($ip === '' || $this->isLoopback($ip)) {
            return;
        }

        try {
            $location = new IpLocation($ip);
            $code = $location->getCountryCode();
            if (is_string($code) && preg_match('/^[A-Z]{2}$/', $code) === 1) {
                $event->trackingData->country = $code;
            }
        } catch (\Throwable) {
            // GeoIP database missing or lookup failed — silently skip
        }
    }

    private function isLoopback(string $ip): bool
    {
        return str_starts_with($ip, '127.') || $ip === '::1';
    }
}
