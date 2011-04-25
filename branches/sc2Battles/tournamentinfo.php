<?php
/**
* TournamentInfo.php
*
*/
require_once("../../class2.php");
require_once(e_PLUGIN."ebattles/include/main.php");
require_once(e_PLUGIN."ebattles/include/paginator.class.php");
require_once(e_PLUGIN."ebattles/include/show_array.php");
require_once(e_PLUGIN."ebattles/include/tournament.php");
require_once(e_PLUGIN."ebattles/include/clan.php");
require_once(e_PLUGIN."ebattles/include/match.php");

/*******************************************************************
********************************************************************/
require_once(HEADERF);

$pages = new Paginator;

$text = '
<script type="text/javascript" src="./js/tabpane.js"></script>
';

if (!isset($_GET['orderby'])) $_GET['orderby'] = 1;
$orderby=$_GET['orderby'];

$sort = "DESC";
if(isset($_GET["sort"]) && !empty($_GET["sort"]))
{
	$sort = ($_GET["sort"]=="ASC") ? "DESC" : "ASC";
	$sort_type = ($_GET["sort"]=="ASC") ? SORT_ASC : SORT_DESC;
}

/* Tournament Name */
$tournament_id = $_GET['TournamentID'];

if (!$tournament_id)
{
	header("Location: ./tournaments.php");
	exit();
}
else
{
	$self = $_SERVER['PHP_SELF'];
	$file = 'cache/sql_cache_tournament_'.$tournament_id.'.txt';
	$file_team = 'cache/sql_cache_tournament_team_'.$tournament_id.'.txt';

	require_once(e_PLUGIN."ebattles/tournamentinfo_process.php");

	$q = "SELECT ".TBL_TOURNAMENTS.".*, "
	.TBL_GAMES.".*, "
	.TBL_USERS.".*"
	." FROM ".TBL_TOURNAMENTS.", "
	.TBL_GAMES.", "
	.TBL_USERS
	." WHERE (".TBL_TOURNAMENTS.".TournamentID = '$tournament_id')"
	."   AND (".TBL_TOURNAMENTS.".Game = ".TBL_GAMES.".GameID)"
	."   AND (".TBL_USERS.".user_id = ".TBL_TOURNAMENTS.".Owner)";
	$result = $sql->db_Query($q);

	$tournament = new Tournament($tournament_id);

	$egame = mysql_result($result,0 , TBL_GAMES.".Name");
	$egameid = mysql_result($result,0 , TBL_GAMES.".GameID");
	$egameicon = mysql_result($result,0 , TBL_GAMES.".Icon");
	$eowner = mysql_result($result,0 , TBL_USERS.".user_id");
	$eownername = mysql_result($result,0 , TBL_USERS.".user_name");

	$tournamentIsChanged = $tournament->getField('IsChanged');

	if ($pref['eb_update_delay_enable'] == 1)
	{
		$eneedupdate = 0;
	}
	else
	{
		// Force always update
		$eneedupdate = 1;
	}

	/*
	if (
	(($time > $nextupdate_timestamp_local) && ($tournamentIsChanged == 1))
	||(file_exists($file) == FALSE)
	||((file_exists($file_team) == FALSE) && (($tournament->getField('Type') == "Team Tournament")||($tournament->getField('Type') == "ClanWar")))
	)
	{
		$eneedupdate = 1;
	}
	*/

	if($tournament->getField('StartDateTime')!=0)
	{
		$start_timestamp_local = $tournament->getField('StartDateTime') + TIMEOFFSET;
		$date_start = date("d M Y, h:i A",$start_timestamp_local);
	}
	else
	{
		$date_start = "-";
	}
	/*
	if($tournament->getField('End_timestamp')!=0)
	{
		$end_timestamp_local = $tournament->getField('End_timestamp') + TIMEOFFSET;
		$date_end = date("d M Y, h:i A",$end_timestamp_local);
	}
	else
	{
		$date_end = "-";
	}
	*/

	$time_comment = '';
	if (  ($tournament->getField('StartDateTime') != 0)
	&&($time <= $tournament->getField('StartDateTime'))
	)
	{
		$time_comment = EB_TOURNAMENT_L2.'&nbsp;'.get_formatted_timediff($time, $tournament->getField('StartDateTime'));
	}
	/*
	else if (  ($tournament->getField('End_timestamp') != 0)
	&&($time <= $tournament->getField('End_timestamp'))
	)
	{
		$time_comment = EB_TOURNAMENT_L3.'&nbsp;'.get_formatted_timediff($time, $tournament->getField('End_timestamp'));
	}
	else if (  ($tournament->getField('End_timestamp') != 0)
	&&($time > $tournament->getField('End_timestamp'))
	)
	{
		$time_comment = EB_TOURNAMENT_L4;
	}
	*/

	/* Nbr players */
	$q = "SELECT COUNT(*) as NbrPlayers"
	." FROM ".TBL_PLAYERS
	." WHERE (".TBL_PLAYERS.".Tournament = '$tournament_id')";
	$result = $sql->db_Query($q);
	$row = mysql_fetch_array($result);
	$nbrplayers = $row['NbrPlayers'];

	$q = "SELECT COUNT(*) as NbrPlayers"
	." FROM ".TBL_PLAYERS
	." WHERE (".TBL_PLAYERS.".Tournament = '$tournament_id')"
	."   AND (".TBL_PLAYERS.".Banned != 1)";
	$result = $sql->db_Query($q);
	$row = mysql_fetch_array($result);
	$nbrplayersNotBanned = $row['NbrPlayers'];

	/* Nbr Teams */
	$q = "SELECT COUNT(*) as NbrTeams"
	." FROM ".TBL_TEAMS
	." WHERE (Tournament = '$tournament_id')";
	$result = $sql->db_Query($q);
	$row = mysql_fetch_array($result);
	$nbrteams = $row['NbrTeams'];

	/* Update Stats */
	if ($eneedupdate == 1)
	{
		$new_nextupdate = $time + 60*$pref['eb_update_delay'];
		$q = "UPDATE ".TBL_TOURNAMENTS." SET NextUpdate_timestamp = $new_nextupdate WHERE (TournamentID = '$tournament_id')";
		$result = $sql->db_Query($q);
		$nextupdate_timestamp_local = $new_nextupdate;

		$q = "UPDATE ".TBL_TOURNAMENTS." SET IsChanged = 0 WHERE (TournamentID = '$tournament_id')";
		$result = $sql->db_Query($q);
		$tournamentIsChanged = 0;

		// TODO: update brackets here
		/*
		switch($tournament->getField('Type'))
		{
			case "One Player Tournament":
			updateStats($tournament_id, $time, TRUE);
			break;
			case "Team Tournament":
			updateStats($tournament_id, $time, TRUE);
			updateTeamStats($tournament_id, $time, TRUE);
			case "ClanWar":
			updateTeamStats($tournament_id, $time, TRUE);
			break;
			default:
		}
		*/

	}

	switch($tournament->getField('MatchType'))
	{
		case "1v1":
		$text .= '<div class="tab-pane" id="tab-pane-15">';
		break;
		default:
		$text .= '<div class="tab-pane" id="tab-pane-15">';
		break;
	}


	$text .= '<div class="tab-page">';
	/* Signup, Join/Quit Tournament */
	$text .= '<div class="tab">'.EB_TOURNAMENT_L35.'</div>';
	$text .= '<table class="signup" style="width:95%"><tbody>';
	$userIsDivisionCaptain = FALSE;
	if(check_class(e_UC_MEMBER))
	{
		// If logged in
		if(($tournament->getField('StartDateTime') == 0) || ($time < $tournament->getField('StartDateTime')))
		{
			// If tournament is not finished
			if (($tournament->getField('Type') == "Team Tournament")||($tournament->getField('Type') == "ClanWar"))
			{
				// Find if user is captain of a division playing that game
				// if yes, propose to join this tournament
				$q = "SELECT ".TBL_DIVISIONS.".*, "
				.TBL_CLANS.".*, "
				.TBL_GAMES.".*, "
				.TBL_USERS.".*"
				." FROM ".TBL_DIVISIONS.", "
				.TBL_CLANS.", "
				.TBL_GAMES.", "
				.TBL_USERS
				." WHERE (".TBL_DIVISIONS.".Game = '$egameid')"
				." AND (".TBL_GAMES.".GameID = '$egameid')"
				." AND (".TBL_CLANS.".ClanID = ".TBL_DIVISIONS.".Clan)"
				." AND (".TBL_USERS.".user_id = ".USERID.")"
				." AND (".TBL_DIVISIONS.".Captain = ".USERID.")";

				$result = $sql->db_Query($q);
				$numDivs = mysql_numrows($result);
				if($numDivs > 0)
				{
					$userIsDivisionCaptain = TRUE;
					for($i=0;$i < $numDivs;$i++)
					{
						$div_name  = mysql_result($result,$i, TBL_CLANS.".Name");
						$div_id    = mysql_result($result,$i, TBL_DIVISIONS.".DivisionID");

						// Is the division signed up
						$q_2 = "SELECT ".TBL_TEAMS.".*"
						." FROM ".TBL_TEAMS
						." WHERE (".TBL_TEAMS.".Tournament = '$tournament_id')"
						." AND (".TBL_TEAMS.".Division = '$div_id')";
						$result_2 = $sql->db_Query($q_2);
						$numTeams = mysql_numrows($result_2);

						$text .= '<tr>';
						$text .= '<td>'.EB_TOURNAMENT_L7.'&nbsp;'.$div_name.'</td>';
						if( $numTeams == 0)
						{

							if ($tournament->getField('Password') != "")
							{
								$text .= '<td>'.EB_TOURNAMENT_L8.'</td>';
								$text .= '<td>
								<form action="'.e_PLUGIN.'ebattles/tournamentinfo.php?TournamentID='.$tournament_id.'" method="post">
								<div>
								<input class="tbox" type="password" title="'.EB_TOURNAMENT_L9.'" name="joinTournamentPassword"/>
								<input type="hidden" name="division" value="'.$div_id.'"/>
								</div>
								'.ebImageTextButton('teamjointournament', 'user_add.png', EB_TOURNAMENT_L10).'
								';
								$text .= '</form>';
								$text .= '</td>';
							}
							else
							{
								$text .= '<td>'.EB_TOURNAMENT_L11.'</td>';
								$text .= '<td>
								<form action="'.e_PLUGIN.'ebattles/tournamentinfo.php?TournamentID='.$tournament_id.'" method="post">
								<div>
								<input type="hidden" name="joinTournamentPassword" value=""/>
								<input type="hidden" name="division" value="'.$div_id.'"/>
								</div>
								'.ebImageTextButton('teamjointournament', 'user_add.png', EB_TOURNAMENT_L12).'
								';
								$text .= '</form>';
								$text .= '</td>';
							}
						}
						else
						{
							// Team signed up.
							$text .= '<td>'.EB_TOURNAMENT_L13.'</td>';
						}
						$text .= '</tr>';
					}
				}
			}

			switch($tournament->getField('Type'))
			{
				case "Team Tournament":
				case "ClanWar":
				// Is the user a member of a division for that game?
				$q_2 = "SELECT ".TBL_CLANS.".*, "
				.TBL_MEMBERS.".*, "
				.TBL_DIVISIONS.".*, "
				.TBL_GAMES.".*, "
				.TBL_USERS.".*"
				." FROM ".TBL_CLANS.", "
				.TBL_MEMBERS.", "
				.TBL_DIVISIONS.", "
				.TBL_GAMES.", "
				.TBL_USERS
				." WHERE (".TBL_DIVISIONS.".Game = '$egameid')"
				." AND (".TBL_GAMES.".GameID = '$egameid')"
				." AND (".TBL_CLANS.".ClanID = ".TBL_DIVISIONS.".Clan)"
				." AND (".TBL_USERS.".user_id = ".USERID.")"
				." AND (".TBL_MEMBERS.".Division = ".TBL_DIVISIONS.".DivisionID)"
				." AND (".TBL_MEMBERS.".User = ".USERID.")";

				$result_2 = $sql->db_Query($q_2);
				$numMembers = mysql_numrows($result_2);
				if(!$result_2 || ( $numMembers == 0))
				{
					$text .= '<tr><td>'.EB_TOURNAMENT_L14.'</td>';
					$text .= '<td></td></tr>';
				}
				else
				{
					for($i=0;$i < $numMembers;$i++)
					{
						$clan_name  = mysql_result($result_2,$i , TBL_CLANS.".Name");
						$div_id  = mysql_result($result_2,$i , TBL_DIVISIONS.".DivisionID");
						$q_3 = "SELECT ".TBL_DIVISIONS.".*, "
						.TBL_USERS.".*"
						." FROM ".TBL_DIVISIONS.", "
						.TBL_USERS
						." WHERE (".TBL_DIVISIONS.".DivisionID = '$div_id')"
						." AND (".TBL_USERS.".user_id = ".TBL_DIVISIONS.".Captain)";
						$result_3 = $sql->db_Query($q_3);
						if($result_3)
						{
							$captain_name  = mysql_result($result_3,0, TBL_USERS.".user_name");
							$captain_id  = mysql_result($result_3,0, TBL_USERS.".user_id");
						}

						$q_3 = "SELECT ".TBL_CLANS.".*, "
						.TBL_TEAMS.".*, "
						.TBL_DIVISIONS.".* "
						." FROM ".TBL_CLANS.", "
						.TBL_TEAMS.", "
						.TBL_DIVISIONS
						." WHERE (".TBL_DIVISIONS.".DivisionID = '$div_id')"
						." AND (".TBL_CLANS.".ClanID = ".TBL_DIVISIONS.".Clan)"
						." AND (".TBL_TEAMS.".Division = ".TBL_DIVISIONS.".DivisionID)"
						." AND (".TBL_TEAMS.".Tournament = '$tournament_id')";
						$result_3 = $sql->db_Query($q_3);
						if(!$result_3 || (mysql_numrows($result_3) == 0))
						{
							if ($captain_id != USERID)
							{
								$text .= '<tr><td>'.EB_TOURNAMENT_L15.'&nbsp;'.$clan_name.'&nbsp;'.EB_TOURNAMENT_L16.'</td>';
								$text .= '<td>'.EB_TOURNAMENT_L17.' <a href="'.e_PLUGIN.'ebattles/userinfo.php?user='.$captain_id.'">'.$captain_name.'</a>.</td></tr>';
							}
						}
						else
						{
							$team_id  = mysql_result($result_3,0 , TBL_TEAMS.".TeamID");
							$text .= '<tr><td>'.EB_TOURNAMENT_L15.'&nbsp;'.$clan_name.'&nbsp;'.EB_TOURNAMENT_L18.'</td>';

							// Is the user already signed up with that team?
							$q = "SELECT ".TBL_PLAYERS.".*"
							." FROM ".TBL_PLAYERS.", "
							.TBL_GAMERS
							." WHERE (".TBL_PLAYERS.".Tournament = '$tournament_id')"
							."   AND (".TBL_PLAYERS.".Gamer = ".TBL_GAMERS.".GamerID)"
							."   AND (".TBL_GAMERS.".User = ".USERID.")"
							."   AND (".TBL_PLAYERS.".Team = '$team_id')";
							$result = $sql->db_Query($q);
							if(!$result || (mysql_numrows($result) == 0))
							{
								$text .= '<td>
								<form action="'.e_PLUGIN.'ebattles/tournamentinfo.php?TournamentID='.$tournament_id.'" method="post">
								<div>
								<input type="hidden" name="team" value="'.$team_id.'"/>
								</div>
								'.ebImageTextButton('jointeamtournament', 'user_add.png', EB_TOURNAMENT_L19).'
								</form></td>
								';
							}
							else
							{
								$user_pid  = mysql_result($result,0 , TBL_PLAYERS.".PlayerID");
								$user_banned  = mysql_result($result,0 , TBL_PLAYERS.".Banned");

								if ($user_banned)
								{
									$text .= '<td>'.EB_TOURNAMENT_L20.'<br />
									'.EB_TOURNAMENT_L21.'</td>';
								}
								else
								{
									// Player signed up
									$text .= '<td>'.EB_TOURNAMENT_L22.'</td>';

									// Player can quit an tournament if he has not played yet
									$q = "SELECT ".TBL_PLAYERS.".*"
									." FROM ".TBL_PLAYERS.", "
									.TBL_SCORES
									." WHERE (".TBL_PLAYERS.".PlayerID = '$user_pid')"
									." AND (".TBL_SCORES.".Player = ".TBL_PLAYERS.".PlayerID)";
									$result = $sql->db_Query($q);
									$nbrscores = mysql_numrows($result);
									if (($nbrscores == 0)&&($user_banned!=1)&&($tournament->getField('Type')!="ClanWar"))
									{
										$text .= '<td>
										<form action="'.e_PLUGIN.'ebattles/tournamentinfo.php?TournamentID='.$tournament_id.'" method="post">
										<div>
										<input type="hidden" name="player" value="'.$user_pid.'"/>
										'.ebImageTextButton('quittournament', 'user_delete.ico', EB_TOURNAMENT_L23, 'negative', EB_TOURNAMENT_L24).'
										</div>
										</form></td>
										';
									}
									else
									{
										$text .= '<td></td>';
									}
								}
							}
							$text .= '</tr>';
						}
					}
				}
				break;
				case "One Player Tournament":
				// Is the user already signed up?
				$q = "SELECT ".TBL_PLAYERS.".*"
				." FROM ".TBL_PLAYERS.", "
				.TBL_GAMERS
				." WHERE (".TBL_PLAYERS.".Tournament = '$tournament_id')"
				."   AND (".TBL_PLAYERS.".Gamer = ".TBL_GAMERS.".GamerID)"
				."   AND (".TBL_GAMERS.".User = ".USERID.")";
				$result = $sql->db_Query($q);
				if(!$result || (mysql_numrows($result) < 1))
				{
					if ($tournament->getField('Password') != "")
					{
						$text .= '<tr><td>'.EB_TOURNAMENT_L25.'</td>';
						$text .= '<td>'.EB_TOURNAMENT_L26.'</td>';
						$text .= '<td>';
						$text .= '
						<form action="'.e_PLUGIN.'ebattles/tournamentinfo.php?TournamentID='.$tournament_id.'" method="post">
						<div>
						<input class="tbox" type="password" title="'.EB_TOURNAMENT_L27.'" name="joinTournamentPassword"/>
						</div>
						'.ebImageTextButton('jointournament', 'user_add.png', EB_TOURNAMENT_L19).'
						</form></td></tr>
						';
					}
					else
					{
						$text .= '<tr><td>'.EB_TOURNAMENT_L28.'</td>';
						$text .= '<td>
						<form action="'.e_PLUGIN.'ebattles/tournamentinfo.php?TournamentID='.$tournament_id.'" method="post">
						<div>
						<input type="hidden" name="joinTournamentPassword" value=""/>
						</div>
						'.ebImageTextButton('jointournament', 'user_add.png', EB_TOURNAMENT_L19).'
						</form></td></tr>
						';
					}
				}
				else
				{
					$user_pid  = mysql_result($result,0 , TBL_PLAYERS.".PlayerID");
					$user_banned  = mysql_result($result,0 , TBL_PLAYERS.".Banned");

					if ($user_banned)
					{
						$text .= '<tr><td>'.EB_TOURNAMENT_L29.'<br />
						'.EB_TOURNAMENT_L30.'</td><td></td></tr>';
					}
					else
					{
						$text .= '<tr><td>'.EB_TOURNAMENT_L31.'</td>';

						// Player can quit an tournament if he has not played yet
						$q = "SELECT ".TBL_PLAYERS.".*"
						." FROM ".TBL_PLAYERS.", "
						.TBL_SCORES
						." WHERE (".TBL_PLAYERS.".PlayerID = '$user_pid')"
						." AND (".TBL_SCORES.".Player = ".TBL_PLAYERS.".PlayerID)";
						$result = $sql->db_Query($q);
						$nbrscores = mysql_numrows($result);
						if ($nbrscores == 0)
						{
							$text .= '<td>
							<form action="'.e_PLUGIN.'ebattles/tournamentinfo.php?TournamentID='.$tournament_id.'" method="post">
							<div>
							<input type="hidden" name="player" value="'.$user_pid.'"/>
							'.ebImageTextButton('quittournament', 'user_delete.ico', EB_TOURNAMENT_L32, 'negative', EB_TOURNAMENT_L33).'
							</div>
							</form></td></tr>
							';
						}
						else
						{
							$text .= '<td></td></tr>';
						}
					}
				}
				break;
				default:
			}
		}
	}
	else
	{
		$text .= '<tr><td>'.EB_TOURNAMENT_L34.'</td>';
		$text .= '<td></td></tr>';
	}
	$text .= '</tbody></table>';

	/* Info */
	$text .= '<table class="fborder" style="width:95%"><tbody>';

	$text .= '<tr>';
	$text .= '<td class="forumheader3">'.EB_TOURNAMENT_L36.'</td>';
	$text .= '<td class="forumheader3"><b>'.$tournament->getField('Name').'</b></td>';
	$text .= '</tr>';

	$text .= '<tr>';
	$text .= '<td class="forumheader3">'.EB_TOURNAMENT_L37.'</td>';
	$text .= '<td class="forumheader3">'.$tournament->getField('MatchType').' - '.tournamentTypeToString($tournament->getField('Type')).'</td>';
	$text .= '</tr>';

	$text .= '<tr>';
	$text .= '<td class="forumheader3">'.EB_TOURNAMENT_L38.'</td>';
	$text .= '<td class="forumheader3"><img '.getGameIconResize($egameicon).'/> '.$egame.'</td>';
	$text .= '</tr>';

	$text .= '<tr>';
	$text .= '<td class="forumheader3">'.EB_TOURNAMENT_L39.'</td>';
	$text .= '<td class="forumheader3"><a href="'.e_PLUGIN.'ebattles/userinfo.php?user='.$eowner.'">'.$eownername.'</a>';
	$can_manage = 0;
	if (check_class($pref['eb_mod_class'])) $can_manage = 1;
	if (USERID==$eowner) $can_manage = 1;
	if ($can_manage == 1)
	$text .= '<br /><a href="'.e_PLUGIN.'ebattles/tournamentmanage.php?TournamentID='.$tournament_id.'">'.EB_TOURNAMENT_L40.'</a>';
	$text .= '</td></tr>';

	$text .= '<tr>';
	$q = "SELECT ".TBL_MODS.".*, "
	.TBL_USERS.".*"
	." FROM ".TBL_MODS.", "
	.TBL_USERS
	." WHERE (".TBL_MODS.".Tournament = '$tournament_id')"
	."   AND (".TBL_USERS.".user_id = ".TBL_MODS.".User)";
	$result = $sql->db_Query($q);
	$numMods = mysql_numrows($result);
	$text .= '<td class="forumheader3">'.EB_TOURNAMENT_L41.'</td>';
	$text .= '<td class="forumheader3">';
	if ($numMods>0)
	{
		$text .= '<ul>';
		for($i=0; $i< $numMods; $i++){
			$modid  = mysql_result($result,$i, TBL_USERS.".user_id");
			$modname  = mysql_result($result,$i, TBL_USERS.".user_name");
			$text .= '<li><a href="'.e_PLUGIN.'ebattles/userinfo.php?user='.$modid.'">'.$modname.'</a></li>';
		}
		$text .= '</ul>';
	}
	$text .= '</td></tr>';

	$text .= '<tr><td class="forumheader3">'.EB_TOURNAMENT_L42.'</td><td class="forumheader3">'.$date_start.'</td></tr>';
	$text .= '<tr><td class="forumheader3">'.EB_TOURNAMENT_L43.'</td><td class="forumheader3">'.$date_end.'</td></tr>';
	$text .= '<tr><td class="forumheader3"></td><td class="forumheader3">'.$time_comment.'</td></tr>';
	$text .= '<tr><td class="forumheader3">'.EB_TOURNAMENT_L44.'</td><td class="forumheader3">'.$tp->toHTML($tournament->getField('Rules'), true).'</td></tr>';
	$text .= '<tr><td class="forumheader3"></td><td class="forumheader3">'.$tp->toHTML($tournament->getField('Description'), true).'</td></tr>';
	$text .= '</tbody></table>';
	$text .= '</div>';    // tab-page "Info"

	/* Teams Standings */
	$can_approve = 0;
	$can_report = 0;
	$can_schedule = 0;
	$can_report_quickloss = 0;
	$can_challenge = 0;
	$userclass = 0;
	// Check if user can report
	// Is the user admin?
	if (check_class($pref['eb_mod_class']))
	{
		$userclass |= eb_UC_EB_MODERATOR;
		$can_report = 1;
		$can_schedule = 1;
		$can_approve = 1;
	}
	// Is the user tournament owner?
	if (USERID==$eowner)
	{
		$userclass |= eb_UC_TOURNAMENT_OWNER;
		$can_report = 1;
		$can_schedule = 1;
		$can_approve = 1;
	}
	// Is the user a moderator?
	$q_2 = "SELECT ".TBL_MODS.".*"
	." FROM ".TBL_MODS
	." WHERE (".TBL_MODS.".Tournament = '$tournament_id')"
	."   AND (".TBL_MODS.".User = ".USERID.")";
	$result_2 = $sql->db_Query($q_2);
	$numMods = mysql_numrows($result_2);
	if ($numMods>0)
	{
		$userclass |= eb_UC_TOURNAMENT_MODERATOR;
		$can_report = 1;
		$can_schedule = 1;
		$can_approve = 1;
	}
	/*
	if ($userIsDivisionCaptain == TRUE)
	{
	$userclass |= eb_UC_TOURNAMENT_PLAYER;
	$can_report = 1;
	}
	*/

	// Is the user a player?
	$q = "SELECT *"
	." FROM ".TBL_PLAYERS
	." WHERE (Tournament = '$tournament_id')"
	."   AND (User = ".USERID.")";
	$result = $sql->db_Query($q);

	$pbanned=0;
	if(mysql_numrows($result) == 1)
	{
		$userclass |= eb_UC_TOURNAMENT_PLAYER;

		// Show link to my position
		$row = mysql_fetch_array($result);
		$prank = $row['Rank'];
		$pbanned = $row['Banned'];

		/* My Position */
		if ($prank==0)
		$prank_txt = EB_TOURNAMENT_L54;
		else
		$prank_txt = "#$prank";

		$search_user = array_searchRecursive( 'user='.USERID.'"', $stats, false);

		($search_user) ? $link_page = ceil($search_user[0]/$pages->items_per_page) : $link_page = 1;

		$myPosition_txt = '<p>';
		$myPosition_txt .= "<a href=\"$self?page=$link_page&amp;ipp=$pages->items_per_page$pages->querystring\">".EB_TOURNAMENT_L55.": $prank_txt</a><br />";
		$myPosition_txt .= '</p>';

		/*
		// Is the tournament started, and not ended
		if (  ($tournament->getField('End_timestamp') == 0)
		||(  ($tournament->getField('End_timestamp') >= $time)
		&&($tournament->getField('StartDateTime') <= $time)
		)
		)
		{
			$can_report = 1;
			$can_report_quickloss = 1;
			$can_challenge = 1;
		}
		*/
	}

	switch($tournament->getField('Type'))
	{
		case "One Player Tournament":
		case "Team Tournament":
		if (($nbrplayersNotBanned < 2)||($pbanned))
		{
			$can_report = 0;
			$can_schedule = 0;
			$can_report_quickloss = 0;
			$can_challenge = 0;
		}
		break;
		case "ClanWar":
		if ($nbrteams < 2)
		{
			$can_report = 0;
			$can_schedule = 0;
			$can_report_quickloss = 0;
			$can_challenge = 0;
		}
		break;
		default:
	}

	// check if only 1 player with this userid
	$q = "SELECT DISTINCT ".TBL_PLAYERS.".*, "
	.TBL_USERS.".*"
	." FROM ".TBL_PLAYERS.", "
	.TBL_GAMERS.", "
	.TBL_USERS
	." WHERE (".TBL_PLAYERS.".Tournament = '$tournament_id')"
	."   AND (".TBL_PLAYERS.".Gamer = ".TBL_GAMERS.".GamerID)"
	."   AND (".TBL_USERS.".user_id = ".TBL_GAMERS.".User)"
	."   AND (".TBL_USERS.".user_id = ".USERID.")";
	$result = $sql->db_Query($q);
	$numPlayers = mysql_numrows($result);
	if ($numPlayers>1)
	$can_report_quickloss = 0;

	// Check if AllowScore is set
	if ($tournament->getField('AllowScore')==TRUE)
	$can_report_quickloss = 0;

	if($tournament->getField('Type') == "ClanWar") $can_report_quickloss = 0;  // Disable quick loss report for clan wars for now
	if($tournament->getField('quick_loss_report')==FALSE) $can_report_quickloss = 0;
	if($userclass < $tournament->getField('match_report_userclass')) $can_report = 0;

	if($userclass < $tournament->getField('MatchesApproval')) $can_approve = 0;
	if($tournament->getField('MatchesApproval') == eb_UC_NONE) $can_approve = 0;

	if($tournament->getField('ChallengesEnable')==FALSE) $can_challenge= 0;

	//fm: Need userclass for match scheduling

	$nextupdate_timestamp_local_local = $nextupdate_timestamp_local + TIMEOFFSET;
	$date_nextupdate = date("d M Y, h:i A",$nextupdate_timestamp_local_local);

	if (($tournament->getField('Type') == "Team Tournament")||($tournament->getField('Type') == "ClanWar"))
	{
		$text .= '<div class="tab-page">';
		$text .= '<div class="tab">'.EB_TOURNAMENT_L45.'</div>';

		if(($can_challenge != 0)&&($tournament->getField('Type') == "ClanWar"))
		{
			$text .= '<form action="'.e_PLUGIN.'ebattles/challengerequest.php?TournamentID='.$tournament_id.'" method="post">';
			$text .= '<table>';
			$text .= '<tr>';
			// "Challenge team" form
			$q = "SELECT ".TBL_PLAYERS.".*"
			." FROM ".TBL_PLAYERS.", "
			.TBL_GAMERS
			." WHERE (".TBL_PLAYERS.".Tournament = '$tournament_id')"
			."   AND (".TBL_PLAYERS.".Gamer = ".TBL_GAMERS.".GamerID)"
			."   AND (".TBL_GAMERS.".User = '".USERID."')";
			$result = $sql->db_Query($q);
			$uteam = mysql_result($result,0 , TBL_PLAYERS.".Team");

			$q = "SELECT ".TBL_CLANS.".*, "
			.TBL_TEAMS.".*, "
			.TBL_DIVISIONS.".* "
			." FROM ".TBL_CLANS.", "
			.TBL_TEAMS.", "
			.TBL_DIVISIONS
			." WHERE (".TBL_CLANS.".ClanID = ".TBL_DIVISIONS.".Clan)"
			." AND (".TBL_TEAMS.".Division = ".TBL_DIVISIONS.".DivisionID)"
			." AND (".TBL_TEAMS.".Tournament = '$tournament_id')"
			." ORDER BY ".TBL_CLANS.".Name";
			$result = $sql->db_Query($q);
			$num_rows = mysql_numrows($result);

			$text .= '<td><div>
			<select class="tbox" name="Challenged">
			';
			for($i=0; $i<$num_rows; $i++)
			{
				$tid  = mysql_result($result,$i, TBL_TEAMS.".TeamID");
				$trank  = mysql_result($result,$i, TBL_TEAMS.".Rank");
				$tname  = mysql_result($result,$i, TBL_CLANS.".Name");

				if(($uteam == 0)||($uteam != $tid))
				{
					if ($trank==0)
					$trank_txt = EB_TOURNAMENT_L54;
					else
					$trank_txt = "#$trank";
					$text .= '<option value="'.$tid.'">'.$tname.' ('.$trank_txt.')</option>';
				}
			}
			$text .= '
			</select>
			</div></td>
			';
			$Challenger = USERID;
			$text .= '<td><div>';
			$text .= '<input type="hidden" name="TournamentID" value="'.$tournament_id.'"/>';
			$text .= '<input type="hidden" name="submitted_by" value="'.$Challenger.'"/>';
			$text .= '</div></td>';

			$text .= '<td>';
			$text .= '<div>';
			$text .= '<input type="hidden" name="userclass" value="'.$userclass.'"/>';
			$text .= ebImageTextButton('challenge_team', 'challenge.png', EB_TOURNAMENT_L71);
			$text .= '</div>';
			$text .= '</td>';
			$text .= '</tr>';
			$text .= '</table>';
			$text .= '</form>';
		}

		if (($time < $nextupdate_timestamp_local) && ($tournamentIsChanged == 1))
		{
			$text .= EB_TOURNAMENT_L46.'&nbsp;'.$date_nextupdate.'<br />';
		}
		$text .= '<div class="spacer">';
		$text .= '<p>';
		$text .= $nbrteams.' teams<br />';
		$text .= EB_TOURNAMENT_L47.'&nbsp;'.$tournament->getField('nbr_team_games_to_rank').'&nbsp;'.EB_TOURNAMENT_L48.'<br /><br />';
		$text .= '</p>';

		// Teams standings stats
		$stats = unserialize(implode('',file($file_team)));
		//print_r($stats);

		// Sorting the stats table
		$header = $stats[0];

		$new_header = array();
		$column = 0;
		foreach ($header as $header_cell)
		{
			//fm echo "column $column: $header_cell<br>";
			$pieces = explode("<br />", $header_cell);

			$new_header[] = '<a href="'.e_PLUGIN.'ebattles/tournamentinfo.php?TournamentID='.$tournament_id.'&amp;orderby='.$column.'&amp;sort='.$sort.'">'.$pieces[0].'</a>'.$pieces[1];
			$column++;
		}
		$header = array($new_header);
		$header[0][0] = "header";

		array_splice($stats,0,1);
		multi2dSortAsc($stats, $orderby, $sort_type);
		$stats = array_merge($header, $stats);
		$num_columns = count($stats[0]) - 1;
		$nbr_rows = count($stats);
		$text .= html_show_table($stats, $nbr_rows, $num_columns);

		$text .= '</div>';
		$text .= '</div>';    // tab-page "Teams Standings"
	}

	/* Players Standings */
	if (($tournament->getField('Type') == "Team Tournament")||($tournament->getField('Type') == "One Player Tournament"))
	{
		// Players standings stats
		$stats = unserialize(implode('',file($file)));
		//print_r($stats);

		// Sorting the stats table
		$header = $stats[0];

		$new_header = array();
		$column = 0;
		foreach ($header as $header_cell)
		{
			//fm echo "column $column: $header_cell<br>";
			$pieces = explode("<br />", $header_cell);

			$new_header[] = '<a href="'.e_PLUGIN.'ebattles/tournamentinfo.php?TournamentID='.$tournament_id.'&amp;orderby='.$column.'&amp;sort='.$sort.'">'.$pieces[0].'</a>'.$pieces[1];
			$column++;
		}
		$header = array($new_header);
		$header[0][0] = "header";

		array_splice($stats,0,1);
		multi2dSortAsc($stats, $orderby, $sort_type);
		$stats = array_merge($header, $stats);
		//print_r($stats);

		$text .= '<div class="tab-page">';
		$text .= '<div class="tab">'.EB_TOURNAMENT_L49.'</div>';

		if($can_challenge != 0)
		{
			$text .= '<form action="'.e_PLUGIN.'ebattles/challengerequest.php?TournamentID='.$tournament_id.'" method="post">';
			$text .= '<table>';
			$text .= '<tr>';
			// "Challenge player" form
			$q = "SELECT ".TBL_PLAYERS.".*"
			." FROM ".TBL_PLAYERS.", "
			.TBL_GAMERS
			." WHERE (".TBL_PLAYERS.".Tournament = '$tournament_id')"
			."   AND (".TBL_PLAYERS.".Gamer = ".TBL_GAMERS.".GamerID)"
			."   AND (".TBL_GAMERS.".User = '".USERID."')";
			$result = $sql->db_Query($q);
			$uteam = mysql_result($result,0 , TBL_PLAYERS.".Team");

			$q = "SELECT ".TBL_PLAYERS.".*, "
			.TBL_USERS.".*"
			." FROM ".TBL_PLAYERS.", "
			.TBL_GAMERS.", "
			.TBL_USERS
			." WHERE (".TBL_PLAYERS.".Tournament = '$tournament_id')"
			."   AND (".TBL_PLAYERS.".Banned != 1)"
			."   AND (".TBL_PLAYERS.".Gamer = ".TBL_GAMERS.".GamerID)"
			."   AND (".TBL_USERS.".user_id = ".TBL_GAMERS.".User)"
			." ORDER BY ".TBL_USERS.".user_name";
			$result = $sql->db_Query($q);
			$num_rows = mysql_numrows($result);

			$text .= '<td><div>
			<select class="tbox" name="Challenged">
			';
			for($i=0; $i<$num_rows; $i++)
			{
				$pid  = mysql_result($result,$i, TBL_PLAYERS.".PlayerID");
				$puid  = mysql_result($result,$i, TBL_USERS.".user_id");
				$prank  = mysql_result($result,$i, TBL_PLAYERS.".Rank");
				$pname  = mysql_result($result,$i, TBL_USERS.".user_name");
				$pteam  = mysql_result($result,$i, TBL_PLAYERS.".Team");
				list($pclan, $pclantag, $pclanid) = getClanInfo($pteam);

				if(($puid != USERID)&&(($uteam == 0)||($uteam != $pteam)))
				{
					if ($prank==0)
					$prank_txt = EB_TOURNAMENT_L54;
					else
					$prank_txt = "#$prank";
					$text .= '<option value="'.$pid.'">'.$pclantag.$pname.' ('.$prank_txt.')</option>';
				}
			}
			$text .= '
			</select>
			</div></td>
			';
			$Challenger = USERID;
			$text .= '<td><div>';
			$text .= '<input type="hidden" name="TournamentID" value="'.$tournament_id.'"/>';
			$text .= '<input type="hidden" name="submitted_by" value="'.$Challenger.'"/>';
			$text .= '</div></td>';

			$text .= '<td>';
			$text .= '<div>';
			$text .= '<input type="hidden" name="userclass" value="'.$userclass.'"/>';
			$text .= ebImageTextButton('challenge_player', 'challenge.png', EB_TOURNAMENT_L65);
			$text .= '</div>';
			$text .= '</td>';
			$text .= '</tr>';
			$text .= '</table>';
			$text .= '</form>';
		}

		if (($time < $nextupdate_timestamp_local) && ($tournamentIsChanged == 1))
		{
			$text .= EB_TOURNAMENT_L50.'&nbsp;'.$date_nextupdate.'<br />';
		}

		/* set pagination variables */
		$totalItems = $nbrplayers;
		$pages->items_total = $totalItems;
		$pages->mid_range = eb_PAGINATION_MIDRANGE;
		$pages->paginate();

		$text .= '<p>';
		$text .= $nbrplayers.'&nbsp;'.EB_TOURNAMENT_L51.'<br />';
		$text .= EB_TOURNAMENT_L52.'&nbsp;'.$tournament->getField('nbr_games_to_rank').'&nbsp;'.EB_TOURNAMENT_L53.'<br />';
		$text .= '</p>';

		$text .= $myPosition_txt;

		$text .= '<br />';
		// Paginate
		$text .= '<span class="paginate" style="float:left;">'.$pages->display_pages().'</span>';
		$text .= '<span style="float:right">';
		// Go To Page
		$text .= $pages->display_jump_menu();
		$text .= '&nbsp;&nbsp;&nbsp;';
		// Items per page
		$text .= $pages->display_items_per_page();
		$text .= '</span><br /><br />';

		// Paginate the statistics array
		$max_row = count($stats);
		$stats_paginate = array($stats[0]);
		$num_columns = count($stats[0]) - 1;
		$nbr_rows = 1;

		for ($i = $pages->low + 1; $i <= $pages->high + 1; $i++)
		{
			if ($i < $max_row)
			{
				$stats_paginate[] = $stats[$i];
				$nbr_rows ++;
			}
		}
		$text .= html_show_table($stats_paginate, $nbr_rows, $num_columns);

		$text .= '</div>';    // tab-page "Players Standings"
	}

	/* Matches */
	$q = "SELECT COUNT(DISTINCT ".TBL_MATCHS.".MatchID) as NbrMatches"
	." FROM ".TBL_MATCHS.", "
	.TBL_SCORES
	." WHERE (".TBL_MATCHS.".Tournament = '$tournament_id')"
	." AND (".TBL_SCORES.".MatchID = ".TBL_MATCHS.".MatchID)"
	." AND (".TBL_MATCHS.".Status = 'pending')";
	$result = $sql->db_Query($q);
	$row = mysql_fetch_array($result);
	$nbrMatchesPending = $row['NbrMatches'];
	if ($nbrMatchesPending == 0) $can_approve = 0;

	$text .= '
	<div class="tab-page">
	<div class="tab">'.EB_TOURNAMENT_L58;
	$text .= ($can_approve == 1) ? ' <span style="color:red">('.$nbrMatchesPending.')</span>' : '';
	$text .= '</div>';

	//dbg: $text .= "Userclass: $userclass<br>";

	/* Display Match Report buttons */
	if(($can_report_quickloss != 0)||($can_report != 0))
	{
		$text .= '<table>';
		$text .= '<tr>';
		if($can_report_quickloss != 0)
		{
			$text .= '<td>';
			$text .= '<form action="'.e_PLUGIN.'ebattles/quickreport.php?TournamentID='.$tournament_id.'" method="post">';
			$text .= ebImageTextButton('quicklossreport', 'flag_red.png', EB_TOURNAMENT_L56);
			$text .= '</form>';
			$text .= '</td>';
		}
		if($can_report != 0)
		{
			$text .= '<td>';
			$text .= '<form action="'.e_PLUGIN.'ebattles/matchreport.php?TournamentID='.$tournament_id.'" method="post">';
			$text .= '<div>';
			$text .= '<input type="hidden" name="userclass" value="'.$userclass.'"/>';
			$text .= ebImageTextButton('matchreport', 'page_white_edit.png', EB_TOURNAMENT_L57);
			$text .= '</div>';
			$text .= '</form>';
			$text .= '</td>';
		}
		if($can_schedule != 0)
		{
			$text .= '<td>';
			$text .= '<form action="'.e_PLUGIN.'ebattles/matchreport.php?TournamentID='.$tournament_id.'" method="post">';
			$text .= '<div>';
			$text .= '<input type="hidden" name="userclass" value="'.$userclass.'"/>';
			$text .= ebImageTextButton('matchschedule', 'add.png', EB_TOURNAMENT_L72);
			$text .= '</div>';
			$text .= '</form>';
			$text .= '</td>';
		}
		$text .= '</tr>';
		$text .= '</table>';
	}
	$text .= '<br />';

	/* Display Active Matches */
	$rowsPerPage = $pref['eb_default_items_per_page'];

	$q = "SELECT COUNT(DISTINCT ".TBL_MATCHS.".MatchID) as NbrMatches"
	." FROM ".TBL_MATCHS.", "
	.TBL_SCORES
	." WHERE (Tournament = '$tournament_id')"
	." AND (".TBL_MATCHS.".Status = 'active')"
	." AND (".TBL_SCORES.".MatchID = ".TBL_MATCHS.".MatchID)";
	$result = $sql->db_Query($q);

	$row = mysql_fetch_array($result);
	$numMatches = $row['NbrMatches'];

	$text .= '<p><b>';
	$text .= $numMatches.'&nbsp;'.EB_TOURNAMENT_L59;
	if ($numMatches>$rowsPerPage)
	{
	$text .= ' [<a href="'.e_PLUGIN.'ebattles/tournamentmatchs.php?TournamentID='.$tournament_id.'">'.EB_TOURNAMENT_L60.'</a>]';
}
	$text .= '</b></p>';
	$text .= '<br />';

	$q = "SELECT DISTINCT ".TBL_MATCHS.".*"
	." FROM ".TBL_MATCHS.", "
	.TBL_SCORES.", "
	.TBL_USERS
	." WHERE (".TBL_MATCHS.".Tournament = '$tournament_id')"
	." AND (".TBL_SCORES.".MatchID = ".TBL_MATCHS.".MatchID)"
	." AND (".TBL_MATCHS.".Status = 'active')"
	." ORDER BY ".TBL_MATCHS.".TimeReported DESC"
	." LIMIT 0, $rowsPerPage";
	$result = $sql->db_Query($q);
	$numMatches = mysql_numrows($result);

	if ($numMatches>0)
	{
		/* Display table contents */
		$text .= '<table class="table_left">';
		for($i=0; $i < $numMatches; $i++)
		{
			$match_id  = mysql_result($result,$i, TBL_MATCHS.".MatchID");
			$match = new Match($match_id);
			$text .= $match->displayMatchInfo(eb_MATCH_NOTOURNAMENTINFO);
		}
		$text .= '</table>';
	}

	$text .= '<br />';

	/* Display Pending Matches */
	$q = "SELECT DISTINCT ".TBL_MATCHS.".*"
	." FROM ".TBL_MATCHS.", "
	.TBL_SCORES.", "
	.TBL_USERS
	." WHERE (".TBL_MATCHS.".Tournament = '$tournament_id')"
	." AND (".TBL_SCORES.".MatchID = ".TBL_MATCHS.".MatchID)"
	." AND (".TBL_MATCHS.".Status = 'pending')"
	." ORDER BY ".TBL_MATCHS.".TimeReported DESC";
	$result = $sql->db_Query($q);
	$numMatches = mysql_numrows($result);

	if ($numMatches>0)
	{
		$text .= '<p><b>';
		$text .= $numMatches.'&nbsp;'.EB_TOURNAMENT_L64;
		$text .= '</b></p>';
		$text .= '<br />';

		/* Display table contents */
		$text .= '<table class="table_left">';
		for($i=0; $i < $numMatches; $i++)
		{
			$match_id  = mysql_result($result,$i, TBL_MATCHS.".MatchID");
			$match = new Match($match_id);
			$text .= $match->displayMatchInfo(eb_MATCH_NOTOURNAMENTINFO);
		}
		$text .= '</table>';
	}

	/* Display Scheduled Matches */
	$text .= '<br />';

	$q = "SELECT DISTINCT ".TBL_MATCHS.".*"
	." FROM ".TBL_MATCHS.", "
	.TBL_SCORES
	." WHERE (".TBL_MATCHS.".Tournament = '$tournament_id')"
	." AND (".TBL_SCORES.".MatchID = ".TBL_MATCHS.".MatchID)"
	." AND (".TBL_MATCHS.".Status = 'scheduled')"
	." ORDER BY ".TBL_MATCHS.".TimeReported DESC";
	$result = $sql->db_Query($q);
	$numMatches = mysql_numrows($result);

	if ($numMatches>0)
	{
		$text .= '<p><b>';
		$text .= $numMatches.'&nbsp;'.EB_TOURNAMENT_L70;
		$text .= '</b></p>';
		$text .= '<br />';

		/* Display table contents */
		$text .= '<table class="table_left">';
		for($i=0; $i < $numMatches; $i++)
		{
			$match_id  = mysql_result($result,$i, TBL_MATCHS.".MatchID");
			$match = new Match($match_id);
			$text .= $match->displayMatchInfo(eb_MATCH_NOTOURNAMENTINFO|eb_MATCH_SCHEDULED);
		}
		$text .= '</table>';
	}

	/* Display Unconfirmed Challenges */
	$text .= '<br />';

	$q = "SELECT DISTINCT ".TBL_CHALLENGES.".*"
	." FROM ".TBL_CHALLENGES.", "
	.TBL_PLAYERS
	." WHERE (".TBL_CHALLENGES.".Tournament = '".$tournament_id."')"
	."   AND (".TBL_CHALLENGES.".Status = 'requested')"
	." ORDER BY ".TBL_CHALLENGES.".TimeReported DESC";
	$result = $sql->db_Query($q);
	$numChallenges = mysql_numrows($result);

	if ($numChallenges>0)
	{
		$text .= '<p><b>';
		$text .= $numChallenges.'&nbsp;'.EB_TOURNAMENT_L67;
		$text .= '</b></p>';
		$text .= '<br />';

		/* Display table contents */
		$text .= '<table class="table_left">';
		for($i=0; $i < $numChallenges; $i++)
		{
			$challenge_id  = mysql_result($result,$i, TBL_CHALLENGES.".ChallengeID");
			$challenge = new Challenge($challenge_id);
			$text .= $challenge->displayChallengeInfo(eb_MATCH_NOTOURNAMENTINFO);
		}
		$text .= '</table>';
	}

	$text .= '</div>';    // tab-page "Matches"

	$text .= '<div class="tab-page">';
	$text .= '<div class="tab">'.EB_TOURNAMENT_L63.'</div>';

	$rowsPerPage = $pref['eb_default_items_per_page'];

	$awards = array();
	$nbr_awards = 0;

	/* Latest awards */
	$q = "SELECT ".TBL_AWARDS.".*, "
	.TBL_PLAYERS.".*, "
	.TBL_USERS.".*"
	." FROM ".TBL_AWARDS.", "
	.TBL_PLAYERS.", "
	.TBL_GAMERS.", "
	.TBL_USERS
	." WHERE (".TBL_AWARDS.".Player = ".TBL_PLAYERS.".PlayerID)"
	." AND (".TBL_PLAYERS.".Gamer = ".TBL_GAMERS.".GamerID)"
	." AND (".TBL_GAMERS.".User = ".TBL_USERS.".user_id)"
	." AND (".TBL_PLAYERS.".Tournament = '$tournament_id')"
	." ORDER BY ".TBL_AWARDS.".timestamp DESC"
	." LIMIT 0, $rowsPerPage";

	$result = $sql->db_Query($q);
	$numAwards = mysql_numrows($result);

	if ($numAwards>0)
	{
		/* Display table contents */
		for($i=0; $i < $numAwards; $i++)
		{
			$aID  = mysql_result($result,$i, TBL_AWARDS.".AwardID");
			$aUser  = mysql_result($result,$i, TBL_USERS.".user_id");
			$aUserNickName  = mysql_result($result,$i, TBL_USERS.".user_name");
			$aType  = mysql_result($result,$i, TBL_AWARDS.".Type");
			$aTime  = mysql_result($result,$i, TBL_AWARDS.".timestamp");
			$aTime_local = $aTime + TIMEOFFSET;
			$date = date("d M Y, h:i A",$aTime_local);

			switch ($aType) {
				case 'PlayerTookFirstPlace':
				$award = EB_AWARD_L2;
				$icon = '<img '.getActivityIconResize(e_PLUGIN."ebattles/images/awards/award_star_gold_3.png").' alt="'.EB_AWARD_L3.'" title="'.EB_AWARD_L3.'"/> ';
				break;
				case 'PlayerInTopTen':
				$award = EB_AWARD_L4;
				$icon = '<img '.getActivityIconResize(e_PLUGIN."ebattles/images/awards/award_star_bronze_3.png").' alt="'.EB_AWARD_L5.'" title="'.EB_AWARD_L5.'"/> ';
				break;
				case 'PlayerStreak5':
				$award = EB_AWARD_L6;
				$icon = '<img '.getActivityIconResize(e_PLUGIN."ebattles/images/awards/medal_bronze_3.png").' alt="'.EB_AWARD_L7.'" title="'.EB_AWARD_L7.'"/> ';
				break;
				case 'PlayerStreak10':
				$award = EB_AWARD_L8;
				$icon = '<img '.getActivityIconResize(e_PLUGIN."ebattles/images/awards/medal_silver_3.png").' alt="'.EB_AWARD_L9.'" title="'.EB_AWARD_L9.'"/> ';
				break;
				case 'PlayerStreak25':
				$award = EB_AWARD_L10;
				$icon = '<img '.getActivityIconResize(e_PLUGIN."ebattles/images/awards/medal_gold_3.png").' alt="'.EB_AWARD_L11.'" title="'.EB_AWARD_L11.'"/> ';
				break;
			}

			$award_string = '<tr><td style="vertical-align:top">'.$icon.'</td>';
			$award_string .= '<td><a href="'.e_PLUGIN.'ebattles/userinfo.php?user='.$aUser.'">'.$aUserNickName.'</a>';
			$award_string .= '&nbsp;'.$award;

			$award_string .= ' <div class="smalltext">';
			if (($time-$aTime) < INT_MINUTE )
			{
				$award_string .= EB_MATCH_L7;
			}
			else if (($time-$aTime) < INT_DAY )
			{
				$award_string .= get_formatted_timediff($aTime, $time).'&nbsp;'.EB_MATCH_L8;
			}
			else
			{
				$award_string .= $date;
			}
			$award_string .= '</div></td></tr>';

			$awards[$nbr_awards][0] = $aTime;
			$awards[$nbr_awards][1] = $award_string;
			$nbr_awards ++;
		}
	}

	$q = "SELECT ".TBL_AWARDS.".*, "
	.TBL_TEAMS.".*"
	." FROM ".TBL_AWARDS.", "
	.TBL_TEAMS
	." WHERE (".TBL_AWARDS.".Team = ".TBL_TEAMS.".TeamID)"
	." AND (".TBL_TEAMS.".Tournament = '$tournament_id')"
	." ORDER BY ".TBL_AWARDS.".timestamp DESC"
	." LIMIT 0, $rowsPerPage";

	$result = $sql->db_Query($q);
	$numAwards = mysql_numrows($result);

	if ($numAwards>0)
	{
		/* Display table contents */
		for($i=0; $i < $numAwards; $i++)
		{
			$aID  = mysql_result($result,$i, TBL_AWARDS.".AwardID");
			$aType  = mysql_result($result,$i, TBL_AWARDS.".Type");
			$aTime  = mysql_result($result,$i, TBL_AWARDS.".timestamp");
			$aTime_local = $aTime + TIMEOFFSET;
			$date = date("d M Y, h:i A",$aTime_local);

			$aClanTeam  = mysql_result($result,$i, TBL_TEAMS.".TeamID");
			list($tclan, $tclantag, $tclanid) = getClanInfo($aClanTeam);

			switch ($aType) {
				case 'TeamTookFirstPlace':
				$award = EB_AWARD_L2;
				$icon = '<img '.getActivityIconResize(e_PLUGIN."ebattles/images/awards/award_star_gold_3.png").' alt="'.EB_AWARD_L3.'" title="'.EB_AWARD_L3.'"/> ';
				break;
				case 'TeamInTopTen':
				$award = EB_AWARD_L4;
				$icon = '<img '.getActivityIconResize(e_PLUGIN."ebattles/images/awards/award_star_bronze_3.png").' alt="'.EB_AWARD_L5.'" title="'.EB_AWARD_L5.'"/> ';
				break;
				case 'TeamStreak5':
				$award = EB_AWARD_L6;
				$icon = '<img '.getActivityIconResize(e_PLUGIN."ebattles/images/awards/medal_bronze_3.png").' alt="'.EB_AWARD_L7.'" title="'.EB_AWARD_L7.'"/> ';
				break;
				case 'TeamStreak10':
				$award = EB_AWARD_L8;
				$icon = '<img '.getActivityIconResize(e_PLUGIN."ebattles/images/awards/medal_silver_3.png").' alt="'.EB_AWARD_L9.'" title="'.EB_AWARD_L9.'"/> ';
				break;
				case 'TeamStreak25':
				$award = EB_AWARD_L10;
				$icon = '<img '.getActivityIconResize(e_PLUGIN."ebattles/images/awards/medal_gold_3.png").' alt="'.EB_AWARD_L11.'" title="'.EB_AWARD_L11.'"/> ';
				break;
			}

			$award_string = '<tr><td style="vertical-align:top">'.$icon.'</td>';
			$award_string .= '<td><a href="'.e_PLUGIN.'ebattles/claninfo.php?clanid='.$tclanid.'">'.$tclan.'</a>';
			$award_string .= '&nbsp;'.$award;

			$award_string .= ' <div class="smalltext">';
			if (($time-$aTime) < INT_MINUTE )
			{
				$award_string .= EB_MATCH_L7;
			}
			else if (($time-$aTime) < INT_DAY )
			{
				$award_string .= get_formatted_timediff($aTime, $time).'&nbsp;'.EB_MATCH_L8;
			}
			else
			{
				$award_string .= $date;
			}
			$award_string .= '</div></td></tr>';

			$awards[$nbr_awards][0] = $aTime;
			$awards[$nbr_awards][1] = $award_string;
			$nbr_awards ++;
		}
	}

	$text .= '<table style="margin-left: 0px; margin-right: auto;">';
	multi2dSortAsc($awards, 0, SORT_DESC);
	for ($index = 0; $index<min($nbr_awards, $rowsPerPage); $index++)
	{
		$text .= $awards[$index][1];
	}
	$text .= '</table>';

	$text .= '<br />';
	$text .= '
	</div>
	</div>
	';    // tab-page "Latest Awards", tab-pane

	$text .= disclaimer();

	$text .= '
	<script type="text/javascript">
	//<![CDATA[
	setupAllTabs();
	//]]>
	</script>
	';
}

$ns->tablerender($tournament->getField('Name')." ($egame - ".tournamentTypeToString($tournament->getField('Type')).")", $text);
require_once(FOOTERF);
exit;

?>

