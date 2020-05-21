<?php


/*
 * Endpoint for handling AJAX requests and returning values as needed.
 *
*/

// NEED TO ADD VERIFICATION FOR ALL FUNCTIONS
// i.e. make sure the request is allowed by the user doing the requesting


if (is_ajax()) {
	if (isset($_POST["action"]) && !empty($_POST["action"])) {
		$action = $_POST["action"];
		switch($action) {
			case "deleteRequest": delete_req(); break;
		}
	}
}

// Function to check if a real AJAX request:
function is_ajax() {
	return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}


function delete_req() {
	global $function_path;
	require_once $function_path . 'delete_request.php';
	$return = $_POST;
	$netid = $return['netid'];
	$room = $return['room'];
	$duration = $return['duration'];
	$hazardous = $return['hazardous'];

	delete_request($netid, $room, $duration, $hazardous);
	echo json_encode("Request Deleted");
}
