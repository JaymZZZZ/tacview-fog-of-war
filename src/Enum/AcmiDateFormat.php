<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          AcmiDateFormat.php
 * @author        JaymZZZZ
 * @copyright     Copyright (c) 2022 James D.
 * @license       This file is part of tacview-fog-of-war - free software licensed under the MIT License
 * @link          https://github.com/JaymZZZZ/tacview-fog-of-war
 *
 * Parser based on https://github.com/kavinsky/tacview-acmi-parser
 */

namespace Jaymzzz\Tacviewfogofwar\Enum;

use MabeEnum\Enum;

/**
 * @psalm-immutable
 */
final class AcmiDateFormat extends Enum
{
    public const NORMAL = 'Y-m-d\TH:i:s\Z';
    public const EXTENDED = 'Y-m-d\TH:i:s.u\Z';

    /**
     *
     * @codeCoverageIgnore
     * @return array<string>
     */
    public static function all(): array
    {
        return [
            AcmiDateFormat::NORMAL,
            AcmiDateFormat::EXTENDED,
        ];
    }
}
