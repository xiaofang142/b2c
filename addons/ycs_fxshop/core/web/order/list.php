<?php


global $_W, $_GPC;

$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
if ($operation == 'display') {
    ca('order.view.status_1|order.view.status0|order.view.status1|order.view.status2|order.view.status3|order.view.status4|order.view.status5');
    $pindex    = max(1, intval($_GPC['page']));
    $psize     = 20;
    $status    = $_GPC['status'];
    $sendtype  = !isset($_GPC['sendtype']) ? 0 : $_GPC['sendtype'];
    $condition = " o.uniacid = :uniacid and o.deleted=0";
    $paras     = array(
        ':uniacid' => $_W['uniacid']
    );
    if (empty($starttime) || empty($endtime)) {
        $starttime = strtotime('-1 month');
        $endtime   = time();
    }
    if (!empty($_GPC['time'])) {
        $starttime = strtotime($_GPC['time']['start']);
        $endtime   = strtotime($_GPC['time']['end']);
        if ($_GPC['searchtime'] == '1') {
            $condition .= " AND o.createtime >= :starttime AND o.createtime <= :endtime ";
            $paras[':starttime'] = $starttime;
            $paras[':endtime']   = $endtime;
        }
    }
    if ($_GPC['paytype'] != '') {
        if ($_GPC['paytype'] == '2') {
            $condition .= " AND ( o.paytype =21 or o.paytype=22 or o.paytype=23 )";
        } else {
            $condition .= " AND o.paytype =" . intval($_GPC['paytype']);
        }
    }
    if (!empty($_GPC['keyword'])) {
        $_GPC['keyword'] = trim($_GPC['keyword']);
        $condition .= " AND o.ordersn LIKE '%{$_GPC['keyword']}%'";
    }
    if (!empty($_GPC['expresssn'])) {
        $_GPC['expresssn'] = trim($_GPC['expresssn']);
        $condition .= " AND o.expresssn LIKE '%{$_GPC['expresssn']}%'";
    }
    if (!empty($_GPC['member'])) {
        $_GPC['member'] = trim($_GPC['member']);
        $condition .= " AND (m.realname LIKE '%{$_GPC['member']}%' or m.mobile LIKE '%{$_GPC['member']}%' or m.nickname LIKE '%{$_GPC['member']}%' " . " or a.realname LIKE '%{$_GPC['member']}%' or a.mobile LIKE '%{$_GPC['member']}%' or o.carrier LIKE '%{$_GPC['member']}%')";
    }
    $statuscondition = '';
    if ($status != '') {
        if ($status == -1) {
            ca('order.view.status_1');
        } else {
            ca('order.view.status' . intval($status));
        }
        if ($status == '-1') {
            $statuscondition = " AND o.status=-1 and isnull(r.id)";
        } else if ($status == '4') {
            $statuscondition = " AND o.refundid<>0 and r.status=0";
        } else if ($status == '5') {
            $statuscondition = " AND r.status=1";
        }else if ($status == '1') {
            $statuscondition = " AND o.status=1 AND o.refundid=0";
        }else {
            $statuscondition = " AND o.status = '" . intval($status) . "'";
        }
    }
    $agentid = intval($_GPC['agentid']);
    $p       = p('commission');
    $level   = 0;
    if ($p) {
        $cset  = $p->getSet();
        $level = intval($cset['level']);
    }
    $olevel = intval($_GPC['olevel']);
    if (!empty($agentid) && $level > 0) {
        $agent = $p->getInfo($agentid, array());
        if (!empty($agent)) {
            $agentLevel = $p->getLevel($agentid);
        }
        if (empty($olevel)) {
            if ($level >= 1) {
                $condition .= ' and  ( o.agentid=' . intval($_GPC['agentid']);
            }
            if ($level >= 2 && $agent['level2'] > 0) {
                $condition .= " or o.agentid in( " . implode(',', array_keys($agent['level1_agentids'])) . ")";
            }
            if ($level >= 3 && $agent['level3'] > 0) {
                $condition .= " or o.agentid in( " . implode(',', array_keys($agent['level2_agentids'])) . ")";
            }
            if ($level >= 1) {
                $condition .= ")";
            }
        } else {
            if ($olevel == 1) {
                $condition .= ' and  o.agentid=' . intval($_GPC['agentid']);
            } else if ($olevel == 2) {
                if ($agent['level2'] > 0) {
                    $condition .= " and o.agentid in( " . implode(',', array_keys($agent['level1_agentids'])) . ")";
                } else {
                    $condition .= " and o.agentid in( 0 )";
                }
            } else if ($olevel == 3) {
                if ($agent['level3'] > 0) {
                    $condition .= " and o.agentid in( " . implode(',', array_keys($agent['level2_agentids'])) . ")";
                } else {
                    $condition .= " and o.agentid in( 0 )";
                }
            }
        }
    }
    $sql = "select o.* , a.realname as arealname,a.mobile as amobile,a.province as aprofince ,a.city as acity , a.area as aarea,a.address as aaddress, d.dispatchname,r.status as refundstatus from " . tablename('ycs_fxshop_order') . " o" . " left join " . tablename('ycs_fxshop_order_refund') . " r on r.orderid=o.id and ifnull(r.status,'')<>''" . " left join " . tablename('ycs_fxshop_member') . " m on m.openid=o.openid and m.uniacid =  o.uniacid " . " left join " . tablename('ycs_fxshop_member_address') . " a on a.id=o.addressid " . " left join " . tablename('ycs_fxshop_dispatch') . " d on d.id = o.dispatchid " . " where $condition $statuscondition ORDER BY o.createtime DESC,o.status DESC  ";
    if (empty($_GPC['export'])) {
        $sql .= "LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
    }
    $list        = pdo_fetchall($sql, $paras);
    $paytype     = array(
        '0' => array(
            'css' => 'default',
            'name' => '未支付'
        ),
        '1' => array(
            'css' => 'danger',
            'name' => '余额支付'
        ),
        '11' => array(
            'css' => 'default',
            'name' => '后台付款'
        ),
        '2' => array(
            'css' => 'danger',
            'name' => '在线支付'
        ),
        '21' => array(
            'css' => 'success',
            'name' => '微信支付'
        ),
        '22' => array(
            'css' => 'warning',
            'name' => '支付宝支付'
        ),
        '23' => array(
            'css' => 'warning',
            'name' => '银联支付'
        ),
        '3' => array(
            'css' => 'primary',
            'name' => '货到付款'
        )
    );
    $orderstatus = array(
        '-1' => array(
            'css' => 'default',
            'name' => '已关闭'
        ),
        '0' => array(
            'css' => 'danger',
            'name' => '待付款'
        ),
        '1' => array(
            'css' => 'info',
            'name' => '待发货'
        ),
        '2' => array(
            'css' => 'warning',
            'name' => '待收货'
        ),
        '3' => array(
            'css' => 'success',
            'name' => '已完成'
        )
    );
    foreach ($list as &$value) {
        $s                    = $value['status'];
        $value['statusvalue'] = $s;
        $value['statuscss']   = $orderstatus[$value['status']]['css'];
        $value['status']      = $orderstatus[$value['status']]['name'];
        if ($s == -1) {
            if ($value['refundstatus'] == 1) {
                $value['status'] = '已退款';
            }
        }
        $p                     = $value['paytype'];
        $value['css']          = $paytype[$p]['css'];
        $value['paytype']      = $paytype[$p]['name'];
        $value['dispatchname'] = empty($value['addressid']) ? '自提' : $value['dispatchname'];
        if (empty($value['dispatchname'])) {
            $value['dispatchname'] = '快递';
        }
        if ($value['isverify'] == 1) {
            $value['dispatchname'] = "线下核销";
        } else if (!empty($value['virtual'])) {
            $value['dispatchname'] = "虚拟物品(卡密)<br/>自动发货";
        }
        if ($value['dispatchtype'] == 1 || !empty($value['isverify']) || !empty($value['virtual'])) {
            $carrier = iunserializer($value['carrier']);
            if (is_array($carrier)) {
                $value['addressdata']['realname'] = $value['realname'] = $carrier['carrier_realname'];
                $value['addressdata']['mobile']   = $value['mobile'] = $carrier['carrier_mobile'];
            }
        } else {
            $address              = iunserializer($value['address']);
            $isarray              = is_array($address);
            $value['realname']    = $isarray ? $address['realname'] : $address['arealname'];
            $value['mobile']      = $isarray ? $address['mobile'] : $address['amobile'];
            $value['province']    = $isarray ? $address['province'] : $address['aprovince'];
            $value['city']        = $isarray ? $address['city'] : $address['acity'];
            $value['area']        = $isarray ? $address['area'] : $address['aarea'];
            $value['address']     = $isarray ? $address['address'] : $address['aaddress'];
            $value['address']     = $value['province'] . " " . $value['city'] . " " . $value['area'] . " " . $value['address'];
            $value['addressdata'] = array(
                'realname' => $value['realname'],
                'mobile' => $value['mobile'],
                'address' => $value['address']
            );
        }
        $order_goods = pdo_fetchall('select g.id,g.title,g.thumb,g.goodssn,og.goodssn as option_goodssn, g.productsn,og.productsn as option_productsn, og.total,og.price,og.optionname as optiontitle, og.realprice from ' . tablename('ycs_fxshop_order_goods') . ' og ' . ' left join ' . tablename('ycs_fxshop_goods') . ' g on g.id=og.goodsid ' . ' where og.uniacid=:uniacid and og.orderid=:orderid ', array(
            ':uniacid' => $_W['uniacid'],
            ':orderid' => $value['id']
        ));
        $goods       = '';
        foreach ($order_goods as &$og) {
            $goods .= "" . $og['title'] . "\r\n";
            if (!empty($og['optiontitle'])) {
                $goods .= " 规格: " . $og['optiontitle'];
            }
            if (!empty($og['option_goodssn'])) {
                $og['goodssn'] = $og['option_goodssn'];
            }
            if (!empty($og['option_productsn'])) {
                $og['productsn'] = $og['option_productsn'];
            }
            if (!empty($og['goodssn'])) {
                $goods .= ' 商品编号: ' . $og['goodssn'];
            }
            if (!empty($og['productsn'])) {
                $goods .= ' 商品条码: ' . $og['productsn'];
            }
            $goods .= ' 单价: ' . ($og['price'] / $og['total']) . ' 折扣后: ' . ($og['realprice'] / $og['total']) . ' 数量: ' . $og['total'] . ' 总价: ' . $og['price'] . " 折扣后: " . $og['realprice'] . "\r\n ";
        }
        unset($og);
        $value['goods']     = set_medias($order_goods, 'thumb');
        $value['goods_str'] = $goods;
        if (!empty($agentid) && $level > 0) {
            $commission_level = 0;
            if ($value['agentid'] == $agentid) {
                $value['level']     = 1;
                $level1_commissions = pdo_fetchall('select commission1  from ' . tablename('ycs_fxshop_order_goods') . ' og ' . ' left join  ' . tablename('ycs_fxshop_order') . ' o on o.id = og.orderid ' . ' where og.orderid=:orderid and o.agentid= ' . $agentid . "  and o.uniacid=:uniacid", array(
                    ':orderid' => $value['id'],
                    ':uniacid' => $_W['uniacid']
                ));
                foreach ($level1_commissions as $c) {
                    $commission = iunserializer($c['commission1']);
                    $commission_level += isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default'];
                }
            } else if (in_array($value['agentid'], array_keys($agent['level1_agentids']))) {
                $value['level'] = 2;
                if ($agent['level2'] > 0) {
                    $level2_commissions = pdo_fetchall('select commission2  from ' . tablename('ycs_fxshop_order_goods') . ' og ' . ' left join  ' . tablename('ycs_fxshop_order') . ' o on o.id = og.orderid ' . ' where og.orderid=:orderid and  o.agentid in ( ' . implode(',', array_keys($agent['level1_agentids'])) . ")  and o.uniacid=:uniacid", array(
                        ':orderid' => $value['id'],
                        ':uniacid' => $_W['uniacid']
                    ));
                    foreach ($level2_commissions as $c) {
                        $commission = iunserializer($c['commission2']);
                        $commission_level += isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default'];
                    }
                }
            } else if (in_array($value['agentid'], array_keys($agent['level2_agentids']))) {
                $value['level'] = 3;
                if ($agent['level3'] > 0) {
                    $level3_commissions = pdo_fetchall('select commission3 from ' . tablename('ycs_fxshop_order_goods') . ' og ' . ' left join  ' . tablename('ycs_fxshop_order') . ' o on o.id = og.orderid ' . ' where og.orderid=:orderid and  o.agentid in ( ' . implode(',', array_keys($agent['level2_agentids'])) . ")  and o.uniacid=:uniacid", array(
                        ':orderid' => $value['id'],
                        ':uniacid' => $_W['uniacid']
                    ));
                    foreach ($level3_commissions as $c) {
                        $commission = iunserializer($c['commission3']);
                        $commission_level += isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default'];
                    }
                }
            }
            $value['commission'] = $commission_level;
        }
    }
    unset($value);
    if ($_GPC['export'] == 1) {
        ca('order.op.export');
        plog('order.op.export', '导出订单');
        $columns = array(
            array(
                'title' => '订单编号',
                'field' => 'ordersn',
                'width' => 24
            ),
            array(
                'title' => '收货姓名(或自提人)',
                'field' => 'realname',
                'width' => 12
            ),
            array(
                'title' => '联系电话',
                'field' => 'mobile',
                'width' => 12
            ),
            array(
                'title' => '收货地址',
                'field' => 'address',
                'width' => 12
            ),
            array(
                'title' => '商品名称',
                'field' => 'goods_title',
                'width' => 24
            ),
            array(
                'title' => '商品编码',
                'field' => 'goods_goodssn',
                'width' => 12
            ),
            array(
                'title' => '商品规格',
                'field' => 'goods_optiontitle',
                'width' => 12
            ),
            array(
                'title' => '商品数量',
                'field' => 'goods_total',
                'width' => 12
            ),
            array(
                'title' => '商品单价(折扣前)',
                'field' => 'goods_price1',
                'width' => 12
            ),
            array(
                'title' => '商品单价(折扣后)',
                'field' => 'goods_price2',
                'width' => 12
            ),
            array(
                'title' => '商品价格(折扣后)',
                'field' => 'goods_rprice1',
                'width' => 12
            ),
            array(
                'title' => '商品价格(折扣后)',
                'field' => 'goods_rprice2',
                'width' => 12
            ),
            array(
                'title' => '支付方式',
                'field' => 'paytype',
                'width' => 12
            ),
            array(
                'title' => '配送方式',
                'field' => 'dispatchname',
                'width' => 12
            ),
            array(
                'title' => '运费',
                'field' => 'dispatchprice',
                'width' => 12
            ),
            array(
                'title' => '总价',
                'field' => 'price',
                'width' => 12
            ),
            array(
                'title' => '状态',
                'field' => 'status',
                'width' => 12
            ),
            array(
                'title' => '下单时间',
                'field' => 'createtime',
                'width' => 24
            ),
            array(
                'title' => '付款时间',
                'field' => 'paytime',
                'width' => 24
            ),
            array(
                'title' => '发货时间',
                'field' => 'sendtime',
                'width' => 24
            ),
            array(
                'title' => '完成时间',
                'field' => 'finishtime',
                'width' => 24
            ),
            array(
                'title' => '快递公司',
                'field' => 'expresscom',
                'width' => 24
            ),
            array(
                'title' => '快递单号',
                'field' => 'expresssn',
                'width' => 24
            ),
            array(
                'title' => '订单备注',
                'field' => 'remark',
                'width' => 36
            )
        );
        if (!empty($agentid) && $level > 0) {
            $columns[] = array(
                'title' => '分销级别',
                'field' => 'level',
                'width' => 24
            );
            $columns[] = array(
                'title' => '分销佣金',
                'field' => 'commission',
                'width' => 24
            );
        }
        foreach ($list as &$row) {
            $row['ordersn']    = $row['ordersn'] . " ";
            $row['expresssn']  = $row['expresssn'] . " ";
            $row['createtime'] = date('Y-m-d H:i:s', $row['createtime']);
            $row['paytime']    = !empty($row['paytime']) ? date('Y-m-d H:i:s', $row['paytime']) : '';
            $row['sendtime']   = !empty($row['sendtime']) ? date('Y-m-d H:i:s', $row['sendtime']) : '';
            $row['finishtime'] = !empty($row['finishtime']) ? date('Y-m-d H:i:s', $row['finishtime']) : '';
        }
        unset($row);
        $exportlist = array();
        foreach ($list as &$r) {
            $ogoods = $r['goods'];
            unset($r['goods']);
            foreach ($ogoods as $k => $g) {
                if ($k > 0) {
                    $r['ordersn']       = '';
                    $r['realname']      = '';
                    $r['mobile']        = '';
                    $r['address']       = '';
                    $r['paytype']       = '';
                    $r['dispatchname']  = '';
                    $r['dispatchprice'] = '';
                    $r['price']         = '';
                    $r['status']        = '';
                    $r['createtime']    = '';
                    $r['sendtime']      = '';
                    $r['finishtime']    = '';
                    $r['expresscom']    = '';
                    $r['expresssn']     = '';
                    $r['remark']        = '';
                }
                $r['goods_title']       = $g['title'];
                $r['goods_goodssn']     = $g['goodssn'];
                $r['goods_optiontitle'] = $g['optiontitle'];
                $r['goods_total']       = $g['total'];
                $r['goods_price1']      = $g['price'] / $g['total'];
                $r['goods_price2']      = $g['realprice'] / $g['total'];
                $r['goods_rprice1']     = $g['price'];
                $r['goods_rprice2']     = $g['realprice'];
                $exportlist[]           = $r;
            }
        }
        unset($r);
        m('excel')->export($exportlist, array(
            "title" => "订单数据-" . date('Y-m-d-H-i', time()),
            "columns" => $columns
        ));
    }
    $total              = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ycs_fxshop_order') . " o " . " left join " . tablename('ycs_fxshop_order_refund') . " r on r.orderid=o.id and ifnull(r.status,'')<>''" . " left join " . tablename('ycs_fxshop_member') . " m on m.openid=o.openid  and m.uniacid =  o.uniacid" . " left join " . tablename('ycs_fxshop_member_address') . " a on o.addressid = a.id " . " WHERE $condition $statuscondition", $paras);
    $totalmoney         = pdo_fetchcolumn('SELECT ifnull(sum(o.price),0) FROM ' . tablename('ycs_fxshop_order') . " o " . " left join " . tablename('ycs_fxshop_order_refund') . " r on r.orderid=o.id and ifnull(r.status,'')<>''" . " left join " . tablename('ycs_fxshop_member') . " m on m.openid=o.openid  and m.uniacid =  o.uniacid" . " left join " . tablename('ycs_fxshop_member_address') . " a on o.addressid = a.id " . " WHERE $condition $statuscondition", $paras);
    $totals             = array();
    $totals['all']      = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ycs_fxshop_order') . " o " . " left join " . tablename('ycs_fxshop_order_refund') . " r on r.orderid=o.id and ifnull(r.status,'')<>''" . " left join " . tablename('ycs_fxshop_member') . " m on m.openid=o.openid  and m.uniacid =  o.uniacid" . " left join " . tablename('ycs_fxshop_member_address') . " a on o.addressid = a.id " . " WHERE o.uniacid = :uniacid and o.deleted=0", $paras);
    $totals['status_1'] = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ycs_fxshop_order') . " o " . " left join " . tablename('ycs_fxshop_order_refund') . " r on r.orderid=o.id and ifnull(r.status,'')<>''" . " left join " . tablename('ycs_fxshop_member') . " m on m.openid=o.openid  and m.uniacid =  o.uniacid" . " left join " . tablename('ycs_fxshop_member_address') . " a on o.addressid = a.id " . " WHERE $condition and o.status=-1", $paras);
    $totals['status0']  = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ycs_fxshop_order') . " o " . " left join " . tablename('ycs_fxshop_order_refund') . " r on r.orderid=o.id and ifnull(r.status,'')<>''" . " left join " . tablename('ycs_fxshop_member') . " m on m.openid=o.openid  and m.uniacid =  o.uniacid" . " left join " . tablename('ycs_fxshop_member_address') . " a on o.addressid = a.id " . " WHERE $condition and o.status=0", $paras);
    $totals['status1']  = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ycs_fxshop_order') . " o " . " left join " . tablename('ycs_fxshop_order_refund') . " r on r.orderid=o.id and ifnull(r.status,'')<>''" . " left join " . tablename('ycs_fxshop_member') . " m on m.openid=o.openid  and m.uniacid =  o.uniacid" . " left join " . tablename('ycs_fxshop_member_address') . " a on o.addressid = a.id " . " WHERE $condition and o.status=1 and o.refundid=0 ", $paras);
    $totals['status2']  = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ycs_fxshop_order') . " o " . " left join " . tablename('ycs_fxshop_order_refund') . " r on r.orderid=o.id and ifnull(r.status,'')<>''" . " left join " . tablename('ycs_fxshop_member') . " m on m.openid=o.openid  and m.uniacid =  o.uniacid" . " left join " . tablename('ycs_fxshop_member_address') . " a on o.addressid = a.id " . " WHERE $condition and o.status=2", $paras);
    $totals['status3']  = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ycs_fxshop_order') . " o " . " left join " . tablename('ycs_fxshop_order_refund') . " r on r.orderid=o.id and ifnull(r.status,'')<>''" . " left join " . tablename('ycs_fxshop_member') . " m on m.openid=o.openid  and m.uniacid =  o.uniacid" . " left join " . tablename('ycs_fxshop_member_address') . " a on o.addressid = a.id " . " WHERE $condition and o.status=3", $paras);
    $totals['status4']  = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ycs_fxshop_order') . " o " . " left join " . tablename('ycs_fxshop_order_refund') . " r on r.orderid=o.id and ifnull(r.status,'')<>''" . " left join " . tablename('ycs_fxshop_member') . " m on m.openid=o.openid  and m.uniacid =  o.uniacid" . " left join " . tablename('ycs_fxshop_member_address') . " a on o.addressid = a.id " . " WHERE $condition and o.refundid<>0 and r.status=0", $paras);
    $totals['status5']  = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ycs_fxshop_order') . " o " . " left join " . tablename('ycs_fxshop_order_refund') . " r on r.orderid=o.id and ifnull(r.status,'')<>''" . " left join " . tablename('ycs_fxshop_member') . " m on m.openid=o.openid  and m.uniacid =  o.uniacid" . " left join " . tablename('ycs_fxshop_member_address') . " a on o.addressid = a.id " . " WHERE $condition and r.status=1", $paras);
    $pager              = pagination($total, $pindex, $psize);
    load()->func('tpl');
    include $this->template('web/order/list');
    exit;
} elseif ($operation == 'detail') {
    $id                  = intval($_GPC['id']);
    $item                = pdo_fetch("SELECT * FROM " . tablename('ycs_fxshop_order') . " WHERE id = :id and uniacid=:uniacid", array(
        ':id' => $id,
        ':uniacid' => $_W['uniacid']
    ));
    $item['statusvalue'] = $item['status'];
    $shopset             = m('common')->getSysset('shop');
    if (empty($item)) {
        message("抱歉，订单不存在!", referer(), "error");
    }
    if (!empty($item['refundid'])) {
        ca('order.view.status4');
    } else {
        if ($item['status'] == -1) {
            ca('order.view.status_1');
        } else {
            ca('order.view.status' . $item['status']);
        }
    }
    if ($_W['ispost']) {
        pdo_update('ycs_fxshop_order', array(
            'remark' => trim($_GPC['remark'])
        ), array(
            'id' => $item['id'],
            'uniacid' => $_W['uniacid']
        ));
        plog('order.op.saveremark', "订单保存备注  ID: {$item['id']} 订单号: {$item['ordersn']}");
        message('订单备注保存成功！', $this->createWebUrl('order', array(
            'op' => 'detail',
            'id' => $item['id']
        )), 'success');
    }
    $member   = m('member')->getInfo($item['openid']);
    $dispatch = pdo_fetch("SELECT * FROM " . tablename('ycs_fxshop_dispatch') . " WHERE id = :id and uniacid=:uniacid", array(
        ':id' => $item['dispatchid'],
        ':uniacid' => $_W['uniacid']
    ));
    if (empty($item['addressid'])) {
        $user = unserialize($item['carrier']);
    } else {
        $user = iunserializer($item['address']);
        if (!is_array($user)) {
            $user = pdo_fetch("SELECT * FROM " . tablename('ycs_fxshop_member_address') . " WHERE id = :id and uniacid=:uniacid", array(
                ':id' => $item['addressid'],
                ':uniacid' => $_W['uniacid']
            ));
        }
        $user['address']     = $user['province'] . ' ' . $user['city'] . ' ' . $user['area'] . ' ' . $user['address'];
        $item['addressdata'] = array(
            'realname' => $user['realname'],
            'mobile' => $user['mobile'],
            'address' => $user['address']
        );
    }
    $refund = pdo_fetch("SELECT * FROM " . tablename('ycs_fxshop_order_refund') . " WHERE orderid = :orderid and uniacid=:uniacid order by id desc", array(
        ':orderid' => $item['id'],
        ':uniacid' => $_W['uniacid']
    ));
    $goods  = pdo_fetchall("SELECT g.*, o.goodssn as option_goodssn, o.productsn as option_productsn,o.total,g.type,o.optionname,o.optionid,o.price as orderprice,o.realprice FROM " . tablename('ycs_fxshop_order_goods') . " o left join " . tablename('ycs_fxshop_goods') . " g on o.goodsid=g.id " . " WHERE o.orderid=:orderid and o.uniacid=:uniacid", array(
        ':orderid' => $id,
        ':uniacid' => $_W['uniacid']
    ));
    foreach ($goods as &$r) {
        if (!empty($r['option_goodssn'])) {
            $r['goodssn'] = $og['option_goodssn'];
        }
        if (!empty($og['option_productsn'])) {
            $r['productsn'] = $og['option_productsn'];
        }
    }
    unset($r);
    $item['goods'] = $goods;
    $agents        = array();
    if (p('commission')) {
        $agents = p('commission')->getAgents($id);
    }
    $condition          = " o.uniacid=:uniacid and o.deleted=0";
    $paras              = array(
        ':uniacid' => $_W['uniacid']
    );
    $totals             = array();
    $totals['all']      = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ycs_fxshop_order') . " o " . " left join " . tablename('ycs_fxshop_order_refund') . " r on r.orderid=o.id and ifnull(r.status,'')<>''" . " left join " . tablename('ycs_fxshop_member') . " m on m.openid=o.openid  and m.uniacid =  o.uniacid" . " left join " . tablename('ycs_fxshop_member_address') . " a on o.addressid = a.id " . " WHERE $condition", $paras);
    $totals['status_1'] = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ycs_fxshop_order') . " o " . " left join " . tablename('ycs_fxshop_order_refund') . " r on r.orderid=o.id and ifnull(r.status,'')<>''" . " left join " . tablename('ycs_fxshop_member') . " m on m.openid=o.openid  and m.uniacid =  o.uniacid" . " left join " . tablename('ycs_fxshop_member_address') . " a on o.addressid = a.id " . " WHERE $condition and o.status=-1", $paras);
    $totals['status0']  = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ycs_fxshop_order') . " o " . " left join " . tablename('ycs_fxshop_order_refund') . " r on r.orderid=o.id and ifnull(r.status,'')<>''" . " left join " . tablename('ycs_fxshop_member') . " m on m.openid=o.openid  and m.uniacid =  o.uniacid" . " left join " . tablename('ycs_fxshop_member_address') . " a on o.addressid = a.id " . " WHERE $condition and o.status=0", $paras);
    $totals['status1']  = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ycs_fxshop_order') . " o " . " left join " . tablename('ycs_fxshop_order_refund') . " r on r.orderid=o.id and ifnull(r.status,'')<>''" . " left join " . tablename('ycs_fxshop_member') . " m on m.openid=o.openid  and m.uniacid =  o.uniacid" . " left join " . tablename('ycs_fxshop_member_address') . " a on o.addressid = a.id " . " WHERE $condition and o.status=1 and o.refundid=0 ", $paras);
    $totals['status2']  = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ycs_fxshop_order') . " o " . " left join " . tablename('ycs_fxshop_order_refund') . " r on r.orderid=o.id and ifnull(r.status,'')<>''" . " left join " . tablename('ycs_fxshop_member') . " m on m.openid=o.openid  and m.uniacid =  o.uniacid" . " left join " . tablename('ycs_fxshop_member_address') . " a on o.addressid = a.id " . " WHERE $condition and o.status=2", $paras);
    $totals['status3']  = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ycs_fxshop_order') . " o " . " left join " . tablename('ycs_fxshop_order_refund') . " r on r.orderid=o.id and ifnull(r.status,'')<>''" . " left join " . tablename('ycs_fxshop_member') . " m on m.openid=o.openid  and m.uniacid =  o.uniacid" . " left join " . tablename('ycs_fxshop_member_address') . " a on o.addressid = a.id " . " WHERE $condition and o.status=3", $paras);
    $totals['status4']  = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ycs_fxshop_order') . " o " . " left join " . tablename('ycs_fxshop_order_refund') . " r on r.orderid=o.id and ifnull(r.status,'')<>''" . " left join " . tablename('ycs_fxshop_member') . " m on m.openid=o.openid  and m.uniacid =  o.uniacid" . " left join " . tablename('ycs_fxshop_member_address') . " a on o.addressid = a.id " . " WHERE $condition and o.refundid<>0 and r.status=0", $paras);
    $totals['status5']  = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ycs_fxshop_order') . " o " . " left join " . tablename('ycs_fxshop_order_refund') . " r on r.orderid=o.id and ifnull(r.status,'')<>''" . " left join " . tablename('ycs_fxshop_member') . " m on m.openid=o.openid  and m.uniacid =  o.uniacid" . " left join " . tablename('ycs_fxshop_member_address') . " a on o.addressid = a.id " . " WHERE $condition and r.status=1", $paras);
    load()->func('tpl');
    include $this->template('web/order/detail');
    exit;
} elseif ($operation == 'delete') {
    ca('order.op.delete');
    $orderid = intval($_GPC['id']);
    pdo_update('ycs_fxshop_order', array(
        'deleted' => 1
    ), array(
        'id' => $orderid,
        'uniacid' => $_W['uniacid']
    ));
    plog('order.op.delete', "订单删除 ID: {$id}");
    message('订单删除成功', $this->createWebUrl('order', array(
        'op' => 'display'
    )), 'success');
} elseif ($operation == 'deal') {
    $id      = intval($_GPC['id']);
    $item    = pdo_fetch("SELECT * FROM " . tablename('ycs_fxshop_order') . " WHERE id = :id and uniacid=:uniacid", array(
        ':id' => $id,
        ':uniacid' => $_W['uniacid']
    ));
    $shopset = m('common')->getSysset('shop');
    if (empty($item)) {
        message("抱歉，订单不存在!", referer(), "error");
    }
    if (!empty($item['refundid'])) {
        ca('order.view.status4');
    } else {
        if ($item['status'] == -1) {
            ca('order.view.status_1');
        } else {
            ca('order.view.status' . $item['status']);
        }
    }
    $to = trim($_GPC['to']);
    if ($to == 'confirmpay') {
        order_list_confirmpay($item);
    } else if ($to == 'cancelpay') {
        order_list_cancelpay($item);
    } else if ($to == 'confirmsend') {
        order_list_confirmsend($item);
    } else if ($to == 'cancelsend') {
        order_list_cancelsend($item);
    } else if ($to == 'confirmsend1') {
        order_list_confirmsend1($item);
    } else if ($to == 'cancelsend1') {
        order_list_cancelsend1($item);
    } else if ($to == 'finish') {
        order_list_finish($item);
    } else if ($to == 'close') {
        order_list_close($item);
    } else if ($to == 'refund') {
        order_list_refund($item);
    }
    exit;
}
function changeWechatSend($ordersn, $status, $msg = '')
{
    global $_W;
    $paylog = pdo_fetch("SELECT plid, openid, tag FROM " . tablename('core_paylog') . " WHERE tid = '{$ordersn}' AND status = 1 AND type = 'wechat'");
    if (!empty($paylog['openid'])) {
        $paylog['tag'] = iunserializer($paylog['tag']);
        $acid          = $paylog['tag']['acid'];
        load()->model('account');
        $account = account_fetch($acid);
        $payment = uni_setting($account['uniacid'], 'payment');
        if ($payment['payment']['wechat']['version'] == '2') {
            return true;
        }
        $send           = array(
            'appid' => $account['key'],
            'openid' => $paylog['openid'],
            'transid' => $paylog['tag']['transaction_id'],
            'out_trade_no' => $paylog['plid'],
            'deliver_timestamp' => TIMESTAMP,
            'deliver_status' => $status,
            'deliver_msg' => $msg
        );
        $sign           = $send;
        $sign['appkey'] = $payment['payment']['wechat']['signkey'];
        ksort($sign);
        $string = '';
        foreach ($sign as $key => $v) {
            $key = strtolower($key);
            $string .= "{$key}={$v}&";
        }
        $send['app_signature'] = sha1(rtrim($string, '&'));
        $send['sign_method']   = 'sha1';
        $account               = WeAccount::create($acid);
        $response              = $account->changeOrderStatus($send);
        if (is_error($response)) {
            message($response['message']);
        }
    }
}
function order_list_backurl()
{
    global $_GPC;
    return $_GPC['op'] == 'detail' ? $this->createWebUrl('order') : referer();
}
function order_list_confirmsend($item)
{
    global $_W, $_GPC;
    ca('order.op.send');
    if ($item['status'] != 1) {
        message('订单未付款，无法发货！');
    }
    if (!empty($_GPC['isexpress']) && empty($_GPC['expresssn'])) {
        message('请输入快递单号！');
    }
    if (!empty($item['transid'])) {
        changeWechatSend($item['ordersn'], 1);
    }
    pdo_update('ycs_fxshop_order', array(
        'status' => 2,
        'remark' => trim($_GPC['remark']),
        'express' => trim($_GPC['express']),
        'expresscom' => trim($_GPC['expresscom']),
        'expresssn' => trim($_GPC['expresssn']),
        'sendtime' => time()
    ), array(
        'id' => $item['id'],
        'uniacid' => $_W['uniacid']
    ));
    if (!empty($item['refundid'])) {
        $refund = pdo_fetch('select * from ' . tablename('ycs_fxshop_order_refund') . ' where id=:id limit 1', array(
            ':id' => $item['refundid']
        ));
        if (!empty($refund)) {
            pdo_update('ycs_fxshop_order_refund', array(
                'status' => -1
            ), array(
                'id' => $item['refundid']
            ));
            pdo_update('ycs_fxshop_order', array(
                'refundid' => 0
            ), array(
                'id' => $item['id']
            ));
        }
    }
    m('notice')->sendOrderMessage($item['id']);
    plog('order.op.send', "订单发货 ID: {$item['id']} 订单号: {$item['ordersn']} <br/>快递公司: {$_GPC['expresscom']} 快递单号: {$_GPC['expresssn']}");
    message('发货操作成功！', order_list_backurl(), 'success');
}
function order_list_confirmsend1($item)
{
    global $_W, $_GPC;
    ca('order.op.fetch');
    if ($item['status'] != 1) {
        message('订单未付款，无法确认取货！');
    }
    $time = time();
    $d    = array(
        'status' => 3,
        'sendtime' => $time,
        'finishtime' => $time,
        'remark' => $_GPC['remark']
    );
    if ($item['isverify'] == 1) {
        $d['verified']     = 1;
        $d['verifytime']   = $time;
        $d['verifyopenid'] = "";
    }
    pdo_update('ycs_fxshop_order', $d, array(
        'id' => $item['id'],
        'uniacid' => $_W['uniacid']
    ));
    if (!empty($item['refundid'])) {
        $refund = pdo_fetch('select * from ' . tablename('ycs_fxshop_order_refund') . ' where id=:id limit 1', array(
            ':id' => $item['refundid']
        ));
        if (!empty($refund)) {
            pdo_update('ycs_fxshop_order_refund', array(
                'status' => -1
            ), array(
                'id' => $item['refundid']
            ));
            pdo_update('ycs_fxshop_order', array(
                'refundid' => 0
            ), array(
                'id' => $item['id']
            ));
        }
    }
    m('member')->upgradeLevel($item['openid']);
    m('notice')->sendOrderMessage($item['id']);
    if (p('commission')) {
        p('commission')->checkOrderFinish($item['id']);
    }
    plog('order.op.fetch', "订单确认取货 ID: {$item['id']} 订单号: {$item['ordersn']}");
    message('发货操作成功！', order_list_backurl(), 'success');
}
function order_list_cancelsend($item)
{
    global $_W, $_GPC;
    ca('order.op.sendcancel');
    if ($item['status'] != 2) {
        message('订单未发货，不需取消发货！');
    }
    if (!empty($item['transid'])) {
        changeWechatSend($item['ordersn'], 0, $_GPC['cancelreson']);
    }
    pdo_update('ycs_fxshop_order', array(
        'status' => 1,
        'sendtime' => 0,
        'remark' => $_GPC['remark']
    ), array(
        'id' => $item['id'],
        'uniacid' => $_W['uniacid']
    ));
    plog('order.op.sencancel', "订单取消发货 ID: {$item['id']} 订单号: {$item['ordersn']}");
    message('取消发货操作成功！', order_list_backurl(), 'success');
}
function order_list_cancelsend1($item)
{
    global $_W, $_GPC;
    ca('order.op.fetchcancel');
    if ($item['status'] != 3) {
        message('订单未取货，不需取消！');
    }
    pdo_update('ycs_fxshop_order', array(
        'status' => 1,
        'finishtime' => 0,
        'remark' => trim($_GPC['remark'])
    ), array(
        'id' => $item['id'],
        'uniacid' => $_W['uniacid']
    ));
    plog('order.op.fetchcancel', "订单取消取货 ID: {$item['id']} 订单号: {$item['ordersn']}");
    message('取消发货操作成功！', order_list_backurl(), 'success');
}
function order_list_finish($item)
{
    global $_W, $_GPC;
    ca('order.op.finish');
    pdo_update('ycs_fxshop_order', array(
        'status' => 3,
        'finishtime' => time(),
        'remark' => $_GPC['remark']
    ), array(
        'id' => $item['id'],
        'uniacid' => $_W['uniacid']
    ));
    m('member')->upgradeLevel($item['openid']);
    m('notice')->sendOrderMessage($item['id']);
    if (p('commission')) {
        p('commission')->checkOrderFinish($item['id']);
    }
    plog('order.op.finish', "订单完成 ID: {$item['id']} 订单号: {$item['ordersn']}");
    message('订单操作成功！', order_list_backurl(), 'success');
}
function order_list_cancelpay($item)
{
    global $_W, $_GPC;
    ca('order.op.paycancel');
    if ($item['status'] != 1) {
        message('订单未付款，不需取消！');
    }
    m('order')->setStocksAndCredits($item['id'], 2);
    pdo_update('ycs_fxshop_order', array(
        'status' => 0,
        'cancelpaytime' => time(),
        'remark' => $_GPC['remark']
    ), array(
        'id' => $item['id'],
        'uniacid' => $_W['uniacid']
    ));
    plog('order.op.paycancel', "订单取消付款 ID: {$item['id']} 订单号: {$item['ordersn']}");
    message('取消订单付款操作成功！', order_list_backurl(), 'success');
}
function order_list_confirmpay($item)
{
    global $_W, $_GPC;
    ca('order.op.pay');
    if ($item['status'] > 1) {
        message('订单已付款，不需重复付款！');
    }
    $pv = p('virtual');
    if (!empty($item['virtual']) && $pv) {
        $pv->pay($item);
    } else {
        pdo_update('ycs_fxshop_order', array(
            'status' => 1,
            'paytype' => 11,
            'paytime' => time(),
            'remark' => $_GPC['remark']
        ), array(
            'id' => $item['id'],
            'uniacid' => $_W['uniacid']
        ));
        m('order')->setStocksAndCredits($item['id'], 1);
        m('notice')->sendOrderMessage($item['id']);
        if (p('commission')) {
            p('commission')->checkOrderPay($item['id']);
        }
    }
    plog('order.op.pay', "订单确认付款 ID: {$item['id']} 订单号: {$item['ordersn']}");
    message('确认订单付款操作成功！', order_list_backurl(), 'success');
}
function order_list_close($item)
{
    global $_W, $_GPC;
    ca('order.op.close');
    if (!empty($item['transid'])) {
        changeWechatSend($item['ordersn'], 0, $_GPC['reson']);
    }
    pdo_update('ycs_fxshop_order', array(
        'status' => -1,
        'canceltime' => time(),
        'remark' => $_GPC['remark']
    ), array(
        'id' => $item['id'],
        'uniacid' => $_W['uniacid']
    ));
    if ($item['deductprice'] > 0) {
        m('member')->setCredit($item['openid'], 'credit1', $item['deductcredit'], array(
            '0',
            $shopset['name'] . "购物返还抵扣积分 积分: {$item['deductcredit']} 抵扣金额: {$item['deductprice']} 订单号: {$item['ordersn']}"
        ));
    }
    plog('order.op.close', "订单关闭 ID: {$item['id']} 订单号: {$item['ordersn']}");
    message('订单关闭操作成功！', order_list_backurl(), 'success');
}
function order_list_refund($item)
{
    global $_W, $_GPC;
    ca('order.op.refund');
    $shopset = m('common')->getSysset('shop');
    if (empty($item['refundid'])) {
        message('订单未申请退款，不需处理！');
    }
    $refund = pdo_fetch('select * from ' . tablename('ycs_fxshop_order_refund') . ' where id=:id and status=0 limit 1', array(
        ':id' => $item['refundid']
    ));
    if (empty($refund)) {
        pdo_update('ycs_fxshop_order', array(
            'refundid' => 0
        ), array(
            'id' => $item['id'],
            'uniacid' => $_W['uniacid']
        ));
        message('未找到退款申请，不需处理！');
    }
    if (empty($refund['refundno'])) {
        $refund['refundno'] = m('common')->createNO('order_refund', 'refundno', 'SR');
        pdo_update('ycs_fxshop_order_refund', array(
            'refundno' => $refund['refundno']
        ), array(
            'id' => $refund['id']
        ));
    }
    $refundstatus  = intval($_GPC['refundstatus']);
    $refundcontent = $_GPC['refundcontent'];
    if ($refundstatus == 0) {
        message('暂不处理', referer());
    } else if ($refundstatus == 1) {
        $realprice = $refund['price'];
        $goods     = pdo_fetchall("SELECT g.id,g.credit, o.total,o.realprice FROM " . tablename('ycs_fxshop_order_goods') . " o left join " . tablename('ycs_fxshop_goods') . " g on o.goodsid=g.id " . " WHERE o.orderid=:orderid and o.uniacid=:uniacid", array(
            ':orderid' => $item['id'],
            ':uniacid' => $_W['uniacid']
        ));
        $credits   = 0;
        foreach ($goods as $g) {
            $credits += $g['credit'] * $g['total'];
        }
        $refundtype = 0;
        if ($item['paytype'] == 1) {
            m('member')->setCredit($item['openid'], 'credit2', $realprice, array(
                0,
                $shopset['name'] . "退款: {$realprice}元 订单号: " . $item['ordersn']
            ));
            $result = true;
        } else if ($item['paytype'] == 21) {
            $realprice  = round($realprice - $item['deductcredit2'], 2);
            $result     = m('finance')->refund($item['openid'], $item['ordersn'], $refund['refundno'], $item['price'] * 100, $realprice * 100);
            $refundtype = 2;
        } else {
            if ($realprice < 1) {
                message('退款金额必须大于1元，才能使用微信企业付款退款!', '', 'error');
            }
            $realprice  = round($realprice - $item['deductcredit2'], 2);
            $result     = m('finance')->pay($item['openid'], 1, $realprice * 100, $refund['refundno'], $shopset['name'] . "退款: {$realprice}元 订单号: " . $item['ordersn']);
            $refundtype = 1;
        }
        if (is_error($result)) {
            message($result['message'], '', 'error');
        }
        m('member')->setCredit($item['openid'], 'credit1', -$credits, array(
            0,
            $shopset['name'] . "退款扣除积分: {$credits} 订单号: " . $item['ordersn']
        ));
        if ($item['deductcredit'] > 0) {
            m('member')->setCredit($item['openid'], 'credit1', $item['deductcredit'], array(
                '0',
                $shopset['name'] . "购物返还抵扣积分 积分: {$item['deductcredit']} 抵扣金额: {$item['deductprice']} 订单号: {$item['ordersn']}"
            ));
        }
        if (!empty($refundtype)) {
            if ($item['deductcredit2'] > 0) {
                m('member')->setCredit($item['openid'], 'credit2', $item['deductcredit2'], array(
                    '0',
                    $shopset['name'] . "购物返还抵扣余额 积分: {$item['deductcredit2']} 订单号: {$item['ordersn']}"
                ));
            }
        }
        pdo_update('ycs_fxshop_order_refund', array(
            'reply' => '',
            'status' => 1,
            'refundtype' => $refundtype
        ), array(
            'id' => $item['refundid']
        ));
        m('notice')->sendOrderMessage($item['id'], true);
        pdo_update('ycs_fxshop_order', array(
            'refundid' => 0,
            'status' => -1,
            'refundtime' => time()
        ), array(
            'id' => $item['id'],
            'uniacid' => $_W['uniacid']
        ));
        foreach ($goods as $g) {
            $salesreal = pdo_fetchcolumn('select ifnull(sum(total),0) from ' . tablename('ycs_fxshop_order_goods') . ' og ' . ' left join ' . tablename('ycs_fxshop_order') . ' o on o.id = og.orderid ' . ' where og.goodsid=:goodsid and o.status>=1 and o.uniacid=:uniacid limit 1', array(
                ':goodsid' => $g['id'],
                ':uniacid' => $_W['uniacid']
            ));
            pdo_update('ycs_fxshop_goods', array(
                'salesreal' => $salesreal
            ), array(
                'id' => $g['id']
            ));
        }
        plog('order.op.refund', "订单退款 ID: {$item['id']} 订单号: {$item['ordersn']}");
    } else if ($refundstatus == -1) {
        pdo_update('ycs_fxshop_order_refund', array(
            'reply' => $refundcontent,
            'status' => -1
        ), array(
            'id' => $item['refundid']
        ));
        m('notice')->sendOrderMessage($item['id'], true);
        plog('order.op.refund', "订单退款拒绝 ID: {$item['id']} 订单号: {$item['ordersn']} 原因: {$refundcontent}");
        pdo_update('ycs_fxshop_order', array(
            'refundid' => 0
        ), array(
            'id' => $item['id'],
            'uniacid' => $_W['uniacid']
        ));
    } else if ($refundstatus == 2) {
        $refundtype = 2;
        pdo_update('ycs_fxshop_order_refund', array(
            'reply' => '',
            'status' => 1,
            'refundtype' => $refundtype
        ), array(
            'id' => $item['refundid']
        ));
        m('notice')->sendOrderMessage($item['id'], true);
        pdo_update('ycs_fxshop_order', array(
            'refundid' => 0,
            'status' => -1,
            'refundtime' => time()
        ), array(
            'id' => $item['id'],
            'uniacid' => $_W['uniacid']
        ));
        foreach ($goods as $g) {
            $salesreal = pdo_fetchcolumn('select ifnull(sum(total),0) from ' . tablename('ycs_fxshop_order_goods') . ' og ' . ' left join ' . tablename('ycs_fxshop_order') . ' o on o.id = og.orderid ' . ' where og.goodsid=:goodsid and o.status>=1 and o.uniacid=:uniacid limit 1', array(
                ':goodsid' => $g['id'],
                ':uniacid' => $_W['uniacid']
            ));
            pdo_update('ycs_fxshop_goods', array(
                'salesreal' => $salesreal
            ), array(
                'id' => $g['id']
            ));
        }
    }
    message('退款申请处理成功!', order_list_backurl(), 'success');
}
if (p('commission')) {
    $com_set = p('commission')->getSet();
}