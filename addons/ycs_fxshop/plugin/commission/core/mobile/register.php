<?php


global $_W, $_GPC;
$openid   = m('user')->getOpenid();
//利用openid  检查是否关注
$uid = pdo_fetchcolumn('select uid from ' . tablename('mc_mapping_fans') . " where uniacid=:uniacid and openid=:openid limit 1", array(
    ':uniacid' => $_W['uniacid'],
    ':openid' => $openid
));

///设置是否关注公众号开关 0 为关注 1 以关注
if(!empty($uid)){
    $attention =1;
    die();
    //有uid 表示已经关注公众号
    if ($member['isagent'] == 1 && $member['status'] == 1) {
        header("location: " . $this->createPluginMobileUrl('commission'));
        exit;
    }
    $shop_set = m('common')->getSysset('shop');
    $set      = set_medias($this->set, 'regbg');
    print_r($set);
    $member   = m('member')->getInfo($openid);
    $mid = intval($_GPC['mid']);
    if ($_W['isajax']) {
        $agent = false;
        if (!empty($member['agentid'])) {
            $mid   = $member['agentid'];
            $agent = m('member')->getMember($member['agentid']);
        } else if (!empty($mid)) {
            $agent = m('member')->getMember($mid);
        }
        $ret           = array(
            'shop_set' => $shop_set,
            'set' => $set,
            'member' => $member,
            'agent' => $agent
        );
        $ret['status'] = 0;
        $status        = intval($set['become_order']) == 0 ? 1 : 3;
//        //0 表示无条件成为分销商  将其关注
//        if (empty($set['become'])) {
//            $become_reg = intval($set['become_reg']);
//            $ret['status'] = $become_check;
////        if (empty($become_reg)) {
////            //是否需要完善资料
////            $become_check  = intval($set['become_check']);
////            $ret['status'] = $become_check;
////            $data          = array(
////                'isagent' => 1,
////                'agentid' => $mid,
////                'status' => $become_check,
////                'realname' => $_GPC['realname'],
////                'mobile' => $_GPC['mobile'],
////                'weixin' => $_GPC['weixin'],
////                'agenttime' => $become_check == 1 ? time() : 0
////            );
////            pdo_update('ycs_fxshop_member', $data, array(
////                'id' => $member['id']
////            ));
////            if ($become_check == 1) {
////                $this->model->sendMessage($member['openid'], array(
////                    'agenttime' => $data['agenttime']
////                ), TM_COMMISSION_BECOME);
////            }
////            if (!empty($member['uid'])) {
////                load()->model('mc');
////                mc_update($member['uid'], array(
////                    'realname' => $data['realname'],
////                    'mobile' => $data['mobile']
////                ));
////            }
////        }
//        } else
            if ($set['become'] == '2') {
            //消费达到一定次数成为分销商 检查订单数量
            $ordercount = pdo_fetchcolumn('select count(*) from ' . tablename('ycs_fxshop_order') . " where uniacid=:uniacid and openid=:openid and status>={$status} limit 1", array(
                ':uniacid' => $_W['uniacid'],
                ':openid' => $openid
            ));
            if ($ordercount < intval($set['become_ordercount'])) {
                $ret['status']     = 1;
                $ret['order']      = number_format($ordercount, 0);
                $ret['ordercount'] = number_format($set['become_ordercount'], 0);
            }
        } else if ($set['become'] == '3') {
            //消费达到一定金额成为分销商
            $moneycount = pdo_fetchcolumn('select sum(goodsprice) from ' . tablename('ycs_fxshop_order') . " where uniacid=:uniacid and openid=:openid and status>={$status} limit 1", array(
                ':uniacid' => $_W['uniacid'],
                ':openid' => $openid
            ));
            if ($moneycount < floatval($set['become_moneycount'])) {
                $ret['status']     = 2;
                $ret['money']      = number_format($moneycount, 2);
                $ret['moneycount'] = number_format($set['become_moneycount'], 2);
            }
        }
        //申请页面，点击提交
        if ($_W['ispost']) {
            if ($member['isagent'] == 1 && $member['status'] == 1) {
                show_json(0, '您已经是' . $set['texts']['become'] . '，无需再次申请!');
            }
            if ($ret['status'] == 1 || $ret['status'] == 2) {
                show_json(0, '您消费的还不够哦，无法申请' . $set['texts']['become'] . '!');
            } else {
                $become_check  = intval($set['become_check']);
                $ret['status'] = $become_check;
                $data          = array(
                    'isagent' => 1,
                    'agentid' => $mid,
                    'status' => $become_check,
                    'realname' => $_GPC['realname'],
                    'mobile' => $_GPC['mobile'],
                    'weixin' => $_GPC['weixin'],
                    'agenttime' => $become_check == 1 ? time() : 0
                );
                pdo_update('ycs_fxshop_member', $data, array(
                    'id' => $member['id']
                ));
                if ($become_check == 1) {
                    $this->model->sendMessage($member['openid'], array(
                        'agenttime' => $data['agenttime']
                    ), TM_COMMISSION_BECOME);
                }
                if (!empty($member['uid'])) {
                    load()->model('mc');
                    mc_update($member['uid'], array(
                        'realname' => $data['realname'],
                        'mobile' => $data['mobile']
                    ));
                    show_json(1, $ret);
                }
            }
        }
        show_json(1, $ret);
    }

}else{
    //没有关注公众号
    $attention =0;
    echo "请先关注公众号";
    die();
}


if (empty($set['become'])) {
}


$this->setHeader();
include $this->template('register');