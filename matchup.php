<?
require_once('template.php');

function getResults($team1, $score1, $score2){
	$scores1 = explode(',',$score1);
	$scores2 = explode(',',$score2);
	$scores = array();
	$win1 = $win2 = 0;
	foreach($scores1 as $i=>$s){
		if($s > $scores2[$i]) $win1++;
		elseif($s < $scores2[$i]) $win2++;
		if($team1) array_push($scores, $s.'-'.$scores2[$i]);
		else array_push($scores,$scores2[$i].'-'.$s);
	}
	if($win1==0 && $win2==0)
		$result = 'N/A';
	else if($team1){
		$result = $win1==$win2?'Tie':($win1>$win2?'Win':'Loss');	
	} else{
		$result = $win1==$win2?'Tie':($win1>$win2?'Loss':'Win');	
	}	
	$scoreStr = implode(', ',$scores);
	return array('scores'=>$scoreStr, 'result'=>$result);
}

$team1 = $_GET['team1'];
$team2 = $_GET['team2'];
$team1name = $db->getv("SELECT team_name FROM teams WHERE team_id = '$team1'");
$team2name = $db->getv("SELECT team_name FROM teams WHERE team_id = '$team2'");

$gamesLeft = $db->fetch_all("SELECT * FROM schedules s WHERE '$team1' IN (team1_id, team2_id) AND score1 IS NOT NULL ORDER BY datetime, matchnum ASC");
$html = '';

foreach($gamesLeft as $g){
	$scoreLeft = ''; $scoreRight = ''; $classLeft = ''; $classRight = '';

	$res = getResults($g['team1_id']==$team1,$g['score1'],$g['score2']);	
	$scoreLeft = $res['scores'];
	$classLeft = $res['result'];
	$opponent = $g['team1_id']==$team1?$g['team2']:$g['team1'];
	$opponentID =  $g['team1_id']==$team1?$g['team2_id']:$g['team1_id'];
	
	$gameRight = $db->get("SELECT * FROM schedules s WHERE '$opponentID' IN (team1_id, team2_id) AND '$team2' IN (team1_id, team2_id) AND score1 IS NOT NULL");
	
	if(!count($gameRight)) continue;	
	if($opponentID == $team2){
		$scoreRight = 'N/A';
	} else{
		$res = getResults($gameRight['team1_id']==$team2,$gameRight['score1'],$gameRight['score2']);
		$scoreRight = $res['scores'];
		$classRight = $res['result'];		
	}
	
	$html .= "<tr>";
	$html .= "	<td class='$classLeft'>$scoreLeft</td>";
	$html .= "	<td class=''><a href='matchup.php?team1=$team1&team2=$opponentID' data-ajax='false'>$opponent</a>";
	$html .= "	<td class='$classRight'>$scoreRight</td>";
	$html .= "</tr>";
				
}
if($html == '') $html = "<div class='ui-block-a'></div><div class='ui-block-b'>No Matchups Available</div>";

htmlHead('Matchup');
?>	<div id="matchup" data-role="page" data-theme="a">
		<div data-role="header">
			<a href="#" data-icon="back" data-iconpos="notext" data-rel="back"></a>
			<h3>View Matchup</h3>
			<a href="index.php" data-icon="home" data-iconpos="notext">Home</a>
			<style>
				.ui-grid-b div{
					text-align:center;	
				}
				.Win{
					background-color:#2BFA5F;	
				}
				.Loss{
					background-color:#F66;	
				}
				.matchupTable th, .matchupTable td{
					width:33.3%;
					text-align:center;	
				}
				.matchupTable th{
					border: 1px solid #006837 /*{a-bar-border}*/;
					background: #006837 /*{a-bar-background-color}*/;
					color: #ffffff /*{a-bar-color}*/;
					font-weight: bold;
					text-shadow: 0 /*{a-bar-shadow-x}*/ 1px /*{a-bar-shadow-y}*/ 0 /*{a-bar-shadow-radius}*/ #444444 /*{a-bar-shadow-color}*/;
					background-image: -webkit-gradient(linear, left top, left bottom, from( #00723c /*{a-bar-background-start}*/), to( #005d31 /*{a-bar-background-end}*/)); /* Saf4+, Chrome */
					background-image: -webkit-linear-gradient( #00723c /*{a-bar-background-start}*/, #005d31 /*{a-bar-background-end}*/); /* Chrome 10+, Saf5.1+ */
					background-image:    -moz-linear-gradient( #00723c /*{a-bar-background-start}*/, #005d31 /*{a-bar-background-end}*/); /* FF3.6 */
					background-image:     -ms-linear-gradient( #00723c /*{a-bar-background-start}*/, #005d31 /*{a-bar-background-end}*/); /* IE10 */
					background-image:      -o-linear-gradient( #00723c /*{a-bar-background-start}*/, #005d31 /*{a-bar-background-end}*/); /* Opera 11.10+ */
					background-image:         linear-gradient( #00723c /*{a-bar-background-start}*/, #005d31 /*{a-bar-background-end}*/);		
				}
			</style>
		</div>
		<div data-role="content">
			<table class="matchupTable" width="100%">
				<thead>
					<th><?=$team1name?></th>
					<th><br>vs</th>
					<th><?=$team2name?></th>
				</thead>
				<tbody>
					<?=$html?>
				</tbody>
			</table>
		</div>
	</div>
<? 
htmlFoot();