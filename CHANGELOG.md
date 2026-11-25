# Changelog

All notable changes to `b13/view-tracker-geoip` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] — 2026-11-25

First public release on GitHub and Packagist.

### Changed

- **Visitors behind a reverse proxy get their own country.** The IP address
  is taken from TYPO3, which applies `reverseProxyIP` and
  `reverseProxyHeaderMultiValue`. Before, the raw `REMOTE_ADDR` was used, so
  every view behind a proxy or load balancer got the proxy's country.
- **Private and reserved addresses are skipped**, not only loopback.
- **Lookup failures are logged.** A missing or unreadable database writes one
  warning per PHP process to the TYPO3 log instead of failing silently. The
  IP address is not part of the message.
- **Faster lookups.** Only the country database is read, and it is opened
  once per PHP process instead of opening the city and country databases for
  every view.
- **Runs after View Tracker CDN Country** when both are installed, so a
  country header from the CDN always wins.

### Documentation

- README covers the MaxMind license key, reverse proxy settings, scheduling
  the database update, and the MaxMind attribution.

### Tests

- Unit tests for IP resolution, proxy handling, skipped addresses, and error
  logging.

## [1.0.1] — 2026-04-14

### Changed

- New extension icon.

## [1.0.0] — 2026-02-24

- Initial release.
