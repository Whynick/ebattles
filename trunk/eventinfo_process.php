<?php
/**
* EventInfo_process.php
*
*/
require_once("../../class2.php");
require_once(e_PLUGIN."ebattles/include/main.php");
require_once(e_PLUGIN.'ebattles/include/event.php');
require_once(e_PLUGIN.'ebattles/include/gamer.php');

$event_id = $_GET['eventid'];
$event = new Event($event_id);

if(isset($_POST['quitevent'])){
	$pid = $_POST['player'];

	// Player can quit an event if he has not played yet
	// TODO - can quit if event not started.
	$q = "SELECT ".TBL_PLAYERS.".*"
	." FROM ".TBL_PLAYERS.", "
	.TBL_SCORES
	." WHERE (".TBL_PLAYERS.".PlayerID = '$pid')"
	." AND (".TBL_SCORES.".Player = ".TBL_PLAYERS.".PlayerID)";
	$result = $sql->db_Query($q);
	$nbrscores = mysql_numrows($result);
	if ($nbrscores == 0)
	{
		deletePlayer($pid);
		$event->setFieldDB('IsChanged', 1);
	}
	header("Location: eventinfo.php?eventid=$event_id");
}
if(isset($_POST['checkinevent'])){
	$pid = $_POST['player'];
	checkinPlayer($pid);
	header("Location: eventinfo.php?eventid=$event_id");
}
if(isset($_POST['teamcheckinevent'])){
	$team_id = $_POST['team'];
	checkinTeam($team_id);
	header("Location: eventinfo.php?eventid=$event_id");
}
if(isset($_POST['joinevent'])){
	
	if ($_POST['joinEventPassword'] == $event->getField('password'))
	{
		$Name = $_POST["gamername"];
		$UniqueGameID = $_POST["gameruniquegameid"];
		updateGamer(USERID, $event->getField('Game'), $Name, $UniqueGameID);
		$event->eventAddPlayer(USERID, 0, FALSE);
	}

	header("Location: eventinfo.php?eventid=$event_id");
}
if(isset($_POST['teamjoinevent'])){
	if ($_POST['joinEventPassword'] == $event->getField('password'))
	{
		$div_id = $_POST['division'];
		$event->eventAddDivision($div_id, FALSE);
	}
	header("Location: eventinfo.php?eventid=$event_id");
}
if(isset($_POST['jointeamevent'])){
	$team_id = $_POST['team'];
	$event->eventAddPlayer (USERID, $team_id, FALSE);
	header("Location: eventinfo.php?eventid=$event_id");
}

?>
