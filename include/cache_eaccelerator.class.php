<?php
/*
	[Destoon B2B System] Copyright (c) 2008-2015 www.destoon.com
	This is NOT a freeware, use is subject to license.txt
*/
defined('IN_DESTOON') or exit('Access Denied');
class dcache {
	var $pre;

    function dcache() {
		//
    }

    function get($key) {
        return eaccelerator_get($this->pre.$key);
    }

    function set($key, $val, $ttl = 600) {
        eaccelerator_lock($this->pre.$key);
        return eaccelerator_put($this->pre.$key, $val, $ttl);
    }

    function rm($key) {
        return eaccelerator_rm($this->pre.$key);
    }

    function clear() {
        return eaccelerator_gc();
    }

	function expire() {
		return eaccelerator_gc();
	}
}
?>