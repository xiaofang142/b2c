<?php


global $_W, $_GPC;

$agentlevels = $this->model->getLevels();
$status      = intval($_GPC['status']);
empty($status) && $status = 1;
$operation = empty($_GPC['op']) ? 'display' : $_GPC['op'];
if ($operation == 'display') {
    if ($status == -1) {
        ca('commission.apply.view_1');
    } else {
        ca('commission.apply.view' . $status);
    }
    $level     = $this->set['level'];
    $pindex    = max(1, intval($_GPC['page']));
    $psize     = 20;
    $condition = ' and a.uniacid=:uniacid and a.status=:status';
    $params    = array(
        ':uniacid' => $_W['uniacid'],
        ':status' => $status
    );
    if (!empty($_GPC['applyno'])) {
        $_GPC['applyno'] = trim($_GPC['applyno']);
        $condition .= ' and a.applyno like :applyno';
        $params[':applyno'] = "%{$_GPC['applyno']}%";
    }
    if (!empty($_GPC['realname'])) {
        $_GPC['realname'] = trim($_GPC['realname']);
        $condition .= ' and (m.realname like :realname or m.nickname like :realname or m.mobile like :realname)';
        $params[':realname'] = "%{$_GPC['realname']}%";
    }
    if (empty($starttime) || empty($endtime)) {
        $starttime = strtotime('-1 month');
        $endtime   = time();
    }
    $timetype = $_GPC['timetype'];
    if (!empty($_GPC['timetype'])) {
        $starttime = strtotime($_GPC['time']['start']);
        $endtime   = strtotime($_GPC['time']['end']);
        if (!empty($timetype)) {
            $condition .= " AND a.{$timetype} >= :starttime AND a.{$timetype}  <= :endtime ";
            $params[':starttime'] = $starttime;
            $params[':endtime']   = $endtime;
        }
    }
    if (!empty($_GPC['agentlevel'])) {
        $condition .= ' and m.agentlevel=' . intval($_GPC['agentlevel']);
    }
    if ($status >= 3) {
        $orderby = 'paytime';
    } else if ($status >= 2) {
        $orderby = ' checktime';
    } else {
        $orderby = 'applytime';
    }
    $list  = pdo_fetchall("select a.*, m.nickname,m.avatar,m.realname,m.mobile,l.levelname from " . tablename('ycs_fxshop_commission_apply') . " a " . " left join " . tablename('ycs_fxshop_member') . " m on m.id = a.mid" . " left join " . tablename('ycs_fxshop_commission_level') . " l on l.id = m.agentlevel" . " where 1 {$condition} ORDER BY {$orderby} desc limit " . ($pindex - 1) * $psize . ',' . $psize, $params);
    $total = pdo_fetchcolumn("select count(a.id) from" . tablename('ycs_fxshop_commission_apply') . " a " . " left join " . tablename('ycs_fxshop_member') . " m on m.uid = a.mid" . " left join " . tablename('ycs_fxshop_commission_level') . " l on l.id = m.agentlevel" . " where 1 {$condition}", $params);
    $pager = pagination($total, $pindex, $psize);
} else if ($operation == 'detail') {

    $id    = intval($_GPC['id']);
    $apply = pdo_fetch('select * from ' . tablename('ycs_fxshop_commission_apply') . ' where uniacid=:uniacid and id=:id limit 1', array(
        ':uniacid' => $_W['uniacid'],
        ':id' => $id
    ));
    if (empty($apply)) {
        message('提现申请不存在!', '', 'error');
    }
    if ($apply['status'] == -1) {
        ca('commission.apply.view_1');
    } else {
        ca('commission.apply.view' . $apply['status']);
    }
    $agentid    = $apply['mid'];
    $member     = $this->model->getInfo($agentid, array(
        'total',
        'ok',
        'apply',
        'lock',
        'check'
    ));
    $hasagent   = $member['agentcount'] > 0;
    $agentLevel = $this->model->getLevel($apply['mid']);
    if (empty($agentLevel['id'])) {
        $agentLevel = array(
            'levelname' => empty($this->set['levelname']) ? '普通等级' : $this->set['levelname'],
            'commission1' => $this->set['commission1'],
            'commission2' => $this->set['commission2'],
            'commission3' => $this->set['commission3']
        );
    }
    $orderids = iunserializer($apply['orderids']);
    if (!is_array($orderids) || count($orderids) <= 0) {
        message('无任何订单，无法查看!', '', 'error');
    }
    $ids = array();
    foreach ($orderids as $o) {
        $ids[] = $o['orderid'];
    }
    $list            = pdo_fetchall("select id,agentid, ordersn,price,goodsprice, dispatchprice,createtime, paytype from " . tablename('ycs_fxshop_order') . " where  id in ( " . implode(",", $ids) . " );");
    $totalcommission = 0;
    $totalpay        = 0;
    foreach ($list as &$row) {
        foreach ($orderids as $o) {
            if ($o['orderid'] == $row['id']) {
                $row['level'] = $o['level'];
                break;
            }
        }
        $goods = pdo_fetchall("SELECT og.id,g.thumb,og.price,og.realprice, og.total,g.title,o.paytype,og.optionname,og.commission1,og.commission2,og.commission3,og.status1,og.status2,og.status3,og.content1,og.content2,og.content3 from " . tablename('ycs_fxshop_order_goods') . " og" . " left join " . tablename('ycs_fxshop_goods') . " g on g.id=og.goodsid  " . " left join " . tablename('ycs_fxshop_order') . " o on o.id=og.orderid  " . " where og.uniacid = :uniacid and og.orderid=:orderid and og.nocommission=0 order by og.createtime  desc ", array(
            ':uniacid' => $_W['uniacid'],
            ':orderid' => $row['id']
        ));
        foreach ($goods as &$g) {
            if ($this->set['level'] >= 1) {
                $commission       = iunserializer($g['commission1']);
                $g['commission1'] = isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default'];
                if ($row['level'] == 1) {
                    $totalcommission += $g['commission1'];
                    if ($g['status1'] >= 2) {
                        $totalpay += $g['commission1'];
                    }
                }
            }
            if ($this->set['level'] >= 2) {
                $commission       = iunserializer($g['commission2']);
                $g['commission2'] = isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default'];
                if ($row['level'] == 2) {
                    $totalcommission += $g['commission2'];
                    if ($g['status2'] >= 2) {
                        $totalpay += $g['commission2'];
                    }
                }
            }
            if ($this->set['level'] >= 3) {
                $commission       = iunserializer($g['commission3']);
                $g['commission3'] = isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default'];
                if ($row['level'] == 3) {
                    $totalcommission += $g['commission3'];
                    if ($g['status3'] >= 2) {
                        $totalpay += $g['commission3'];
                    }
                }
            }
            $g['level'] = $row['level'];
        }
        unset($g);
        $row['goods'] = $goods;
        $totalmoney += $row['price'];
    }
    unset($row);
    $totalcount = $total = pdo_fetchcolumn("select count(*) from " . tablename('ycs_fxshop_order') . ' o ' . " left join " . tablename('ycs_fxshop_member') . " m on o.openid = m.openid " . " left join " . tablename('ycs_fxshop_member_address') . " a on a.id = o.addressid " . " where o.id in ( " . implode(",", $ids) . " );");
    if (checksubmit('submit_check') && $apply['status'] == 1) {
        ca('commission.apply.check');
        $paycommission = 0;
        $ogids         = array();
        foreach ($list as $row) {
            $goods = pdo_fetchall("SELECT id from " . tablename('ycs_fxshop_order_goods') . " where uniacid = :uniacid and orderid=:orderid and nocommission=0", array(
                ':uniacid' => $_W['uniacid'],
                ':orderid' => $row['id']
            ));
            foreach ($goods as $g) {
                $ogids[] = $g['id'];
            }
        }
        if (!is_array($ogids)) {
            message('数据出错，请重新设置!', '', 'error');
        }
        $time         = time();
        $isAllUncheck = true;
        foreach ($ogids as $ogid) {
            $g = pdo_fetch("SELECT total, commission1,commission2,commission3 from " . tablename('ycs_fxshop_order_goods') . "  " . "where id=:id and uniacid = :uniacid limit 1", array(
                ':uniacid' => $_W['uniacid'],
                ':id' => $ogid
            ));
            if (empty($g)) {
                continue;
            }
            if ($this->set['level'] >= 1) {
                $commission       = iunserializer($g['commission1']);
                $g['commission1'] = isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default'];
            }
            if ($this->set['level'] >= 2) {
                $commission       = iunserializer($g['commission2']);
                $g['commission2'] = isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default'];
            }
            if ($this->set['level'] >= 3) {
                $commission       = iunserializer($g['commission3']);
                $g['commission3'] = isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default'];
            }
            $update = array();
            if (isset($_GPC['status1'][$ogid])) {
                if (intval($_GPC['status1'][$ogid]) == 2) {
                    $paycommission += $g['commission1'];
                    $isAllUncheck = false;
                }
                $update = array(
                    'checktime1' => $time,
                    'status1' => intval($_GPC['status1'][$ogid]),
                    'content1' => $_GPC['content1'][$ogid]
                );
            } else if (isset($_GPC['status2'][$ogid])) {
                if (intval($_GPC['status2'][$ogid]) == 2) {
                    $paycommission += $g['commission2'];
                    $isAllUncheck = false;
                }
                $update = array(
                    'checktime2' => $time,
                    'status2' => intval($_GPC['status2'][$ogid]),
                    'content2' => $_GPC['content2'][$ogid]
                );
            } else if (isset($_GPC['status3'][$ogid])) {
                if (intval($_GPC['status3'][$ogid]) == 2) {
                    $paycommission += $g['commission3'];
                    $isAllUncheck = false;
                }
                $update = array(
                    'checktime3' => $time,
                    'status3' => intval($_GPC['status3'][$ogid]),
                    'content3' => $_GPC['content3'][$ogid]
                );
            }
            if (!empty($update)) {
                pdo_update('ycs_fxshop_order_goods', $update, array(
                    'id' => $ogid
                ));
            }
        }
        if ($isAllUncheck) {
            pdo_update('ycs_fxshop_commission_apply', array(
                'status' => -1,
                'invalidtime' => $time
            ), array(
                'id' => $id,
                'uniacid' => $_W['uniacid']
            ));
        } else {
            pdo_update('ycs_fxshop_commission_apply', array(
                'status' => 2,
                'checktime' => $time
            ), array(
                'id' => $id,
                'uniacid' => $_W['uniacid']
            ));
            $this->model->sendMessage($member['openid'], array(
                'commission' => $paycommission,
                'type' => $apply['type'] == 1 ? '微信' : '余额'
            ), TM_COMMISSION_CHECK);
        }
        plog('commission.apply.check', "佣金审核 ID: {$id} 申请编号: {$apply['applyno']} 总佣金: {$totalmoney} 审核通过佣金: {$paycommission} ");
        message('申请处理成功!', $this->createPluginWebUrl('commission/apply', array(
            'status' => $apply['status']
        )), 'success');
    }
}
if (checksubmit('submit_cancel') && ($apply['status'] == 2 || $apply['status'] == -1)) {
    ca('commission.apply.cancel');
    $time = time();
    foreach ($list as $row) {
        $update = array();
        foreach ($row['goods'] as $g) {
            $update = array();
            if ($row['level'] == 1) {
                $update = array(
                    'checktime1' => 0,
                    'status1' => 1
                );
            } else if ($row['level'] == 2) {
                $update = array(
                    'checktime2' => 0,
                    'status2' => 1
                );
            } else if ($row['level'] == 3) {
                $update = array(
                    'checktime3' => 0,
                    'status3' => 1
                );
            }
            if (!empty($update)) {
                pdo_update('ycs_fxshop_order_goods', $update, array(
                    'id' => $g['id']
                ));
            }
        }
    }
    pdo_update('ycs_fxshop_commission_apply', array(
        'status' => 1,
        'checktime' => 0,
        'invalidtime' => 0
    ), array(
        'id' => $id,
        'uniacid' => $_W['uniacid']
    ));
    plog('commission.apply.cancel', "重新审核申请 ID: {$id} 申请编号: {$apply['applyno']} ");
    message('撤销审核处理成功!', $this->createPluginWebUrl('commission/apply', array(
        'status' => 1
    )), 'success');
}
if (checksubmit('submit_pay') && $apply['status'] == 2) {


    ca('commission.apply.pay');
    $time = time();
    $pay  = $totalpay;
    if ($apply['type'] == 1) {
        $pay *= 100;
    }

    $result = m('finance')->pay($member['openid'], $apply['type'], $pay, $apply['applyno']);  //这里是去打款提现之类得到操作

    if (is_error($result)) {
        if (strexists($result['message'], '系统繁忙')) {
            $updateno['applyno'] = $apply['applyno'] = m('common')->createNO('commission_apply', 'applyno', 'CA');
            pdo_update('ycs_fxshop_commission_apply', $updateno, array(
                'id' => $apply['id']
            ));

            $result = m('finance')->pay($member['openid'], $apply['type'], $pay, $apply['applyno']);
            if (is_error($result)) {
                message($result['message'], '', 'error');
            }
        }
     message($result['message'], '', 'error');
    }
    foreach ($list as $row) {
        $update = array();
        foreach ($row['goods'] as $g) {
            $update = array();
            if ($row['level'] == 1 && $g['status1'] == 2) {
                $update = array(
                    'paytime1' => $time,
                    'status1' => 3
                );
            } else if ($row['level'] == 2 && $g['status2'] == 2) {
                $update = array(
                    'paytime2' => $time,
                    'status2' => 3
                );
            } else if ($row['level'] == 3 && $g['status3'] == 2) {
                $update = array(
                    'paytime3' => $time,
                    'status3' => 3
                );
            }
            if (!empty($update)) {
                pdo_update('ycs_fxshop_order_goods', $update, array(
                    'id' => $g['id']
                ));
            }
        }
    }
    pdo_update('ycs_fxshop_commission_apply', array(
        'status' => 3,
        'paytime' => $time,
        'commission_pay' => $totalpay
    ), array(
        'id' => $id,
        'uniacid' => $_W['uniacid']
    ));
    $log = array(
        'uniacid' => $_W['uniacid'],
        'applyid' => $apply['id'],
        'mid' => $member['id'],
        'commission' => $totalcommission,
        'commission_pay' => $totalpay,
        'createtime' => $time
    );
    pdo_insert('ycs_fxshop_commission_log', $log);
    $this->model->sendMessage($member['openid'], array(
        'commission' => $totalpay,
        'type' => $apply['type'] == 1 ? '微信' : '余额'
    ), TM_COMMISSION_PAY);
    plog('commission.apply.pay', "佣金打款 ID: {$id} 申请编号: {$apply['applyno']} 总佣金: {$totalcommission} 审核通过佣金: {$totalpay} ");
    message('佣金打款处理成功!', $this->createPluginWebUrl('commission/apply', array(
        'status' => $apply['status']
    )), 'success');
}
load()->func('tpl');
include $this->template('apply');