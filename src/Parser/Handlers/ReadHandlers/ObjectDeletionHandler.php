<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          ObjectDeletionHandler.php
 * @author        JaymZZZZ
 * @copyright     Copyright (c) 2022 James D.
 * @license       This file is part of tacview-fog-of-war - free software licensed under the MIT License
 * @link          https://github.com/JaymZZZZ/tacview-fog-of-war
 *
 * Parser based on https://github.com/kavinsky/tacview-acmi-parser
 */

namespace Jaymzzz\Tacviewfogofwar\Parser\Handlers\ReadHandlers;

use Jaymzzz\Tacviewfogofwar\Acmi;
use Jaymzzz\Tacviewfogofwar\Libraries\GeoMapLibrary;
use Jaymzzz\Tacviewfogofwar\Libraries\OutputWriterLibrary;
use Jaymzzz\Tacviewfogofwar\Objects\AcmiObject;

/**
 *
 */
class ObjectDeletionHandler implements SentenceHandlerInterface
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
        return preg_match('/^-([0-9a-fA-F]{0,16})$/si', $sentence, $this->matches) > 0;
    }

    /**
     * @inheritDoc
     */
    public function handle(string $sentence, Acmi $acmi, float $delta = 0): void
    {
        if ($this->matches === null) {
            return;
        }

        [, $hexId] = $this->matches;

        $this->getObject($acmi, $hexId);
        $acmi->properties->delta = $delta;

    }

    /**
     * @param Acmi $acmi
     * @param string $id
     */
    function getObject(Acmi $acmi, string $id)
    {
        $object = $acmi->objects[$id];

        switch ($object->type[0]) {
            case "AIR":
                $this->unregister_air($object);
                break;
            case "SEA":
                $this->unregister_sea($object);
                break;
            case "GROUND":
                $this->unregister_ground($object);
                break;
            case "WEAPON":
                $this->unregister_weapon($acmi, $object);
                break;
            default:
                $this->unregister_misc($object);

        }


    }

    /**
     * @param AcmiObject $object
     */
    protected
    function unregister_air(AcmiObject $object)
    {
        OutputWriterLibrary::write_message("DELETE: removed " . $object->color . " " . $object->type[0] . " aircraft with name " . $object->name . " flown by pilot: " . $object->pilot . " (" . $object->id . ")", "magenta");
    }

    /**
     * @param AcmiObject $object
     */
    protected
    function unregister_sea(AcmiObject $object)
    {
        OutputWriterLibrary::write_message("DELETE: removed " . $object->color . " boat with name " . $object->name . " commanded by: " . $object->pilot, "magenta");
    }

    /**
     * @param AcmiObject $object
     */
    protected
    function unregister_ground(AcmiObject $object)
    {
        OutputWriterLibrary::write_message("DELETE: removed " . $object->color . " ground object with name " . $object->name . " commanded by: " . $object->pilot, "magenta");
    }

    /**
     * @param Acmi $acmi
     * @param AcmiObject $object
     */
    protected
    function unregister_weapon(Acmi $acmi, AcmiObject $object)
    {
        $target_object = $this->find_weapon_target($acmi, $object);
        if ($target_object->id == -1) {
            OutputWriterLibrary::write_message("DELETE: removed " . $object->color . " WEAPON with name " . $object->name . " - Did not hit any targets", "magenta");
        } else {
            OutputWriterLibrary::write_message("DELETE: removed " . $object->color . " WEAPON with name " . $object->name . " Target HIT: " . $target_object->name . " (" . $target_object->pilot . ") ", "magenta");
        }
        $acmi->objects[$object->id]->active = TRUE;
    }

    /**
     * @param Acmi $acmi
     * @param AcmiObject $weapon
     * @return AcmiObject
     */
    protected
    function find_weapon_target(Acmi $acmi, AcmiObject $weapon): AcmiObject
    {
        $result = null;

        $distance_in_meters = 50;
        if (isset($_ENV['WPN_RADIUS']) && is_integer($_ENV['WPN_RADIUS'])) {
            $distance_in_meters = $_ENV['WPN_RADIUS'];
        }

        foreach ($acmi->objects as $object) {
            if (isset($object->type[0]) && in_array($object->type[0], ["AIR", "SEA", "GROUND"])) {
                $distance = GeoMapLibrary::get_distance_between_objects($acmi, $object->position, $weapon->position);

                if ($distance < (1000 / $distance_in_meters)) {
                    $result = $object;
                    $lat_long_delta = $distance;
                    OutputWriterLibrary::write_message("Updated to new target " . $object->color . " " . $object->type[0] . " " . $object->name . " with distance of " . number_format($lat_long_delta, 3) . " KM");
                    $acmi->objects[$result->id]->active = TRUE;
                }

            }
        }

        if (!is_null($result)) {
            return $result;
        }

        return new AcmiObject(-1);
    }

    /**
     * @param AcmiObject $object
     */
    protected
    function unregister_misc(AcmiObject $object)
    {
        if (!in_array($object->type[0], ['SHRAPNEL', 'DECOY'])) {
            OutputWriterLibrary::write_message("DELETE: removed misc object with type " . $object->type[0], "magenta");
        }
    }

}
