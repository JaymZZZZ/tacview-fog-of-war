<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          GeoMapLibrary.php
 * @author        JaymZZZZ
 * @copyright     Copyright (c) 2022 James D.
 * @license       This file is part of tacview-fog-of-war - free software licensed under the MIT License
 * @link          https://github.com/JaymZZZZ/tacview-fog-of-war
 *
 * Parser based on https://github.com/kavinsky/tacview-acmi-parser
 */

namespace Jaymzzz\Tacviewfogofwar\Libraries;

use Jaymzzz\Tacviewfogofwar\Acmi;
use Jaymzzz\Tacviewfogofwar\Objects\AcmiObjectRecord;
use JetBrains\PhpStorm\Pure;

/**
 *
 */
class GeoMapLibrary
{

    /**
     * @var float
     */
    private static float $radius_equatorial = 6378137.0;
    /**
     * @var float
     */
    private static float $radius_polar = 6356752.3;
    /**
     * @var float
     */
    private static float $e2 = 0.00669437999014;


    /**
     * @param Acmi $acmi
     * @param AcmiObjectRecord $from
     * @param AcmiObjectRecord $to
     * @param string $measurement
     * @return float
     */
    #[Pure] public static
    function get_distance_between_objects(Acmi $acmi, AcmiObjectRecord $from, AcmiObjectRecord $to, $measurement = "km"): float
    {

        $from_lat = $acmi->properties->referenceLatitude + $from->latitude;
        $from_long = $acmi->properties->referenceLongitude + $from->longitude;
        $to_lat = $acmi->properties->referenceLatitude + $to->latitude;
        $to_long = $acmi->properties->referenceLongitude + $to->longitude;

        $xyz_from = self::location_to_point($from_lat, $from_long, $from->altitude);
        $xyz_to = self::location_to_point($to_lat, $to_long, $to->altitude);

        $dx = $xyz_from[0] - $xyz_to[0];
        $dy = $xyz_from[1] - $xyz_to[1];
        $dz = $xyz_from[2] - $xyz_to[2];

        $distance_in_meters = sqrt(pow($dx, 2) + pow($dy, 2) + pow($dz, 2));

        return match (strtolower($measurement)) {
            "km" => $distance_in_meters / 1000,
            "mi" => $distance_in_meters / 1609,
            "nm" => $distance_in_meters / 1852,
            default => $distance_in_meters,
        };


    }

    /**
     * @param float $latitude
     * @param float $longitude
     * @param float $altitude
     * @return array
     */
    #[Pure] private static
    function location_to_point(float $latitude, float $longitude, float $altitude): array
    {
        $lat = $latitude * M_PI / 180;
        $lon = $longitude * M_PI / 180;
        $radius = self::earth_radius_in_meters($lat);
        $clat = self::geocentric_latitude($lat);

        $cos_lon = cos($lon);
        $sin_lon = sin($lon);
        $cos_lat = cos($clat);
        $sin_lat = sin($clat);

        $xyz = [];
        $xyz[] = $radius * $cos_lon * $cos_lat;
        $xyz[] = $radius * $sin_lon * $cos_lat;
        $xyz[] = $radius * $sin_lat;

        $cos_glat = cos($lat);
        $sin_glat = sin($lat);

        $nx = $cos_glat * $cos_lon;
        $ny = $cos_glat * $sin_lon;
        $nz = $sin_glat;

        $xyz[0] = $xyz[0] + ($altitude * $nx);
        $xyz[1] = $xyz[1] + ($altitude * $ny);
        $xyz[2] = $xyz[2] + ($altitude * $nz);

        return $xyz;
    }

    /**
     * @param float $latitude
     * @return float
     */
    private static
    function earth_radius_in_meters(float $latitude): float
    {

        $cos_lat = cos($latitude);
        $sin_lat = sin($latitude);
        $t1 = pow(self::$radius_equatorial, 2) * $cos_lat;
        $t2 = pow(self::$radius_polar, 2) * $sin_lat;
        $t3 = self::$radius_equatorial * $cos_lat;
        $t4 = self::$radius_polar * $sin_lat;

        return sqrt((pow($t1, 2) + pow($t2, 2)) / (pow($t3, 2) + pow($t4, 2)));
    }

    /**
     * @param float $latitude
     * @return float
     */
    private static
    function geocentric_latitude(float $latitude): float
    {
        return atan((1.0 - self::$e2) * tan($latitude));
    }

}
