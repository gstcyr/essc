<?
require_once('db.php');
$db = new db();
session_start();
	$userID = @$_SESSION['usr_id'];		
session_write_close();
if($userID && (
	strpos($_SERVER['PHP_SELF'],'login.php')!==false 
	||
	strpos($_SERVER['PHP_SELF'],'register.php')!==false
)) header('Location: index.php');

if(!$userID && ( 
		strpos($_SERVER['PHP_SELF'],'login.php')===false 
		&& 
		strpos($_SERVER['PHP_SELF'],'register.php')===false
		)
	){
	header('Location: login.php');	
}
function numord($number){
	$ends = array('th','st','nd','rd','th','th','th','th','th','th');
	if (($number %100) >= 11 && ($number%100) <= 13)
	   $abbreviation = $number. 'th';
	else
	   $abbreviation = $number. $ends[$number % 10];	
   return $abbreviation; 
}

$nights = array('M'=>'Monday','T'=>'Tuesday','W'=>'Wednesday','Th'=>'Thursday','F'=>'Friday','S'=>'Saturday','Su'=>'Sunday');
$currSeason = $db->getv("SELECT DISTINCT CONCAT(season,' ',year) FROM divisions ORDER BY year DESC, FIELD(season,'FALL','SUMMER','SPRING','WINTER') LIMIT 1");
$currSeason = 'FALL 2014';
//$seasons = array('WINTER','SPRING','SUMMER','FALL');

function htmlHead($title){
?><!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?=$title?></title>		
	<link rel="shortcut icon" href="favicon.ico" />
	<link rel="stylesheet" href="themes/essc.min.css" />
	<link rel="stylesheet" href="themes/jquery.mobile.structure-1.3.2.css" />	
	<script src="js/jquery.js"></script>
    <script src="js/jquery.cookie.js"></script>
	<script src="js/essc.js"></script>
	<script src="js/jquery.mobile-1.3.2.js"></script>
	<script src="http://ajax.aspnetcdn.com/ajax/jquery.validate/1.11.1/jquery.validate.js"></script>
	<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
</head>
<link rel="apple-touch-icon" sizes="57x57" href="resources/apple-icon-57x57.png" />
<link rel="apple-touch-icon" sizes="72x72" href="resources/apple-icon-72x72.png" />
<link rel="apple-touch-icon" sizes="114x114" href="resources/apple-icon-114x114.png" />
<link rel="apple-touch-icon" sizes="144x144" href="resources/apple-icon-144x144.png" />
<style>
	th, td {text-align:left;}	
	.ui-icon-marker {
		background-image: url("resources/icon-marker.png");
	}
	.ui-icon-vs {
		background-image: url("resources/icon-vs.png");
	}
	.team{
		display:inline-block;
		max-width:50%;
		text-overflow:ellipsis;
		overflow-x:hidden;
	}
	.myteam{ color:#063; font-weight:bold }
	.matches{ 
		font-size:0.9em;
	}
	label.error{
		color: red;	
		padding-top: .5em;
		vertical-align:top;
		margin-left:22%;
	}
	.ui-grid-a > .ui-block-a.main { width: 66% }
	.ui-grid-a > .ui-block-b.side { width: 33%; } 
	.disclaimer{ font-size:0.8em; margin:4px; }
	ul li.ui-li {
		border-top:1px solid #999 !important;
	}
	ul li.ui-li-divider{
		border-top:none !important;
	}
	@media ( min-width: 40em ) {
		/* Show the table header rows and set all cells to display: table-cell */ 
		.standingsTable td,
		.standingsTable th,
		.standingsTable tbody th,
		.standingsTable tbody td,
		.standingsTable thead td,
		.standingsTable thead th {
			display: table-cell;
			margin: 0;
		}
		/* Hide the labels in each cell */ 
		.standingsTable td .ui-table-cell-label,
		.standingsTable th .ui-table-cell-label { 
			display: none;
		}
		.ui-table-top-header{
			/*border-bottom:2px solid black !important;*/
			text-align:center !important;				
		}
		.standingsTable th.left, .standingsTable td.left{
			text-align:left !important;	
		}
		.standingsTable td{
			text-align:center !important;	
		}
		.standingsTable th{
			text-align:center !important;	
		}
	}
	@media screen and (max-width:39.99em){
		.standingsTable th:nth-child(2){ 
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
		.standingsTable th:nth-child(2) a {
			color:#fff !important;	
		}
		.standingsTable th:nth-child(2):before
		{ 
			content	: attr(rank) " - ";	
		}
		/* Hide the label in the first cell */ 
		.standingsTable td:first-child, .standingsTable th:nth-child(2) .ui-table-cell-label { 
			display:none;
		}
		.ui-table-reflow th .ui-table-cell-label-top,
		.ui-table-reflow td .ui-table-cell-label-top {
			font-weight: bold;
			color:#319B47;
			font-size:1.1em;
		}
		.ui-table-reflow tbody th{
			margin-top:1em !important;	
		}
	}
		.th-groups th {
		/*background-color:#09553E; rgba(0,0,0,0.07);
		background-image: linear-gradient( #00723C,#005D31 );*/
		border-right:1px solid #fff;
		text-align:center;
	}
	
</style>
<body>	
<?				
}
function pageFoot(){
?>		<div data-role="footer" data-id="foot" data-position="fixed">			
			<div data-role="navbar">
				<ul>
					<li><a href="index.php" <?=strpos($_SERVER['PHP_SELF'],'index.php')?'class="ui-btn-active ui-state-persist"':''?>>My Games</a></li>
					<li><a href="users.php" <?=strpos($_SERVER['PHP_SELF'],'users.php')?'class="ui-btn-active ui-state-persist"':''?>>Find Team</a></li>
					<li><a href="companies.php" <?=strpos($_SERVER['PHP_SELF'],'companies.php')?'class="ui-btn-active ui-state-persist"':''?>>Companies</a></li>				
					<li><a href="history.php" <?=strpos($_SERVER['PHP_SELF'],'history.php')?'class="ui-btn-active ui-state-persist"':''?>>History</a></li>
				</ul>
			</div>
		</div>
<?	
}

function htmlFoot(){
?></body>
</html><?
}
?>