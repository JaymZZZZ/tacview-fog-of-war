<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          GlobalPropertyHandler.php
 * @author        JaymZZZZ
 * @copyright     Copyright (c) 2022 James D.
 * @license       This file is part of tacview-fog-of-war - free software licensed under the MIT License
 * @link          https://github.com/JaymZZZZ/tacview-fog-of-war
 *
 * Parser based on https://github.com/kavinsky/tacview-acmi-parser
 */

namespace Jaymzzz\Tacviewfogofwar\Parser\Handlers\ReadHandlers;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeZone;
use Exception;
use Illuminate\Support\Str;
use Jaymzzz\Tacviewfogofwar\Acmi;
use Jaymzzz\Tacviewfogofwar\Enum\AcmiDateFormat;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionUnionType;

/**
 *
 */
class GlobalPropertyHandler implements SentenceHandlerInterface
{
    /**
     * @var array|null
     */
    protected ?array $matches = null;

    /**
     * @inheritDoc
     */
    public function matches(string $sentence): bool
    {
        return preg_match('/^0,(\w*)=(.*)/is', $sentence, $this->matches) > 0;
    }

    /**
     * @inheritDoc
     */
    public function handle(string $sentence, Acmi $acmi, float $delta = 0): void
    {
        if ($this->matches === null) {
            return;
        }

        [, $propertyKey, $value] = $this->matches;
        $propertyKey = Str::camel($propertyKey);

        try {
            $acmiReflectionClass = new ReflectionClass($acmi->properties);
            $acmiProperty = $acmiReflectionClass->getProperty($propertyKey);

            if ($this->isPropertyTypeScalar($acmiProperty->getType())) {
                if ($acmiProperty->getType() instanceof ReflectionNamedType) {
                    $value = match ($acmiProperty->getType()->getName()) {
                        default => stripcslashes((string)$value),
                        'float' => (float)$value,
                        'int' => (int)$value,
                    };
                }

                $this->setGlobalProperty($acmi, $propertyKey, $value);

                return;
            }

            if ($this->isPropertyTypeDateTimeInterface($acmiProperty->getType())) {
                $this->setGlobalProperty($acmi, $propertyKey, $this->makeDate($value));
            }
        } catch (ReflectionException $e) {
        }
    }

    /**
     * Checks if a propertyType is a Scalar Value
     *
     * @param ReflectionUnionType|ReflectionNamedType|null $type
     * @return bool
     */
    protected function isPropertyTypeScalar(ReflectionUnionType|ReflectionNamedType|null $type): bool
    {
        return in_array($type?->getName(), ['string', 'int', 'float']);
    }

    /**
     * Sets a ACMI Global Property by Key and Value
     *
     * @param Acmi $acmi
     * @param string $key
     * @param mixed $value
     */
    protected function setGlobalProperty(Acmi $acmi, string $key, mixed $value): void
    {
        $acmi->properties->{$key} = $value;
    }

    /**
     * Checks if a propertyType is a DateTimeInterface
     *
     * @param ReflectionUnionType|ReflectionNamedType|null $type
     * @return bool
     */
    private function isPropertyTypeDateTimeInterface(ReflectionUnionType|ReflectionNamedType|null $type): bool
    {
        return in_array($type?->getName(), [CarbonImmutable::class]);
    }

    /**
     * Creates a Carbon Date parsing from posible formats
     *
     * @param string $value
     * @return CarbonInterface|null
     */
    private function makeDate(string $value): ?CarbonInterface
    {
        foreach (AcmiDateFormat::all() as $format) {
            try {
                $date = CarbonImmutable::createFromFormat(
                    $format,
                    $value,
                    new DateTimeZone('UTC')
                );

                if ($date) {
                    return $date;
                }
            } catch (Exception $e) {
                // no op
            }
        }

        return null;
    }
}
