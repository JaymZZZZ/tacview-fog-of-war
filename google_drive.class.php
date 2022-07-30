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

    public function create_folder()
    {

        $this->folder_name = $this->clean_file_name($this->folder_name);

        if ($folder_id = $this->find_folder($this->folder_name)) {
            OutputWriterLibrary::write_critical_message("Google Drive Directory '" . $this->folder_name . "' Exists", "yellow");
            return $folder_id;
        }

        try {
            $client = $this->get_client();
            $client->addScope(Drive::DRIVE);
            $drive_service = new Drive($client);

            $file_metadata = new Drive\DriveFile([
                'name' => $this->folder_name,
                'mimeType' => 'application/vnd.google-apps.folder']);

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
        $name = str_replace(' ', '-', $name);

        return preg_replace('/[^A-Za-z0-9\-._]/', '', $name);
    }

    function find_folder($folder_name)
    {
        return $this->find_object($folder_name, 'application/vnd.google-apps.folder');
    }

    function find_object($file_name, $file_type)
    {
        try {
            $client = $this->get_client();
            $client->addScope(Drive::DRIVE);
            $drive_service = new Drive($client);
            $page_token = null;
            do {
                $response = $drive_service->files->listFiles([
                    'q' => "mimeType='" . $file_type . "' and name='" . $file_name . "' and trashed=false",
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

        if ($file_id = $this->find_file($file_name)) {
            OutputWriterLibrary::write_critical_message("Google Drive File '" . $file_name . "' Exists", "yellow");
            return $file_id;
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
                'parents' => array($folder_id)
            ]);
            $content = file_get_contents($local_file_path);
            $file = $drive_service->files->create($file_metadata, [
                'data' => $content,
                'mimeType' => $mime,
                'uploadType' => 'multipart',
                'fields' => 'id']);

            OutputWriterLibrary::write_critical_message("Uploaded File: '" . $file_name . "' to '" . $this->folder_name . "'", "green");

            return $file->id;
        } catch (Exception $e) {
            OutputWriterLibrary::write_critical_message("Google Drive ERROR: " . $e->getMessage(), "red");
        }
        return null;
    }

    function find_file($file_name)
    {
        if (str_contains($file_name, "txt")) {
            $mime = "text/plain";
        } else {
            $mime = "application/zip";
        }
        return $this->find_object($file_name, $mime);
    }

}