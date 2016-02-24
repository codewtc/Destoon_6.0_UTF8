<?php
/*
	[Destoon B2B System] Copyright (c) 2008-2015 www.destoon.com
	This is NOT a freeware, use is subject to license.txt
*/
defined('IN_DESTOON') or exit('Access Denied');
class dcache {
	var $pre;
	var $obj;

    function dcache() {
		$this->obj = new Memcache;
		include DT_ROOT.'/file/config/memcache.inc.php';
		$num = count($MemServer);
		$key = $num == 1 ? 0 : abs(crc32($GLOBALS['DT_IP']))%$num;
		$this->obj->connect($MemServer[$key]['host'], $MemServer[$key]['port'], 2);
    }

	function get($key) {
        return $this->obj->get($this->pre.$key);
    }

    function set($key, $val, $ttl = 600) {
         return $this->obj->set($this->pre.$key, $val, 0, $ttl);
    }

    function rm($key) {
        return $this->obj->delete($this->pre.$key);
    }

    function clear() {
        return $this->obj->flush();
    }

	function expire() {
		return true;
	}
}
?>