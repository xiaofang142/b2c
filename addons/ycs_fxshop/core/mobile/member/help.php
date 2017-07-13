<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}
global $_W, $_GPC;
$openid = m('user')->getOpenid();
$setdata = pdo_fetch("select * from " . tablename('ycs_fxshop_sysset') . ' where uniacid=:uniacid limit 1', array(
    ':uniacid' => $_W['uniacid']
));
$set     = unserialize($setdata['sets']);
include $this->template('member/help');