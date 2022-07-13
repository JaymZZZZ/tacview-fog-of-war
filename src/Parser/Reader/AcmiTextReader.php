<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          AcmiTextReader.php
 * @author        JaymZZZZ
 * @copyright     Copyright (c) 2022 James D.
 * @license       This file is part of tacview-fog-of-war - free software licensed under the MIT License
 * @link          https://github.com/JaymZZZZ/tacview-fog-of-war
 *
 * Parser based on https://github.com/kavinsky/tacview-acmi-parser
 */

namespace Jaymzzz\Tacviewfogofwar\Parser\Reader;

/**
 *
 */
class AcmiTextReader extends AbstractStreamReader
{
    /**
     * @param string $filePath
     * @return bool
     */
    public function supports(string $filePath): bool
    {
        return str_ends_with($filePath, '.txt.acmi');
    }

    /**
     * @throws Exceptions\AccessErrorException
     */
    public function start(string $filePath): void
    {
        $this->handle = $this->makeHandle('file://' . $filePath);
    }
}
