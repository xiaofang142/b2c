<?php

/*
*Url http://yqhls.cn
*/
if (!defined('IN_IA')) {
    exit('Access Denied');
}
if (!class_exists('SaleModel')) {
    class SaleModel extends PluginModel
    {
        public function perms()
        {
            return array(
                'sale' => array(
                    'text' => $this->getName(),
                    'isplugin' => true,
                    'child' => array(
                        'deduct' => array(
                            'text' => '抵扣设置',
                            'view' => '查看',
                            'save' => '保存-log'
                        ),
                        'enough' => array(
                            'text' => '满额优惠设置',
                            'view' => '查看',
                            'save' => '保存-log'
                        )
                    )
                )
            );
        }
    }
}