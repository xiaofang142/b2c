<?php


if (!defined('IN_IA')) {
    exit('Access Denied');
}
class Plugin extends Core
{
    public $pluginname;
    public $model;
    public function __construct($name = '')
    {
        parent::__construct();
        $this->modulename = 'ycs_fxshop';
        $this->pluginname = $name;
        $this->loadModel();
        if (strexists($_SERVER['REQUEST_URI'], '/web/')) {
            cpa($this->pluginname);
        } else if (strexists($_SERVER['REQUEST_URI'], '/app/')) {
            if (is_weixin()) {
                $this->setFooter();
            }
        }
        $this->module['title'] = pdo_fetchcolumn('select title from ' . tablename('modules') . " where name='ycs_fxshop' limit 1");
    }
    private function loadModel()
    {
        $modelfile = IA_ROOT . '/addons/' . $this->modulename . "/plugin/" . $this->pluginname . "/model.php";
        if (is_file($modelfile)) {
            $classname = ucfirst($this->pluginname) . "Model";
            require $modelfile;
            $this->model = new $classname($this->pluginname);
        }
    }
    public function getSet()
    {
        return $this->model->getSet();
    }
    public function updateSet($data = array())
    {
        $this->model->updateSet($data);
    }
    public function template($filename, $type = TEMPLATE_INCLUDEPATH)
    {
        global $_W;
        $defineDir = IA_ROOT . "/addons/ycs_fxshop/";
        if (defined('IN_SYS')) {
            $source  = IA_ROOT . "/addons/ycs_fxshop/plugin/" . $this->pluginname . "/template/{$filename}.html";
            $compile = IA_ROOT . "/data/tpl/web/{$_W['template']}/ycs_fxshop/plugin/" . $this->pluginname . "/{$filename}.tpl.php";
            if (!is_file($source)) {
                $source  = IA_ROOT . "/addons/ycs_fxshop/template/{$filename}.html";
                $compile = IA_ROOT . "/data/tpl/web/{$_W['template']}/ycs_fxshop/{$filename}.tpl.php";
            }
            if (!is_file($source)) {
                $source  = IA_ROOT . "/web/themes/{$_W['template']}/{$filename}.html";
                $compile = IA_ROOT . "/data/tpl/web/{$_W['template']}/{$filename}.tpl.php";
            }
            if (!is_file($source)) {
                $source  = IA_ROOT . "/web/themes/default/{$filename}.html";
                $compile = IA_ROOT . "/data/tpl/web/default/{$filename}.tpl.php";
            }
        } else {
            $global_template = "default";
            $file            = IA_ROOT . "/addons/ycs_fxshop/data/template/shop_" . $_W['uniacid'];
            if (is_file($file)) {
                $global_template = file_get_contents($file);
                if (!is_dir(IA_ROOT . '/addons/ycs_fxshop/template/mobile/' . $template)) {
                    $global_template = "default";
                }
            }
            $template = "default";
            $file     = IA_ROOT . "/addons/ycs_fxshop/data/template/plugin_" . $this->pluginname . "_" . $_W['uniacid'];
            if (is_file($file)) {
                $template = file_get_contents($file);
                if (!is_dir(IA_ROOT . '/addons/ycs_fxshop/plugin/' . $this->pluginname . "/template/mobile/" . $template)) {
                    $template = "default";
                }
            }
            $compile = IA_ROOT . "/data/app/ycs_fxshop/plugin/" . $this->pluginname . "/{$template}/mobile/{$filename}.tpl.php";
            $source  = $defineDir . "/plugin/" . $this->pluginname . "/template/mobile/{$template}/{$filename}.html";
            if (!is_file($source)) {
                $source  = $defineDir . "/plugin/" . $this->pluginname . "/template/mobile/default/{$filename}.html";
                $compile = IA_ROOT . "/data/app/ycs_fxshop/plugin/" . $this->pluginname . "/default/mobile/{$filename}.tpl.php";
            }
            if (!is_file($source)) {
                $source  = $defineDir . "/template/mobile/{$global_template}/{$filename}.html";
                $compile = IA_ROOT . "/data/app/ycs_fxshop/{$global_template}/{$filename}.tpl.php";
            }
            if (!is_file($source)) {
                $source  = $defineDir . "/template/mobile/default/{$filename}.html";
                $compile = IA_ROOT . "/data/app/ycs_fxshop/default/{$filename}.tpl.php";
            }
            if (!is_file($source)) {
                $source  = $defineDir . "/template/mobile/{$filename}.html";
                $compile = IA_ROOT . "/data/app/ycs_fxshop/{$filename}.tpl.php";
            }
            if (!is_file($source)) {
                $names      = explode('/', $filename);
                $pluginname = $names[0];
                $ptemplate  = "default";
                $file       = IA_ROOT . "/addons/ycs_fxshop/data/template/plugin_" . $pluginname . "_" . $_W['uniacid'];
                if (is_file($file)) {
                    $ptemplate = @file_get_contents($file);
                    if (empty($ptemplate) || !is_dir(IA_ROOT . "/addons/ycs_fxshop/plugin/" . $pluginname . "/template/mobile/" . $ptemplate)) {
                        $ptemplate = "default";
                    }
                }
                $pfilename = $names[1];
                $source    = IA_ROOT . "/addons/ycs_fxshop/plugin/" . $pluginname . "/template/mobile/" . $ptemplate . "/{$pfilename}.html";
            }
        }
        if (!is_file($source)) {
            exit("Error: template source '{$filename}' is not exist!");
        }
        if (DEVELOPMENT || !is_file($compile) || filemtime($source) > filemtime($compile)) {
            shop_template_compile($source, $compile, true);
        }
        return $compile;
    }
    public function _exec_plugin($do, $web = true)
    {
        global $_GPC;
        if ($web) {
            $file = IA_ROOT . "/addons/ycs_fxshop/plugin/" . $this->pluginname . "/core/web/" . $do . ".php";
        } else {
            $file = IA_ROOT . "/addons/ycs_fxshop/plugin/" . $this->pluginname . "/core/mobile/" . $do . ".php";
        }
        if (!is_file($file)) {
            message("未找到控制器文件 : {$file}");
        }
        include $file;
        exit;
    }
}