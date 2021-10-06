<?php
require_once __DIR__ . '\vendor\autoload.php';

class GetSheet
{
    public $service;
    protected $googleAccountKeyFilePath;
    protected $scope;
    protected $client;

    function __construct($googleAccountKeyFilePath)
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $googleAccountKeyFilePath);

        $this->setGoogleAccountKeyFilePath($googleAccountKeyFilePath);
        $this->client = new Google_Client();
        $this->client->useApplicationDefaultCredentials();
        $this->client->addScope('https://www.googleapis.com/auth/spreadsheets');
        $this->service = new Google_Service_Sheets($this->client);
    }

    function setGoogleAccountKeyFilePath($path)
    {
        $this->googleAccountKeyFilePath = $path;
    }

    function setScope($scope)
    {
        $this->scope = $scope;
    }

    protected function getIdFromURL($url)
    {
        $arr = explode('/', $url);
        return $arr[5];
    }

    function getValues($url, $range)
    {
        $response = $this->service->spreadsheets_values->get($this->getIdFromURL($url), $range);
        return $response->getValues();
    }

    function getTitle($url)
    {
        $response = $this->service->spreadsheets->get($this->getIdFromURL($url));
        $prop = $response->getProperties();
        return $prop->getTitle();
    }

    function getNewService()
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $this->googleAccountKeyFilePath);

        $client = new Google_Client();
        $client->useApplicationDefaultCredentials();
        $client->addScope($this->scope);

        $service = new Google_Service_Sheets($client);
        return $service;
    }
}
