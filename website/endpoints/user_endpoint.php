<?php



// Endpoint for user creation, login, logout, and name update


require_once $function_path . 'add_new_user.php';
$return = $_POST;
$name = $return['username'];
$netid = $return['netid'];
$pref = $return['time_pref'];

add_new_user($netid, $name, $pref);

header('Location: ../index.php');
