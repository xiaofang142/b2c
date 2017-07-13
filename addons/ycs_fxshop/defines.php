<?php

/*
*Url http://yqhls.cn
*/ 
if (!defined('IN_IA')) {
    exit('Access Denied');
}
define('YCS_FXSHOP_DEBUG', false);
!defined('YCS_FXSHOP_PATH') && define('YCS_FXSHOP_PATH', IA_ROOT . '/addons/ycs_fxshop/');
!defined('YCS_FXSHOP_CORE') && define('YCS_FXSHOP_CORE', YCS_FXSHOP_PATH . 'core/');
!defined('YCS_FXSHOP_PLUGIN') && define('YCS_FXSHOP_PLUGIN', YCS_FXSHOP_PATH . 'plugin/');
!defined('YCS_FXSHOP_INC') && define('YCS_FXSHOP_INC', YCS_FXSHOP_CORE . 'inc/');
!defined('YCS_FXSHOP_URL') && define('YCS_FXSHOP_URL', $_W['siteroot'] . 'addons/ycs_fxshop/');
!defined('YCS_FXSHOP_STATIC') && define('YCS_FXSHOP_STATIC', YCS_FXSHOP_URL . 'static/');
!defined('YCS_FXSHOP_PREFIX') && define('YCS_FXSHOP_PREFIX', 'ycs_fxshop_');
