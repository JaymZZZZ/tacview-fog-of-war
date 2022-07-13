<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          AcmiReaderInterface.php
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
interface AcmiReaderInterface
{
    /**
     *
     */
    public const LINE_SPLIT_CHAR = "\\\n";

    /**
     * This method is checked by the Parser in order to see
     * if the given filePath is compatible with the reader.
     *
     * @param string $filePath
     * @return bool
     */
    public function supports(string $filePath): bool;

    /**
     * Starts reading the ACMI File
     *
     * @param string $filePath
     */
    public function start(string $filePath): void;

    /**
     * Returns the next line in the pointer
     *
     * @param int $length
     * @return string|null
     */
    public function nextLine(int $length = 4096): ?string;

    /**
     * Returns the current line in the pointer
     *
     * @return string|null
     */
    public function line(): ?string;

    /**
     * Gets and moves the pointer to the next sentence
     *
     * @param int $length
     * @return string|null
     */
    public function nextSentence(int $length = 4096): ?string;

    /**
     * Checks if the sentence is finished, used with "\" at end of line
     *
     * @return bool
     */
    public function isSentenceIncomplete(): bool;

    /**
     * Checks if the reader is at end of file
     *
     * @return bool
     */
    public function eof(): bool;
}
