<?php

/*
*Url http://yqhls.cn
*/ 
if (!defined('IN_IA')) {
    exit('Access Denied');
}
class Ycs_fxshopModule extends WeModule
{
    public function fieldsFormDisplay($rid = 0)
    {
    }
    public function fieldsFormSubmit($rid = 0)
    {
        return true;
    }
    public function settingsDisplay($settings)
    {
    }
}