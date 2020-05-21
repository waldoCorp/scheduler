<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="utf=8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

<!-- Google Font Lookup -->
<link href="https://fonts.googleapis.com/css?family=Cousine&display=swap" rel="stylesheet">

<?php include("./resources.php"); ?>

<style>
.tooltip.show {
  opacity: 1 !important;
  filter: alpha(opacity=100);
}

.monospace {
  font-family: 'Cousine', monospace;
}


</style>

<title>Halas Lab Scheduler</title>

</head>

<?php
// Region to set up PHP stuff

require_once $function_path . 'get_names.php';
require_once $function_path . 'get_requests.php';
//require_once $function_path . 'get_schedule.php';
require_once $function_path . 'get_rooms.php';


// List of all users that are currently registered
$names = get_names();

// Get all requests for the next week:
$requests = get_requests();

// List of available rooms:
$rooms = get_rooms('DBH');

// Find the schedule for the current week:
$prior_week = date('Y-m-d', strtotime('last sunday'));
//$schedule = get_schedule($prior_week);


// Date for start of next week:
$next_week = date('Y-m-d', strtotime('next sunday'));

$schedule = [];


?>

<body>

<?php include("header.php"); ?>

<main role="main">

<div class="container">
  <h1>Welcome to the Scheduler!</h1>
  <br>
  <!-- Users -->
  <div class="row">
    <button class="btn btn-outline-secondary btn-sm" type="button" data-toggle="collapse" data-target="#usersDiv" aria-expanded="false" aria-controls="usersDiv">
      Add/Update a User
    </button>
  </div>
  <div class="row">
    <div class="collapse" id="usersDiv">
      <br>
      <form id="userForm" action="endpoints/user_endpoint.php" method="post">
        <div class="input-group">
          <div class="input-group-prepend">
            <span class="input-group-text">NetId</span>
          </div>
          <input type="text" class="form-control" id="netidAdd" name="netid">
          <div class="input-group-prepend">
            <span class="input-group-text">Name</span>
          </div>
          <input type="text" class="form-control" id="username" name="username">
          <div class="input-group-prepend">
            <span class="input-group-text">Time Preference</span>
          </div>
          <select class="form-control" id="timePref" name="time_pref">
            <option value="None">None</option>
            <option value="AM">Mornings</option>
            <option value="PM">Afternoons</option>
          </select>
        </div>
        <br>
        <button type="submit" class="btn btn-secondary" id="userAdd">Add/Update User</button>
      </form>
      <br>
      <h4>Current Users</h4>
      <table class="table">
        <thead>
          <tr>
            <th scope="col">NetID</th>
            <th scope="col">Name</th>
            <th scope="col">Time Preference</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($names as $name) { ?>
            <tr>
              <td><?php echo htmlspecialchars($name['netid']) ?></td>
              <td><?php echo htmlspecialchars($name['name']) ?></td>
              <td><?php echo htmlspecialchars($name['time_pref']) ?></td>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
  <br>
  <br>
  <!-- Requests -->
  <div class="row">
    <button class="btn btn-outline-secondary btn-sm" type="button" data-toggle="collapse" data-target="#requestsDiv" aria-expanded="false" aria-controls="requestsDiv">
      Request Time
    </button>
  </div>
  <div class="row">
    <div class="collapse" id="requestsDiv">
      <br>
      <form id="requestForm" action="endpoints/request_endpoint.php" method="post">
        <div class="input-group">
          <div class="input-group-prepend">
            <span class="input-group-text">NetId</span>
          </div>
          <select class="form-control" id="netidRequest" name="netid">
            <?php foreach($names as $name) { ?>
              <option value="<?php echo htmlspecialchars($name['netid']) ?>">
              <?php echo htmlspecialchars($name['netid']) ?></option>
            <?php } ?>
          </select>
          <div class="input-group-prepend">
            <span class="input-group-text">Room</span>
          </div>
          <select class="form-control" id="roomRequest" name="room">
            <?php foreach($rooms as $room) { ?>
              <option value="<?php echo htmlspecialchars($room['room_id']) ?>">
                <?php echo htmlspecialchars($room['room_id']) ?></option>
            <?php } ?>
          </select>
          <div class="input-group-prepend">
            <span class="input-group-text">Duration (hours)</span>
          </div>
          <input type="number" class="form-control" id="durationRequest" min="0" step="0.25" value="1" name="duration">
        </div>
        <br>
        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="hazard" name="hazardous" checked>
          <label class="form-check-label" for="hazard">Hazardous Activities?</label>
        </div>
        <br>
        <button type="submit" class="btn btn-secondary" id="requestSubmit">Submit Request</button>
      </form>

      <br>
      <br>
      <h4>Requests for the week of <?php echo htmlspecialchars($next_week) ?></h4>
      <table class="table">
        <thead>
          <tr>
            <th scope="col">NetID</th>
            <th scope="col">Room Requested</th>
            <th scope="col">Duration Requested</th>
            <th scope="col">Hazardous Materials?</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($requests as $request) { ?>
              <tr>
                <td class="netid"><?php echo htmlspecialchars($request['netid']) ?></td>
                <td class="room_id"><?php echo htmlspecialchars($request['room_id']) ?></td>
                <td class="duration"><?php echo htmlspecialchars($request['duration']) ?></td>
                <td class="hazardous"><?php echo ($request['hazardous'] ? 'Yes' : 'No') ?></td>
                <td><input type="button" class="btn btn-outline-danger deleteRequest" value="&#x274C;"></td>
              </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
  <br>
  <br>
  <!-- Current Schedule -->
  <div class="row">
    <button class="btn btn-outline-secondary btn-sm" type="button" data-toggle="collapse" data-target="#scheduleDiv" aria-expanded="true" aria-controls="scheduleDiv">
      Current Schedule
    </button>
  </div>
  <div class="row">
    <div class="collapse show" id="scheduleDiv">
    <br>
    <h4>Schedule for the week of <?php echo htmlspecialchars($prior_week) ?></h4>
      <table class="table">
        <thead>
          <tr>
            <th scope="col">NetID</th>
            <th scope="col">Room</th>
            <th scope="col">Time Slot</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($schedule as $schedule) { ?>
            <tr>
              <td><?php echo htmlspecialchars($request['netid']) ?></td>
              <td><?php echo htmlspecialchars($request['room_id']) ?></td>
              <td><?php echo htmlspecialchars($request['time_slot']) ?></td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>


<!-- Container -->
</div>
</main>

<script type="text/javascript">

$('.deleteRequest').click(function() {
  var row = $(this).closest("tr");
  var netid = row.find(".netid").html();
  var room = row.find(".room_id").html();
  var duration = row.find(".duration").html();
  var hazardous = row.find(".hazardous").html();

  deleteRequest(netid,room,duration,hazardous);
  row.fadeOut();

});

function deleteRequest(netid,room,duration,hazardous) {
  // AJAX Request here
  var data = {"action":'deleteRequest', "netid":netid, "room":room,
              "duration":duration, "hazardous":hazardous};

  // AJAX Request here
  $.ajax({
    type: "POST",
    dataType: "json",
    url: "./endpoints/ajax_endpoint.php",
    data: data,
  });
}
</script>


</body>

</html>
