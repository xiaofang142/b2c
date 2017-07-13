<?php

/**

 * [MxWeixin System] Copyright (c) 2014 WEIXIN.MX

 * MxWeixin is NOT a free software, it under the license terms, visited http://yqhls.cn/ for more details.

 */

if($action != 'entry') {

	define('FRAME', 'setting');

	$frames = buildframes(array(FRAME));

	$frames = $frames[FRAME];

}

