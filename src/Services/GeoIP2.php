<?php

declare(strict_types=1);

namespace App\Services;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Db\Reader\InvalidDatabaseException;
use const BASE_PATH;

final class GeoIP2
{
    private Reader $city_reader;
    private Reader $country_reader;

    public static function isAvailable(): bool
    {
        return is_readable(self::cityDatabasePath())
            && is_readable(self::countryDatabasePath());
    }

    public static function cityDatabasePath(): string
    {
        return BASE_PATH . '/storage/GeoLite2-City/GeoLite2-City.mmdb';
    }

    public static function countryDatabasePath(): string
    {
        return BASE_PATH . '/storage/GeoLite2-Country/GeoLite2-Country.mmdb';
    }

    /**
     * @throws InvalidDatabaseException
     */
    public function __construct()
    {
        $this->city_reader = new Reader(self::cityDatabasePath());
        $this->country_reader = new Reader(self::countryDatabasePath());
    }

    /**
     * @throws AddressNotFoundException
     * @throws InvalidDatabaseException
     */
    public function getCity(string $ip): ?string
    {
        $record = $this?->city_reader?->city($ip);
        return $record?->city?->names[$_ENV['geoip_locale']] ?? $record?->city?->name;
    }

    /**
     * @throws AddressNotFoundException
     * @throws InvalidDatabaseException
     */
    public function getCountry(string $ip): ?string
    {
        $record = $this?->country_reader?->country($ip);
        return $record?->country?->names[$_ENV['geoip_locale']] ?? $record?->country?->name;
    }
}
