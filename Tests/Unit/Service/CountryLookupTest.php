<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "view_tracker_geoip" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\ViewTrackerGeoip\Tests\Unit\Service;

use B13\ViewTrackerGeoip\Service\CountryLookup;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CountryLookupTest extends UnitTestCase
{
    #[Test]
    public function missingDatabaseThrows(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['GeoIPPath'] = '/nonexistent/geoip';

        $this->expectException(\InvalidArgumentException::class);
        (new CountryLookup())->lookup('203.0.113.10');
    }
}
