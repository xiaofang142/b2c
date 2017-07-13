<?php

/*
*Url http://yqhls.cn
*/ 
global $_W, $_GPC;
if (!$_W['isfounder']) {
    message('无权访问!');
}
if (checksubmit('submit')) {
    if (!empty($_GPC['displayorder'])) {
        foreach ($_GPC['displayorder'] as $id => $displayorder) {
            pdo_update('ycs_fxshop_plugin', array('displayorder' => $displayorder, 'name' => $_GPC['name'][$id]), array('id' => $id));
        }
        message('插件信息更新成功！', $this->createWebUrl('plugins/setting'), 'success');
    }
}
$condition = '';
if (!empty($_GPC['keyword'])) {
    $condition .= ' and identity like :keyword or name like :keyword';
    $params[':keyword'] = "%{$_GPC['keyword']}";
}
$list = pdo_fetchall('select * from ' . tablename('ycs_fxshop_plugin') . " where 1 {$condition} order by displayorder asc", $params);
$total = count($list);
include $this->template('web/plugins/setting');
die;