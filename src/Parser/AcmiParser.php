<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          AcmiParser.php
 * @author        JaymZZZZ
 * @copyright     Copyright (c) 2022 James D.
 * @license       This file is part of tacview-fog-of-war - free software licensed under the MIT License
 * @link          https://github.com/JaymZZZZ/tacview-fog-of-war
 *
 * Parser based on https://github.com/kavinsky/tacview-acmi-parser
 */

namespace Jaymzzz\Tacviewfogofwar\Parser;

use Exception;
use Jaymzzz\Tacviewfogofwar\Acmi;
use Jaymzzz\Tacviewfogofwar\Libraries\OutputWriterLibrary;
use Jaymzzz\Tacviewfogofwar\Parser\Handlers\ReadHandlers\FileHeadersHandler;
use Jaymzzz\Tacviewfogofwar\Parser\Handlers\ReadHandlers\GlobalPropertyHandler;
use Jaymzzz\Tacviewfogofwar\Parser\Handlers\ReadHandlers\SentenceHandlerInterface;
use Jaymzzz\Tacviewfogofwar\Parser\Reader\AcmiReaderInterface;

/**
 *
 */
class AcmiParser
{
    /**
     * The registered Reader
     *
     * @var AcmiReaderInterface
     */
    protected AcmiReaderInterface $reader;


    /**
     * SentenceHandlers
     *
     * @var SentenceHandlerInterface[]
     */
    protected array $handlers;

    /**
     * @param AcmiReaderInterface $reader
     * @param array $handlers
     */
    public function __construct(AcmiReaderInterface $reader, array $handlers = [])
    {
        $this->reader = $reader;
        $this->handlers = $handlers;
    }

    /**
     * @param string $filePath
     * @return Acmi
     * @throws Exception
     */
    public function parseFromFile(string $filePath): Acmi
    {
        $this->reader->start($filePath);
        $count = 1;
        $this->acmi = new Acmi();
        $delta = 0.0;
        while (!$this->reader->eof()) {
            $sentence = $this->reader->nextSentence();

            if ($sentence === null) {
                break;
            }

            $delta = $this->parseTimeframeChange($sentence, $delta);

            $this->runHandlers($sentence, $delta);
            $count++;
            if ($count % 100000 == 0) {
                OutputWriterLibrary::write_message(number_format($count, 0) . " records...Memory Usage: " . number_format(memory_get_usage() / (1024 * 1024), 2) . "MB", "yellow");
            }
        }
        OutputWriterLibrary::write_message(number_format($count, 0) . " records...Memory Usage: " . number_format(memory_get_usage() / (1024 * 1024), 2) . "MB", "yellow");


        return $this->acmi;
    }

    /**
     * Parses for the #0.0 sentence to change the timeframe
     *
     * @param string $sentence
     * @param float $delta
     * @return float
     * @throws Exception
     */
    protected function parseTimeframeChange(string $sentence, float $delta = 0.0): float
    {
        if (str_starts_with($sentence, '#')) {
            if ($this->acmi->properties->referenceTime === null) {
                throw new Exception('The ACMI Report is not correctly formated, no ReferenceTime set before #timestamp change.');
            }

            return (float)substr($sentence, 1, strlen($sentence));
        }

        return $delta;
    }

    /**
     * Executes the registered handlers over the given sentence
     *
     * @param string|null $sentence The sentence to process by the handlers
     * @param float $delta Delta in seconds from the mission referenceTime.
     */
    protected function runHandlers(?string $sentence, float $delta = 0): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler->matches($sentence)) {
                $handler->handle($sentence, $this->acmi, $delta);
            }
        }
    }

    /**
     * @param string $filePath
     * @return Acmi
     */
    public function parseGlobalPropertiesFromFile(string $filePath): Acmi
    {
        $this->reader->start($filePath);
        $count = 1;
        $this->acmi = new Acmi();
        $delta = 0.0;
        while (!$this->reader->eof()) {
            $sentence = $this->reader->nextSentence();
            $global_property = new GlobalPropertyHandler();
            $header_property = new FileHeadersHandler();

            if ($sentence === null) {
                break;
            }

            if (!$global_property->matches($sentence) && !$header_property->matches($sentence)) {
                break;
            }

            $this->runHandlers($sentence, $delta);
            $count++;
            if ($count % 100000 == 0) {
                OutputWriterLibrary::write_message(number_format($count, 0) . " records...Memory Usage: " . number_format(memory_get_usage() / (1024 * 1024), 2) . "MB", "yellow");
            }
        }
        OutputWriterLibrary::write_message(number_format($count, 0) . " records...Memory Usage: " . number_format(memory_get_usage() / (1024 * 1024), 2) . "MB", "yellow");


        return $this->acmi;
    }
}
