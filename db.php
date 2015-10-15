<?
//requires conf.php
class db{
    private $mysqli;
    private $host, $user, $pass, $db;

    public function __construct($db='essc', $host = '127.0.0.1', $user ='root', $pass='**password**') {
		$this->host = $host;
		$this->user = $user;
		$this->pass = $pass;
		$this->db = $db;

		$this->connect();
    }
    
    public function connect(){
        $this->mysqli = new mysqli($this->host, $this->user, $this->pass, $this->db);
    }

    public function __wakeup()
    {
        $this->connect();
    }

    public function __destruct(){
        $this->close();
    }

    public function close() {
        if($this->mysqli){
            try{
                $this->mysqli->close();
            }
            catch(Exception $e){

            }

            $this->mysqli = null;        
        }
    }

    public function select_db($dbname) {
        return $this->mysqli->select_db($dbname);
    }

    public function query($sql) {
        return $this->mysqli->query($sql);
    }

    public function affected_rows() {
        return $this->mysqli->affected_rows;
    }
    
    public function error() {
        return $this->mysqli->error;
    }

    public function fetch_all($sql,$type=MYSQL_ASSOC) {
        $res = $this->query($sql);
        $arr = array();
        
        if($res){
            while($r = @$res->fetch_array($type)){
                $arr[] = $r;
            }
        }
        return $arr;
    }

    public function fetch_values($sql) {
        $res = $this->query($sql);
        $arr = array();
        
        if($res){
            while($r = $res->fetch_row()){
                $arr[] = $r[0];
            }
        }
        return $arr;
    }

    public function get($sql,$type=MYSQL_ASSOC) {
        $res = $this->query($sql);
        return $res ? $res->fetch_array($type) : array();
    }

    public function getv($sql) {
        $res = $this->query($sql);
        if($res){
            $row = $res->fetch_array(MYSQL_NUM);
            return $row[0];
        }else{
            return '';
        }
    }
    public function getOpts($sql,$selected=NULL){
		$res = $this->query($sql);
		$opts = '';
		while($row = $res->fetch_array(MYSQL_BOTH)){			
			$sel = $selected==$row['value']?'selected':'';
			$opts .= "<option value='".(@$row['value']?$row['value']:$row[0])."' $sel>".(@$row['text']?$row['text']:$row[1])."</option>";
		}
		return $opts;	
	}
    public function prepare($qry){
        $stmt = $this->mysqli->stmt_init();
        $stmt->prepare($qry); 
        return $stmt;
    }

    public function esc(&$val) {
        if(is_array($val)){
            array_walk($val, array($this, 'esc'));
            
        }else{
            if(get_magic_quotes_gpc()) $val = stripslashes($val);
            $val = $this->mysqli->real_escape_string($val);
        }
        
        return $val;
    }
}