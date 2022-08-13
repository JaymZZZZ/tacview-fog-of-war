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
    * This distance can be editted in the `.env` file

## Before and After Screenshot

Before (Left) and After (right)
![](Media/before_and_after.png)

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

### Running in Standalone Mode ()

* FOR A FULL RUN, execute `php tacview-fow.php`
* FOR A DRY RUN, execute `php tacview-fow-dry-run.php`
* FOR A READ ONLY RUN, execute `php tacview-fow-read-only.php`
* FOR A LESS VERBOSE RUN, execute `php tacview-fow-less-verbose.php`

### Including in your own project

To run this in your own project simply add the following lines

For a dry run (Will only list the files that will be processed):

```
require_once "./tacview_parser.class.php";

$parser = new tacview_parser();
$parser->dry_run();
```

For a read-only run (ACMI will be read and validated but no output file written):

```
require_once "./tacview_parser.class.php";

$parser = new tacview_parser();
$parser->read_only_run();
```

For a real run:

```
require_once "./tacview_parser.class.php";

$parser = new tacview_parser();
$parser->run();
```

To disable any verbose output:

```
require_once "./tacview_parser.class.php";

$parser = new tacview_parser();
$parser->set_verbose(FALSE);
$parser->run();
```

### Notes

* Output files will automatically be created in the `Output` directory.
* File names will include the name of the author of the ACMI file
* These files will not display the "This file has not been altered" message in Tacview for obvious reasons.

### Notes on Google Drive support

Google drive support will not work but there are certain steps that need to be followed in order to be able to use
Google Drive

* You need to create your own Google Cloud Project
  * (https://developers.google.com/workspace/guides/create-project)
* You need to enable the Google Drive API integrations for your project
  * (https://developers.google.com/workspace/guides/enable-apis)
* You need to create a service account and credentials for the service account
  * (https://developers.google.com/workspace/guides/create-credentials#service-account)

Once this is done you need to do two more steps:

1. Rename the JSON file `credentials.json` and place it in the root directory of this project.
2. Run `tacview-fow-with-google-drive.php` and follow the instructions.

#### Example credentials.json file

```json
{
  "type": "service_account",
  "project_id": "<YOUR_PROJECT_ID>",
  "private_key_id": "<YOUR PRIVATE KEY ID>",
  "private_key": "-----PRIVATE KEY-----",
  "client_email": "tacviewfow@example.iam.gserviceaccount.com",
  "client_id": "<YOUR CLIENT ID>",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url": "https://www.googleapis.com/robot/v1/metadata/x509/tacviewfow%40example.iam.gserviceaccount.com"
}
```

## Future Plans and Ideas

* Download Tacview files from a remote server/location
* Provide MD5sum verification that the file was ONLY altered by tacview-fog-of-war in cases where proof may be needed
