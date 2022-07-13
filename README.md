# tacview-fog-of-war

A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction.

This tool will only keep the following:

* Blue and Neutral objects
* Any Red objects that have fired a significant weapon such as:
    * Missiles
    * Rockets
    * Bombs
* Any Red object that was fired upon by Blue including
    * Red air and sea-craft that were hit by A-A or A-G missiles
    * Red ground and sea-craft that were within 50 meters of a dropped weapon

## Installation and Usage Instructions

Installing the scripts is pretty easy and has minimal requirements:

### Requirements

* PHP Composer
* PHP 8.0 or higher

### Installation and Usage

* Download the code repo to a location of your choice
* Place all of the tacview files that you want to process in the `Tacview` directory. The files must be a valid ACMI
  file:
    * zip.acmi
    * txt.acmi
* In the directory, execute the `composer update` command to install the required packages.

### Running in Standalone Mode

* FOR A DRY RUN, execute `php tacview-fow-dry-run.php`
* FOR A REAL RUN, execute `php tacview-fow.php`

### Including in your own project

To run this in your own project simply add the following lines

For a dry run:

```
require_once "./tacview_parser.class.php";

$parser = new tacview_parser(TRUE);
```

For a real run:

```
require_once "./tacview_parser.class.php";

$parser = new tacview_parser();
```

### Notes

* Output files will automatically be created in the `Output` directory.
* File names will include the name of the author of the ACMI file
* These files will not display the "This file has not been altered" message in Tacview for obvious reasons.

## Future Plans and Ideas

* Download Tacview files from a remote server/location
* Provide MD5sum verification that the file was ONLY altered by tacview-fog-of-war in cases where proof may be needed
* Upload the output files to a remote server or Google Drive.