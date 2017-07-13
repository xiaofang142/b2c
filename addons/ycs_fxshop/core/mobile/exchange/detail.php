<?php

if (!defined('IN_IA')) {
	exit('Access Denied');
}
global $_W, $_GPC;
if ($_W['ispost']) {
	$goodsid = intval($_GPC['id']);
	$goods = pdo_fetch('SELECT * FROM ' . tablename('ycs_dshop_exchange_goods') . ' WHERE id = :id', array(':id' => $goodsid));
	if (empty($goods)) {
		show_json(0, '抱歉，兑换商品不存在或是已经被删除！', '', 'error');
	}
	pdo_query('update ' . tablename('ycs_dshop_exchange_goods') . " set viewcount=viewcount+1 where id=:id and weid='{$_W['uniacid']}' ", array(':id' => $goodsid));
}
include $this->template('mobile/shop/detail');