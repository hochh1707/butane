<?php

class database {
	var $host;
	var $user;
	var $pass;
	var $database;
	var $persistent = 0;
	var $last_query;
	var $prior_query;
	var $result;
	var $connection_id;
	var $num_queries = 0;
	var $start_time;
	var $domain;
	var $timeoffset;
	var $datetime;
	var $unixtime;
	function __construct($array) {
        $this->host = $array['hostname'] ? $array['hostname'] : 'localhost' ;
		$this->user = $array['username'];
		$this->pass = $array['password'];
		$this->database = $array['database'];
		$this->persistent = $array['persistent'];
        $this->timezone = $array['timezone'];
		$this->unixtime = time();
		$this->datetime=date('Y/m/d H:i:s',$this->unixtime);
		return 1; //Success.
	}
	function connect() {
		if ($this->persistent) {
			$this->connection_id = mysql_pconnect($this->host, $this->user, $this->pass) or $this->connection_error();
		} else {
			$this->connection_id = mysql_connect($this->host, $this->user, $this->pass, 1) or $this->connection_error();
		}
		mysql_select_db($this->database, $this->connection_id);
        $this->query("set session wait_timeout=600");
		return $this->connection_id;
	}
    function set_timezone($zone='') {
        if ($zone) {
            $this->timezone=$zone;
        }
        if ($this->timezone) {
            date_default_timezone_set($this->timezone);
        }
    }
    function disconnect() {
		if ($this->connection_id) {
			mysql_close($this->connection_id);
			$this->connection_id = 0;
			return 1;
		} else {
			return 0;
		}
	}
	function change_db($database) {
		mysql_select_db($database, $this->connection_id);
		$this->database = $database;
	}
	function query($query,$errortrap=True) {
		$this->prior_query = $this->last_query;
		$this->last_query = $query;
		$this->num_queries++;
		if ($errortrap==True) {
			$this->result = mysql_query($this->last_query, $this->connection_id) or $this->query_error();
		}else{
			$this->result = mysql_query($this->last_query, $this->connection_id);
		}
		return $this->result;
	}

    function row($result = 0) {
	    if (!$result) {
			$result = $this->result;
		}
		$ret=mysql_fetch_row($result);
        $this->result=$result;
        return $ret;
	}
	function assoc($result = 0) {
	    if (!$result) {
			$result = $this->result;
		}
		$ret=mysql_fetch_assoc($result);
        $this->result=$result;
        return $ret;
	}
	function num($result = 0) {
		if (!$result) {
			$result = $this->result;
		}
		return mysql_num_rows($result);
	}

	function numq($query,$errortrap=True) {
		$this->prior_query = $this->last_query;
		$this->last_query = $query;
		$this->num_queries++;
		if ($errortrap==True) {
			$this->result = mysql_query($this->last_query, $this->connection_id) or $this->query_error();
		}else{
			$this->result = mysql_query($this->last_query, $this->connection_id);
		}
		$result=$this->result;
        return mysql_num_rows($result);
	}

	function id() {
		return mysql_insert_id($this->connection_id);
	}

	function connection_error() {
		die("<b>FATAL ERROR:</b> Could not connect to database on {$this->host} (" . mysql_error() . ")");
	}
	function query_error() {
		global $isdev, $function, $userid, $ir, $db;
		if (ereg("Table '\.\/[a-zA-Z_]*\/(.*)' is marked as crashed and should be repaired",mysql_error(),$result)) {
			$result=str_replace("'",'',$result[1]);
			$db->query("repair table {$result}");
			#mail('thomas@Tremaininc.com', "{$this->domain} table {$result} repaired", '');
			echo "Query Error, Please try again.";
			die();
		}
		echo "Query Error, emailed to developers";

		$subject = "QUERY ERROR: {$this->domain} ";
		$body = "Error: " . mysql_error();
		$body .= "\nQuery: {$this->last_query}\n";
		$body .= "page={$_SERVER['REQUEST_URI']}\n";
		$body .= "username={$ir['username']} [{$ir['userid']}]\n";
		$body .= "\nPrior Query: {$this->prior_query}\n";
		$body .= "function: $function\n";
		$body .= 'IP: '.IP."\n";
		$body .= "function: $function\n";

		echo nl2br($body);
		//mail('thomas@Tremaininc.com',$subject,$body);
		mail('agripp36@gmail.com',$subject,$body);
		die();
	}

	function single($result = 0) {
		if (!$result) {
			$result = $this->result;
		}
        if (mysql_num_rows($result)){
    		return mysql_result($result, 0, 0);
        }else{
            return;
        }
	}
	function singleq($query,$errortrap=True) {
		$this->prior_query = $this->last_query;
		$this->last_query = $query;
		$this->num_queries++;
		if ($errortrap==True) {
			$this->result = mysql_query($this->last_query, $this->connection_id) or $this->query_error();
		}else{
			$this->result = mysql_query($this->last_query, $this->connection_id);
		}
		$result=$this->result;
        if (mysql_num_rows($result)){
    		return mysql_result($result, 0, 0);
        }else{
            return False;
        }
	}

	function affected($conn = null) {
		return mysql_affected_rows($this->connection_id);
	}

	function mymicro() {
		$m = explode(" ", microtime());
		return $m[0] + $m[1];
	}
	function clock_start() {
		$this->start_time = $this->mymicro();
	}
	function clock_end() {
		$t = $this->mymicro();
		return round($t - $this->start_time, 4);
	}
	function clean_input($in){
        if (!is_array($in)) {
            $in=stripslashes($in);
            $in=str_replace(array("<",">",'"',"'","\n"), array("&lt;","&gt;","&quot;","&#39;","<br />"), $in);
        }else{
            foreach ($in as $key=>$value) {
                $in[$key]=$this->clean_input($value);
            }
        }
        return $in;
    }
	function clean_input_nohtml($in) {
		$in = stripslashes($in);
		return str_replace(array("'"), array("&#39;"), $in);
	}
	function clean_input_nonform($in) {
		return addslashes($in);
	}
	function easy_insert($table, $data) {
		$query = "INSERT INTO `$table` SET ";
		$i = 0;
		foreach($data as $k => $v) {
			$i++;
			if ($i > 1) {
				$query .= ", ";
			}
			$query .= "`$k` = '" . mysql_real_escape_string($v) . "'";
		}

		return $this->query($query);
	}
	function make_integer($str, $positive = 1) {
		$str = (string) $str;
		$ret = "";
		for($i = 0;$i < strlen($str);$i++) {
			if ((ord($str[$i]) > 47 && ord($str[$i]) < 58) or ($str[$i] == "-" && $positive == 0)) {
				$ret .= $str[$i];
			}
		}
		if (strlen($ret) == 0) {
			return "0";
		}
		return $ret;
	}
	function unhtmlize($text) {
		return str_replace(array("<br />","<br/>","<br>","</br>"),"\n", $text);
	}
	function escape($text) {
		return mysql_real_escape_string($text, $this->connection_id);
	}

	function unixtime(){
        return $this->unixtime;
    }
    function datetime(){
        return $this->datetime;
    }
}

?>
