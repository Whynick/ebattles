<?php
/**
* EventMatchs.php
*
*/
require_once("../../class2.php");
require_once(e_PLUGIN."ebattles/include/main.php");
require_once(e_PLUGIN."ebattles/include/paginator.class.php");
require_once(e_PLUGIN."ebattles/include/clan.php");
require_once(e_PLUGIN."ebattles/include/event.php");
require_once(e_PLUGIN."ebattles/include/match.php");

/*******************************************************************
********************************************************************/
require_once(HEADERF);

$pages = new Paginator;

$text ='
<script type="text/javascript" src="./js/tabpane.js"></script>
';

/* Event Name */
$event_id = $_GET['eventid'];

if (!$event_id)
{
    header("Location: ./events.php");
    exit();
}
else
{
    $q = "SELECT ".TBL_EVENTS.".*, "
    .TBL_GAMES.".*"
    ." FROM ".TBL_EVENTS.", "
    .TBL_GAMES
    ." WHERE (".TBL_EVENTS.".eventid = '$event_id')"
    ."   AND (".TBL_EVENTS.".Game = ".TBL_GAMES.".GameID)";

    $result = $sql->db_Query($q);
    $mEventID  = mysql_result($result,0, TBL_EVENTS.".EventID");
    $mEventName  = mysql_result($result,0, TBL_EVENTS.".Name");
    $mEventgame = mysql_result($result,0 , TBL_GAMES.".Name");
    $mEventgameicon = mysql_result($result,0 , TBL_GAMES.".Icon");
    $mEventType  = mysql_result($result,0 , TBL_EVENTS.".Type");
    $mEventAllowScore = mysql_result($result,0 , TBL_EVENTS.".AllowScore");

    $text .= '<div class="tab-pane" id="tab-pane-11">';
    $text .= '<div class="tab-page">';
    $text .= '<div class="tab">'.EB_MATCHS_L1.'</div>';
    $q = "SELECT COUNT(DISTINCT ".TBL_MATCHS.".MatchID) as NbrMatches"
    ." FROM ".TBL_MATCHS.", "
    .TBL_SCORES
    ." WHERE (Event = '$event_id')"
    ." AND (".TBL_MATCHS.".Status = 'active')"
    ." AND (".TBL_SCORES.".MatchID = ".TBL_MATCHS.".MatchID)";
    $result = $sql->db_Query($q);
    $row = mysql_fetch_array($result);
    $nbrmatches = $row['NbrMatches'];
    $text .= '<p>';
    $text .= $nbrmatches.' '.EB_MATCHS_L2;
    $text .= '</p>';
    $text .= '<br />';

    /* set pagination variables */
    $totalItems = $nbrmatches;
    $pages->items_total = $totalItems;
    $pages->mid_range = eb_PAGINATION_MIDRANGE;
    $pages->paginate();

    // Paginate
    $text .= '<span class="paginate" style="float:left;">'.$pages->display_pages().'</span>';
    $text .= '<span style="float:right">';
    // Go To Page
    $text .= $pages->display_jump_menu();
    $text .= '&nbsp;&nbsp;&nbsp;';
    // Items per page
    $text .= $pages->display_items_per_page();
    $text .= '</span><br /><br />';

    /* Stats/Results */
    $q = "SELECT DISTINCT ".TBL_MATCHS.".*"
    ." FROM ".TBL_MATCHS.", "
    .TBL_SCORES
    ." WHERE (".TBL_MATCHS.".Event = '$event_id')"
    ." AND (".TBL_SCORES.".MatchID = ".TBL_MATCHS.".MatchID)"
    ." AND (".TBL_MATCHS.".Status = 'active')"
    ." ORDER BY ".TBL_MATCHS.".TimeReported DESC"
    ." $pages->limit";

    $result = $sql->db_Query($q);
    $num_rows = mysql_numrows($result);
    if ($num_rows>0)
    {
        /* Display table contents */
        $text .= '<table class="table_left">';
        for($i=0; $i<$num_rows; $i++)
        {
            $mID  = mysql_result($result,$i, TBL_MATCHS.".MatchID");
            $text .= displayMatchInfo($mID, 1);
        }
        $text .= '</table>';
    }
    $text .= '<br />';

    $text .= '<p>';
    $text .= EB_MATCHS_L3.' [<a href="'.e_PLUGIN.'ebattles/eventinfo.php?eventid='.$event_id.'">'.EB_MATCHS_L4.'</a>]<br />';
    $text .= '</p>';

    $text .= '</div>';
    $text .= '</div>';
}
$ns->tablerender("$mEventName ($mEventgame - ".eventType($mEventType).")", $text);
require_once(FOOTERF);
exit;
?>