<?php


// Endpoint for adding a request for a room


require_once $function_path . 'add_new_request.php';
$return = $_POST;
$netid = $return['netid'];
$room = $return['room'];
$duration = $return['duration'] . ' hour';

if( is_null($return['hazardous']) ) {
  $hazardous = 0;
} else {
  $hazardous = 1;
}

add_new_request($netid, $room, $duration, $hazardous);

header('Location: ../index.php');
