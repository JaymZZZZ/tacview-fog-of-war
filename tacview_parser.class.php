<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          tacview_parser.class.php
 * @author        JaymZZZZ
 * @copyright     Copyright (c) 2022 James D.
 * @license       This file is part of tacview-fog-of-war - free software licensed under the MIT License
 * @link          https://github.com/JaymZZZZ/tacview-fog-of-war
 *
 * Parser based on https://github.com/kavinsky/tacview-acmi-parser
 */

require __DIR__ . '/vendor/autoload.php';


use Dotenv\Dotenv;
use Jaymzzz\Tacviewfogofwar\Acmi;
use Jaymzzz\Tacviewfogofwar\Libraries\OutputWriterLibrary;
use Jaymzzz\Tacviewfogofwar\Parser\AcmiParserFactory;
use Jaymzzz\Tacviewfogofwar\Parser\AcmiWriterFactory;

/**
 *
 */
class tacview_parser
{


    /**
     * Set the input directory as a relative file path
     * @var string
     */
    private string $input_directory = "/Tacview/";
    /**
     * set the output directory as a relative file path
     * @var string
     */
    private string $output_directory = "/Output/";

    /**
     * @var string|null
     */
    private ?string $file_name = null;
    /**
     * @var string|void|null
     */
    private $output_name = null;
    /**
     * @var array
     */
    private array $files = [];

    /**
     * @var AcmiParserFactory
     */
    private AcmiParserFactory $acmi_parser_factory;

    /**
     * @var Acmi
     */
    private Acmi $acmi_parser;

    /**
     * @var AcmiWriterFactory
     */
    private AcmiWriterFactory $acmi_write_factory;

    /**
     * @var float|null
     */
    private ?float $total_start_time;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->total_start_time = microtime(true);
        try {
            $dotenv = Dotenv::createImmutable("./");
            $dotenv->load();
            $dotenv->required('WPN_RADIUS')->isInteger();
        } catch (Exception $e) {
            OutputWriterLibrary::write_critical_message("Error: " . $e->getMessage(), "red");
        }
    }

    /**
     * Main loop
     */
    public function run()
    {
        if (isset($_ENV['ENABLE_VERBOSE']) && $_ENV['ENABLE_VERBOSE'] == "false") {
            OutputWriterLibrary::write_critical_message("VERBOSE MODE DISABLED: More detailed messaging will be skipped", "magenta_bg");
        }
        $this->scan_input_directory();

        foreach ($this->files as $file) {
            $this->file_name = __DIR__ . $this->input_directory . $file;

            if ($this->is_zip() || $this->is_txt()) {
                $this->output_name = $this->generate_output_filename();

                if (isset($_ENV['DRY_RUN']) && $_ENV['DRY_RUN'] == "true") {
                    continue;
                }
                if (isset($_ENV['READ_ONLY']) && $_ENV['READ_ONLY'] == "true") {
                    $this->parse_acmi_file();
                } else {
                    $this->parse_and_write_acmi_file();
                }
            }
        }


        $execution_time = (microtime(true) - $this->total_start_time);

        OutputWriterLibrary::write_critical_message("TOTAL EXECUTION TIME: " . number_format($execution_time, 3) . " sec", "cyan_bg");

    }

    /**
     *
     */
    public function read_only_run()
    {
        $_ENV['READ_ONLY'] = "true";
        OutputWriterLibrary::write_critical_message("READ ONLY MODE ENABLED: This is a read-only run. Nothing will be changed", "magenta_bg");

        $this->run();
    }

    /**
     *
     */
    public function dry_run()
    {
        $_ENV['DRY_RUN'] = "true";
        OutputWriterLibrary::write_critical_message("DRY RUN ENABLED: This is a dry run. Nothing will be changed", "magenta_bg");

        $this->run();
    }

    /**
     *
     */
    public function set_verbose($verbose = FALSE)
    {
        $_ENV['ENABLE_VERBOSE'] = ($verbose == TRUE) ? "true" : "false";
    }

    /**
     * Scan the input directory for ACMI files. Return list of files.
     */
    private function scan_input_directory(): void
    {
        $this->files = array_diff(scandir(__DIR__ . $this->input_directory), array('.', '..'));
    }

    /**
     * Verify whether the file is a ZIP file.
     * @return bool|int
     */
    private function is_zip(): bool|int
    {
        return strpos($this->file_name, "zip.acmi");
    }

    /**
     * Verify whether the file is a TXT file.
     * @return bool|int
     */
    private function is_txt(): bool|int
    {
        return strpos($this->file_name, "txt.acmi");
    }

    /**
     * Generate the output name based on author information within the ACMI file
     * @return string|void
     */
    private function generate_output_filename()
    {

        if (!$this->is_txt() && !$this->is_zip()) {
            OutputWriterLibrary::write_critical_message("Invalid filename selected...", "red");
            die();
        }

        $stripped_filename = $this->get_stripped_filename($this->file_name);
        OutputWriterLibrary::write_critical_message("Input file :" . $this->file_name . "...", "green");
        $output = $stripped_filename . "_fog_of_war.txt.acmi";
        OutputWriterLibrary::write_critical_message("Output file :" . $output . "...", "yellow");

        return $output;

    }

    /**
     * Trim and parse the input file name into an acceptable output name
     * @param $filename
     * @return array|string
     */
    private function get_stripped_filename($filename): array|string
    {
        $filename = str_replace(' ', '-', $filename);
        $filename = preg_replace('/[^A-Za-z0-9.\-\/]/', '', $filename);
        $filename = str_replace(".zip.acmi", '', $filename);
        $filename = str_replace(".txt.acmi", '', $filename);
        $filename = str_replace($this->input_directory, $this->output_directory, $filename);

        return $filename;

    }

    /**
     * Function to both parse and write an output file
     */
    private
    function parse_and_write_acmi_file()
    {

        $this->parse_acmi_file();
        $this->write_acmi_output();

    }

    /**
     * Main parsing function
     */
    private
    function parse_acmi_file()
    {
        $start_time = microtime(true);
        $this->acmi_parser_factory = new AcmiParserFactory();

        $header = $this->acmi_parser_factory->parse_global_properties($this->file_name);

        $author = $this->trim_and_clean_author($header->properties->author);
        OutputWriterLibrary::write_critical_message("File Author: " . $author, "yellow");

        if (isset($_ENV['DRY_RUN']) && isset($_ENV['DRY_RUN']) == "true") {
            OutputWriterLibrary::write_critical_message("This is a Dry run. Skipping..\r\n", "red");
            return;
        }

        if ($this->output_file_exists()) {
            OutputWriterLibrary::write_critical_message("Output file exists. Skipping..\r\n", "red");
            return;
        }
        try {
            $this->acmi_parser = $this->acmi_parser_factory->parse($this->file_name);
            $result = $this->acmi_parser->objects;
            $active = $result->where('active', '=', TRUE);
            $inactive = $result->where('active', '=', FALSE);
        } catch (Exception $e) {
            OutputWriterLibrary::write_critical_message("Error: " . $e->getMessage(), "red");
            exit();
        }

        OutputWriterLibrary::write_critical_message("+++++++++++++++++++++++++++++++++++", "magenta");
        OutputWriterLibrary::write_critical_message("ACTIVE OBJECTS: " . $active->count() . "", "green");
        OutputWriterLibrary::write_critical_message("INACTIVE OBJECTS: " . $inactive->count() . "", "yellow");

        $execution_time = (microtime(true) - $start_time);

        OutputWriterLibrary::write_critical_message("File read time: " . number_format($execution_time, 3) . " sec", "cyan_bg");
    }

    /**
     * Main writing function
     */
    private function write_acmi_output()
    {

        if ($this->output_file_exists()) {
            return;
        }

        $start_time = microtime(true);
        $this->acmi_write_factory = new AcmiWriterFactory($this->output_name);

        try {
            $this->acmi_write_factory->parseAndWrite($this->file_name, $this->acmi_parser);
        } catch (Exception $e) {
            OutputWriterLibrary::write_critical_message("Error: " . $e->getMessage(), "red");
            exit();
        }

        $execution_time = (microtime(true) - $start_time);

        OutputWriterLibrary::write_critical_message("File write time: " . number_format($execution_time, 3) . " sec", "cyan_bg");

    }

    /**
     * Trim and clean up the author name
     * @param $author
     * @return array|string|null
     */
    function trim_and_clean_author($author): array|string|null
    {
        $author = str_replace(' ', '-', $author);

        return preg_replace('/[^A-Za-z0-9\-]/', '', $author);

    }

    /**
     * Verify whether there is already a parsed output file
     * @return bool
     */
    function output_file_exists(): bool
    {
        return is_file($this->output_name);
    }


}