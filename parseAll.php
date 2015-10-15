<?
require_once('db.php');
$db = new db();
// go through each sport

// call parse.php?sport=sport_name

// display results
if(@$_POST['cmd']){
	switch($_POST['cmd']){
		case 'getSports' :
			$sports = $db->fetch_all("SELECT s_id, s_name FROM sports ORDER BY s_name ASC");
			echo json_encode(array('sports'=>$sports));
		break;		
		case 'parseSport' : 
			$s_id = $_POST['s_id'];
			$force = $_POST['force'];
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "http://".$_SERVER['HTTP_HOST'].'/parse.php?s_id='.$s_id.'&force='.$force);
			//curl_setopt($ch, CURLOPT_POST,1);
			//curl_setopt($ch, CURLOPT_POSTFIELDS, "cmd=getHistory");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);			 
			$res = curl_exec($ch);
			curl_close($ch);
			echo $res;		
		break;	
	}	
	exit;
}

?>
<!doctype html>
<html>
	<head>
		<script src="js/jquery.js"></script>
		<style>
			#sidebar{
				float:left;	
				width:150px;
			}
			#results{
				border:1px solid black;
				margin-left:160px;	
				padding:5px;
			}
		</style>
	</head>
	<body>	
		<div id="sidebar">
			<div id="sports">				
			</div>
			<input type="button" value="Process" id="processB"> <img src="themes/images/ajax-loader.gif" id="spinner" style="display:none"/><br>
			Force Update? <input type="checkbox" id="force"/>
		</div>
		<div id="results">
			Select sports and click 'Process'
		</div>					
		<script>			
			var ESSC = function(){
				this.initialize(arguments);		
			}			
			$.extend(ESSC.prototype,{
				initialize : function(){
					this.sports = [];
					this.processB = $('#processB');
					this.spinner = $('#spinner');
					this.force = $('#force')[0];
					
					this.processB.on('click', $.proxy(this.parse,this));
					
					this.getSports();	
				},
				getSports : function(){
					var self = this;
					$.ajax({
						url : location.href,
						type : 'POST',
						data : { 'cmd' : 'getSports' },
						dataType: "json"
					}).done($.proxy(self.loadSports,this));
				},
				loadSports : function(data){		
					var sports = $('#sports');
					for(var i=0; i<data.sports.length;i++){
						var s = data.sports[i];
						this.sports[s.s_id] = s.s_name;
						sports.append("<input type='checkbox' value='"+s.s_id+"'> "+s.s_name+"<BR>");						
					}
				},
				parse : function(){
					this.processB.prop('disabled',true);
					this.spinner.show();
					this.chkbxs = $('input[type=checkbox]:checked');
					this.index = 0;
					this.parseSport(this.chkbxs[0].value);	
					$('#results').html("Loading...");					
				},
				parseSport : function(s_id){
					var self = this;
					$.ajax({
						url : location.href,
						type : 'post',
						data : {'cmd' : 'parseSport', 's_id':s_id, 'force':this.force.checked?'1':''},
						dataType : 'html'
					}).done(function(data){
						if(self.index==0) $('#results').html('');
						$('#results').append(data);
												
						self.index++;						
						if(self.chkbxs[self.index]){
							self.parseSport(self.chkbxs[self.index].value);
						} else {
							self.processB.prop('disabled',false);
							self.spinner.hide();	
						}
					});
				}
				
				
			});
			var essc;
			$(document).ready(function(){	
				essc = new ESSC();	
			});
		</script>
	</body>
</html>
