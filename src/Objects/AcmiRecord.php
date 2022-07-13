<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          AcmiRecord.php
 * @author        JaymZZZZ
 * @copyright     Copyright (c) 2022 James D.
 * @license       This file is part of tacview-fog-of-war - free software licensed under the MIT License
 * @link          https://github.com/JaymZZZZ/tacview-fog-of-war
 *
 * Parser based on https://github.com/kavinsky/tacview-acmi-parser
 */

namespace Jaymzzz\Tacviewfogofwar\Objects;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;
use JetBrains\PhpStorm\ArrayShape;

/**
 *
 */
abstract class AcmiRecord implements Arrayable
{
    /**
     * @var CarbonImmutable
     */
    private CarbonImmutable $timestamp;

    /**
     * @param CarbonImmutable $timestamp
     */
    public function setTimeframe(CarbonImmutable $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    /**
     * Returns when the Record happens
     *
     * @return CarbonImmutable
     */
    public function timestamp(): CarbonImmutable
    {
        return $this->timestamp;
    }

    /**
     *
     * @psalm-mutation-free
     * @param array $properties
     * @return array
     */
    #[ArrayShape(['recordType' => "string", 'properties' => "array"])] protected function toRecordArray(array $properties = []): array
    {
        return [
            'recordType' => self::class,
            'properties' => $properties,
        ];
    }
}
