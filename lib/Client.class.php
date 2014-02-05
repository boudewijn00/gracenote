<?php

namespace Gracenote\WebAPI;

error_reporting(E_ALL);

// You will need a Gracenote Client ID to use this. Visit https://developer.gracenote.com/ for info.

// Defaults
if (!defined("GN_DEBUG")) { define("GN_DEBUG", false); }

// Dependencies
include(dirname( __FILE__ )."/Error.class.php");
include(dirname( __FILE__ )."/HTTP.class.php");

class Client {
    
    private $_clientID  = null;
    private $_clientTag = null;
    private $_userID    = null;
    private $_apiURL    = "https://[[CLID]].web.cddbp.net/webapi/xml/1.0/";

    // Constructor
    public function __construct($clientID, $clientTag, $userID = null){
        
        // Sanity checks
        if ($clientID === null || $clientID == "")   { throw new Exception(Error::INVALID_INPUT_SPECIFIED, "clientID"); }
        if ($clientTag === null || $clientTag == "") { throw new Exception(Error::INVALID_INPUT_SPECIFIED, "clientTag"); }

        $this->_clientID  = $clientID;
        $this->_clientTag = $clientTag;
        $this->_userID    = $userID;
        $this->_apiURL    = str_replace("[[CLID]]", $this->_clientID, $this->_apiURL);
        
    }

    // Will register your clientID and Tag in order to get a userID. The userID should be stored
    // in a persistent form (filesystem, db, etc) otherwise you will hit your user limit.
    public function register($clientID = null){
        
        // Use members from constructor if no input is specified.
        if ($clientID === null) { $clientID = $this->_clientID."-".$this->_clientTag; }

        // Make sure user doesn't try to register again if they already have a userID in the ctor.
        if ($this->_userID !== null)
        {
            echo "Warning: You already have a userID, no need to register another. Using current ID.\n";
            return $this->_userID;
        }

        // Do the register request
        $request = "<QUERIES>
                       <QUERY CMD=\"REGISTER\">
                          <CLIENT>".$clientID."</CLIENT>
                       </QUERY>
                    </QUERIES>";
        $http = new HTTP($this->_apiURL);
        $response = $http->post($request);
        $response = $this->_checkResponse($response);

        // Cache it locally then return to user.
        $this->_userID = (string)$response->RESPONSE->USER;
        return $this->_userID;
        
    }
    
    // Check the response for any Gracenote API errors.
    protected function _checkResponse($response = null){
        
        // Response is in XML, so attempt to load into a SimpleXMLElement.
        $xml = null;
        try{
            $xml = new \SimpleXMLElement($response);
        } catch (Exception $e){
            throw new Exception(Error::UNABLE_TO_PARSE_RESPONSE);
        }

        // Get response status code.
        $status = (string) $xml->RESPONSE->attributes()->STATUS;

        // Check for any error codes and handle accordingly.
        switch ($status){
            case "ERROR":    throw new Exception(Error::API_RESPONSE_ERROR, (string) $xml->MESSAGE); break;
            case "NO_MATCH": throw new Exception(Error::API_NO_MATCH); break;
            default:
                if ($status != "OK") { throw new Exception(Error::API_NON_OK_RESPONSE, $status); }
        }

        return $xml;
    }
    
}


?>
