# View Tracker GeoIP

Country enrichment for [b13/view-tracker](https://github.com/b13/view-tracker) via MaxMind GeoLite2 database lookups, using the [b13/magnets](https://packagist.com/packages/b13/magnets) extension.

## How it works

When a page view is tracked, this extension resolves the visitor's country from their IP address using a local MaxMind GeoLite2 database. The country is stored as an ISO 3166-1 alpha-2 code (e.g. `DE`, `US`, `FR`).

Loopback IPs (`127.x.x.x`, `::1`) and missing/invalid lookups are handled gracefully — the country simply stays empty.

If the country was already resolved by another listener (e.g. `b13/view-tracker-cdn-country`), this extension skips processing.

## Requirements

- PHP 8.1+
- TYPO3 12+
- `b13/view-tracker`
- `b13/magnets` ^3.0
- MaxMind GeoLite2 database files (see setup below)

## Installation

```bash
composer require b13/view-tracker-geoip
```

The event listener is auto-discovered via the `#[AsEventListener]` attribute. No Services.yaml configuration needed.

## Setup

### 1. Get a MaxMind license key

Register for a free account at [maxmind.com/en/geolite2/signup](https://www.maxmind.com/en/geolite2/signup) and create a license key.

### 2. Configure TYPO3

Add to your `config/system/additional.php`:

```php
$GLOBALS['TYPO3_CONF_VARS']['SYS']['GeoIPPath'] = \TYPO3\CMS\Core\Core\Environment::getVarPath() . '/geoip';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['GeoIPLicenceKey'] = 'YOUR_MAXMIND_LICENSE_KEY';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['GeoIPSource'] = [
    'url' => 'https://download.maxmind.com/app/geoip_download?edition_id=###REMOTE_EDITION###&suffix=tar.gz',
    'headers' => [],
];
```

### 3. Download the databases

```bash
vendor/bin/typo3 geoip:lib:update
```

This downloads `GeoLite2-City.mmdb` and `GeoLite2-Country.mmdb` into the configured `GeoIPPath`.

The databases are updated monthly by MaxMind. Set up a cron job to keep them current:

```
0 3 1 * * cd /path/to/project && vendor/bin/typo3 geoip:lib:update
```

## Coexistence with b13/view-tracker-cdn-country

Both extensions can be installed simultaneously. Each listener checks if the country is already set and skips if so. A typical setup uses the CDN extension in production (zero overhead) and GeoIP as a fallback for environments without a CDN.

## Background, authors & maintenance

This extension was created by David Steeb in 2026 for [b13 GmbH, Stuttgart](https://b13.com).

[Find more TYPO3 extensions we have developed](https://b13.com/useful-typo3-extensions-from-b13-to-you) that help us deliver
value in client projects. As part of our work, we focus on testing and best practices to ensure long-term performance,
reliability, and results in all our code.


