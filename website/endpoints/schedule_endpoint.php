<?php



// Endpoint for recreating the schedule:


require_once $function_path . 'create_schedule.php';

if (isset($_SESSION['uuid'])) { // To stop bots from triggering schedule calls
  create_schedule();
}

header('Location: ../index.php');
