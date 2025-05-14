<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

if(defined('INTER_MODEL')){
    Route::any('s/:key$', 'index/index')->pattern(['key' => '\w+']);
    Route::any('s/:key/$', 'index/index')->pattern(['key' => '\w+']);
    Route::any('s/:key/:action', 'index/:action')->pattern(['key' => '\w+']);
    Route::any('callback/notify','callback/notify_url');
    Route::any('callback/return','callback/return_url');
    Route::any('callback/return_ds','callback/return_ds');
    Route::any('callback/notify_jl','callback/julong_notify');
    Route::any('callback/return_jl','callback/julong_return');
    
    Route::any('callback/notify_frb','callback/frb_notify');
    Route::any('callback/return_frb','callback/frb_return');
}