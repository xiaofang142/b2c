<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

global $_W, $_GPC;
ca('statistics.view.sale_analysis');
function sale_analysis_count($sql)
{
    $c = pdo_fetchcolumn($sql);
    return intval($c);
}
$member_count    = sale_analysis_count("SELECT count(*) FROM " . tablename('ycs_fxshop_member') . "   WHERE uniacid = '{$_W['uniacid']}' ");
$orderprice      = sale_analysis_count("SELECT sum(price) FROM " . tablename('ycs_fxshop_order') . " WHERE status>=1 and uniacid = '{$_W['uniacid']}' ");
$ordercount      = sale_analysis_count("SELECT count(*) FROM " . tablename('ycs_fxshop_order') . " WHERE status>=1 and uniacid = '{$_W['uniacid']}' ");
$viewcount       = sale_analysis_count("SELECT sum(viewcount) FROM " . tablename('ycs_fxshop_goods') . " WHERE uniacid = '{$_W['uniacid']}' ");
$member_buycount = sale_analysis_count("SELECT count(*) from " . tablename('ycs_fxshop_order') . " o " . " left join " . tablename('ycs_fxshop_member') . " m on o.openid = m.openid " . "  WHERE o.uniacid = '{$_W['uniacid']}' and o.status>=1 " . " group by m.openid ");
include $this->template('web/statistics/sale_analysis');