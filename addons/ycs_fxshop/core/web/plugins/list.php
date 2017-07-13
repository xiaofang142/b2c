<?php

/*
*Url http://yqhls.cn
*/
global $_W, $_GPC;
$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
if ($operation == 'display') {
    $plugins = m('plugin')->getAll();
    include $this->template('web/plugins/list');
    exit;
}