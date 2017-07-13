<?php

/*
*Url http://yqhls.cn
*/ 
if (!defined('IN_IA')) {
    exit('Access Denied');
}
require IA_ROOT . '/addons/ycs_fxshop/version.php';
require IA_ROOT . '/addons/ycs_fxshop/defines.php';
require YCS_FXSHOP_INC . 'functions.php';
require YCS_FXSHOP_INC . 'processor.php';
require YCS_FXSHOP_INC . 'plugin/plugin_model.php';
class Ycs_fxshopModuleProcessor extends Processor
{
    public function respond()
    {
        return parent::respond();
    }
}