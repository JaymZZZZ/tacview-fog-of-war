<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          OutputWriterLibrary.php
 * @author        JaymZZZZ
 * @copyright     Copyright (c) 2022 James D.
 * @license       This file is part of tacview-fog-of-war - free software licensed under the MIT License
 * @link          https://github.com/JaymZZZZ/tacview-fog-of-war
 *
 * Parser based on https://github.com/kavinsky/tacview-acmi-parser
 */

namespace Jaymzzz\Tacviewfogofwar\Libraries;

/**
 *
 */
class OutputWriterLibrary
{

    /**
     * @var array|int[]
     */
    protected static array $ANSI_CODES = array(
        "off" => 0,
        "bold" => 1,
        "italic" => 3,
        "underline" => 4,
        "blink" => 5,
        "inverse" => 7,
        "hidden" => 8,
        "black" => 30,
        "red" => 31,
        "green" => 32,
        "yellow" => 33,
        "blue" => 34,
        "magenta" => 35,
        "cyan" => 36,
        "white" => 37,
        "black_bg" => 40,
        "red_bg" => 41,
        "green_bg" => 42,
        "yellow_bg" => 43,
        "blue_bg" => 44,
        "magenta_bg" => 45,
        "cyan_bg" => 46,
        "white_bg" => 47
    );

    /**
     * @param string $message
     * @param string $color
     */
    public static
    function write_message(string $message, $color = "off"): void
    {
        if (isset($_ENV['ENABLE_VERBOSE']) && $_ENV['ENABLE_VERBOSE'] == "false") {
            return;
        }
        echo self::set_color($message, $color) . PHP_EOL;
    }

    /**
     * @param string $message
     * @param string $color
     */
    public static
    function write_critical_message(string $message, $color = "off"): void
    {
        echo self::set_color($message, $color) . PHP_EOL;
    }

    /**
     * @param $message
     * @param null $color
     * @return string
     */
    public static function set_color($message, $color = null): string
    {
        if ($color == "") {
            $color = "off";
        }

        $color_attributes = explode("+", $color);
        $colored_string = "";

        foreach ($color_attributes as $color_attribute) {
            $colored_string .= "\033[" . self::$ANSI_CODES[$color_attribute] . "m";
        }
        $colored_string .= $message . "\033[" . self::$ANSI_CODES["off"] . "m";

        return $colored_string;
    }


}
