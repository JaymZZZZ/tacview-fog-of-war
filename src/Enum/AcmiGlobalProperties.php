<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          AcmiGlobalProperties.php
 * @author        JaymZZZZ
 * @copyright     Copyright (c) 2022 James D.
 * @license       This file is part of tacview-fog-of-war - free software licensed under the MIT License
 * @link          https://github.com/JaymZZZZ/tacview-fog-of-war
 *
 * Parser based on https://github.com/kavinsky/tacview-acmi-parser
 */

namespace Jaymzzz\Tacviewfogofwar\Enum;

use Carbon\CarbonImmutable;

class AcmiGlobalProperties
{
    /**
     * Base time (UTC) for the current mission. (In Game)
     *
     * This time is combined with each frame offset (in seconds) to get the final
     * absolute UTC time for each data sample.
     *
     * @var CarbonImmutable|null
     */
    public ?CarbonImmutable $referenceTime = null;

    /**
     * Recording (file) creation (UTC) time. (Real Time)
     *
     * @var CarbonImmutable|null
     */
    public ?CarbonImmutable $recordingTime = null;

    /**
     * @var float|null
     */
    public ?float $delta = 0.0;

    /**
     * Mission/flight title or designation.
     *
     * @var string|null
     */
    public ?string $title = null;

    /**
     * Software or hardware used to record the data.
     *
     * @var string|null
     */
    public ?string $dataRecorder = null;

    /**
     * Source simulator, control station or file format.
     *
     * @var string|null
     */
    public ?string $dataSource = null;

    /**
     * Author or operator who has created this recording.
     *
     * @var string|null
     */
    public ?string $author = null;

    /**
     * These properties are used to reduce the file size by centering coordinates around a median point.
     * They will be added to each object Longitude and Latitude to get the final coordinates.
     *
     * Unit: deg
     *
     * @var float|null
     */
    public ?float $referenceLongitude = null;

    /**
     * These properties are used to reduce the file size by centering coordinates around a median point.
     * They will be added to each object Longitude and Latitude to get the final coordinates.
     *
     * Unit: deg
     *
     * @var float|null
     */
    public ?float $referenceLatitude = null;

    /**
     * Category of the flight/mission.
     *
     * @var string|null
     */
    public ?string $category = null;

    /**
     * Free text containing the briefing of the flight/mission.
     *
     * @var string|null
     */
    public ?string $briefing = null;

    /**
     * Free text containing the debriefing.
     *
     * @var string|null
     */
    public ?string $debriefing = null;


    /**
     * Free comments about the flight.
     *
     * @var string|null
     */
    public ?string $comments = null;

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'referenceTime' => $this->referenceTime?->toIso8601ZuluString(),
            'recordingTime' => $this->recordingTime?->toIso8601ZuluString(),
            'title' => $this->title,
            'dataRecorder' => $this->dataRecorder,
            'dataSource' => $this->dataSource,
            'author' => $this->author,
            'referenceLongitude' => $this->referenceLongitude,
            'referenceLatitude' => $this->referenceLatitude,
            'category' => $this->category,
            'briefing' => $this->briefing,
            'debriefing' => $this->debriefing,
            'comments' => $this->comments,
        ];
    }
}
