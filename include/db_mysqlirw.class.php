<?php
/*
	[Destoon B2B System] Copyright (c) 2008-2015 www.destoon.com
	This is NOT a freeware, use is subject to license.txt
*/
defined('IN_DESTOON') or exit('Access Denied');
class db_mysqlirw {
	var $connid;
	var $pre;
	var $querynum = 0;
	var $ttl;
	var $cursor = 0;
	var $cache_id = '';
	var $cache_ttl = '';
	var $halt = 0;
	var $cids = 0;
	var $linked = 1;
	var $result = array();
	var $cache_ids = array();
	var $connrw;
	var $read = false;

	function connect($dbhost, $dbuser, $dbpass, $dbname, $dbttl, $dbcharset, $pconnect = 0) {
		$this->ttl = $dbttl;
		@list($dbhost, $dbport) = explode(':', $dbhost);
		$dbport or $dbport = 3306;
		$this->connid = mysqli_init();
		if(mysqli_real_connect($this->connid, $dbhost, $dbuser, $dbpass, false, $dbport)) {
			//
		} else {
			$this->linked = 0;
			$retry = 5;
			while($retry-- > 0) {
				if(mysqli_real_connect($this->connid, $dbhost, $dbuser, $dbpass, false, $dbport)) {
					$this->linked = 1;
					break;
				}
			}
			if($this->linked == 0) {
				global $DT_BOT;
				if($DT_BOT) dhttp(503);
				if($this->halt) {
					exit(include template('mysql', 'message'));
				} else {
					$this->halt('Can not connect to MySQL server');
				}
			}
		}
		$version = $this->version();
		if($version > '4.1' && $dbcharset) mysqli_query($this->connid, IN_ADMIN ? "SET NAMES '".$dbcharset."'" : "SET character_set_connection=".$dbcharset.", character_set_results=".$dbcharset.", character_set_client=binary");
		if($version > '5.0') mysqli_query($this->connid, "SET sql_mode=''");
		if($dbname && !mysqli_select_db($this->connid, $dbname)) $this->halt('Cannot use database '.$dbname);
		if(IN_ADMIN || defined('DT_MEMBER')) {
			$this->connrw = &$this->connid;
		} else {
			include DT_ROOT.'/file/config/mysqlrw.inc.php';
			$num = count($MYSQLRW);
			if($num == 1) {
				$key = 0;
			} else {
				$key = abs(crc32($GLOBALS['DT_IP']))%$num;
			}
			@list($rwhost, $rwport) = explode(':', $MYSQLRW[$key]['host']);
			$rwport or $rwport = 3306;
			$this->connrw = mysqli_init();
			if(mysqli_real_connect($this->connrw, $rwhost, $MYSQLRW[$key]['user'], $MYSQLRW[$key]['pass'], false, $rwport)) {
				if($version > '4.1' && $dbcharset) mysqli_query($this->connrw, "SET NAMES '".$dbcharset."'");
				if($version > '5.0') mysqli_query($this->connrw, "SET sql_mode=''");
				if($dbname && !mysqli_select_db($this->connrw, $dbname)) $this->halt('Cannot use database '.$dbname.'(R)');
			} else {
				$this->connrw = &$this->connid;
			}
		}
		return $this->connid;
	}

	function select_db($dbname) {
		return mysqli_select_db($this->connid, $dbname);
	}

	function query($sql, $type = '', $ttl = 0, $save_id = false) {
		$this->read = strtoupper(substr($sql, 0, 7)) == 'SELECT ' ? true : false;
		if($this->ttl > 0 && $type == 'CACHE' && $select) {
		if($this->ttl > 0 && $type == 'CACHE' && $this->read) {
			$this->cursor = 0;
			$this->cache_id = md5($sql);
			if($this->cids) $this->cache_ids[] = $this->cache_id;
			$this->result = array();
			$this->cache_ttl = ($ttl ? $ttl : $this->ttl) + mt_rand(-10, 30);
			return $this->_query($sql);
		}
		if(!$save_id) $this->cache_id = 0;
		if(!($query = mysqli_query($this->read ? $this->connrw : $this->connid, $sql))) $this->halt('MySQL Query Error', $sql);
		$this->querynum++;
		return $query;
	}

	function get_one($sql, $type = '', $ttl = 0) {
		$sql = str_replace(array('select ', ' limit '), array('SELECT ', ' LIMIT '), $sql);
		if(strpos($sql, 'SELECT ') !== false && strpos($sql, ' LIMIT ') === false) $sql .= ' LIMIT 0,1';
		$query = $this->query($sql, $type, $ttl);
		$r = $this->fetch_array($query);
		$this->free_result($query);
		return $r;
	}
	
	function count($table, $condition = '', $ttl = 0) {
		global $DT_TIME;
		$sql = 'SELECT COUNT(*) as amount FROM '.$table;
		if($condition) $sql .= ' WHERE '.$condition;
		$r = $this->get_one($sql, $ttl ? 'CACHE' : '', $ttl);
		return $r ? $r['amount'] : 0;
	}

	function fetch_array($query, $result_type = MYSQL_ASSOC) {
		return $this->cache_id ? $this->_fetch_array($query) : mysqli_fetch_array($query, $result_type);
	}

	function affected_rows() {
		return mysqli_affected_rows($this->read ? $this->connrw : $this->connid);
	}

	function num_rows($query) {
		return mysqli_num_rows($query);
	}

	function num_fields($query) {
		return mysqli_num_fields($query);
	}

	function result($query, $row) {//DEBUG
		return @mysqli_result($query, $row);
	}

	function free_result($query) {
		//if(is_resource($query) && get_resource_type($query) === 'mysql result') {
			return @mysqli_free_result($query);
		//}
	}

	function insert_id() {
		return mysqli_insert_id($this->connid);
	}

	function fetch_row($query) {
		return mysqli_fetch_row($query);
	}

	function version() {
		return mysqli_get_server_info($this->connid);
	}

	function close() {
		mysqli_close($this->connrw);
		return mysqli_close($this->connid);
	}

	function error() {
		return @mysqli_error($this->read ? $this->connrw : $this->connid);
	}

	function errno() {
		return intval($this->error());
	}

	function halt($message = '', $sql = '')	{
		if($message && DT_DEBUG) log_write("\t\t<query>".$sql."</query>\n\t\t<errno>".$this->errno()."</errno>\n\t\t<error>".$this->error()."</error>\n\t\t<errmsg>".$message."</errmsg>\n", 'sql');
		if($this->halt) message('MySQL Query:'.str_replace($this->pre, '[pre]', $sql).' <br/> MySQL Error:'.str_replace($this->pre, '[pre]', $this->error()).' MySQL Errno:'.$this->errno().' <br/>Message:'.$message);
	}

	function _query($sql) {
		global $dc;
		$this->result = $dc->get($this->cache_id);
		if(!is_array($this->result)) {
			$tmp = array(); 
			$result = $this->query($sql, '', '', true);
			while($r = mysqli_fetch_array($result, MYSQL_ASSOC)) {
				$tmp[] = $r; 
			}
			$this->result = $tmp;
			$this->free_result($result);
			$dc->set($this->cache_id, $tmp, $this->cache_ttl);
		}
		return $this->result;
	}

	function _fetch_array($query = array()) {
		if($query) $this->result = $query; 
		if(isset($this->result[$this->cursor])) {
			return $this->result[$this->cursor++];
		} else {
			$this->cursor = $this->cache_id = 0;
			return array();
		}
	}
}
?>