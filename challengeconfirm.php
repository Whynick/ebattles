<?php
/**
* ChallengeRequest.php
*
*/
require_once("../../class2.php");
require_once(e_PLUGIN.'ebattles/include/main.php');
require_once(e_PLUGIN.'ebattles/include/match.php');
require_once(e_PLUGIN."ebattles/include/event.php");
require_once(e_PLUGIN."ebattles/include/clan.php");
require_once(e_PLUGIN."ebattles/include/challenge.php");

/*******************************************************************
********************************************************************/
require_once(HEADERF);

/*
//dbg form
echo "<br>_POST: ";
print_r($_POST);    // show $_POST
echo "<br>_GET: ";
print_r($_GET);     // show $_GET
*/

/* Event Name */
$event_id = $_GET['EventID'];
$challenge_id = $_GET['challengeid'];

$event = new Event($event_id);
$challenge = new Challenge($challenge_id);

$text = '';

if(isset($_POST['challenge_withdraw']))
{
	$challenge->deleteChallenge();
	$text .= EB_EVENT_L69;
	$text .= '<br />';
	$text .= '<br />'.EB_CHALLENGE_L13.' [<a href="'.e_PLUGIN.'ebattles/eventinfo.php?EventID='.$event_id.'">'.EB_CHALLENGE_L14.'</a>]<br />';
}
if(isset($_POST['challenge_confirm']))
{
	$text .= $challenge->ChallengeConfirmForm();
}
if(isset($_POST['challenge_accept']))
{
	// Verify form
	$error_str = '';

	if (!empty($error_str)) {
		// show form again
		$text .= $challenge->ChallengeConfirmForm();

		// errors have occured, halt execution and show form again.
		$text .= '<p style="color:red">'.EB_MATCHR_L14;
		$text .= '<ul style="color:red">'.$error_str.'</ul></p>';
	}
	else
	{
		$challenge->ChallengeAccept();
		$text .= EB_CHALLENGE_L22;
		$text .= '<br />';
		$text .= '<br />'.EB_CHALLENGE_L13.' [<a href="'.e_PLUGIN.'ebattles/eventinfo.php?EventID='.$event_id.'">'.EB_CHALLENGE_L14.'</a>]<br />';
	}
}
if(isset($_POST['challenge_decline']))
{
	$challenge->Challengedecline();
	$text .= EB_EVENT_L69;

	$text .= '<br />';
	$text .= '<br />'.EB_CHALLENGE_L13.' [<a href="'.e_PLUGIN.'ebattles/eventinfo.php?EventID='.$event_id.'">'.EB_CHALLENGE_L14.'</a>]<br />';
}

$ns->tablerender($event->getField('Name')." (".$event->eventTypeToString().") - ".EB_CHALLENGE_L1, $text);
require_once(FOOTERF);
exit;

?>



