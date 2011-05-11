<?php
/**
* quickreport.php
*
* This page is for users to report a loss of a 1v1 match
* the player just needs to input who he conceided to loss to.
*
*/
require_once("../../class2.php");
require_once(e_PLUGIN."ebattles/include/main.php");
require_once(e_PLUGIN."ebattles/include/clan.php");
require_once(HEADERF);

$text = '';

/* Ladder Name */
$ladder_id = $_GET['LadderID'];

if ( (!isset($_POST['quicklossreport'])) || (!isset($_GET['LadderID'])))
{
    $text .= '<br />'.EB_MATCHQL_L2.'<br />';
    $text .= '<br />'.EB_MATCHQL_L3.' [<a href="'.e_PLUGIN.'ebattles/ladderinfo.php?LadderID='.$ladder_id.'">'.EB_MATCHQL_L4.'</a>]<br />';
}
else
{
    $text .= EB_MATCHQL_L5;

   	$ladder = new Ladder($ladder_id);

    $q = "SELECT ".TBL_PLAYERS.".*"
    ." FROM ".TBL_PLAYERS.", "
	.TBL_GAMERS
    ." WHERE (".TBL_PLAYERS.".Ladder = '$ladder_id')"
	."   AND (".TBL_PLAYERS.".Gamer = ".TBL_GAMERS.".GamerID)"
    ."   AND (".TBL_GAMERS.".User = '".USERID."')";
    $result = $sql->db_Query($q);
    $uteam = mysql_result($result,0 , TBL_PLAYERS.".Team");
    
    $q = "SELECT ".TBL_PLAYERS.".*, "
    .TBL_USERS.".*"
    ." FROM ".TBL_PLAYERS.", "
	.TBL_GAMERS.", "
    .TBL_USERS
    ." WHERE (".TBL_PLAYERS.".Ladder = '$ladder_id')"
    ."   AND (".TBL_PLAYERS.".Banned != 1)"
	."   AND (".TBL_PLAYERS.".Gamer = ".TBL_GAMERS.".GamerID)"
    ."   AND (".TBL_USERS.".user_id = ".TBL_GAMERS.".User)"
    ." ORDER BY ".TBL_USERS.".user_name";
    $result = $sql->db_Query($q);
    $num_rows = mysql_numrows($result);

    $text .= '
    <div class="spacer">
    ';

    $text .= '<form action="'.e_PLUGIN.'ebattles/matchprocess.php" method="post">';
    $text .= '
    <table>
    <tr>
    <td>
    Player:
    <select class="tbox" name="Player">
    ';

    for($i=0; $i<$num_rows; $i++)
    {
        $pid  = mysql_result($result,$i, TBL_PLAYERS.".PlayerID");
        $puid  = mysql_result($result,$i, TBL_USERS.".user_id");
        $prank  = mysql_result($result,$i, TBL_PLAYERS.".Rank");
       	$gamer_id = mysql_result($result,$i, TBL_PLAYERS.".Gamer");
       	$gamer = new SC2Gamer($gamer_id);
       	$pname = $gamer->getGamerName();
        $pteam  = mysql_result($result,$i, TBL_PLAYERS.".Team");
        list($pclan, $pclantag, $pclanid) = getClanInfo($pteam);

        if(($puid != USERID)&&(($uteam == 0)||($uteam != $pteam)))
        {
            if ($prank==0)
            $prank_txt = EB_LADDER_L54;
            else
            $prank_txt = "#$prank";
            $text .= '<option value="'.$pid.'">'.$pclantag.$pname.' ('.$prank_txt.')</option>';
        }
    }

    $text .= '
    </select>
    </td>
    </tr>
    <tr>
    <td>
    ';

    $reported_by = USERID;
    $text .= '<div>';
    $text .= '<input type="hidden" name="LadderID" value="'.$ladder_id.'"/>';
    $text .= '<input type="hidden" name="reported_by" value="'.$reported_by.'"/>';

    $text .= '
    </div>
    '.ebImageTextButton('qrsubmitloss', 'flag_red.png', EB_MATCHQL_L6).'
    </td>
    </tr>
    </table>
    </form>
    </div>
    ';
}

$ns->tablerender($ladder->getField('Name')." ($egame - ".ladderTypeToString($ladder->getField('Type')).") - ".EB_MATCHQL_L1, $text);
require_once(FOOTERF);
exit;
?>
