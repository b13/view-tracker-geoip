<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "view_tracker_geoip" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\ViewTrackerGeoip\Tests\Unit\EventListener;

use B13\ViewTracker\DTO\TrackingData;
use B13\ViewTracker\Event\EnrichTrackingDataEvent;
use B13\ViewTrackerGeoip\EventListener\GeoIpCountryListener;
use B13\ViewTrackerGeoip\Service\CountryLookup;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\AbstractLogger;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class GeoIpCountryListenerTest extends UnitTestCase
{
    /** @var list<string> */
    private array $lookedUp = [];

    /** @var list<array{string, string, array}> */
    private array $logged = [];

    #[Test]
    public function countryOfVisitorIsStored(): void
    {
        $data = $this->dispatch(['REMOTE_ADDR' => '203.0.113.10'], ['203.0.113.10' => 'DE']);

        self::assertSame('DE', $data->country);
        self::assertSame(['203.0.113.10'], $this->lookedUp);
    }

    #[Test]
    public function visitorBehindTrustedReverseProxyIsLookedUp(): void
    {
        $data = $this->dispatch(
            ['REMOTE_ADDR' => '198.51.100.1', 'HTTP_X_FORWARDED_FOR' => '203.0.113.10'],
            ['203.0.113.10' => 'FR', '198.51.100.1' => 'US'],
            ['reverseProxyIP' => '198.51.100.1', 'reverseProxyHeaderMultiValue' => 'first']
        );

        self::assertSame('FR', $data->country);
    }

    #[Test]
    public function forwardedHeaderOfUntrustedClientIsIgnored(): void
    {
        $data = $this->dispatch(
            ['REMOTE_ADDR' => '198.51.100.1', 'HTTP_X_FORWARDED_FOR' => '203.0.113.10'],
            ['203.0.113.10' => 'FR', '198.51.100.1' => 'US'],
        );

        self::assertSame('US', $data->country);
    }

    #[Test]
    public function rawRemoteAddressIsUsedWithoutNormalizedParams(): void
    {
        $data = new TrackingData(1);
        $request = new ServerRequest('https://example.com/_b13vt?page=1', 'GET', 'php://input', [], ['REMOTE_ADDR' => '203.0.113.10']);
        $this->listener(['203.0.113.10' => 'IT'])(new EnrichTrackingDataEvent($data, $request));

        self::assertSame('IT', $data->country);
    }

    public static function addressWithoutCountryProvider(): \Generator
    {
        yield 'IPv4 loopback' => ['127.0.0.1'];
        yield 'IPv6 loopback' => ['::1'];
        yield 'private network' => ['192.168.1.20'];
        yield 'private network 10/8' => ['10.0.0.5'];
        yield 'IPv6 unique local' => ['fd12:3456:789a::1'];
        yield 'empty' => [''];
        yield 'garbage' => ['not-an-ip'];
    }

    #[Test]
    #[DataProvider('addressWithoutCountryProvider')]
    public function addressesWithoutCountryAreNotLookedUp(string $ip): void
    {
        $data = $this->dispatch(['REMOTE_ADDR' => $ip], [$ip => 'DE']);

        self::assertSame('', $data->country);
        self::assertSame([], $this->lookedUp);
    }

    #[Test]
    public function countryFromEarlierListenerIsKept(): void
    {
        $data = new TrackingData(1);
        $data->country = 'AT';
        $request = new ServerRequest('https://example.com/', 'GET', 'php://input', [], ['REMOTE_ADDR' => '203.0.113.10']);
        $this->listener(['203.0.113.10' => 'DE'])(new EnrichTrackingDataEvent($data, $request));

        self::assertSame('AT', $data->country);
        self::assertSame([], $this->lookedUp);
    }

    #[Test]
    public function unknownAddressAndInvalidCodesLeaveCountryEmpty(): void
    {
        self::assertSame('', $this->dispatch(['REMOTE_ADDR' => '203.0.113.10'], [])->country);
        self::assertSame('', $this->dispatch(['REMOTE_ADDR' => '203.0.113.10'], ['203.0.113.10' => 'de'])->country);
        self::assertSame([], $this->logged);
    }

    #[Test]
    public function lookupFailureIsLoggedOnceWithoutIpAddress(): void
    {
        $listener = $this->listener(new \RuntimeException('The file "/var/geoip/GeoLite2-Country.mmdb" does not exist'));

        foreach (['203.0.113.10', '203.0.113.11'] as $ip) {
            $data = new TrackingData(1);
            $listener(new EnrichTrackingDataEvent($data, $this->request(['REMOTE_ADDR' => $ip])));
            self::assertSame('', $data->country);
        }

        self::assertCount(1, $this->logged);
        [$level, $message, $context] = $this->logged[0];
        self::assertSame('warning', $level);
        self::assertSame('RuntimeException', $context['class']);
        self::assertStringNotContainsString('203.0.113', $message . json_encode($context));
    }

    private function dispatch(array $serverParams, array $countries, array $proxyConfiguration = []): TrackingData
    {
        $data = new TrackingData(1);
        $this->listener($countries)(new EnrichTrackingDataEvent($data, $this->request($serverParams, $proxyConfiguration)));
        return $data;
    }

    private function request(array $serverParams, array $proxyConfiguration = []): ServerRequest
    {
        $serverParams += ['HTTP_HOST' => 'example.com', 'SCRIPT_NAME' => '/index.php'];
        $request = new ServerRequest('https://example.com/_b13vt?page=1', 'GET', 'php://input', [], $serverParams);
        return $request->withAttribute('normalizedParams', NormalizedParams::createFromServerParams($serverParams, $proxyConfiguration));
    }

    /**
     * @param array<string, string>|\Throwable $countries
     */
    private function listener(array|\Throwable $countries): GeoIpCountryListener
    {
        $lookedUp = &$this->lookedUp;
        $lookup = new class ($countries, $lookedUp) extends CountryLookup {
            public function __construct(private readonly array|\Throwable $countries, private array &$lookedUp) {}

            public function lookup(string $ip): ?string
            {
                $this->lookedUp[] = $ip;
                if ($this->countries instanceof \Throwable) {
                    throw $this->countries;
                }
                return $this->countries[$ip] ?? null;
            }
        };
        $logged = &$this->logged;
        $logger = new class ($logged) extends AbstractLogger {
            public function __construct(private array &$logged) {}

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->logged[] = [$level, (string)$message, $context];
            }
        };
        return new GeoIpCountryListener($lookup, $logger);
    }
}
