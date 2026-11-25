<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "view_tracker_geoip" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\ViewTrackerGeoip\Service;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;

/**
 * Looks up the country of an IP address in the local GeoLite2 country
 * database that b13/magnets downloads with "geoip:lib:update".
 */
class CountryLookup
{
    private ?Reader $reader = null;

    /**
     * @return string|null ISO 3166-1 alpha-2 code, null if the address is not in the database
     * @throws \Throwable if the database is missing or cannot be read
     */
    public function lookup(string $ip): ?string
    {
        try {
            return $this->getReader()->country($ip)->country->isoCode;
        } catch (AddressNotFoundException) {
            return null;
        }
    }

    private function getReader(): Reader
    {
        // One reader per process: opening the database for every view is
        // the expensive part of a lookup.
        return $this->reader ??= new Reader(
            rtrim((string)($GLOBALS['TYPO3_CONF_VARS']['SYS']['GeoIPPath'] ?? ''), '/') . '/GeoLite2-Country.mmdb'
        );
    }
}
