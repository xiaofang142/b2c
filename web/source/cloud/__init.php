<?php

/**

 * [MxWeixin System] Copyright (c) 2014 WEIXIN.MX

 * MxWeixin is NOT a free software, it under the license terms, visited http://yqhls.cn/ for more details.

 */



define('IN_GW', true);



if(in_array($action, array('profile', 'device', 'callback', 'appstore'))) {

	$do = $action;

	$action = 'redirect';

}

if($action == 'touch') {

	exit('success');

}

