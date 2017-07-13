<?php

/*
*Url http://yqhls.cn
*/
if (!defined('IN_IA')) {
	die('Access Denied');
}
if (!pdo_fieldexists('ycs_dshop_goods', 'taotaoid')) {
	pdo_query('ALTER TABLE ' . tablename('ycs_dshop_goods') . ' ADD `taotaoid` varchar(255) DEFAULT \'\';');
}
if (!pdo_fieldexists('ycs_dshop_goods', 'taobaourl')) {
	pdo_query('ALTER TABLE ' . tablename('ycs_dshop_goods') . ' ADD `taobaourl` varchar(255) DEFAULT \'\';');
}
if (!pdo_fieldexists('ycs_dshop_goods', 'updatetime')) {
	pdo_query('ALTER TABLE ' . tablename('ycs_dshop_goods') . ' ADD `updatetime` int(11) default 0;');
}
if (!pdo_fieldexists('ycs_dshop_goods', 'updatetime')) {
	pdo_query('ALTER TABLE ' . tablename('ycs_dshop_goods') . ' ADD `updatetime` int(11) default 0;');
}
if (!pdo_fieldexists('ycs_dshop_goods_option', 'skuId')) {
	pdo_query('ALTER TABLE ' . tablename('ycs_dshop_goods_option') . ' ADD `skuId` varchar(255) DEFAULT \'\';');
}
if (!pdo_fieldexists('ycs_dshop_goods_spec', 'propId')) {
	pdo_query('ALTER TABLE ' . tablename('ycs_dshop_goods_spec') . ' ADD `propId` varchar(255) DEFAULT \'\';');
}
if (!pdo_fieldexists('ycs_dshop_goods_spec_item', 'valueId')) {
	pdo_query('ALTER TABLE ' . tablename('ycs_dshop_goods_spec_item') . ' ADD `valueId` varchar(255) DEFAULT \'\';');
}