<?php

namespace Gracenote\WebAPI;

// Defaults
if (!defined("GN_DEBUG")) { define("GN_DEBUG", false); }

class Video
{
    // Constants
    const BEST_MATCH_ONLY = 0; // Will put API into "SINGLE_BEST" mode.
    const ALL_RESULTS     = 1;
    
    // Supported commands
    const AV_WORK_SEARCH = "AV_WORK_SEARCH";
    const AV_WORK_FETCH = "AV_WORK_FETCH";
    const SERIES_FETCH = "SERIES_FETCH";
    const CONTRIBUTOR_SEARCH = "CONTRIBUTOR_SEARCH";
    
    private $_client = null;

    // Constructor
    public function __construct(Client $client)
    {
        $this->_client = (object) $client;
    }
    
    // Method to query the Gracenote WEBAPI
    public function query($title,$command,Array $params = null)
    {
    
        // Sanity checks
        if ($this->_client->userID === null) { 
             throw new Exception(Error::UNABLE_TO_PARSE_RESPONSE);
        }

        $body = (string) $this->_constructQueryBody($title,$command,$params);
        $data = (string) $this->_constructQueryRequest($body,$command);
        
        return $this->_execute($data);
        
    }

    // Simply executes the query to Gracenote WebAPI
    protected function _execute($data)
    {
        $request = new HTTP($this->_client->apiURL);
        $response = (string) $request->post($data);
        return $this->_parseResponse($response);
    }

    // This will construct the gracenote query, adding in the authentication header, etc.
    protected function _constructQueryRequest($body, $command = "AV_WORK_SEARCH")
    {
        $query = 
            "<QUERIES>
                <LANG>eng</LANG>";
        
        if($command == "AV_WORK_SEARCH"){
            $query .= " <COUNTRY>us</COUNTRY>";
        }
        
        $query .= "<AUTH>
                    <CLIENT>".$this->_client->clientID."-".$this->_client->clientTag."</CLIENT>
                    <USER>".$this->_client->userID."</USER>
                </AUTH>
                <QUERY CMD=\"".$command."\">
                    ".$body."
                </QUERY>
            </QUERIES>";
        
        return $query;
    }

    // Constructs the main request body, including some default options for metadata, etc.
    protected function _constructQueryBody($title,$command,Array $params = null)
    {
        $body = "";
        
        // Set first part of body containing the TEXT TYPE, or GN_ID
        switch($command)
        {
            case \Gracenote\WebAPI\Video::AV_WORK_SEARCH:
                $body .= "<TEXT TYPE=\"TITLE\">$title</TEXT>";
                break;
            case \Gracenote\WebAPI\Video::AV_WORK_FETCH:
                $body .= "<GN_ID>$title</GN_ID>";
                break;
            case \Gracenote\WebAPI\Video::SERIES_FETCH:
                $body .= "<GN_ID>$title</GN_ID>";
                break;
            case \Gracenote\WebAPI\Video::CONTRIBUTOR_SEARCH:
                $body .= "<TEXT TYPE=\"NAME\">$title</TEXT>";
                break;
        }
        
        // If we have params to set in the query body
        if(count($params) > 0)
        {
            $body .= "<OPTION><PARAMETER>SELECT_EXTENDED</PARAMETER>";
            $body .= "<VALUE>";
            
            $i = 0;
            
            foreach($params AS $param)
            {
                $separator = ($i > 0)? "," : "";
                $body .= $separator.$param;
                $i++;
            }
            
            $body .= "</VALUE>";
            $body .= "</OPTION>";
        }
        
        // If we cant produce a body based on the command, throw an exception
        if(empty($body)){ throw new Exception(Error::INVALID_INPUT_SPECIFIED, $command); }
        
        return $body;
    }

    // Check the response for any Gracenote API errors.
    protected function _checkResponse($response = null)
    {
        // Response is in XML, so attempt to load into a SimpleXMLElement.
        $xml = null;
        try
        {
            $xml = new \SimpleXMLElement($response);
        }
        catch (Exception $e)
        {
            throw new Exception(Error::UNABLE_TO_PARSE_RESPONSE);
        }

        // Get response status code.
        $status = (string) $xml->RESPONSE->attributes()->STATUS;

        // Check for any error codes and handle accordingly.
        switch ($status)
        {
            case "ERROR":    throw new Exception(Error::API_RESPONSE_ERROR, (string) $xml->MESSAGE); break;
            case "NO_MATCH": throw new Exception(Error::API_NO_MATCH); break;
            default:
                if ($status != "OK") { throw new Exception(Error::API_NON_OK_RESPONSE, $status); }
        }

        return $xml;
    }

    // This parses the API response into a PHP Array object.
    protected function _parseResponse($response)
    {
        // Parse the response from Gracenote, check for errors, etc.
        try
        {
            $xml = $this->_checkResponse($response);
            return $xml;
        }
        catch (SAPIException $e)
        {
            throw $e;
        }

       
    }

}
