<?php

if (!defined('IN_IA')) {
	exit('Access Denied');
}
global $_W, $_GPC;
if ($_W['ispost']) {
	$pindex = max(1, intval($_GPC['page']));
	$psize = 20;
	$condition = ' and uniacid=:uniacid and delete=0';
	$creditstart = intval($_GPC['creditstart']);
	$creditend = intval($_GPC['creditend']);
	if (!empty($creditstart)) {
		$condition .= ' and credit>=' . $creditstart;
	}
	if (!empty($creditend)) {
		$condition .= ' and credit<=' . $creditend;
	}
	$list = pdo_fetchall('SELECT *  FROM ' . tablename('ycs_dshop_exchange_goods') . " WHERE 1 {$condition} ORDER BY id LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
	$total = pdo_column('SELECT count(*) FROM ' . tablename('ycs_dshop_exchange_goods') . " WHERE 1 {$condition}");
	show_json(1, array('total' => $total, 'list' => $list));
}
include $this->template('list');