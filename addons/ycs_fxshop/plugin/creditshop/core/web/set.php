<?php
global $_W, $_GPC;

ca('creditshop.set.view');
$set = $this->getSet();
if (checksubmit('submit')) {
    ca('creditshop.set.save');
    $data = is_array($_GPC['setdata']) ? array_merge($set, $_GPC['setdata']) : array();
    $this->updateSet($data);
    $exchangekeyword = $data['exchangekeyword'];
    $rule            = pdo_fetch("select * from " . tablename('rule') . ' where uniacid=:uniacid and module=:module and name=:name  limit 1', array(
        ':uniacid' => $_W['uniacid'],
        ':module' => 'ycs_fxshop',
        ':name' => "ycs_fxshop:creditshop"
    ));
    if (empty($rule)) {
        $rule_data = array(
            'uniacid' => $_W['uniacid'],
            'name' => 'ycs_fxshop:creditshop',
            'module' => 'ycs_fxshop',
            'displayorder' => 0,
            'status' => 1
        );
        pdo_insert('rule', $rule_data);
        $rid          = pdo_insertid();
        $keyword_data = array(
            'uniacid' => $_W['uniacid'],
            'rid' => $rid,
            'module' => 'ycs_fxshop',
            'content' => trim($exchangekeyword),
            'type' => 1,
            'displayorder' => 0,
            'status' => 1
        );
        pdo_insert('rule_keyword', $keyword_data);
    } else {
        pdo_update('rule_keyword', array(
            'content' => trim($exchangekeyword)
        ), array(
            'rid' => $rule['id']
        ));
    }
    $datapath = IA_ROOT . "/addons/ycs_fxshop/data/template";
    if (!is_dir($datapath)) {
        load()->func('file');
        @mkdirs($datapath, "777");
    }
    file_put_contents($datapath . "/plugin_" . $this->pluginname . "_" . $_W['uniacid'], $data['style']);
    plog('creditshop.set.save', '修改积分商城基本设置');
    message('设置保存成功!', referer(), 'success');
}
$styles = array();
$dir    = IA_ROOT . "/addons/ycs_fxshop/plugin/" . $this->pluginname . "/template/mobile/";
if ($handle = opendir($dir)) {
    while (($file = readdir($handle)) !== false) {
        if ($file != ".." && $file != ".") {
            if (is_dir($dir . "/" . $file)) {
                $styles[] = $file;
            }
        }
    }
    closedir($handle);
}
load()->func('tpl');
include $this->template('set');