<?php

/*
*Url http://yqhls.cn
*/
if (!defined('IN_IA')) {
    exit('Access Denied');
}
global $_W, $_GPC;
$kwd = trim($_GPC['keyword']);
$params = array();
$params[':uniacid'] = $_W['uniacid'];
$condition = " and uniacid=:uniacid";
if (!empty($kwd)) {
	$condition .= " AND ( `nickname` LIKE :keyword or `realname` LIKE :keyword or `mobile` LIKE :keyword )";
	$params[':keyword'] = "%{$kwd}%";
}
$ds = pdo_fetchall('SELECT id,avatar,nickname,openid,realname,mobile FROM ' . tablename('ycs_fxshop_member') . " WHERE 1 {$condition} order by createtime desc", $params);
include $this->template('web/member/query');