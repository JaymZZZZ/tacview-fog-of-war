<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          AcmiWriter.php
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
use Jaymzzz\Tacviewfogofwar\Parser\Handlers\WriteHandlers\SentenceHandlerWriteInterface;
use Jaymzzz\Tacviewfogofwar\Parser\Reader\AcmiReaderInterface;
use ZipArchive;

/**
 *
 */
class AcmiWriter
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
     * @var SentenceHandlerWriteInterface[]
     */
    protected array $handlers;

    /**
     * @var Acmi
     */
    protected Acmi $acmi;

    /**
     * @var null
     */
    private $file = null;

    /**
     * @param Acmi $acmi
     * @param AcmiReaderInterface $reader
     * @param array $handlers
     */
    public function __construct(Acmi $acmi, AcmiReaderInterface $reader, array $handlers = [])
    {
        $this->reader = $reader;
        $this->handlers = $handlers;
        $this->acmi = $acmi;
    }

    /**
     * @param string $readPath
     * @param string $write_path
     * @return Acmi
     * @throws Exception
     */
    public function parseAndWriteToFile(string $readPath, string $write_path): void
    {
        $this->reader->start($readPath);
        $count = 1;
        $start_time = microtime(true);

        $this->file = fopen($write_path, "w");

        while (!$this->reader->eof()) {
            $sentence = $this->reader->nextSentence();

            if ($sentence === null) {
                break;
            }

            $delta = $this->parseTimeframeChange($sentence, $this->file, 0.0);

            //       if (str_starts_with($sentence, '4109,T=') || str_starts_with($sentence, '-4109')) {
            //             fwrite($this->file, $sentence . PHP_EOL);
            //        }

            $this->runHandlers($sentence, $delta);
            $count++;
            if ($count % 100000 == 0) {
                $end_time = microtime(true);
                OutputWriterLibrary::write_message("WRITING: " . number_format($count, 0) . " records...Memory Usage: " . number_format(memory_get_usage() / (1024 * 1024), 2) . "MB (" . number_format($end_time - $start_time, 2) . " sec)", "yellow");
                $start_time = microtime(true);
            }
        }

        fclose($this->file);


        $zipArchive = new ZipArchive();
        $zip_name = str_replace("txt", "zip", $write_path);
        OutputWriterLibrary::write_message("Creating zip archive: " . $zip_name);
        $zipArchive->open($zip_name, ZipArchive::CREATE);
        $zipArchive->addFile($write_path);
        $zipArchive->close();
        OutputWriterLibrary::write_message("Deleting text file: " . $write_path);
        unlink($write_path);
    }

    /**
     * Parses for the #0.0 sentence to change the timeframe
     *
     * @param string $sentence
     * @param string $write_path
     * @param float $delta
     * @return float
     * @throws Exception
     */
    protected function parseTimeframeChange(string $sentence, $file, float $delta): float
    {
        if (str_starts_with($sentence, '#')) {
            if ($this->acmi->properties->referenceTime === null) {
                throw new Exception('The ACMI Report is not correctly formated, no ReferenceTime set before #timestamp change.');
            }
            fwrite($file, $sentence . PHP_EOL);
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
                $handler->handle($sentence, $this->acmi, $this->file, $delta);
            }
        }
    }
}
