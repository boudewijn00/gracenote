<?php

error_reporting(E_ALL);

include(dirname( __FILE__ )."/lib/Error.class.php");
include(dirname( __FILE__ )."/lib/HTTP.class.php");
include(dirname( __FILE__ )."/lib/Client.class.php");
include(dirname( __FILE__ )."/lib/Video.class.php");

// You will need a Gracenote Client ID to use this. Visit https://developer.gracenote.com/ for more information.
$clientID  = "12332032"; // Put your Client ID here.
$clientTag = "30EAE524C9797E9EF051B965F103F2E8"; // Put your Client Tag here.


/* You first need to register your client information in order to get a userID.
Best practice is for an application to call this only once, and then cache the userID in
persistent storage, then only use the userID for subsequent API calls. The class will cache
it for just this session on your behalf, but you should store it yourself. */
$client = new Gracenote\WebAPI\Client($clientID, $clientTag); // If you have a userID, you can specify as third parameter to constructor.
$client->register();

// Client object is used to send video meta requests
$video = new Gracenote\WebAPI\Video($client);

$params = array("IMAGE","MEDIAGRAPHY_IMAGES","LINK");
$results = $video->query("Jimmy Fallon","CONTRIBUTOR_SEARCH",$params);
//$params = array("IMAGE","CONTRIBUTOR_IMAGE","VIDEODISCSET","VIDEODISCSET_COVERART","LINK","VIDEODISCSET","LINK","VIDEOPROPERTIES");
//$results = $video->query("238040098-9006FFB633AC73C062297CDB9B5851F7","SERIES_FETCH",$params);

var_dump($results);

?>
