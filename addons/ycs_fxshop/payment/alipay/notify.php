<?php
error_reporting(0);
define('IN_MOBILE', true);
if (!empty($_POST)) {
    $out_trade_no = $_POST['out_trade_no'];
    require '../../../../framework/bootstrap.inc.php';
    $body          = $_POST['body'];
    $strs          = explode(':', $body);
    $_W['uniacid'] = $_W['weid'] = $strs[0];
    $type          = $strs[1];
    $setting       = uni_setting($_W['uniacid'], array(
        'payment'
    ));
    if (is_array($setting['payment'])) {
        $alipay = $setting['payment']['alipay'];
        if (!empty($alipay)) {
            $prepares = array();
            foreach ($_POST as $key => $value) {
                if ($key != 'sign' && $key != 'sign_type') {
                    $prepares[] = "{$key}={$value}";
                }
            }
            sort($prepares);
            $string = implode($prepares, '&');
            $string .= $alipay['secret'];
            $sign = md5($string);
            if ($sign == $_POST['sign']) {
                if ($type == '0') {
                    $tid               = $out_trade_no;
                    $sql               = 'SELECT * FROM ' . tablename('core_paylog') . ' WHERE `tid`=:tid and `module`=:module limit 1';
                    $params            = array();
                    $params[':tid']    = $tid;
                    $params[':module'] = 'ycs_fxshop';
                    $log               = pdo_fetch($sql, $params);
                    if (!empty($log) && $log['status'] == '0') {
                        $record           = array();
                        $record['status'] = '1';
                        pdo_update('core_paylog', $record, array(
                            'plid' => $log['plid']
                        ));
                        if ($log['is_usecard'] == 1 && $log['card_type'] == 1 && !empty($log['encrypt_code']) && $log['acid']) {
                            load()->classs('coupon');
                            $acc                     = new coupon($log['acid']);
                            $codearr['encrypt_code'] = $log['encrypt_code'];
                            $codearr['module']       = $log['module'];
                            $codearr['card_id']      = $log['card_id'];
                            $acc->PayConsumeCode($codearr);
                        }
                        if ($log['is_usecard'] == 1 && $log['card_type'] == 2) {
                            $now            = time();
                            $log['card_id'] = intval($log['card_id']);
                            $iscard         = pdo_fetchcolumn('SELECT iscard FROM ' . tablename('modules') . ' WHERE name = :name', array(
                                ':name' => $log['module']
                            ));
                            $condition      = '';
                            if ($iscard == 1) {
                                $condition = " AND grantmodule = '{$log['module']}'";
                            }
                            pdo_query('UPDATE ' . tablename('activity_coupon_record') . " SET status = 2, usetime = {$now}, usemodule = '{$log['module']}' WHERE uniacid = :aid AND couponid = :cid AND uid = :uid AND status = 1 {$condition} LIMIT 1", array(
                                ':aid' => $_W['uniacid'],
                                ':uid' => $log['openid'],
                                ':cid' => $log['card_id']
                            ));
                        }
                        $site = WeUtility::createModuleSite($log['module']);
                        if (!is_error($site)) {
                            $method = 'payResult';
                            if (method_exists($site, $method)) {
                                $ret               = array();
                                $ret['weid']       = $log['weid'];
                                $ret['uniacid']    = $log['uniacid'];
                                $ret['result']     = 'success';
                                $ret['type']       = $log['type'];
                                $ret['from']       = 'return';
                                $ret['tid']        = $log['tid'];
                                $ret['user']       = $log['openid'];
                                $ret['fee']        = $log['fee'];
                                $ret['is_usecard'] = $log['is_usecard'];
                                $ret['card_type']  = $log['card_type'];
                                $ret['card_fee']   = $log['card_fee'];
                                $ret['card_id']    = $log['card_id'];
                                $site->$method($ret);
                                exit('success');
                            }
                        }
                    }
                } else if ($type == '1') {
                    require '../../../../addons/ycs_fxshop/defines.php';
                    require '../../../../addons/ycs_fxshop/core/inc/functions.php';
                    $logno = trim($out_trade_no);
                    if (empty($logno)) {
                        exit;
                    }
                    $log = pdo_fetch('SELECT * FROM ' . tablename('ycs_fxshop_member_log') . ' WHERE `uniacid`=:uniacid and `logno`=:logno limit 1', array(
                        ':uniacid' => $_W['uniacid'],
                        ':logno' => $logno
                    ));
                    if (!empty($log) && empty($log['status'])) {
                        pdo_update('ycs_fxshop_member_log', array(
                            'status' => 1,
                            'rechargetype' => 'alipay'
                        ), array(
                            'id' => $log['id']
                        ));
                        m('member')->setCredit($log['openid'], 'credit2', $log['money'], array(
                            0,
                            '云分销商城会员充值:credit2:' . $log['money']
                        ));
                        m('member')->setRechargeCredit($log['openid'], $log['money']);
                        m('notice')->sendMemberLogMessage($log['id']);
                    }
                }
            }
        }
    }
}
exit('fail');