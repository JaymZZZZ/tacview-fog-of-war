<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          AcmiEventRecord.php
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
class AcmiEventRecord extends AcmiRecord
{
    /**
     * @var string
     */
    public string $name;

    /**
     * @var array
     */
    public array $properties;

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    #[Pure] public function toArray(): array
    {
        return parent::toRecordArray([
            'name' => $this->name,
            'properties' => $this->properties,
        ]);
    }
}
