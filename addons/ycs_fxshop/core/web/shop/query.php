<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}
global $_W, $_GPC;
$kwd                = trim($_GPC['keyword']);
$params             = array();
$params[':uniacid'] = $_W['uniacid'];
$condition          = " and uniacid=:uniacid";
if (!empty($kwd)) {
    $condition .= " AND `title` LIKE :keyword";
    $params[':keyword'] = "%{$kwd}%";
}
$ds = pdo_fetchall('SELECT id,title,thumb FROM ' . tablename('ycs_fxshop_goods') . " WHERE 1 {$condition} order by createtime desc", $params);
$ds = set_medias($ds, 'thumb');
include $this->template('web/shop/query');