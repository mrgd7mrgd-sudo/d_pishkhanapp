<?php

declare(strict_types=1);

namespace App\Integration\Geo\Enums;

/**
 * Supported map themes (§8.4)
 * Mapped from prototype themes: OSM, Voyager, Positron
 */
enum MapTheme: string
{
    case STANDARD_DAY = 'standard-day';
    case NESHAN = 'neshan';
    case DREAMY = 'dreamy';

    /**
     * Map prototype theme names to standard Neshan themes (§8.4).
     */
    public static function fromPrototype(string $theme): self
    {
        return match (strtolower(trim($theme))) {
            'voyager', 'standard-day', 'standard' => self::STANDARD_DAY,
            'osm', 'neshan' => self::NESHAN,
            'positron', 'dreamy', 'dark' => self::DREAMY,
            default => self::STANDARD_DAY,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
