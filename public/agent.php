<?php

namespace think;

// [ PHP版本检查 ]
header("Content-type: text/html; charset=utf-8");
if (version_compare(PHP_VERSION, '5.6', '<')) {
    die('PHP版本过低，最少需要PHP5.6，请升级PHP版本！');
}

// [ 应用入口文件 ]

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');

define('ENTRANCE', 'agent');

define('ADMIN_FILE', 'admin.php');

// 加载基础文件
require __DIR__ . '/../thinkphp/base.php';

// 支持事先使用静态方法设置Request对象和Config对象

// 检查是否安装
// 执行应用并响应
Container::get('app')->bind(ENTRANCE)->run()->send();