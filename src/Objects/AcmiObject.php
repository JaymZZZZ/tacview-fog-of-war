<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          AcmiObject.php
 * @author        JaymZZZZ
 * @copyright     Copyright (c) 2022 James D.
 * @license       This file is part of tacview-fog-of-war - free software licensed under the MIT License
 * @link          https://github.com/JaymZZZZ/tacview-fog-of-war
 *
 * Parser based on https://github.com/kavinsky/tacview-acmi-parser
 */

namespace Jaymzzz\Tacviewfogofwar\Objects;

use Illuminate\Contracts\Support\Arrayable;

/**
 *
 */
class AcmiObject implements Arrayable
{
    /**
     * Object ids are expressed using 64-bit hexadecimal numbers (without prefix or leading zeros to save space).
     *
     * @var string
     */
    public string $id;

    /**
     * The object name should use the most common notation for each object.
     * It is strongly recommended to use ICAO or NATO names like: C172 or F/A-18C. This will help Tacview
     * to associate each object with the corresponding entry in its database. Type and Name are the
     * only properties which *CANNOT* be predefined in Tacview database.
     *
     * @var ?string
     */
    public ?string $name = null;

    /**
     * Parent hexadecimal object id. Useful to associate for example a missile
     * (child object) and its launcher aircraft (parent object).
     *
     * @var ?string
     */
    public ?string $parent = null;

    /**
     * Hexadecimal id of the following object. Typically, used to link waypoints together.
     *
     * @var ?string
     */
    public ?string $next = null;
    /**
     * @var string|null
     */
    public ?string $pilot = null;

    /**
     * @var string|null
     */
    public ?string $color = null;
    /**
     * @var string|null
     */
    public ?string $coalition = null;
    /**
     * @var string|null
     */
    public ?string $country = null;
    /**
     * @var array|null
     */
    public ?array $type = null;
    /**
     * @var bool|null
     */
    public ?bool $active = FALSE;
    /**
     * @var AcmiObjectRecord|null
     */
    public ?AcmiObjectRecord $position = null;


    /**
     * @param string $id
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'parent' => $this->parent,
            'pilot' => $this->pilot,
            'color' => $this->color,
            'coalition' => $this->coalition,
            'country' => $this->country,
            'next' => $this->next,
            'type' => $this->type,
            'active' => $this->active,
            'position' => $this->position
        ];
    }
}
