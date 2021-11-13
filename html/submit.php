<?php 
//quickly editable db location
$sqlitefile = "/var/www/html/resources/todolist.db";
$datefile = "/var/www/html/resources/lastupdate.txt";

//all the posted variables
$newitems = json_decode($_POST['newitems']);
$unaltereditems = json_decode($_POST['unaltereditems']);
$deleteditems = json_decode($_POST['deleteditems']);
$newtasks = json_decode($_POST['newtasks']);
$startingorder = json_decode($_POST['startingorder']);
$listorder = json_decode($_POST['listorder']);

//if there is no changes to be made, just exit the script
if ($startingorder == $listorder) {
  return;
}

$date = date("Y-m-d H:i:s");


$insertquery = '';
if ($newtasks) {
  //add new items, each entry is an array of id and text
  $insertquery = 'INSERT INTO todolist_items (id, todoitem, ip, date) VALUES ';
  foreach ($newtasks as $i) {
    //echo "$i[0], $i[1] was added.\n";
    $id = $i[0];
    $safe = SQLite3::escapeString($i[1]);
    $ip = $_SERVER['REMOTE_ADDR'];
    $insertquery .= "($id, '$safe', '$ip', '$date'),";
  }
  $insertquery = substr($insertquery, 0, -1);
}

//delete all rows in the listorder table
$deletequery = 'DELETE FROM listorder';

//add the new listorder to the table
$listquery = 'INSERT INTO listorder (item_id) VALUES ';
foreach ($listorder as $i) {
  $listquery .= "($i),";
}
//removes trailing comma
$listquery = substr($listquery, 0, -1);



//db insertions
$db = new SQLite3($sqlitefile);
if ($insertquery)
  $db->exec($insertquery);
$db->exec($deletequery);
$db->exec($listquery);


//the following is all to prevent multiple refreshes in a short period,
//  as the e-ink screen takes a decent amount of time to refresh (5-20 sec)

//3 cases for the lastupdate file:
// timestamp is in the past: 
//  safe to refresh screen. refresh screen and write timestamp 1 minute in the future
// timestamp is in the future:
//  screen has refreshed recently, but another refresh is not scheduled
//  _at_ the timestamp in the file, refresh the screen
//  _at_ the timestamp in the file, write the _now_ timestamp +2m to signal recent refresh
//  _now_ rewrite lastupdate to SCHEDULED to signal a refresh is scheduled
// timestamp is "SCHEDULED":
//  do nothing. a refresh is scheduled, and at this point the database has been updated

//get the time for the next update
$file = fopen($datefile, 'r');
$fileline = fread($file, 21);
//if file reads: SCHEDULED, return, db has been updated and screen will update soon
//php7 workaround, php8 use str_contains
if (strpos($fileline, "SCHEDULED") !== false)
  return;
$nextavailableupdate = new DateTime($fileline);

//compare that to now
$now = new DateTime('now');
$interval = date_diff($now, $nextavailableupdate);

//if the time to the next update is in the past, update now
if ($interval->format('%R') == '-') {
  //update now
  exec("python3 /var/www/html/resources/main.py");
  //write t+60 to the update file to prevent refreshes for 60s
  $nowinterval = $now->add(new DateInterval('PT1M'));
  $oneminuteformatted = $nowinterval->format('Y-m-d H:i:s');
  exec("echo $oneminuteformatted > /var/www/html/resources/lastupdate.txt");

//else: schedule for update on that time, AND change the next update to 1m from THEN
} else {
  //schedule for update on the next possible update time
  //echo "at now + 1 minute | $scripttime pythonscript.py\n";
  exec("echo \"python3 /var/www/html/resources/main.py\" | at now + 1 minute");
  //make the new possible update time 1m from then
  $nextavailableupdate->add(new DateInterval('PT1M'));
  $scripttime = $nextavailableupdate->format('Y-m-d H:i:s');
  echo "at 1m from now echo the date/time+2m into lastupdate.txt\n";
  exec("echo \"echo $scripttime > /var/www/html/resources/lastupdate.txt\" | at now + 1 minute");
  //prevent updates during the timeframe an update is scheduled
  echo "write SCHEDULED to lastupdate\n";
  exec("echo 'SCHEDULED' > /var/www/html/resources/lastupdate.txt");
}

?>