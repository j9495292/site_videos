<?php

namespace app\agent\home;

use app\common\builder\ZBuilder;
use think\facade\Env;
use app\common\model\LinkVideo as LinkVideoModel;

class Index extends Base {

    public function index(){
        $video_count = LinkVideoModel::where('uid',$this->UID)
            ->count();
        $book_count = db('link_book')
            ->where('uid',$this->UID)
            ->count();

        //今日收入
        $today_money_a = db('order')
            ->where('uid',$this->UID)
            ->whereTime('create_time', 'today')
            ->where('is_kouliang', 0)
            ->where('status',1)
            ->sum('money');
        $today_money_ticheng = db('order')
            ->where('uid',$this->UID)
            ->whereTime('create_time', 'today')
            ->where('is_kouliang', 0)
            ->where('status',1)
            ->sum('ticheng');

        $today_money = (float)$today_money_a - (float)$today_money_ticheng;
        //昨日收入
        $yes_money_a = db('order')
            ->where('uid',$this->UID)
            ->whereTime('create_time', 'yesterday')
            ->where('is_kouliang', 0)
            ->where('status',1)
            ->sum('money');
        $yes_money_ticheng = db('order')
            ->where('uid',$this->UID)
            ->whereTime('create_time', 'yesterday')
            ->where('is_kouliang', 0)
            ->where('status',1)
            ->sum('ticheng');
        $yes_money = (float)$yes_money_a - (float)$yes_money_ticheng;
        
        // 今日订单数
        $today_order_count = db('order')
            ->where('uid',$this->UID)
            ->whereTime('create_time', 'today')
            ->where('is_kouliang', 0)
            ->where('status',1)
            ->count();
            
        // 昨日订单数
        $yes_order_count = db('order')
            ->where('uid',$this->UID)
            ->whereTime('create_time', 'yesterday')
            ->where('is_kouliang', 0)
            ->where('status',1)
            ->count();
            
        //今日访客
        $today_visitor =  db('agent_visitor')
            ->where('uid',$this->UID)
            ->whereTime('create_time', 'today')
            ->count();
            
        //今日浏览量
        $today_browse =  db('agent_browse')
            ->where('uid',$this->UID)
            ->whereTime('create_time', 'today')
            ->find();

        //订单统计
        $count =  db('order')
            ->where('uid',$this->UID)
            ->where('is_kouliang', 0)
            ->where('status',1)
            ->count();


        //订单统计
        $notice =  db('agent_notice')
            ->order('create_time', 'desc')
            ->limit(5)
            ->select();

        //用户数量
        $user_count =  db('user')
            ->where('uid',$this->UID)
            ->count();
        
        $this->assign('user_count',$user_count);
        $this->assign('today_visitor',$today_visitor);
        $this->assign('today_browse',$today_browse['browse_num'] ?? 0);
        $this->assign('yes_order_count',$yes_order_count);
        $this->assign('today_order_count',$today_order_count);
        $this->assign('order_count',$count);
        $this->assign('yes_money',$yes_money);
        $this->assign('today_money',$today_money);
        $this->assign('video_count',$video_count);
        $this->assign('book_count',$book_count);
        $this->assign('user',$this->USER);
        $this->assign('notice',$notice);
        $this->assign('notice_one',$notice[0]);
        return $this->fetch();


        
    }

    public function qrcode(){
        $url = input('url');
        include Env::get('root_path').'/extend/util/Qrcode.php';
        if(empty($url)){
            http_response_code(404);
        }
        $qrcode = new \QRcode();
        $level = 'H';// 纠错级别：L、M、Q、H
        $size = 10;//元素尺寸
        $margin = 2;//边距
        $outfile = 'erweima.png';
        $back_color = 0xFFFFFF;
        $fore_color = 0x000000;
        header('Content-type:image/png');
        $qrcode->png($url, $outfile, $level, $size, $margin, true, $back_color, $fore_color);
        exit;
    }

}