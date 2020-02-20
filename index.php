<?php
require_once "./mysafe.php";
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// [ 应用入口文件 ]
$url='http://'.$_SERVER['SERVER_NAME'].$_SERVER["REQUEST_URI"];

if($url=='http://'.$_SERVER['SERVER_NAME'].'/watch/2924837'){

    header('location:http://easycast.cloud/index.php/index-ad_id-92.html');
    die;
}

if (!defined('__PUBLIC__')) {
    $_public = rtrim(dirname(rtrim($_SERVER['SCRIPT_NAME'], '/')), '/');
    
    define('__PUBLIC__', (('/' == $_public || '\\' == $_public) ? '' : $_public).'/public');
}
// 定义应用目录
define('APP_PATH', __DIR__ . '/app/');
define('DATA_PATH',  __DIR__.'/runtime/Data/');
//插件目录
define('PLUGIN_PATH', __DIR__ . '/plugins/');
// 加载框架引导文件
require __DIR__ . '/thinkphp/start.php';
