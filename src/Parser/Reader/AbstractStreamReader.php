<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          AbstractStreamReader.php
 * @author        JaymZZZZ
 * @copyright     Copyright (c) 2022 James D.
 * @license       This file is part of tacview-fog-of-war - free software licensed under the MIT License
 * @link          https://github.com/JaymZZZZ/tacview-fog-of-war
 *
 * Parser based on https://github.com/kavinsky/tacview-acmi-parser
 */

namespace Jaymzzz\Tacviewfogofwar\Parser\Reader;

use ErrorException;
use Jaymzzz\Tacviewfogofwar\Parser\Reader\Exceptions\AccessErrorException;
use Jaymzzz\Tacviewfogofwar\Parser\Reader\Exceptions\EndOfFileException;
use JetBrains\PhpStorm\Pure;

/**
 *
 */
abstract class AbstractStreamReader implements AcmiReaderInterface
{
    /**
     * @var resource|null
     */
    protected $handle;

    /**
     * @var ?string
     */
    protected ?string $line = null;

    /**
     * @var bool
     */
    protected bool $feof = false;

    /**
     *
     */
    public function __destruct()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }

    /**
     * Gets and moves the pointer to the next sentence
     *
     * @param int $length
     * @return ?string
     */
    public function nextSentence(int $length = 4096): ?string
    {
        if ($this->eof()) {
            return null;
        }

        $sentence = '';

        do {
            $sentence .= $this->nextLine($length) ?? '';
        } while (!$this->eof() && $this->isSentenceIncomplete());

        return trim($sentence);
    }

    /**
     * Checks if the reader is at end of file
     *
     * @return bool
     */
    public function eof(): bool
    {
        return $this->feof || feof($this->handle);
    }

    /**
     * Returns the next line in the pointer
     *
     * @param int $length
     * @return string
     * @throws EndOfFileException|AccessErrorException
     */
    public function nextLine(int $length = 4096): ?string
    {
        if (!$this->eof()) {
            $line = fgets($this->handle, $length);

            if (!$line) {
                $this->feof = true;

                return null;
            }

            if (!$this->line) {
                $bom = pack('CCC', 0xEF, 0xBB, 0xBF);
                $line = str_replace($bom, '', $line);
            }


            $this->line = $line;

            return $this->line;
        }

        return null;
    }

    /**
     * Checks if the sentence is finished, used with "\" at end of line
     *
     * @return bool
     */
    #[Pure] public function isSentenceIncomplete(): bool
    {
        return substr($this->line() ?? '', -2, 2) === AcmiReaderInterface::LINE_SPLIT_CHAR;
    }

    /**
     * Returns the current line in the pointer
     *
     * @return ?string
     */
    public function line(): ?string
    {
        return $this->line;
    }

    /**
     * @param string $fileUrlHandle
     * @return resource
     * @throws AccessErrorException
     */
    protected function makeHandle(string $fileUrlHandle)
    {
        try {
            $handle = fopen($fileUrlHandle, 'r');

            if ($handle === false) {
                throw new AccessErrorException();
            }

            return $handle;
        } catch (ErrorException $e) {
            throw new AccessErrorException();
        }
    }
}
