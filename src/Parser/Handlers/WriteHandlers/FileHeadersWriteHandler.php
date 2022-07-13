<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          FileHeadersWriteHandler.php
 * @author        JaymZZZZ
 * @copyright     Copyright (c) 2022 James D.
 * @license       This file is part of tacview-fog-of-war - free software licensed under the MIT License
 * @link          https://github.com/JaymZZZZ/tacview-fog-of-war
 *
 * Parser based on https://github.com/kavinsky/tacview-acmi-parser
 */

namespace Jaymzzz\Tacviewfogofwar\Parser\Handlers\WriteHandlers;

use Jaymzzz\Tacviewfogofwar\Acmi;


/**
 *
 */
class FileHeadersWriteHandler implements SentenceHandlerWriteInterface
{
    /**
     * @var string|null
     */
    protected ?string $filename = null;

    /**
     * @param $filename
     */
    public function __construct($filename)
    {
        $this->filename = $filename;
    }

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
    public function handle(string $sentence, Acmi $acmi, $file, float $delta = 0): void
    {
        fwrite($file, $sentence . PHP_EOL);
    }
}
