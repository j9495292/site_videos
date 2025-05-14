<?php

namespace app\admin\controller;

use think\facade\Config;

class Task{

    // public function index2(){
    //     header("Content-type:text/html;charset=utf-8");
    //     $type = ['qq','wx','dy'];
    //     $check = $type[array_rand($type,1)];
    //     $map = [];
    //     if($check == 'qq') $map['qq_status'] = 1;
    //     if($check == 'wx') $map['wx_status'] = 1;
    //     if($check == 'dy') $map['dy_status'] = 1;
    //     $list = db('domain')
    //         ->where('status',1)
    //         ->where($map)
    //         ->field('id,domain')
    //         ->orderRand()
    //         ->limit(20)
    //         ->select();
    //     $count = count($list);
    //     $error = 0;
    //     //检查域名
    //     foreach ($list as $item){
    //         if(url_check($item['domain'],$check) == 201){
    //             db('domain')->where('id',$item['id'])->setField($check.'_status',0);
    //             $error++;
    //         }
    //     }
    //     echo '本次检测域名'.$count.'个，异常域名'.$error.'个';
    //     exit();
    // }

    public function index(){
        header("Content-type:text/html;charset=utf-8");
        $list = db('domain')
            ->where('status',1)
            ->field('id,domain')
            ->orderRand()
            ->limit(20)
            ->select();
        $count = count($list);
        $error = 0;
        $text = '';
        //检查域名
        foreach ($list as $item){
            // 微信检测
            $wx_status = ql_check($item['domain'],'wx');
            if ($wx_status['code']=='1002') {
                db('domain')->where('id',$item['id'])->setField('wx_status',0);
            }
            sleep(3);
            // QQ检测
            $qq_status = ql_check($item['domain'],'qq');
            if ($qq_status['code']=='1002') {
                db('domain')->where('id',$item['id'])->setField('qq_status',0);
            }
            sleep(3);
            $text = $text."检测域名：".$item['domain']."，微信：".$wx_status['msg']."，QQ：".$qq_status['msg']."<br>";
        }
        echo $text;
        exit();
    }

    // 检测域名
    public function index3(){
        header("Content-type:text/html;charset=utf-8");
        $type = ['wx'];
        $check = $type[array_rand($type,1)];
        $map = [];
        if($check == 'wx') $map['wx_status'] = 1;
        $domain = db('domain')
            ->where('status',1)
            ->where('qq_status',1)
            ->where('dy_status',1)
            ->where($map)
            ->field('id,domain')
            ->find();

        $result = url_check($domain['domain'],$check);
        // 200:表示正常|201:表示异常|500:表示失败
        if($result == 201){
            db('domain')->where('id',$item['id'])->setField($check.'_status',0);
            echo '本次检测域名：'.$domain['domain'].'，检测结果：异常！';
        } elseif ($result == 200) {
            echo '本次检测域名：'.$domain['domain'].'，检测结果：正常！';
        } elseif ($result == -1) {
            echo '本次检测域名：'.$domain['domain'].'，检测失败。原因：点数不足、用户不存在、非法请求或API缺少参数！';
        }
        exit();
    }
    
    
    //自动禁用
    public function auto_delete(){
        $domains = db('domain')
        ->where('dy_status',0)
        ->where('wx_status',0)
        ->where('qq_status',0)
        ->setField('status',0);
        exit('本次处理域名数量'.$domains.'个');
    }

    // 游戏通道支付网关
    public function check_yx_api(){
        $config = config('web.');
        // 检测网关是否被屏蔽
        $x = 1;
        $text = '';
        while (true) {
            $api_num = 'pay_yx_api_'.$x;
            if(substr_count($config[$api_num],"已屏蔽") == 0){
                // 可用
                $domain = $config['pay_yx_api_'.$x];
                $result = ql_check($domain,'wx');
                if($result['code'] == '1002'){
                    config_set('web',['pay_yx_api_'.$x => $config[$api_num].'----已屏蔽']);
                    $text = $text."检测域名：".$domain."，微信：".$result['msg']."<br>";
                } else {
                    $text = $text."检测域名：".$domain."，微信：".$result['msg']."<br>";
                    break;
                }
            }
            if($x == 3) {
                // 轮训次数上限，默认为第一个
                break;
            }
            sleep(2);
            $x++;
        };
        exit($text);
    }

    


}