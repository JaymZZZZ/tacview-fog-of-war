<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          AcmiWriterFactory.php
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
use Jaymzzz\Tacviewfogofwar\Parser\Handlers\ReadHandlers\SentenceHandlerInterface;
use Jaymzzz\Tacviewfogofwar\Parser\Handlers\WriteHandlers\EventWriteHandler;
use Jaymzzz\Tacviewfogofwar\Parser\Handlers\WriteHandlers\FileHeadersWriteHandler;
use Jaymzzz\Tacviewfogofwar\Parser\Handlers\WriteHandlers\GlobalPropertyWriteHandler;
use Jaymzzz\Tacviewfogofwar\Parser\Handlers\WriteHandlers\ObjectDeletionWriteHandler;
use Jaymzzz\Tacviewfogofwar\Parser\Handlers\WriteHandlers\ObjectWriteHandler;
use Jaymzzz\Tacviewfogofwar\Parser\Handlers\WriteHandlers\SentenceHandlerWriteInterface;
use Jaymzzz\Tacviewfogofwar\Parser\Reader\AcmiReaderInterface;
use Jaymzzz\Tacviewfogofwar\Parser\Reader\AcmiTextReader;
use Jaymzzz\Tacviewfogofwar\Parser\Reader\AcmiZipReader;
use JetBrains\PhpStorm\Pure;

/**
 *
 */
class AcmiWriterFactory
{
    /**
     * @var AcmiReaderInterface[]
     */
    protected array $readers = [];

    /**
     * @var SentenceHandlerWriteInterface[]
     */
    protected ?array $handlers = null;
    /**
     * @var string|null
     */
    protected ?string $writePath = null;

    /**
     * @param string $writePath
     * @param AcmiReaderInterface|array $readers
     * @param SentenceHandlerWriteInterface|array $handlers
     */
    public function __construct(string $writePath, AcmiReaderInterface|array $readers = [], SentenceHandlerWriteInterface|array $handlers = [])
    {
        $this->writePath = $writePath;

        if (is_file($this->writePath) || is_file(str_replace("txt.acmi", "zip.acmi", $this->writePath))) {
            OutputWriterLibrary::write_message("ERROR: File already exists. Exiting...", "red");
        }
        if (is_array($readers) && count($readers) < 1) {
            $readers = static::defaultReaders();
        }

        $this->setReader($readers);

        if (is_array($handlers) && count($handlers) < 1) {
            $handlers = static::defaultHandlers($writePath);
        }

        $this->setHandlers($handlers);
    }

    /**
     * This are the default readers to work with.
     *
     * @return array
     */
    #[Pure] public static function defaultReaders(): array
    {
        return [
            new AcmiZipReader(),
            new AcmiTextReader(),
        ];
    }

    /**
     * Overrides the current registered readers
     *
     * @param AcmiReaderInterface|array<AcmiReaderInterface> $readers
     * @return $this
     */
    public function setReader(AcmiReaderInterface|array $readers): self
    {
        if (!is_array($readers)) {
            $readers = [$readers];
        }

        $this->readers = $readers;

        return $this;
    }

    /**
     * This are the default handlers to work with.
     *
     * @return SentenceHandlerInterface[]
     */
    #[Pure] public static function defaultHandlers(string $writePath): array
    {
        return [
            new FileHeadersWriteHandler($writePath),
            new GlobalPropertyWriteHandler($writePath),
            new ObjectWriteHandler($writePath),
            new ObjectDeletionWriteHandler($writePath),
            new EventWriteHandler($writePath),
        ];
    }

    /**
     * Sets the Handler library
     *
     * @param SentenceHandlerWriteInterface|array<SentenceHandlerWriteInterface> $handlers
     * @return $this
     */
    public function setHandlers(SentenceHandlerWriteInterface|array $handlers): self
    {
        if (!is_array($handlers)) {
            $handlers = [$handlers];
        }

        $this->handlers = $handlers;

        return $this;
    }

    /**
     * @param string $readPath
     * @param Acmi $acmi
     * @throws Exception
     */
    public function parseAndWrite(string $readPath, Acmi $acmi): void
    {
        $reader = $this->getSupportedReader($readPath);

        try {
            $writer = new AcmiWriter($acmi, $reader, $this->handlers);
            $writer->parseAndWriteToFile($readPath, $this->writePath);
        } catch (Exception $e) {
            OutputWriterLibrary::write_message("error in method parseAndWriteToFile", "red");
        }
    }

    /**
     * Gets a supported by the filePath acmi reader.
     *
     * @param string $filePath
     * @return AcmiReaderInterface
     * @throws Exception
     */
    protected function getSupportedReader(string $filePath): AcmiReaderInterface
    {
        foreach ($this->readers as $reader) {
            if ($reader->supports($filePath)) {
                return $reader;
            }
        }

        throw new Exception('No supported readers available to read file: ' . basename($filePath));
    }

    /**
     * Appends a sentence handler to the registry
     *
     * @param SentenceHandlerWriteInterface $handler
     * @return $this
     */
    public function addHandler(SentenceHandlerWriteInterface $handler): self
    {
        array_push($this->handlers, $handler);

        return $this;
    }
}
