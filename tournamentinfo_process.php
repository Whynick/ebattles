<?php
/**
* EventInfo_process.php
*
*/
require_once("../../class2.php");
require_once(e_PLUGIN."ebattles/include/main.php");
require_once(e_PLUGIN.'ebattles/include/event.php');
require_once(e_PLUGIN.'ebattles/include/gamer.php');

$event_id = $_GET['EventID'];
$event = new Event($event_id);

if(isset($_POST['quitevent'])){
	$pid = $_POST['player'];

	// Player can quit an event if he has not played yet
	// TODO - can quit if event not started.
	$q = "SELECT ".TBL_TPLAYERS.".*"
	." FROM ".TBL_TPLAYERS.", "
	.TBL_SCORES
	." WHERE (".TBL_TPLAYERS.".TPlayerID = '$pid')"
	." AND (".TBL_SCORES.".Player = ".TBL_TPLAYERS.".TPlayerID)";
	$result = $sql->db_Query($q);
	$nbrscores = mysql_numrows($result);

	$nbrscores = 0;
	if ($nbrscores == 0)
	{
		deleteTPlayer($pid);
		$q = "UPDATE ".TBL_EVENTS." SET IsChanged = 1 WHERE (EventID = '$event_id')";
		$result = $sql->db_Query($q);
	}
	header("Location: eventinfo.php?EventID=$event_id");
}
if(isset($_POST['joinevent'])){
	
	if ($_POST['joinEventPassword'] == $event->getField('password'))
	{
		$UniqueGameID = $tp->toDB($_POST["charactername"].'#'.$_POST["code"]);
		updateGamer(USERID, $event->getField('Game'), $UniqueGameID);
		$event->eventAddPlayer(USERID, 0, FALSE);
	}

	header("Location: eventinfo.php?EventID=$event_id");
}
if(isset($_POST['teamjoinevent'])){
	if ($_POST['joinEventPassword'] == $event->getField('password'))
	{
		$div_id = $_POST['division'];
		$event->eventAddDivision($div_id, FALSE);
	}
	header("Location: eventinfo.php?EventID=$event_id");
}
if(isset($_POST['jointeamevent'])){
	$team_id = $_POST['team'];
	$event->eventAddPlayer (USERID, $team_id, FALSE);
	header("Location: eventinfo.php?EventID=$event_id");
}

?>
