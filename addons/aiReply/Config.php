<?php
/**
 * Created by PhpStorm.
 * User: lazen
 * Date: 2017/11/8
 * Time: 12:20
 */

return array(
    'name' => '智能回复',
    'addon' => 'aiReply',
    'desc' => '微信内公号智能回复，结合小说特定数据库',
    'version' => '1.0',
    'author' => 'lazen',
    'logo' => 'logo.jpg',
    'menu_show' => '1',
    'entry_url' => '',
    //  'install_sql' => 'install.sql',
    'upgrade_sql' => '',
    'menu' => [
        [
            'name' => '应用配置',
            'url' => 'aiReply/Index/index',
            'icon' => ''
        ],
    ],
    'config' => array(
        [
            'name' => 'dsn',
            'title' => '连接参数',
            'type' => 'text',
            'value' => 'mysql://root:1234@127.0.0.1:3306/novelDB#utf8',
            'placeholder' => '',
            'tip' => '数据库连接参数',
        ],
        [
            'name' => 'domain',
            'title' => '服务域名',
            'type' => 'text',
            'value' => 'https://',
            'placeholder' => '',
            'tip' => '服务器域名https://w.a.com',
        ],
    ),

);