<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          FileHeadersHandler.php
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
class FileHeadersHandler implements SentenceHandlerInterface
{

    /**
     * {@inheritDoc}
     */
    public function matches(string $sentence): bool
    {
        return str_starts_with($sentence, 'FileType=') || str_starts_with($sentence, 'FileVersion=');
    }

    /**
     * {@inheritDoc}
     */
    public function handle(string $sentence, Acmi $acmi, float $delta = 0): void
    {

        if (str_starts_with($sentence, 'FileType=')) {
            $acmi->file_type = str_replace('FileType=', '', $sentence);
        }


        if (str_starts_with($sentence, 'FileVersion=')) {
            $acmi->version = str_replace('FileVersion=', '', $sentence);
        }
    }
}
