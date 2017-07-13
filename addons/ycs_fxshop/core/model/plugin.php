<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}
class Ycs_DShop_Plugin
{
    public function getSet($plugin = '', $key = '', $uniacid = 0)
    {
        global $_W, $_GPC;
        if (empty($uniacid)) {
            $uniacid = $_W['uniacid'];
        }
        $set = pdo_fetch("select * from " . tablename('ycs_fxshop_sysset') . ' where uniacid=:uniacid limit 1', array(
            ':uniacid' => $uniacid
        ));
        if (empty($set)) {
            return array();
        }
        $allset = unserialize($set['sets']);
        if (empty($key)) {
            return $allset;
        }
        return $allset[$key];
    }
    public function exists($pluginName = '')
    {
        $dbplugin = pdo_fetchall('select * from ' . tablename('ycs_fxshop_plugin') . ' where identity=:identyty limit  1', array(
            ':identity' => $pluginName
        ));
        if (empty($dbplugin)) {
            return false;
        }
        return true;
    }
    public function getAll()
    {
        global $_W;
        $path = IA_ROOT . "/addons/ycs_fxshop/data/perm";
        if (!is_dir($path)) {
            load()->func('file');
            @mkdirs($path);
        }
        $cachefile = $path . "/plugins";
        $plugins   = iunserializer(@file_get_contents($cachefile));
        if (!is_array($plugins)) {
            $plugins = pdo_fetchall('select * from ' . tablename('ycs_fxshop_plugin') . ' order by displayorder asc');
            file_put_contents($cachefile, iserializer($plugins));
        }
        return $plugins;
    }
}