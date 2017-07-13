<?php

/*
*Url http://yqhls.cn
*/
global $_W, $_GPC;

ca('qiniu.admin');
$set = $this->getSet();
if (checksubmit('submit')) {
	$set['user'] = is_array($_GPC['user']) ? $_GPC['user'] : array();
	if (!empty($set['user']['upload'])) {
		$ret = $this->check($set['user']);
		if (empty($ret)) {
			message('配置有误，请仔细检查参数设置!', '', 'error');
		}
	}
	$this->updateSet($set);
	message('设置保存成功!', referer(), 'success');
}
if (checksubmit('submit_admin')) {
	$set['admin'] = is_array($_GPC['admin']) ? $_GPC['admin'] : array();
	if (!empty($set['admin']['upload'])) {
		$ret = $this->check($set['admin']);
		if (empty($ret)) {
			message('配置有误，请仔细检查参数设置!', '', 'error');
		}
	}
	load()->func('file');
	@mkdirs(IA_ROOT . '/addons/ycs_fxshop/data/sysset', '0777');
	file_put_contents(IA_ROOT . '/addons/ycs_fxshop/data/sysset/qiniu', json_encode($set['admin']));
	plog('qiniu.admin', '设置七牛');
	message('设置保存成功!', referer(), 'success');
}
$set['admin'] = @json_decode(@file_get_contents(IA_ROOT . "/addons/ycs_fxshop/data/sysset/qiniu"), true);
include $this->template('set');