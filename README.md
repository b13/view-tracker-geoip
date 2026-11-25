# View Tracker GeoIP for TYPO3

Country detection for [View Tracker](https://github.com/b13/view-tracker) from
the visitor's IP address, looked up in a local MaxMind GeoLite2 database. The
IP address never leaves your server and is never stored.

## How It Works

When View Tracker counts a page view, this extension looks up the visitor's IP
address in the GeoLite2 country database and stores the country as an ISO
3166-1 alpha-2 code (e.g. `DE`, `US`, `FR`). The database is downloaded and
updated by [b13/magnets](https://packagist.org/packages/b13/magnets).

- The IP address comes from TYPO3, so the reverse proxy settings
  `reverseProxyIP` and `reverseProxyHeaderMultiValue` apply. Behind a proxy
  or load balancer, configure them, or every view gets the proxy's country.
- Private, loopback, and reserved addresses are skipped.
- If the database is missing or cannot be read, the view is still counted
  without a country, and one warning per PHP process is written to the TYPO3
  log. The warning does not contain the IP address.
- If another listener already set the country, for example
  [View Tracker CDN Country](https://github.com/b13/view-tracker-cdn-country),
  no lookup happens.

## Compatibility

TYPO3 v13.4 and v14, PHP 8.2 or newer, `b13/view-tracker` 1.0 or newer.

Release notes live in [CHANGELOG.md](CHANGELOG.md).

## Installation

```bash
composer require b13/view-tracker-geoip
```

Without the database, nothing happens: views are counted without a country.
Set it up as follows.

### 1. Get a MaxMind license key

Create a free GeoLite account at
[maxmind.com/en/geolite2/signup](https://www.maxmind.com/en/geolite2/signup)
and generate a license key under "Manage License Keys". Treat the key like a
password: keep it out of version control, for example in an environment
variable.

### 2. Configure TYPO3

In `config/system/additional.php`:

```php
$GLOBALS['TYPO3_CONF_VARS']['SYS']['GeoIPPath'] = \TYPO3\CMS\Core\Core\Environment::getVarPath() . '/geoip';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['GeoIPLicenceKey'] = getenv('MAXMIND_LICENSE_KEY');
$GLOBALS['TYPO3_CONF_VARS']['SYS']['GeoIPSource'] = [
    'url' => 'https://download.maxmind.com/app/geoip_download?edition_id=###REMOTE_EDITION###&suffix=tar.gz',
    'headers' => [],
];
```

If TYPO3 runs behind a reverse proxy, also set `reverseProxyIP` (and, if
needed, `reverseProxyHeaderMultiValue`) in `$GLOBALS['TYPO3_CONF_VARS']['SYS']`.

### 3. Download the database

```bash
vendor/bin/typo3 geoip:lib:update
```

This downloads the GeoLite2 databases into `GeoIPPath`. View Tracker GeoIP
reads `GeoLite2-Country.mmdb`. MaxMind updates the databases regularly, and
the GeoLite license expects you to use current data, so schedule the command,
for example weekly:

```
0 3 * * 1 cd /path/to/project && vendor/bin/typo3 geoip:lib:update
```

## Together With View Tracker CDN Country

Both extensions can be installed at the same time. The CDN extension runs
first; if your CDN sent a country header, this extension skips its lookup.
That way the CDN header is used where it exists, and the local database is
the fallback, for example on a staging system without a CDN.

## Credits

View Tracker GeoIP was created by David Steeb and is maintained by
[b13 GmbH](https://b13.com/?utm_source=view_tracker_geoip&utm_medium=readme), Stuttgart, Germany.

This product includes GeoLite2 data created by MaxMind, available from
[maxmind.com](https://www.maxmind.com).

[Find more TYPO3 extensions we have developed](https://b13.com/useful-typo3-extensions-from-b13-to-you?utm_source=view_tracker_geoip&utm_medium=readme)
that help us deliver value in client projects. As part of our work, we focus on
testing and best practices to ensure long-term performance, reliability, and
results in all our code.
