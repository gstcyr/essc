<?
require_once('phpQuery/phpQuery/phpQuery.php');
header('Content-type: text/html; charset=UTF-8');
require_once('template.php');
require_once('calcStandings.php');

echo "<PRE>";

echo md5("brouwers");

//new standings('72');


exit;

$season = 'FALL';
$year = '2013';

$orderStr = "'";
	$today = date('N')-1;
	$arrNights = array('M','T','W','Th','F','S','Su');
	for($i=$today; $i<$today+7; $i++){
		$j = $i%7;
		$orderStr .= $arrNights[$j]."','";
	}
	$orderStr = trim($orderStr,",'");
	$orderStr = "'".$orderStr."'";
	$sql = "SELECT ut.usr_id, ut.team_id, t.team_name, d.*, s.s_name FROM users_teams ut
			LEFT JOIN teams t ON ut.team_id = t.team_id
			LEFT JOIN divisions d ON t.div_id = d.div_id AND d.season = '$season' AND d.year = '$year'
			LEFT JOIN sports s ON d.s_id = s.s_id
			WHERE ut.usr_id = '$userID'
			ORDER BY FIELD(night,$orderStr)";
			
	echo $sql;



?>