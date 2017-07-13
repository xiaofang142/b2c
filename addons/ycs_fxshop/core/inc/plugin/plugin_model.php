<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}
class PluginModel
{
    private $pluginname;
    public function __construct($name = '')
    {
        $this->pluginname = $name;
    }
    public function getSet()
    {
        global $_W, $_GPC;
        $set    = m('common')->getSetData();
        $allset = iunserializer($set['plugins']);
        if (is_array($allset) && isset($allset[$this->pluginname])) {
            return $allset[$this->pluginname];
        }
        return array();
    }
    public function updateSet($data = array())
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $set     = m('common')->getSetData();
        if (empty($set)) {
            pdo_insert('ycs_fxshop_sysset', array(
                'uniacid' => $uniacid,
                'sets' => iserializer(array()),
                'plugins' => iserializer(array(
                    $this->pluginname => $data
                ))
            ));
        } else {
            $sets                    = unserialize($set['plugins']);
            $sets[$this->pluginname] = $data;
            pdo_update('ycs_fxshop_sysset', array(
                'plugins' => iserializer($sets)
            ), array(
                'uniacid' => $uniacid
            ));
        }
        $set       = pdo_fetch("select * from " . tablename('ycs_fxshop_sysset') . ' where uniacid=:uniacid limit 1', array(
            ':uniacid' => $uniacid
        ));
        $path      = IA_ROOT . "/addons/ycs_fxshop/data/sysset";
        $cachefile = $path . "/sysset_" . $uniacid;
        if (!is_dir($path)) {
            load()->func('file');
            @mkdirs($path);
        }
        file_put_contents($cachefile, iserializer($set));
    }
    function getName()
    {
        return pdo_fetchcolumn('select name from ' . tablename('ycs_fxshop_plugin') . ' where identity=:identity limit 1', array(
            ':identity' => $this->pluginname
        ));
    }
}