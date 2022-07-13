<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          AcmiZipReader.php
 * @author        JaymZZZZ
 * @copyright     Copyright (c) 2022 James D.
 * @license       This file is part of tacview-fog-of-war - free software licensed under the MIT License
 * @link          https://github.com/JaymZZZZ/tacview-fog-of-war
 *
 * Parser based on https://github.com/kavinsky/tacview-acmi-parser
 */

namespace Jaymzzz\Tacviewfogofwar\Parser\Reader;

use Error;
use Exception;
use Jaymzzz\Tacviewfogofwar\Parser\Reader\Exceptions\AccessErrorException;
use ZipArchive;

/**
 *
 */
class AcmiZipReader extends AbstractStreamReader
{
    /**
     * @var ZipArchive
     */
    protected ZipArchive $zipArchive;

    /**
     * {@inheritDoc}
     */
    public function supports(string $filePath): bool
    {
        return str_ends_with($filePath, '.zip.acmi');
    }

    /**
     * {@inheritDoc}
     */
    public function start(string $filePath): void
    {
        if (!in_array('zip', stream_get_wrappers())) {
            throw new Exception('Zip isn\'t supported by your current PHP, is not between stream_get_wrappers().');
        }

        $this->zipArchive = new ZipArchive();

        if (!$this->zipArchive->open($filePath)) {
            throw new Exception($this->zipArchive->getStatusString());
        }

        try {
            $fileName = $this->zipArchive->getNameIndex(0);
            $this->handle = $this->zipArchive->getStream($fileName);
        } catch (Error $e) {
            throw new AccessErrorException($e->getMessage());
        }
    }
}
