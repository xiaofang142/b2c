<?php


global $_W, $_GPC;
$mid      = intval($_GPC['mid']);
$openid   = m('user')->getOpenid();

// $openid    ="oM0hFs-QZO0jUVXbd6hXSyoh3J-k";
$member   = m('member')->getInfo($openid);
$shop_set = set_medias(m('common')->getSysset('shop'), 'logo');
$can      = false;
if ($member['isagent'] == 1 && $member['status'] == 1) {
    $can = true;
}
if (!$can) {
    header("location: " . $this->createPluginMobileUrl('commission/register'));
    exit;
}
$returnurl = urlencode($this->createPluginMobileUrl('commission/shares', array(
    'goodsid' => $_GPC['goodsid']
)));
$infourl   = "";
$set       = $this->set;
if (empty($set['become_reg'])) {
    if (empty($member['realname']) || empty($member['mobile'])) {
        $infourl = $this->createMobileUrl('member/info', array(
            'returnurl' => $returnurl
        ));
    }
}
if (empty($infourl)) {
    $myshop      = $this->model->getShop($member['id']);
    $share_goods = false;
    $share       = array();
    $goodsid     = intval($_GPC['goodsid']);
    if (!empty($goodsid)) {
        $goods = pdo_fetch('select * from ' . tablename('ycs_fxshop_goods') . ' where uniacid=:uniacid and id=:id limit 1', array(
            ':uniacid' => $_W['uniacid'],
            ':id' => $goodsid
        ));
        $goods = set_medias($goods, 'thumb');
        if (!empty($goods)) {
            $commission      = number_format($this->model->getCommission($goods), 2);
            $share_goods     = true;
            $_W['shopshare'] = array(
                'title' => !empty($goods['share_title']) ? $goods['share_title'] : $goods['title'],
                'imgUrl' => !empty($goods['share_icon']) ? tomedia($goods['share_icon']) : tomedia($goods['thumb']),
                'desc' => !empty($goods['description']) ? $goods['description'] : (empty($set['closemyshop']) ? $myshop['name'] : $shop_set['name']),
                'link' => $this->createMobileUrl('shop/detail', array(
                    'id' => $goods['id'],
                    'mid' => $member['id']
                ), true)
            );
        }
    }
    if (!$share_goods) {
        if (!empty($_GPC['mid'])) {
            if (empty($set['closemyshop'])) {
                $shop            = $this->model->getShop($_GPC['mid']);
                $_W['shopshare'] = array(
                    'imgUrl' => $shop['logo'],
                    'title' => $shop['name'],
                    'desc' => $shop['desc'],
                    'link' => $this->createPluginMobileUrl('commission/myshop', array(
                        'mid' => $shop['mid']
                    ), true)
                );
            } else {
                $_W['shopshare'] = array(
                    'imgUrl' => $shop_set['logo'],
                    'title' => $shop_set['name'],
                    'desc' => $shop_set['desc'],
                    'link' => $this->createMobileUrl('shop', array(
                        'mid' => $_GPC['mid']
                    ), true)
                );
            }
        } else {
            if (empty($set['closemyshop'])) {
                $_W['shopshare'] = array(
                    'imgUrl' => $myshop['logo'],
                    'title' => $myshop['name'],
                    'desc' => $myshop['desc'],
                    'link' => $this->createPluginMobileUrl('commission/myshop', array(
                        'mid' => $member['id']
                    ), true)
                );
            } else {
                $_W['shopshare'] = array(
                    'imgUrl' => $shop_set['logo'],
                    'title' => $shop_set['name'],
                    'desc' => $shop_set['desc'],
                    'link' => $this->createMobileUrl('shop', array(
                        'mid' => $member['mid']
                    ), true)
                );
            }
        }
    }
}
if (empty($infourl) && $_W['isajax']) {
    $p = p('poster');
    if ($share_goods) {
        if ($p) {
            $img = $p->createCommissionPoster($openid, $goods['id']);
        }
        if (empty($img)) {
            $img = $this->model->createGoodsImage($goods, $shop_set);
        }
    } else {
        if ($p) {

            $img = $p->createCommissionPoster($openid);
        }
        if (empty($img)) {

            $img = $this->model->createShopImage($shop_set);
        }
    }
    die($img);
}
include $this->template('shares');