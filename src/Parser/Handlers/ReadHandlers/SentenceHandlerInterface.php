<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          SentenceHandlerInterface.php
 * @author        JaymZZZZ
 * @copyright     Copyright (c) 2022 James D.
 * @license       This file is part of tacview-fog-of-war - free software licensed under the MIT License
 * @link          https://github.com/JaymZZZZ/tacview-fog-of-war
 *
 * Parser based on https://github.com/kavinsky/tacview-acmi-parser
 */

namespace Jaymzzz\Tacviewfogofwar\Parser\Handlers\ReadHandlers;

use Jaymzzz\Tacviewfogofwar\Acmi;

/**
 *
 */
interface SentenceHandlerInterface
{
    /**
     * Checks if the passed sentence is compatible with this handler.
     *
     * @param string $sentence
     * @return bool
     */
    public function matches(string $sentence): bool;

    /**
     * Handles the sentence on the given Acmi class.
     *
     * @param string $sentence
     * @param Acmi $acmi
     */
    public function handle(string $sentence, Acmi $acmi, float $delta = 0): void;

}
