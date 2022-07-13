<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          AcmiParserFactory.php
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
use Jaymzzz\Tacviewfogofwar\Parser\Handlers\ReadHandlers\FileHeadersHandler;
use Jaymzzz\Tacviewfogofwar\Parser\Handlers\ReadHandlers\GlobalPropertyHandler;
use Jaymzzz\Tacviewfogofwar\Parser\Handlers\ReadHandlers\ObjectDeletionHandler;
use Jaymzzz\Tacviewfogofwar\Parser\Handlers\ReadHandlers\ObjectHandler;
use Jaymzzz\Tacviewfogofwar\Parser\Handlers\ReadHandlers\SentenceHandlerInterface;
use Jaymzzz\Tacviewfogofwar\Parser\Reader\AcmiReaderInterface;
use Jaymzzz\Tacviewfogofwar\Parser\Reader\AcmiTextReader;
use Jaymzzz\Tacviewfogofwar\Parser\Reader\AcmiZipReader;
use JetBrains\PhpStorm\Pure;

/**
 *
 */
class AcmiParserFactory
{
    /**
     * @var AcmiReaderInterface[]
     */
    protected array $readers = [];

    /**
     * @var SentenceHandlerInterface[]
     */
    protected ?array $handlers = null;

    /**
     * @param AcmiReaderInterface|array $readers
     * @param SentenceHandlerInterface|array $handlers
     */
    public function __construct(AcmiReaderInterface|array $readers = [], SentenceHandlerInterface|array $handlers = [])
    {
        if (is_array($readers) && count($readers) < 1) {
            $readers = static::defaultReaders();
        }

        $this->setReader($readers);

        if (is_array($handlers) && count($handlers) < 1) {
            $handlers = static::defaultHandlers();
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
    #[Pure] public static function defaultHandlers(): array
    {
        return [
            new FileHeadersHandler(),
            new GlobalPropertyHandler(),
            new ObjectHandler(),
            new ObjectDeletionHandler()
        ];
    }

    /**
     * Sets the Handler library
     *
     * @param SentenceHandlerInterface|array<SentenceHandlerInterface> $handlers
     * @return $this
     */
    public function setHandlers(SentenceHandlerInterface|array $handlers): self
    {
        if (!is_array($handlers)) {
            $handlers = [$handlers];
        }

        $this->handlers = $handlers;

        return $this;
    }

    /**
     * @param string $filePath
     * @return Acmi
     * @throws Exception
     */
    public function parse(string $filePath): Acmi
    {
        $reader = $this->getSupportedReader($filePath);

        return (new AcmiParser($reader, $this->handlers))
            ->parseFromFile($filePath);
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
     * @param string $filePath
     * @return Acmi
     * @throws Exception
     */
    public function parse_global_properties(string $filePath): Acmi
    {
        $reader = $this->getSupportedReader($filePath);

        return (new AcmiParser($reader, $this->handlers))
            ->parseGlobalPropertiesFromFile($filePath);
    }

    /**
     * Appends a sentence handler to the registry
     *
     * @param SentenceHandlerInterface $handler
     * @return $this
     */
    public function addHandler(SentenceHandlerInterface $handler): self
    {
        array_push($this->handlers, $handler);

        return $this;
    }
}
