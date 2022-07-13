<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          AcmiObjectRecord.php
 * @author        JaymZZZZ
 * @copyright     Copyright (c) 2022 James D.
 * @license       This file is part of tacview-fog-of-war - free software licensed under the MIT License
 * @link          https://github.com/JaymZZZZ/tacview-fog-of-war
 *
 * Parser based on https://github.com/kavinsky/tacview-acmi-parser
 */

namespace Jaymzzz\Tacviewfogofwar\Objects;


use JetBrains\PhpStorm\Pure;

/**
 *
 */
class AcmiObjectRecord extends AcmiRecord
{
    /**
     * Longitude in Degress
     */
    public ?float $longitude = null;

    /**
     * Latitude in Degress
     */
    public ?float $latitude = null;

    /**
     * Altitude in Meters
     */
    public ?float $altitude = 0;


    /**
     * @param float|null $lon
     * @param float|null $lat
     * @param float|int|null $alt
     */
    public function __construct(
        ?float $lon = null,
        ?float $lat = null,
        ?float $alt = 0,
    )
    {
        $this->longitude = $lon;
        $this->latitude = $lat;
        $this->altitude = $alt;

    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    #[Pure] public function toArray(): array
    {
        return parent::toRecordArray([
            'lon' => $this->longitude,
            'lat' => $this->latitude,
            'alt' => $this->altitude
        ]);
    }
}
