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
    private string $input_directory = __DIR__ . "/Tacview";
    /**
     * set the output directory as a relative file path
     * @var string
     */
    public string $output_directory = __DIR__ . "/Output";

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
     * @var string|void
     */
    private $output_name;

    private $file_name;

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
    public function run_recursive()
    {
        if (isset($_ENV['ENABLE_VERBOSE']) && $_ENV['ENABLE_VERBOSE'] == "false") {
            OutputWriterLibrary::write_critical_message("VERBOSE MODE DISABLED: More detailed messaging will be skipped", "magenta_bg");
        }
        $files = $this->scan_input_directory();
        $this->run($files);

        $execution_time = (microtime(true) - $this->total_start_time);

        OutputWriterLibrary::write_critical_message("TOTAL EXECUTION TIME: " . number_format($execution_time, 3) . " sec", "cyan_bg");

    }

    function run($dir)
    {
        foreach ($dir as $file) {
            if (is_array($file)) {
                $this->run($file);
            } else {
                if ($this->is_zip($file) || $this->is_txt($file)) {
                    $this->file_name = $file;
                    $this->output_name = $this->generate_output_filename($file);

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
        }

    }

    /**
     *
     */
    public function read_only_run()
    {
        $_ENV['READ_ONLY'] = "true";
        OutputWriterLibrary::write_critical_message("READ ONLY MODE ENABLED: This is a read-only run. Nothing will be changed", "magenta_bg");

        $this->run_recursive();
    }

    /**
     *
     */
    public function dry_run()
    {
        $_ENV['DRY_RUN'] = "true";
        OutputWriterLibrary::write_critical_message("DRY RUN ENABLED: This is a dry run. Nothing will be changed", "magenta_bg");

        $this->run_recursive();
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
    private function scan_input_directory(): array
    {

        return $this->scan_directory($this->input_directory);
    }

    /**
     * Scan the output directory for ACMI files. Return list of files.
     */
    public function scan_output_directory(): array
    {
        return $this->scan_directory($this->output_directory);
    }

    private function scan_directory($dir): array
    {
        $dirs = [];

        foreach (new DirectoryIterator($dir) as $fileInfo) {
            if ($fileInfo->isDot()) continue;
            if ($fileInfo->isDir()) {
                $dirs[$fileInfo->getFilename()] = $this->scan_directory($dir . DIRECTORY_SEPARATOR . $fileInfo->getFilename());
            } else if ($this->is_txt($fileInfo->getFilename()) || $this->is_zip($fileInfo->getFilename())) {
                $dirs[] = $fileInfo->getPath() . DIRECTORY_SEPARATOR . $fileInfo->getFilename();
            }
        }

        return $dirs;
    }

    /**
     * Verify whether the file is a ZIP file.
     * @return bool|int
     */
    private function is_zip($file_name): bool|int
    {
        return strpos($file_name, "zip.acmi");
    }

    /**
     * Verify whether the file is a TXT file.
     * @return bool|int
     */
    private function is_txt($file_name): bool|int
    {
        return strpos($file_name, "txt.acmi");
    }

    /**
     * Generate the output name based on author information within the ACMI file
     * @return string|void
     */
    private function generate_output_filename($input_file)
    {

        if (!$this->is_txt($input_file) && !$this->is_zip($input_file)) {
            OutputWriterLibrary::write_critical_message("Invalid filename selected...", "red");
            die();
        }

        $stripped_filename = $this->get_stripped_filename($input_file);
        OutputWriterLibrary::write_critical_message("Input file :" . $input_file . "...", "green");

        $output_file = $stripped_filename . "_fog_of_war.txt.acmi";
        OutputWriterLibrary::write_critical_message("Output file :" . $output_file . "...", "yellow");

        if (!is_dir(dirname($output_file))) {
            mkdir(dirname($output_file), 0755, true);
        }

        return $output_file;

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
            if (!$this->is_valid_acmi()) {
                OutputWriterLibrary::write_critical_message("Not a valid ACMI file. Skipping..\r\n", "red");
                return;
            }
            $active = $this->acmi_parser->active_objects;
        } catch (Exception $e) {
            OutputWriterLibrary::write_critical_message("Error: " . $e->getMessage(), "red");
            exit();
        }

        OutputWriterLibrary::write_critical_message("+++++++++++++++++++++++++++++++++++", "magenta");
        OutputWriterLibrary::write_critical_message("ACTIVE OBJECTS: " . count($active) . "", "green");

        $execution_time = (microtime(true) - $start_time);

        OutputWriterLibrary::write_critical_message("File read time: " . number_format($execution_time, 3) . " sec", "cyan_bg");
    }

    /**
     * Main writing function
     */
    private
    function write_acmi_output()
    {

        if ($this->output_file_exists($this->output_name) || !$this->is_valid_acmi()) {
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
        if ($author || $author == "") {
            return null;
        }
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

    private
    function is_valid_acmi()
    {

        $properties = $this->acmi_parser->properties;

        if (is_null($properties->referenceTime) || is_null($properties->delta) || is_null($properties->dataRecorder) || is_null($properties->dataSource)) {
            return false;
        }

        return true;
    }

}