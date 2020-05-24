<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="utf=8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

<!-- Google Font Lookup -->
<link href="https://fonts.googleapis.com/css?family=Cousine&display=swap" rel="stylesheet">

<?php include("./resources.php"); ?>

<!-- Toast UI CDN -->
<link rel="stylesheet" type="text/css" href="https://uicdn.toast.com/tui-calendar/latest/tui-calendar.css" />

<!-- If you use the default popups, use this. -->
<link rel="stylesheet" type="text/css" href="https://uicdn.toast.com/tui.date-picker/latest/tui-date-picker.css" />
<link rel="stylesheet" type="text/css" href="https://uicdn.toast.com/tui.time-picker/latest/tui-time-picker.css" />

<script src="https://uicdn.toast.com/tui.code-snippet/latest/tui-code-snippet.js"></script>
<script src="https://uicdn.toast.com/tui.dom/v3.0.0/tui-dom.js"></script>
<script src="https://uicdn.toast.com/tui.time-picker/latest/tui-time-picker.min.js"></script>
<script src="https://uicdn.toast.com/tui.date-picker/latest/tui-date-picker.min.js"></script>
<script src="https://uicdn.toast.com/tui-calendar/latest/tui-calendar.js"></script>
<!-- End Toast UI -->

<!-- Moment.JS for time/date math... -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.26.0/moment.min.js"></script>

<!-- DataTables for formatting users / requests -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.css">

<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.js"></script>


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
require_once $function_path . 'get_schedule.php';
require_once $function_path . 'get_rooms.php';


// List of all users that are currently registered
$names = get_names();

// Get all requests for the next week:
$requests = get_requests();

// List of available rooms:
$rooms = get_rooms('DBH');

// Find the schedule for the current week:
$prior_week = date('Y-m-d', strtotime('Sunday this week'));
$schedule = get_schedule($prior_week);


// Date for start of next week:
$next_week = date('Y-m-d', strtotime('next sunday'));

//$schedule = [];


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
      <table id="usersTable" class="table">
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
      <table id="requestsTable" class="table">
        <thead>
          <tr>
            <th scope="col">NetID</th>
            <th scope="col">Room Requested</th>
            <th scope="col">Duration Requested</th>
            <th scope="col">Hazardous Materials?</th>
            <th scope="col">Remove Request</th>
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
    <div id="calendar" style="height: 700px; width: 900px"></div>

    </div>
  </div>


<!-- Container -->
</div>
</main>

<script type="text/javascript">
// Removing time requests:
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

// Turn our tables into DataTables
$(document).ready( function() {
  $('#usersTable').DataTable();
  $('#requestsTable').DataTable();
})

<?php // List of colors to use for calendars:
  $txt_colors = ['#00000','#00000','#00000','#ffffff','#ffffff'];
  $bg_colors = ['#feebe2', '#fbb4b9', '#f768a1', '#c51b8a', '#7a0177', '#00a9ff'];
  $i = 0;
?>


function getPadStart(value) {
  value = value.toString();

  return value.padStart(2, '0');
}

const template = {
  timegridDisplayPrimaryTime: function(time) {
            var meridiem = 'am';
            var hour = time.hour;

            if (time.hour > 12) {
                meridiem = 'pm';
                hour = time.hour - 12;
            }
            return time.hour;
            //return hour + ' ' + meridiem;
        },
        timegridDisplayTime: function(time) {
            return getPadStart(time.hour) + ':' + getPadStart(time.hour);
        },
        timegridCurrentTime: function(timezone) {
          var templates = [];

          if (timezone.dateDifference) {
              templates.push('[' + timezone.dateDifferenceSign + timezone.dateDifference + ']<br>');
          }

          templates.push(moment(timezone.hourmarker.toUTCString()).format('HH:mm'));
          return templates.join('');
        },
}

// Calendar instantiation and scheduling
var Calendar = tui.Calendar;
var calendar = new Calendar('#calendar', {
  defaultView: 'week',
  taskView: false,
  useDetailPopup: true,
  week: {
    hourStart: "7",
    hourEnd: "23"
  },
  timezones: [
            {
                timezoneOffset: parseInt(-300),
                tooltip: 'US/Central'
            }],
  //template: template,
  calendars: [
    <?php foreach($rooms as $room) { ?>
      {
        id: "<?php echo htmlspecialchars($room['room_id']); ?>",
        name: "<?php echo htmlspecialchars($room['room_id']); ?>",
        color: "<?php echo htmlspecialchars($txt_colors[$i]); ?>",
        bgColor: "<?php echo htmlspecialchars($bg_colors[$i]); ?>",
        dragBgColor: "<?php echo htmlspecialchars($bg_colors[$i]); ?>",
        borderColor: "<?php echo htmlspecialchars($bg_colors[$i]); ?>",
      },
    <?php $i++; } ?>
  ]
});

// Create a calendar for each room:


// Add our 'events'
calendar.createSchedules([
<?php
$i = 0;
foreach($schedule as $booking) { ?>
  {
    id: <?php echo json_encode($i); ?>,
    calendarId: "<?php echo htmlspecialchars($booking['room_id']); ?>",
    title: "<?php echo htmlspecialchars($booking['name']); ?>",
    category: 'time',
    dueDateClass: '',
    start: "<?php echo htmlspecialchars($booking['start_time']); ?>",
    end: "<?php echo htmlspecialchars($booking['end_time']); ?>",
    attendees: ["<?php echo htmlspecialchars($booking['name'].", ".$booking['netid']); ?>"],
    isReadOnly: true
  },
<?php $i++; } ?>
]);

</script>


</body>

</html>
