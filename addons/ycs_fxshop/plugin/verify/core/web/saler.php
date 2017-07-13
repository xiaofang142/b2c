<?php

/*
*Url http://yqhls.cn
*/
if (!defined('IN_IA')) {
    exit('Access Denied');
}
global $_W, $_GPC;

$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
if ($operation == 'display') {
    ca('verify.saler.view');
    $list = pdo_fetchall("SELECT s.*,m.nickname,m.avatar,m.mobile,m.realname,store.storename FROM " . tablename('ycs_fxshop_saler') . "  s " . " left join " . tablename('ycs_fxshop_member') . " m on s.openid=m.openid " . " left join " . tablename('ycs_fxshop_store') . " store on store.id=s.storeid " . " WHERE s.uniacid = '{$_W['uniacid']}' ORDER BY id asc");
} elseif ($operation == 'post') {
    $id = intval($_GPC['id']);
    if (empty($id)) {
        ca('verify.saler.add');
    } else {
        ca('verify.saler.view|verify.saler.edit');
    }
    $item = pdo_fetch("SELECT * FROM " . tablename('ycs_fxshop_saler') . " WHERE id =:id and uniacid=:uniacid limit 1", array(
        ':uniacid' => $_W['uniacid'],
        ':id' => $id
    ));
    if (!empty($item)) {
        $saler = m('member')->getMember($item['openid']);
        $store = pdo_fetch("SELECT * FROM " . tablename('ycs_fxshop_store') . " WHERE id =:id and uniacid=:uniacid limit 1", array(
            ':uniacid' => $_W['uniacid'],
            ':id' => $item['storeid']
        ));
    }
    if (checksubmit('submit')) {
        $data = array(
            'uniacid' => $_W['uniacid'],
            'storeid' => intval($_GPC['storeid']),
            'openid' => trim($_GPC['openid']),
            'status' => intval($_GPC['status'])
        );
        $m    = m('member')->getMember($data['openid']);
        if (!empty($id)) {
            pdo_update('ycs_fxshop_saler', $data, array(
                'id' => $id,
                'uniacid' => $_W['uniacid']
            ));
            plog('verify.saler.edit', "编辑核销员 ID: {$id} <br/>核销员信息: ID: {$m['id']} / {$m['openid']}/{$m['nickname']}/{$m['realname']}/{$m['mobile']} ");
        } else {
            pdo_insert('ycs_fxshop_saler', $data);
            $id = pdo_insertid();
            plog('verify.saler.add', "添加核销员 ID: {$id}  <br/>核销员信息: ID: {$m['id']} / {$m['openid']}/{$m['nickname']}/{$m['realname']}/{$m['mobile']} ");
        }
        message('更新核销员成功！', $this->createPluginWebUrl('verify/saler', array(
            'op' => 'display'
        )), 'success');
    }
} elseif ($operation == 'delete') {
    ca('verify.saler.delete');
    $id   = intval($_GPC['id']);
    $item = pdo_fetch("SELECT id,openid FROM " . tablename('ycs_fxshop_saler') . " WHERE id = '$id'");
    if (empty($item)) {
        message('抱歉，核销员不存在或是已经被删除！', $this->createPluginWebUrl('verify/saler', array(
            'op' => 'display'
        )), 'error');
    }
    pdo_delete('ycs_fxshop_saler', array(
        'id' => $id,
        'uniacid' => $_W['uniacid']
    ));
    $m = m('member')->getMember($item['openid']);
    plog('verify.saler.delete', "删除核销员 ID: {$id}  <br/>核销员信息: ID: {$m['id']} / {$m['openid']}/{$m['nickname']}/{$m['realname']}/{$m['mobile']} ");
    message('核销员删除成功！', $this->createPluginWebUrl('verify/saler', array(
        'op' => 'display'
    )), 'success');
} elseif ($operation == 'query') {
    $kwd                = trim($_GPC['keyword']);
    $params             = array();
    $params[':uniacid'] = $_W['uniacid'];
    $condition          = " and s.uniacid=:uniacid";
    if (!empty($kwd)) {
        $condition .= " AND ( m.nickname LIKE :keyword or m.realname LIKE :keyword or m.mobile LIKE :keyword or store.storename like :keyword )";
        $params[':keyword'] = "%{$kwd}%";
    }
    $ds = pdo_fetchall("SELECT s.*,m.nickname,m.avatar,m.mobile,m.realname,store.storename FROM " . tablename('ycs_fxshop_saler') . "  s " . " left join " . tablename('ycs_fxshop_member') . " m on s.openid=m.openid " . " left join " . tablename('ycs_fxshop_store') . " store on store.id=s.storeid " . " WHERE 1 {$condition} ORDER BY id asc", $params);
    include $this->template('query_saler');
    exit;
}
load()->func('tpl');
include $this->template('saler');