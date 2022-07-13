<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          Acmi.php
 * @author        JaymZZZZ
 * @copyright     Copyright (c) 2022 James D.
 * @license       This file is part of tacview-fog-of-war - free software licensed under the MIT License
 * @link          https://github.com/JaymZZZZ/tacview-fog-of-war
 *
 * Parser based on https://github.com/kavinsky/tacview-acmi-parser
 */

namespace Jaymzzz\Tacviewfogofwar;

use Illuminate\Contracts\Support\Arrayable;
use Jaymzzz\Tacviewfogofwar\Collections\AcmiObjectCollection;
use Jaymzzz\Tacviewfogofwar\Enum\AcmiGlobalProperties;
use JetBrains\PhpStorm\ArrayShape;

/**
 * The ACMI Report Object
 *
 * @see https://www.tacview.net/documentation/acmi/en/
 *
 */
class Acmi implements Arrayable
{
    /**
     * Type of ACMI file format
     *
     * @var string|null
     */
    public ?string $file_type = null;
    /**
     * Version of the TacView ACMI file format
     *
     * @var string|null
     */
    public ?string $version = null;

    /**
     * ACMI Global Properties
     *
     * @var AcmiGlobalProperties
     */
    public AcmiGlobalProperties $properties;

    /**
     * @var AcmiObjectCollection
     */
    public AcmiObjectCollection $objects;


    /**
     *
     */
    public function __construct()
    {
        $this->properties = new AcmiGlobalProperties();
        $this->objects = new AcmiObjectCollection();
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    #[ArrayShape(['file_type' => "null|string", 'version' => "null|string", 'properties' => "array", 'objects' => "array"])] public function toArray(): array
    {
        return [
            'file_type' => $this->file_type,
            'version' => $this->version,
            'properties' => $this->properties->toArray(),
            'objects' => $this->toArray(),
        ];
    }
}
