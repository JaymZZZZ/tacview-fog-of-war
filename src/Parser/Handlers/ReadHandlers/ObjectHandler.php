<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          ObjectHandler.php
 * @author        JaymZZZZ
 * @copyright     Copyright (c) 2022 James D.
 * @license       This file is part of tacview-fog-of-war - free software licensed under the MIT License
 * @link          https://github.com/JaymZZZZ/tacview-fog-of-war
 *
 * Parser based on https://github.com/kavinsky/tacview-acmi-parser
 */

namespace Jaymzzz\Tacviewfogofwar\Parser\Handlers\ReadHandlers;

use Illuminate\Support\Str;
use Jaymzzz\Tacviewfogofwar\Acmi;
use Jaymzzz\Tacviewfogofwar\Enum\AcmiObjectType;
use Jaymzzz\Tacviewfogofwar\Libraries\GeoMapLibrary;
use Jaymzzz\Tacviewfogofwar\Libraries\OutputWriterLibrary;
use Jaymzzz\Tacviewfogofwar\Objects\AcmiObject;
use Jaymzzz\Tacviewfogofwar\Objects\AcmiObjectRecord;

/**
 *
 */
class ObjectHandler implements SentenceHandlerInterface
{
    /**
     * @var array|null
     */
    protected ?array $matches = null;

    /**
     * @inheritDoc
     */
    public function matches(string $sentence): bool
    {
        return preg_match('/([0-9a-fA-F]{0,16}),T=(.*)/is', $sentence, $this->matches) > 0;
    }

    /**
     * @inheritDoc
     */
    public function handle(string $sentence, Acmi $acmi, float $delta = 0): void
    {
        if ($this->matches === null) {
            return;
        }

        [, $hexId, $payload] = $this->matches;
        $acmi->properties->delta = $delta;

        $object = $this->getOrCreateObject($acmi, $hexId, $payload);
    }

    /**
     * Access to the object collection and if it exists returns it, if not creates it
     *
     * @param Acmi $acmi
     * @param string $id
     * @return AcmiObject
     */
    protected
    function getOrCreateObject(Acmi $acmi, string $id, string $payload): AcmiObject
    {
        $object = new AcmiObject($id);

        $payload = explode(',', $payload);
        $transformationVector = array_shift($payload);

        $object = $this->parseTransformation($acmi, $object, $transformationVector, $payload);

        if (!$acmi->objects->has($id)) {

            switch ($object->type[0]) {
                case "AIR":
                    $this->register_air($object);
                    break;
                case "SEA":
                    $this->register_sea($object);
                    break;
                case "GROUND":
                    $this->register_ground($object);
                    break;
                case "WEAPON":
                    $this->register_weapon($acmi, $object);
                    $acmi->active_objects[$object->id] = "active";
                    break;
                default:
                    $this->register_misc($object);

            }

            $acmi->objects->put($id, $object);

            if ($object->color != "Red") {
                $acmi->active_objects[$object->id] = "active";
            }


        } else {
            $acmi->objects[$id]->position = $object->position;
        }


        /*
                if(isset($acmi->objects["3d202"])) {
                    echo $acmi->objects["3d202"]->pilot." | ".$acmi->objects["3d202"]->position->latitude." | ".$acmi->objects["3d202"]->position->longitude."\r\n";
                    if(is_null($acmi->objects["3d202"]->pilot)){
                        sleep(10);
                    }
                }
        */

        return $object;

    }

    /**
     * Parses the first part of the sentence, transformation vector and payload
     *
     * @param Acmi $acmi
     * @param AcmiObject $object
     * @param string $transformString
     * @param array<string> $payload
     * @return AcmiObject
     */
    protected function parseTransformation(Acmi $acmi, AcmiObject $object, string $transformString, array $payload = []): AcmiObject
    {
        $rawTransform = explode('|', $transformString);
        match (count($rawTransform)) {
            3 => [$lon, $lat, $alt] = $rawTransform,
            5 => [$lon, $lat, $alt, $u, $v] = $rawTransform,
            6 => [$lon, $lat, $alt, $roll, $pitch, $yaw] = $rawTransform,
            9 => [$lon, $lat, $alt, $roll, $pitch, $yaw, $u, $v, $heading] = $rawTransform
        };

        $properties = $this->parseProperties($payload);

        $this->parseObjectProperties($object, $properties);

        if (!isset($object->position)) {
            $object->position = new AcmiObjectRecord();
        }

        if (!is_null($lat) && $lat != '') {
            $object->position->longitude = ($object->position->longitude != $lon) ? $lon : $object->position->longitude;
        }
        if (!is_null($lon) && $lon != '') {
            $object->position->latitude = ($object->position->latitude != $lat) ? $lat : $object->position->latitude;
        }
        if (!is_null($alt) && $alt != '') {
            $object->position->altitude = ($object->position->altitude != $alt) ? $alt : $object->position->altitude;
        }

        return $object;

    }

    /**
     * Parses the rest of the sentence getting the payload properties
     *
     * @param array<string> $payload
     * @return array<string,mixed>
     */
    protected function parseProperties(array $payload): array
    {
        $bag = [];
        foreach ($payload as $item) {
            [$key, $value] = explode('=', $item);
            $bag[$key] = $value;
        }

        return $bag;
    }

    /**
     * Parses the rest of the object properties
     *
     * @param AcmiObject $object
     * @param array $properties
     */
    protected
    function parseObjectProperties(AcmiObject $object, array $properties): void
    {
        $familyProperties = ['Name', 'Parent', 'Next', 'Pilot', 'Coalition', 'Color', 'Country', 'Type'];

        foreach ($familyProperties as $expectKey) {
            $camelCaseKey = Str::camel($expectKey);

            if (array_key_exists($expectKey, $properties)) {
                if ($expectKey == 'Type') {
                    $object->{$camelCaseKey} = AcmiObjectType::fromString($properties[$expectKey]);
                } else {
                    $object->{$camelCaseKey} = $properties[$expectKey];
                }
            }

        }
    }

    /**
     * @param AcmiObject $object
     */
    protected
    function register_air(AcmiObject $object)
    {
        OutputWriterLibrary::write_message("Added new " . $object->color . " " . $object->type[0] . " aircraft with name " . $object->name . " flown by pilot: " . $object->pilot . " (" . $object->id . ")", "green");

    }

    /**
     * @param AcmiObject $object
     */
    protected
    function register_sea(AcmiObject $object)
    {
        OutputWriterLibrary::write_message("Added new " . $object->color . " boat with name " . $object->name . " commanded by: " . $object->pilot, "green");

    }

    /**
     * @param AcmiObject $object
     */
    protected
    function register_ground(AcmiObject $object)
    {
        OutputWriterLibrary::write_message("Added new " . $object->color . " ground object with name " . $object->name . " commanded by: " . $object->pilot, "green");

    }

    /**
     * @param Acmi $acmi
     * @param AcmiObject $object
     */
    protected
    function register_weapon(Acmi $acmi, AcmiObject $object)
    {
        $parent_object = $this->find_weapon_deployer($acmi, $object);
        if ($parent_object->id != -1) {
            OutputWriterLibrary::write_message("WEAPON DEPLOYED - Added new " . $object->color . " WEAPON with name " . $object->name . " fired by: " . $parent_object->name . " (" . $parent_object->pilot . ") ", "red");
            $acmi->active_objects[$parent_object->id] = "active";
        } else {
            OutputWriterLibrary::write_message("WEAPON DEPLOYED - Added new " . $object->color . " WEAPON with name " . $object->name . " fired by: UNKNOWN", "red");
        }
    }

    /**
     * @param Acmi $acmi
     * @param AcmiObject $weapon
     * @return AcmiObject
     */
    protected
    function find_weapon_deployer(Acmi $acmi, AcmiObject $weapon): AcmiObject
    {
        $lat_long_delta = 100000000;
        $result = null;
        foreach ($acmi->objects as $object) {
            if ($object->color == $weapon->color && $object->country = $weapon->country) {
                if (isset($object->type[0]) && in_array($object->type[0], ["AIR", "SEA", "GROUND"])) {
                    $distance = GeoMapLibrary::get_distance_between_objects($acmi, $object->position, $weapon->position);

                    if ($distance < $lat_long_delta) {
                        $result = $object;
                        $lat_long_delta = $distance;
                        OutputWriterLibrary::write_message("Updated to new shooter " . $object->color . " " . $object->type[0] . " " . $object->name . " with distance of " . number_format($lat_long_delta, 3) . " KM");
                    }

                }
            }
        }

        if (is_null($result)) {
            return new AcmiObject(-1);
        }

        return $result;
    }

    /**
     * @param AcmiObject $object
     */
    protected
    function register_misc(AcmiObject $object)
    {
        if (!in_array($object->type[0], ['SHRAPNEL', 'DECOY'])) {
            OutputWriterLibrary::write_message("Added new misc object with type " . $object->type[0], "green");
        }

    }

}
