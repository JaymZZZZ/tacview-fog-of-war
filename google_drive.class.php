<?php
/*
 * tacview-fog-of-war
 * ----------------------------
 * A PHP 8.0+ tool to parse ACMI files and implement fog-of-war by removing all enemy objects that have had no interaction
 *
 * @package       tacview-fog-of-war
 * @version       1.0
 * @file          google_drive.class.php
 * @author        JaymZZZZ
 * @copyright     Copyright (c) 2022 James D.
 * @license       This file is part of tacview-fog-of-war - free software licensed under the MIT License
 * @link          https://github.com/JaymZZZZ/tacview-fog-of-war
 *
 * Parser based on https://github.com/kavinsky/tacview-acmi-parser
 */

require __DIR__ . '/vendor/autoload.php';

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

use Google\Client;
use Google\Service\Drive;
use Jaymzzz\Tacviewfogofwar\Libraries\OutputWriterLibrary;


class google_drive
{

    public string $folder_name = "Tacview FOV";

    public int $timeout_seconds = 180;

    public function __construct()
    {
        $this->get_cache_file();
    }

    public function create_folder($folder_name = null, $parent_id = null)
    {


        if (is_null($folder_name)) {
            $folder_name = $this->folder_name;
        }
        $folder_name = $this->clean_file_name($folder_name);

        if ($folder_id = $this->find_folder($folder_name, $parent_id)) {
            OutputWriterLibrary::write_critical_message("Google Drive Directory '" . $folder_name . "' Exists", "blue");
            $this->folder_id = $folder_id;
            return $folder_id;
        } else {
            OutputWriterLibrary::write_critical_message("Creating folder '" . $folder_name . "'");
        }

        try {
            $client = $this->get_client();
            $client->addScope(Drive::DRIVE);
            $drive_service = new Drive($client);

            $file_metadata = new Drive\DriveFile([
                'name' => $folder_name,
                'mimeType' => 'application/vnd.google-apps.folder',
                'appProperties' => [
                    "tacview-fow-id" => $parent_id,
                ]]);

            if (!is_null($parent_id)) {
                $file_metadata->parents = [$parent_id];
            }
            $file = $drive_service->files->create($file_metadata, [
                'fields' => 'id']);
            return $file->id;

        } catch (Exception $e) {
            OutputWriterLibrary::write_critical_message("Google Drive ERROR: " . $e->getMessage(), "red");
            die();
        }
    }

    private function clean_file_name($name): array|string|null
    {
        $name = trim($name);

        return preg_replace('/[^A-Za-z0-9\-._] /', '', $name);
    }

    function find_folder($folder_name, $parent_id = null)
    {
        return $this->find_object($folder_name, 'application/vnd.google-apps.folder', $parent_id);
    }

    function find_object($file_name, $file_type, $folder_id = null)
    {
        try {
            $client = $this->get_client();
            $client->addScope(Drive::DRIVE);
            $drive_service = new Drive($client);
            $page_token = null;
            do {
                $response = $drive_service->files->listFiles([
                    'q' => "mimeType='" . $file_type . "' and name='" . $file_name . "' and trashed=false and appProperties has { key='tacview-fow-id' and value='" . $folder_id . "' }",
                    'spaces' => 'drive',
                    'pageToken' => $page_token,
                    'fields' => 'nextPageToken, files(id, name)',
                ]);
                foreach ($response->files as $file) {
                    return $file->id;
                }

                $page_token = $response->pageToken;
            } while ($page_token != null);
        } catch (Exception $e) {
            OutputWriterLibrary::write_critical_message("Google Drive ERROR: " . $e->getMessage(), "red");
        }
        return null;
    }

    public function get_client()
    {
        try {
            $client = new Client();
            $client->setApplicationName('Tacview-FOW');
            $client->setScopes('https://www.googleapis.com/auth/drive');
            $client->setAuthConfig('credentials.json');
            $client->setAccessType('offline');
            $client->setPrompt('select_account consent');

            $token_path = 'token.json';
            if (file_exists($token_path)) {
                $access_token = json_decode(file_get_contents($token_path), true);
                $client->setAccessToken($access_token);
            }


            if ($client->isAccessTokenExpired()) {
                // Refresh the token if possible, else fetch a new one.
                if ($client->getRefreshToken()) {
                    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                } else {
                    // Request authorization from the user.
                    $auth_url = $client->createAuthUrl();
                    printf("Open the following link in your browser:\n%s\n", $auth_url);
                    print 'Enter verification code: ';
                    $auth_code = trim(fgets(STDIN));

                    $access_token = $client->fetchAccessTokenWithAuthCode($auth_code);
                    $client->setAccessToken($access_token);

                    // Check to see if there was an error.
                    if (array_key_exists('error', $access_token)) {
                        throw new Exception(join(', ', $access_token));
                    }
                }
                // Save the token to a file.
                if (!file_exists(dirname($token_path))) {
                    mkdir(dirname($token_path), 0700, true);
                }
                file_put_contents($token_path, json_encode($client->getAccessToken()));
            }
        } catch (Exception $e) {
            if ($e instanceof InvalidArgumentException) {
                OutputWriterLibrary::write_critical_message("Google Drive ERROR: " . $e->getMessage(), "red");
                OutputWriterLibrary::write_critical_message("Google Drive ERROR: Please create and download a credentials file in order to use Google Drive and name it 'credentials.json'", "red");
                OutputWriterLibrary::write_critical_message("Google Drive ERROR: For information on how to do so, please visit:", "red");
                OutputWriterLibrary::write_critical_message("Google Drive ERROR: https://developers.google.com/workspace/guides/create-credentials#desktop-app", "red");
            }
            OutputWriterLibrary::write_critical_message("Google Drive ERROR: " . $e->getMessage(), "red");
            die();
        }


        return $client;
    }

    public function upload_to_folder($file_name, $local_file_path, $folder_id)
    {

        $file_name = $this->clean_file_name($file_name);


        if ($file_id = $this->find_file($file_name, $folder_id)) {
            OutputWriterLibrary::write_critical_message("Google Drive File '" . $file_name . "' Exists", "blue");
            return $file_id;
        } else {
            OutputWriterLibrary::write_critical_message("Attempting to upload '" . $file_name . "' to Google Drive in directory with ID: " . $folder_id . "...");
        }

        if (str_contains($file_name, "txt")) {
            $mime = "text/plain";
        } else {
            $mime = "application/zip";
        }

        try {
            $client = $this->get_client();
            $client->addScope(Drive::DRIVE);
            $drive_service = new Drive($client);
            $file_metadata = new Drive\DriveFile([
                'name' => $file_name,
                'parents' => array($folder_id),
                'appProperties' => [
                    "tacview-fow-id" => $folder_id,
                ]
            ]);
            $content = file_get_contents($local_file_path);
            $file = $drive_service->files->create($file_metadata, [
                'data' => $content,
                'mimeType' => $mime,
                'uploadType' => 'multipart',
                'fields' => 'id']);

            OutputWriterLibrary::write_critical_message("Uploaded File: '" . $file_name . "' to Folder with ID: '" . $folder_id . "'", "green");
            OutputWriterLibrary::write_critical_message("URL to Upload Directory: https://drive.google.com/drive/u/0/folders/" . $folder_id, "green");
            OutputWriterLibrary::write_critical_message("Sharing Link: https://drive.google.com/file/d/" . $file->id . "/view?usp=sharing", "green");

            return $file->id;
        } catch (Exception $e) {
            OutputWriterLibrary::write_critical_message("Google Drive ERROR: " . $e->getMessage(), "red");
        }
        return null;
    }

    function find_file($file_name, $folder_id = null)
    {
        if (str_contains($file_name, "txt")) {
            $mime = "text/plain";
        } else {
            $mime = "application/zip";
        }
        return $this->find_object($file_name, $mime, $folder_id);
    }

    public function upload_dir($dir, $parent_id = null)
    {

        $this->set_cache_file();
        if (is_null($parent_id)) {
            $parent_id = $this->find_file($this->folder_name);
        }

        foreach ($dir as $key => $file) {
            if (is_array($file)) {
                $sub_dir_id = $this->create_folder($key, $parent_id);
                $this->upload_dir($file, $sub_dir_id);
            } else {
                $this->upload_to_folder(basename($file), $file, $parent_id);

            }
        }
        $this->unset_cache_file();
    }

    public function set_cache_file()
    {
        $array = [
            'timestamp' => time()
        ];

        file_put_contents("cache.json", json_encode($array));
    }

    public function get_cache_file()
    {
        if (is_file("cache.json")) {
            $data = json_decode(file_get_contents("cache.json"));
            if ($data->timestamp && $data->timestamp >= time() - $this->timeout_seconds) {
                $seconds = $data->timestamp + $this->timeout_seconds - time();
                OutputWriterLibrary::write_critical_message("Script may already be running. Please try again in " . $seconds . " seconds...", "red_bg");
                die();
            }
        }
    }

    public function unset_cache_file()
    {
        if (is_file("cache.json")) {
            unlink("cache.json");
        }
    }

}