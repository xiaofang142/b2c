<?php


if (!defined('IN_IA')) {
    exit('Access Denied');
}
class Core extends WeModuleSite
{
    public $footer = array();
    public $header = null;
    public function __construct()
    {
        global $_W;
        $this->autoFinishOrders();
        if (is_weixin()) {
            m('member')->checkMember();
        }
    }
    private function autoFinishOrders()
    {
        global $_W;
        $file     = IA_ROOT . "/addons/ycs_fxshop/data/receive";
        $lasttime = strtotime(@file_get_contents($file));
        $interval = intval(@file_get_contents(IA_ROOT . "/addons/ycs_fxshop/data/receive_time"));
        if (empty($interval)) {
            $interval = 60;
        }
        $interval *= 60;
        $current = time();
        if ($lasttime + $interval <= $current) {
            file_put_contents($file, date('Y-m-d H:i:s', $current));
            load()->func('communication');
            ihttp_request($_W['siteroot'] . "addons/ycs_fxshop/core/mobile/order/receive.php", null, null, 1);
        }
    }
    public function setHeader()
    {
        global $_W, $_GPC;
        $openid   = m('user')->getOpenid();
        $followed = m('user')->followed($openid);
        $mid      = intval($_GPC['mid']);
        $memberid = m('member')->getMid();
        @session_start();
        if (!$followed && $memberid != $mid) {
            $set          = m('common')->getSysset();
            $this->header = array(
                'url' => $set['share']['followurl']
            );
            $friend       = false;
            if (!empty($mid)) {
                if (!empty($_SESSION[YCS_FXSHOP_PREFIX . '_shareid']) && $_SESSION[YCS_FXSHOP_PREFIX . '_shareid'] == $mid) {
                    $mid = $_SESSION[YCS_FXSHOP_PREFIX . '_shareid'];
                }
                $member = m('member')->getMember($mid);
                if (!empty($member)) {
                    $_SESSION[YCS_FXSHOP_PREFIX . '_shareid'] = $mid;
                    $friend                                  = true;
                    $this->header['icon']                    = $member['avatar'];
                    $this->header['text']                    = '来自好友 <span>' . $member['nickname'] . '</span> 的推荐';
                }
            }
            if (!$friend) {
                $this->header['icon'] = tomedia($set['shop']['logo']);
                $this->header['text'] = '欢迎进入 <span>' . $set['shop']['name'] . '</span>';
            }
        }
    }
    public function setFooter()
    {
        global $_GPC;
        $openid = m('user')->getOpenid();
        if (empty($openid)) {
            return;
        }
        $designer = p('designer');
        if ($designer && $_GPC['p'] != 'designer') {
            $menu = $designer->getDefaultMenu();
            if (!empty($menu)) {
                $this->footer['diymenu']   = true;
                $this->footer['diymenus']  = $menu['menus'];
                $this->footer['diyparams'] = $menu['params'];
                return;
            }
        }
        $mid                        = intval($_GPC['mid']);
        $this->footer['first']      = array(
            'text' => '首页',
            'ico' => 'home',
            'url' => $this->createMobileUrl('shop')
        );
        $this->footer['second']     = array(
            'text' => '分类',
            'ico' => 'list',
            'url' => $this->createMobileUrl('shop/category')
        );
        $this->footer['commission'] = false;
        if (p('commission')) {
            $set = p('commission')->getSet();
            if (empty($set['level'])) {
                return;
            }
            $member  = m('member')->getMember($openid);
            $isagent = $member['isagent'] == 1 && $member['status'] == 1;
            if ($_GPC['do'] == 'plugin') {
                $this->footer['first'] = array(
                    'text' => empty($set['closemyshop']) ? $set['texts']['shop'] : '首页',
                    'ico' => 'home',
                    'url' => empty($set['closemyshop']) ? $this->createPluginMobileUrl('commission/myshop', array(
                        'mid' => $member['id']
                    )) : $this->createMobileUrl('shop')
                );
                if ($_GPC['method'] == '') {
                    $this->footer['first']['text'] = empty($set['closemyshop']) ? $set['texts']['myshop'] : '首页';
                }
                if (empty($member['agentblack'])) {
                    $this->footer['commission'] = array(
                        'text' => $set['texts']['center'],
                        'ico' => 'sitemap',
                        'url' => $this->createPluginMobileUrl('commission')
                    );
                }
            } else {
                if (empty($member['agentblack'])) {
                    if (!$isagent) {
                        $this->footer['commission'] = array(
                            'text' => $set['texts']['become'],
                            'ico' => 'sitemap',
                            'url' => $this->createPluginMobileUrl('commission/register')
                        );
                    } else {
                        $this->footer['commission'] = array(
                            'text' => empty($set['closemyshop']) ? $set['texts']['shop'] : $set['texts']['center'],
                            'ico' => empty($set['closemyshop']) ? 'heart' : 'sitemap',
                            'url' => empty($set['closemyshop']) ? $this->createPluginMobileUrl('commission/myshop', array(
                                'mid' => $member['id']
                            )) : $this->createPluginMobileUrl('commission')
                        );
                    }
                }
            }
        }
    }
    public function createMobileUrl($do, $query = array(), $noredirect = true)
    {
        global $_W, $_GPC;
        $do = explode('/', $do);
        if (isset($do[1])) {
            $query = array_merge(array(
                'p' => $do[1]
            ), $query);
        }
        if (empty($query['mid'])) {
            $mid = intval($_GPC['mid']);
            if (!empty($mid)) {
                $query['mid'] = $mid;
            }
        }
        return $_W['siteroot'] . 'app/' . substr(parent::createMobileUrl($do[0], $query, true), 2);
    }
    public function createWebUrl($do, $query = array())
    {
        global $_W;
        $do = explode('/', $do);
        if (count($do) > 1 && isset($do[1])) {
            $query = array_merge(array(
                'p' => $do[1]
            ), $query);
        }
        return $_W['siteroot'] . 'web/' . substr(parent::createWebUrl($do[0], $query, true), 2);
    }
    public function createPluginMobileUrl($do, $query = array())
    {
        global $_W, $_GPC;
        $do         = explode('/', $do);
        $query      = array_merge(array(
            'p' => $do[0]
        ), $query);
        $query['m'] = 'ycs_fxshop';
        if (isset($do[1])) {
            $query = array_merge(array(
                'method' => $do[1]
            ), $query);
        }
        if (isset($do[2])) {
            $query = array_merge(array(
                'op' => $do[2]
            ), $query);
        }
        if (empty($query['mid'])) {
            $mid = intval($_GPC['mid']);
            if (!empty($mid)) {
                $query['mid'] = $mid;
            }
        }
        return $_W['siteroot'] . 'app/' . substr(parent::createMobileUrl('plugin', $query, true), 2);
    }
    public function createPluginWebUrl($do, $query = array())
    {
        global $_W;
        $do    = explode('/', $do);
        $query = array_merge(array(
            'p' => $do[0]
        ), $query);
        if (isset($do[1])) {
            $query = array_merge(array(
                'method' => $do[1]
            ), $query);
        }
        if (isset($do[2])) {
            $query = array_merge(array(
                'op' => $do[2]
            ), $query);
        }
        return $_W['siteroot'] . 'web/' . substr(parent::createWebUrl('plugin', $query, true), 2);
    }
    public function _exec($do, $default = '', $web = true)
    {
        global $_GPC;
        $do = strtolower(substr($do, $web ? 5 : 8));
        $p  = trim($_GPC['p']);
        empty($p) && $p = $default;
        if ($web) {
            $file = IA_ROOT . "/addons/ycs_fxshop/core/web/" . $do . "/" . $p . ".php";
        } else {
            $this->setFooter();
            $file = IA_ROOT . "/addons/ycs_fxshop/core/mobile/" . $do . "/" . $p . ".php";
        }
        if (!is_file($file)) {
            message("未找到 控制器文件 {$do}::{$p} : {$file}");
        }
        include $file;
        exit;
    }
    public function template($filename, $type = TEMPLATE_INCLUDEPATH)
    {
        global $_W;
        $name = strtolower($this->modulename);
        if (defined('IN_SYS')) {
            $source  = IA_ROOT . "/web/themes/{$_W['template']}/{$name}/{$filename}.html";
            $compile = IA_ROOT . "/data/tpl/web/{$_W['template']}/{$name}/{$filename}.tpl.php";
            if (!is_file($source)) {
                $source = IA_ROOT . "/web/themes/default/{$name}/{$filename}.html";
            }
            if (!is_file($source)) {
                $source = IA_ROOT . "/addons/{$name}/template/{$filename}.html";
            }
            if (!is_file($source)) {
                $source = IA_ROOT . "/web/themes/{$_W['template']}/{$filename}.html";
            }
            if (!is_file($source)) {
                $source = IA_ROOT . "/web/themes/default/{$filename}.html";
            }
            if (!is_file($source)) {
                $explode = explode('/', $filename);
                $temp    = array_slice($explode, 1);
                $source  = IA_ROOT . "/addons/{$name}/plugin/" . $explode[0] . "/template/" . implode('/', $temp) . ".html";
            }
        } else {
            $template = "default";
            $file     = IA_ROOT . "/addons/ycs_fxshop/data/template/shop_" . $_W['uniacid'];
            if (is_file($file)) {
                $template = file_get_contents($file);
                if (!is_dir(IA_ROOT . '/addons/ycs_fxshop/template/mobile/' . $template)) {
                    $template = "default";
                }
            }
            $compile = IA_ROOT . "/data/tpl/app/ycs_fxshop/{$template}/mobile/{$filename}.tpl.php";
            $source  = IA_ROOT . "/addons/{$name}/template/mobile/{$template}/{$filename}.html";
            if (!is_file($source)) {
                $source = IA_ROOT . "/addons/{$name}/template/mobile/default/{$filename}.html";
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
            if (!is_file($source)) {
                $source = IA_ROOT . "/app/themes/{$_W['template']}/{$filename}.html";
            }
            if (!is_file($source)) {
                $source = IA_ROOT . "/app/themes/default/{$filename}.html";
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
    public function getUrl()
    {
        if (p('commission')) {
            $set = p('commission')->getSet();
            if (!empty($set['level'])) {
                return $this->createPluginMobileUrl('commission/myshop');
            }
        }
        return $this->createMobileUrl('shop');
    }
}