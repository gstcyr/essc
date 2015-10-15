<?
require_once('template.php');
require_once('calcStandings.php');

$divID = @$_GET['divID'];
$divInfo = $db->get("SELECT d.*, s_name FROM divisions d LEFT JOIN sports s ON d.s_id = s.s_id WHERE div_id = '$divID'");
$standings = new standings($divID);

htmlHead('Standings');
?>	<div id="standingsPage" data-role="page" data-theme="a">
		<div data-role="header">
			<a href="#" data-role="button" data-icon="back" data-iconpos="notext" data-rel="back"></a>
			<h3>Standings</h3>
			<a href="index.php" data-icon="home" data-iconpos="notext">Home</a>
		</div>
		<div data-role="content">
			<h3><?=$nights[$divInfo['night']].' '.$divInfo['div_name'].' '.$divInfo['s_name']?></h3>
			<table data-role="table" class="standingsTable table-stroke ui-table">
				<thead>
					<tr class="th-groups ui-bar-a">
						<th colspan="2"></th>
						<th colspan="3" class="ui-table-top-header">Matches</th>
<?					if($standings->multiMatch){
?>						<th></th>
						<th colspan="3" class="ui-table-top-header">Games</th>
						<th></th>
						<th colspan="3" class="ui-table-top-header">Points</th>
<?					} else {
?>						<th></th>
						<th class="ui-table-top-header"></th>
						<th colspan="3" class="ui-table-top-header">Games</th>
<?					}
?>					</tr>
					<tr>
						<th></th>
						<th class="left">Team</th>
						<th>Wins</th>
						<th>Losses</th>
						<th>Ties</th>						
<?					if($standings->multiMatch){
?>						<th>Win %</th>
						<th>Wins</th>
						<th>Losses</th>
						<th>Ties</th>
						<th>Diff</th>
<?					} else {
?>						<th>Points</th>
						<th>Sprit</th>
<?					}
?>						<th>For</th>
						<th>Agst.</th>
						<th>+/-</th>
					</tr>
				</thead>
				<tbody>
<?				foreach($standings->results as $i=>$r){
?>					<tr>
						<td><?=$i+1?></td>
						<th rank="<?=numord($i+1)?>" class="left">
							<a href="history.php?team=<?=$r['TeamID']?>" data-ajax="false"><?=$db->getv("SELECT team_name FROM teams WHERE team_id = '$r[TeamID]'");?></a>
						</th>
						<td><?=$r['MatchesWon']?></td>
						<td><?=$r['MatchesLost']?></td>
						<td><?=$r['MatchesTied']?></td>
<?					if($standings->multiMatch){
?>						<td><?=$r['WinPercentage']*100?>%</td>
						<td><?=$r['GamesWon']?></td>
						<td><?=$r['GamesLost']?></td>
						<td><?=$r['GamesTied']?></td>
						<td><?=$r['GamesDiff']?></td>
<?					} else {
?>						<td><?=$r['TotalPoints']?></td>
						<td><?=$r['Spirit'].'/'.$r['MaxSpirit']?></td>						
<?					}
?>						<td><?=$r['PointsFor']?></td>
						<td><?=$r['PointsAgainst']?></td>
						<td><?=$r['PointsDiff']?></td>
					</tr>
<?					}
?>				</tbody>							
			</table>
		</div>
	</div>
<?
htmlFoot();