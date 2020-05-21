<?php
/**
 * File that is prepended to all pages in our "logged_in" direcotry.
 * Ensures that all requests are from a verified source and we are not
 * the target of a fixation attack.
**/

//session_start();

// First, we check to make sure we have a test server login token set:
/*
if (!$_SESSION['login']) {
	header('Location: /index.php');
}
*/

// Then, we check for a session canary, and generate it if it doesn't exist yet
/*if (!isset($_SESSION['canary'])) {
	session_regenerate_id(true);
	$_SESSION['canary'] = time();
}

// Also, regenerate the ID every 5 minutes:
if ($_SESSION['canary'] < time() - 1800) {
	session_regenerate_id(true);
	$_SESSION['canary'] = time();
}
*/
// Set up paths for scripts:
$function_path = __DIR__ . "/functions.php/";

// Could get $uuid here...
