<?php

/**

 * [MxWeixin System] Copyright (c) 2014 WEIXIN.MX

 * MxWeixin is NOT a free software, it under the license terms, visited http://yqhls.cn/ for more details.

 */

defined('IN_IA') or exit('Access Denied');

header('content-type: text/css');

$src = '';

if(!empty($_W['styles']['imgdir'])) {

	$src = $_W['styles']['imgdir'];

}