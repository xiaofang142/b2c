<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}
global $_W, $_GPC;

ca('statistics.view.goods');
$pindex    = max(1, intval($_GPC['page']));
$psize     = 20;
$condition = ' and og.uniacid=:uniacid and o.status>=1';
$params    = array(
    ':uniacid' => $_W['uniacid']
);
if (empty($starttime) || empty($endtime)) {
    $starttime = strtotime('-1 month');
    $endtime   = time();
}
if (!empty($_GPC['datetime'])) {
    $starttime = strtotime($_GPC['datetime']['start']);
    $endtime   = strtotime($_GPC['datetime']['end']);
    if (!empty($_GPC['searchtime'])) {
        $condition .= " AND o.createtime >= :starttime AND o.createtime <= :endtime ";
        $params[':starttime'] = $starttime;
        $params[':endtime']   = $endtime;
    }
}
if (!empty($_GPC['title'])) {
    $condition .= " and g.title like :title";
    $params[':title'] = "%{$_GPC['title']}%";
}
$orderby = !isset($_GPC['orderby']) ? 'og.price' : (empty($_GPC['orderby']) ? 'og.price' : 'og.total');
$sql     = "select og.price,og.total,o.createtime,o.ordersn,g.title,g.thumb from " . tablename('ycs_fxshop_order_goods') . ' og ' . " left join " . tablename('ycs_fxshop_order') . " o on o.id = og.orderid " . " left join " . tablename('ycs_fxshop_goods') . " g on g.id = og.goodsid " . " where 1 {$condition} order by {$orderby} desc ";
if (empty($_GPC['export'])) {
    $sql .= "LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
}
$list = pdo_fetchall($sql, $params);
foreach ($list as &$row) {
    $row['goods'] = pdo_fetchall("SELECT g.thumb,og.price,og.total,g.title,og.optionname from " . tablename('ycs_fxshop_order_goods') . " og" . " left join " . tablename('ycs_fxshop_goods') . " g on g.id = og.goodsid " . " where og.uniacid = :uniacid and og.orderid=:orderid order by og.createtime  desc ", array(
        ':uniacid' => $_W['uniacid'],
        ':orderid' => $row['id']
    ));
}
unset($row);
$total = pdo_fetchcolumn("select  count(*) from " . tablename('ycs_fxshop_order_goods') . ' og ' . " left join " . tablename('ycs_fxshop_order') . " o on o.id = og.orderid " . " left join " . tablename('ycs_fxshop_goods') . " g on g.id = og.goodsid " . " where 1 {$condition}", $params);
$pager = pagination($total, $pindex, $psize);
if ($_GPC['export'] == 1) {
    ca('statistics.export.goods');
    plog('statistics.export.goods', '导出商品销售明细');
    $list[] = array(
        'data' => '商品总计',
        'count' => $total
    );
    foreach ($list as &$row) {
        $row['createtime'] = date('Y-m-d H:i', $row['createtime']);
    }
    unset($row);
    m('excel')->export($list, array(
        "title" => "商品销售报告-" . date('Y-m-d-H-i', time()),
        "columns" => array(
            array(
                'title' => '订单号',
                'field' => 'ordersn',
                'width' => 24
            ),
            array(
                'title' => '商品名称',
                'field' => 'title',
                'width' => 12
            ),
            array(
                'title' => '数量',
                'field' => 'total',
                'width' => 12
            ),
            array(
                'title' => '价格',
                'field' => 'price',
                'width' => 12
            ),
            array(
                'title' => '成交时间',
                'field' => 'createtime',
                'width' => 24
            )
        )
    ));
}
load()->func('tpl');
include $this->template('web/statistics/goods');
exit;