<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\index\controller;

use app\index\pays\Epay;
use app\index\pays\EpayException;
use app\index\pays\YiPay;
use app\index\pays\YiPayException;
use think\facade\Env;
use app\common\model\LinkVideo as LinkVideoModel;

/**
 * 前台首页控制器
 * @package app\index\controller
 */
class Index extends Home
{
    public function guide(){
        $domain = 'http://'.$_SERVER['HTTP_HOST'];
        $url = $domain.iurl('index');
        $this->redirect($url);
    }
    // 推广入口
    public function entry_y(){
        $t = input('t');
        //入口域名
        if(empty($t)){
            $url = getPublicUrl($this->AGENT_ID,1);
            if(empty($url)){
                http_response_code(404);
                exit;
            }else{
                if(isQQ() && isWx() == false){
                    $this->assign('url',url_short($url));
                    return $this->fetch('jump');
                }else{
                    $this->assign('url',$url);
                    return $this->fetch('load');
                }
            }
        }
        $type = substr($t,0,1);
        if(!in_array($type,['Z','L'])){
            http_response_code(404);
            exit;
        }
        if($type == 'Z'){
            $url = getPublicUrl($this->AGENT_ID,2);
            if(empty($url)){
                http_response_code(404);
                exit;
            }else{
                $this->assign('url',$url);
                return $this->fetch('load');
            }
        }

        $domain = 'http://'.$_SERVER['HTTP_HOST'];
        $url = $domain.iurl('index');
        $this->redirect($url);
    }

    // 推广入口
    public function entry()
    {   
        $t = input('t');
        $id = input('id');
        if (empty($t)) {
            http_response_code(404);
            exit;
        } else {
            if ($t !== 'all' && empty($id)) {
                http_response_code(404);
                exit;
            } else {
                // $url = jumpDomain($t,$id);
                $url = lastDomain($t,$id);
                return $this->redirect($url);
            }
        }
    }


    public function jump()
    {
        $t = input('t');
        $id = input('id');

        if (empty($t)) {
            http_response_code(404);
            exit;
        } else {
            if ($t !== 'all' && empty($id)) {
                http_response_code(404);
                exit;
            } else {
                $url = lastDomain($t,$id);
                $this->assign('url',$url);
                return $this->fetch('load');
            }
        }
        
        // echo $url;
        
    }

    public function share(){
        $t = input('t');
        $model = input('type');
        $link_id = input('id');

        //参数不存在
        if(empty($model) || empty($link_id)){
            http_response_code(404);
            exit;
        }

        //入口域名
        if(empty($t)){
            $url = getPublicShare($this->AGENT_ID,1,$model,$link_id);
            if(empty($url)){
                http_response_code(404);
                exit;
            }else{
                $this->assign('url',$url);
                if(isQQ() && isWx() == false){
                    return $this->fetch('jump');
                }else{
                    return $this->fetch('load');
                }
            }
        }

        $type = substr($t,0,1);
        if(!in_array($type,['Z','L'])){
            http_response_code(404);
            exit;
        }

        if($type == 'Z'){
            $url = getPublicShare($this->AGENT_ID,2,$model,$link_id);
            if(empty($url)){
                http_response_code(404);
                exit;
            }else{
                $this->assign('url',$url);
                return $this->fetch('load');
            }
        }
        //跳转
        $domain = 'http://'.$_SERVER['HTTP_HOST'];
        if($model == 'video'){
            $url = $domain.iurl('video_detail',['id' => $link_id]);
        }else{
            $url = $domain.iurl('book_detail',['id' => $link_id]);
        }
        $this->redirect($url);
    }

    public function index(){
        if($this->request->isAjax()){
            $page = input('page',1);
            $list = LinkVideoModel::where('uid',$this->AGENT_ID)
                ->where('status',1)
                ->where('free',0)
                ->field('id,title,cover,play_num')
                ->orderRand()
                ->page($page,20)
                ->select();
            foreach ($list as $key => $item){
                if(strpos($item['cover'],'http') === false){
                    $list[$key]['cover'] = get_file_path($item['cover']);
                }
                $list[$key]['play_num'] = rand(5000,100000);
                $list[$key]['hp'] = rand(99,100);
            }
            $this->success('获取成功','',$list);
        }
        $site_conf = config('web.');
        //获取轮播图
        $slider = [];
        
        for ($i = 1;$i < count($site_conf);$i++){
            if(isset($site_conf['slide_img_'.$i]) && isset($site_conf['slide_url_'.$i])){
                $slider[] = [
                    'img' => $site_conf['slide_img_'.$i],
                    'url' => $site_conf['slide_url_'.$i]
                ];
            }else{
                break;
            }
        }

        // 查询|记录访客IP 和 浏览量
        $visitor = db('agent_visitor')
                    ->where('uid',$this->AGENT_ID)
                    ->where('ip',getClientIpv4())
                    ->whereTime('create_time', 'today')
                    ->find();

        if (empty($visitor)) {
            $visitor_log = [
                'uid' => $this->AGENT_ID,
                'ip' => getClientIpv4(),
                'create_time' => time()
            ];
            db('agent_visitor')->insertGetId($visitor_log);
        }

        $browse = db('agent_browse')
                    ->where('uid',$this->AGENT_ID)
                    ->whereTime('create_time', 'today')
                    ->find();
        if (empty($browse)) {
            $browse_log = [
                'uid' => $this->AGENT_ID,
                'browse_num' => 1,
                'create_time' => time()
            ];
            db('agent_browse')->insertGetId($browse_log);
        } else {
            db('agent_browse')->where('id', $browse['id'])->update(['browse_num' => $browse['browse_num']+1]);
        }

        $this->assign('agent_id',id_encode($this->AGENT_ID));
        $this->assign('slider',$slider);
        //获取分类
        $cat_list = db('cat')->where('type',0)->field('id,name,icon')->select();
        $this->assign('cat',$cat_list);
        return $this->fetch();
    }

    public function video(){
        $cat = input('cat','');
        $keyword = input('keyword','');
        $page = input('page',1);
        if($this->request->isAjax()){
            $map = [];
            if(!empty($cat)){
                $map['cat_id'] = $cat;
            }
            if(!empty($keyword)){
                $list = LinkVideoModel::where('title','like',['%'.$keyword,$keyword.'%','%'.$keyword.'%'],'OR')
                    ->where('uid',$this->AGENT_ID)
                    ->where('status',1)
                    ->where('free',0)
                    ->field('id,title,cover,play_num')
                    ->order('read_num,id desc')
                    ->page($page,20)
                    ->select();
            }else{
                $list = LinkVideoModel::where($map)
                    ->where('uid',$this->AGENT_ID)
                    ->where('status',1)
                    ->where('free',0)
                    ->field('id,title,cover,play_num')
                    // ->order('read_num,id desc')
                    ->orderRand()
                    ->page($page,20)
                    ->select();
            }

            foreach ($list as $key => $item){
                if(strpos($item['cover'],'http') === false){
                    $list[$key]['cover'] = get_file_path($item['cover']);
                }
                $list[$key]['play_num'] = rand(5000,100000);
                $list[$key]['hp'] = rand(99,100);
            }
            $this->success('获取成功','',$list);
        }

        //获取分类
        $cat_list = db('cat')->where('type',0)->field('id,name')->select();
        $this->assign('cat',$cat_list);
        $this->assign('cat_id',$cat);
        return $this->fetch();
    }

    public function book(){
        $cat = input('cat',0);
        $keyword = input('keyword','');
        $page = input('page',1);
        if($this->request->isAjax()){
            $map = [];
            if(!empty($cat)){
                $map['v.cat_id'] = $cat;
            }
            if(!empty($keyword)){

                $list = db('link_book')
                    ->alias('v')
                    ->join('resource r','v.res_id = r.id')
                    ->where('r.title','like',['%'.$keyword,$keyword.'%','%'.$keyword.'%'],'OR')
                    ->where('v.uid',$this->AGENT_ID)
                    ->where('v.status',1)
                    ->where('v.free',0)
                    ->field('v.id,r.title,v.create_time')
                    ->order('v.id desc')
                    ->page($page,20)
                    ->select();

            }else{
                $list = db('link_book')->where($map)
                    ->alias('v')
                    ->join('resource r','v.res_id = r.id')
                    ->where('v.uid',$this->AGENT_ID)
                    ->where('v.status',1)
                    ->where('v.free',0)
                    ->field('v.id,r.title,v.create_time')
                    // ->order('v.id desc')
                    ->orderRand()
                    ->page($page,20)
                    ->select();
            }

            foreach ($list as $key => $item){
                $list[$key]['create_time'] = date('m-d',$item['create_time']);
            }
            $this->success('获取成功','',$list);
        }
        //获取分类
        $cat_list = db('cat')->where('type',1)->field('id,name')->select();
        $this->assign('cat',$cat_list);
        $this->assign('cat_id',$cat);
        return $this->fetch();
    }

    public function buyinfo(){
        $id = input('id');
        $type = input('type');
        if($type == 'video'){
            $info = LinkVideoModel::where('id',$id)
                ->where('uid',$this->AGENT_ID)
                ->where('status',1)
                ->where('free',0)
                ->field('id,title,cover,shikan,money')
                ->find();

            if(empty($info)){
                $this->error('视频不存在');
            }

            if(strpos($info['cover'],'http') === false){
                $info['cover'] = get_file_path($info['cover']);
            }
            $info['url'] = format_video_url($info['url']);

            //是否购买、是否VIP
            if($this->isUserBuy($id,0) || $this->isVideoVip()){
                $this->success('获取成功','',['is_buy' => 1]);
            }

            $site_conf = config('web.');

            //获取配置
            $agent_config_json = db('agent')->where('id',$this->AGENT_ID)->value('config');
            $agent_config = json_decode($agent_config_json,true);

            $pays = [];
            //是否存在试看
            if(intval($info['shikan']) > 0){
                $pays[] = ['name' => '立即试看'.$info['shikan'].'秒','type' => 'shikan','money' => '0'];
            }
            //单片
            $pays[] = ['name' => $site_conf['dg_btn_dan'],'type' => 'buy','money' => $info['money']];
            //包天金额
            if(isset($agent_config['v_bao_day']) && !empty($agent_config['v_bao_day'])){
                $pays[] = ['name' => $site_conf['dg_btn_day'],'type' => 'bao_day','money' => $agent_config['v_bao_day']];
            }
            //包周金额
            if(isset($agent_config['v_bao_week']) && !empty($agent_config['v_bao_week'])){
                $pays[] = ['name' => $site_conf['dg_btn_week'],'type' => 'bao_week','money' => $agent_config['v_bao_week']];
            }
            //包周金额
            if(isset($agent_config['v_bao_month']) && !empty($agent_config['v_bao_month'])){
                $pays[] = ['name' => $site_conf['dg_btn_month'],'type' => 'bao_month','money' => $agent_config['v_bao_month']];
            }
            //返回信息
            $data = [
                'info' => $info,
                'pays' => $pays,
                'conf' => [
                    'title' => $site_conf['dg_video_title'],
                    'bg' => empty($site_conf['dg_bg']) ? '' : get_file_path($site_conf['dg_bg']),
                    'tips' => $site_conf['dg_tips']
                ]
            ];
            $this->success('获取成功','',$data);
        }else{
            $info = db('link_book')
                ->alias('v')
                ->join('resource r','v.res_id = r.id')
                ->where('v.id',$id)
                ->where('v.uid',$this->AGENT_ID)
                ->where('v.status',1)
                ->where('v.free',0)
                ->field('v.id,r.title,v.money')
                ->find();
            if(empty($info)){
                $this->error('小说不存在');
            }
            //是否购买、是否开通VIP
            if($this->isUserBuy($id,1) || $this->isBookVip() || $this->isVideoVip()){
                $this->success('获取成功','',['is_buy' => 1]);
            }

            $site_conf = config('web.');

            //获取配置
            $agent_config_json = db('agent')->where('id',$this->AGENT_ID)->value('config');
            $agent_config = json_decode($agent_config_json,true);

            $pays = [];
            //单片
            $pays[] = ['name' => $site_conf['dg_btn_dan'],'type' => 'buy','money' => $info['money']];
            //包天金额
            if(isset($agent_config['b_bao_day']) && !empty($agent_config['b_bao_day'])){
                $pays[] = ['name' => $site_conf['dg_btn_day'],'type' => 'bao_day','money' => $agent_config['b_bao_day']];
            }
            //包周金额
            if(isset($agent_config['b_bao_week']) && !empty($agent_config['b_bao_week'])){
                $pays[] = ['name' => $site_conf['dg_btn_week'],'type' => 'bao_week','money' => $agent_config['b_bao_week']];
            }
            //包周金额
            if(isset($agent_config['b_bao_month']) && !empty($agent_config['b_bao_month'])){
                $pays[] = ['name' => $site_conf['dg_btn_month'],'type' => 'bao_month','money' => $agent_config['b_bao_month']];
            }
            //信息
            $data = [
                'info' => $info,
                'pays' => $pays,
                'conf' => [
                    'title' => $site_conf['dg_book_title'],
                    'bg' => empty($site_conf['dg_bg']) ? '' : get_file_path($site_conf['dg_bg']),
                    'tips' => $site_conf['dg_tips']
                ]
            ];
            $this->success('获取成功','',$data);
        }
    }

    public function video_detail(){
        $id = input('id');
        $type = input('type','ds');
        $ts_login = input('ts_login');
        $info = LinkVideoModel::where('uid',$this->AGENT_ID)
            ->where('status',1)
            ->where('id',$id)
            ->find();
        if(empty($info)){
            exit('参数错误，数据不存在');
        }

        $this->assign('ts_login',$ts_login);
        //地址转换
        if(strpos($info['cover'],'http') === false){
            $info['cover'] = get_file_path($info['cover']);
        }
        $info['url'] = format_video_url($info['url']);
        //免费
        if($info['free'] == 1){
            $this->assign('video',$info);
            LinkVideoModel::where('id',$id)->setInc('play_num');
            $about = LinkVideoModel::where('uid',$this->AGENT_ID)
                ->where('status',1)
                ->orderRand()
                ->field('id,title,cover,play_num')
                ->limit(8)
                ->select();
            foreach ($about as $key => $value){
                if(strpos($value['cover'],'http') === false){
                    $about[$key]['cover'] = get_file_path($value['cover']);
                }
                $about[$key]['play_num'] = rand(5000,100000);
                $about[$key]['hp'] = rand(99,100);
            }
            $this->assign('about',$about);
            return $this->fetch('video_free');
        }
        //判断VIP或者已购买
        if($this->isUserBuy($id,0) || $this->isVideoVip()){
            LinkVideoModel::where('id',$id)->setInc('play_num');
            $about = LinkVideoModel::where('uid',$this->AGENT_ID)
                ->where('status',1)
                ->orderRand()
                ->field('id,title,cover,play_num')
                ->limit(8)
                ->select();
            foreach ($about as $key => $value){
                if(strpos($value['cover'],'http') === false){
                    $about[$key]['cover'] = get_file_path($value['cover']);
                }
                $about[$key]['play_num'] = rand(5000,100000);
                $about[$key]['hp'] = rand(99,100);
            }
            $this->assign('video',$info);
            $this->assign('about',$about);
            return $this->fetch('video_free');
        }
        //试看
        if($info['shikan'] > 0 && $type == 'sk'){
            $this->assign('video',$info);
            LinkVideoModel::where('id',$id)->setInc('play_num');
            $about = LinkVideoModel::where('uid',$this->AGENT_ID)
                ->where('status',1)
                ->orderRand()
                ->field('id,title,cover,play_num')
                ->limit(8)
                ->select();
            foreach ($about as $key => $value){
                if(strpos($value['cover'],'http') === false){
                    $about[$key]['cover'] = get_file_path($value['cover']);
                }
                $about[$key]['play_num'] = rand(5000,100000);
                $about[$key]['hp'] = rand(99,100);
            }
            $this->assign('about',$about);
            return $this->fetch('video_shikan');
        }
        //输出打赏
        $site_conf = config('web.');

        //获取配置
        $agent_config_json = db('agent')->where('id',$this->AGENT_ID)->value('config');
        $agent_config = json_decode($agent_config_json,true);

        $pays = [];

        if(intval($info['shikan']) > 0){
            $pays[] = ['name' => '立即试看'.$info['shikan'].'秒','type' => 'shikan','money' => '0'];
        }
        //单片
        $pays[] = ['name' => $site_conf['dg_btn_dan'],'type' => 'buy','money' => $info['money']];
        //包天金额
        if(isset($agent_config['v_bao_day']) && !empty($agent_config['v_bao_day'])){
            $pays[] = ['name' => $site_conf['dg_btn_day'],'type' => 'bao_day','money' => $agent_config['v_bao_day']];
        }
        //包周金额
        if(isset($agent_config['v_bao_week']) && !empty($agent_config['v_bao_week'])){
            $pays[] = ['name' => $site_conf['dg_btn_week'],'type' => 'bao_week','money' => $agent_config['v_bao_week']];
        }
        //包周金额
        if(isset($agent_config['v_bao_month']) && !empty($agent_config['v_bao_month'])){
            $pays[] = ['name' => $site_conf['dg_btn_month'],'type' => 'bao_month','money' => $agent_config['v_bao_month']];
        }

        // 查询|记录访客IP 和 浏览量
        $visitor = db('agent_visitor')
                    ->where('uid',$this->AGENT_ID)
                    ->where('ip',getClientIpv4())
                    ->whereTime('create_time', 'today')
                    ->find();

        if (empty($visitor)) {
            $visitor_log = [
                'uid' => $this->AGENT_ID,
                'ip' => getClientIpv4(),
                'create_time' => time()
            ];
            db('agent_visitor')->insertGetId($visitor_log);
        }

        $browse = db('agent_browse')
                    ->where('uid',$this->AGENT_ID)
                    ->whereTime('create_time', 'today')
                    ->find();
        if (empty($browse)) {
            $browse_log = [
                'uid' => $this->AGENT_ID,
                'browse_num' => 1,
                'create_time' => time()
            ];
            db('agent_browse')->insertGetId($browse_log);
        } else {
            db('agent_browse')->where('id', $browse['id'])->update(['browse_num' => $browse['browse_num']+1]);
        }
        

        foreach ($pays as $key => $item){
            $pays[$key]['name'] = str_replace('{m}',$item['money'],$item['name']);
        }
        $this->assign('ds_bg',empty($site_conf['dg_bg']) ? '' : get_file_path($site_conf['dg_bg']));
        $this->assign('ds_title',$site_conf['dg_video_title']);
        $this->assign('pays',$pays);
        $this->assign('video',$info);
        return $this->fetch('video_ds');
    }

    public function book_detail(){
        $id = input('id');
        $ts_login = input('ts_login');
        $info = db('link_book')
            ->alias('v')
            ->join('resource r','v.res_id = r.id')
            ->where('v.uid',$this->AGENT_ID)
            ->where('v.status',1)
            ->where('v.id',$id)
            ->field('v.*,r.title as title')
            ->field('v.*,r.content as content')
            ->find();

        $this->assign('ts_login',$ts_login);
        //是否免费
        if($info['free'] == 1){
            $this->assign('info',$info);
            return $this->fetch('book_detail');
        }
        //是否购买或VIP
        if($this->isUserBuy($id,1) || $this->isBookVip() || $this->isVideoVip()){
            $this->assign('info',$info);
            return $this->fetch('book_detail');
        }

        $site_conf = config('web.');

        $agent_config_json = db('agent')->where('id',$this->AGENT_ID)->value('config');
        $agent_config = json_decode($agent_config_json,true);

        $pays = [];
        //单片
        $pays[] = ['name' => $site_conf['dg_btn_dan'],'type' => 'buy','money' => $info['money']];
        //包天金额
        if(isset($agent_config['b_bao_day']) && !empty($agent_config['b_bao_day'])){
            $pays[] = ['name' => $site_conf['dg_btn_day'],'type' => 'bao_day','money' => $agent_config['b_bao_day']];
        }
        //包周金额
        if(isset($agent_config['b_bao_week']) && !empty($agent_config['b_bao_week'])){
            $pays[] = ['name' => $site_conf['dg_btn_week'],'type' => 'bao_week','money' => $agent_config['b_bao_week']];
        }
        //包周金额
        if(isset($agent_config['b_bao_month']) && !empty($agent_config['b_bao_month'])){
            $pays[] = ['name' => $site_conf['dg_btn_month'],'type' => 'bao_month','money' => $agent_config['b_bao_month']];
        }

        foreach ($pays as $key => $item){
            $pays[$key]['name'] = str_replace('{m}',$item['money'],$item['name']);
        }
        //购买页面
        $this->assign('ds_bg',empty($site_conf['dg_bg']) ? '' : get_file_path($site_conf['dg_bg']));
        $this->assign('ds_title',$site_conf['dg_book_title']);
        $this->assign('pays',$pays);
        $this->assign('info',$info);

        return $this->fetch('book_ds');
    }

    public function like(){
        $id = input('id');
        $info = LinkVideoModel::where('uid',$this->AGENT_ID)
            ->where('status',1)
            ->where('id',$id)
            ->find();
        if(empty($info)){
            $this->error('信息获取失败');
        }
        if(session('like_'.$id)){
            $this->error('您已经点过赞了');
        }
        //点赞成功
        session('like_'.$id,'1');
        LinkVideoModel::where('id',$id)->setInc('like_num',1);
        $this->success('点赞成功');
    }

    public function user(){
        $type = input('type','yg-video');
        $page = input('page',1);

        $info = $this->getUserInfo();

        if(empty($info)){
            //统计资源
            $this->assign('is_login',0);
        }else{
            //vip判断
            $is_xs_vip = 0;
            if(getVipTime($info['xs_vip']) > 0){
                $is_xs_vip = 1;
            }
            $is_sp_vip = 0;
            if(getVipTime($info['sp_vip']) > 0){
                $is_sp_vip = 1;
            }
            //统计已购资源
            $this->assign('type',$type);
            $this->assign('is_login',1);
            $this->assign('xs_vip',$is_xs_vip);
            $this->assign('sp_vip',$is_sp_vip);
            $this->assign('info',$info);
        }

        $yg_book = $this->getUserBuyCount(1);
        $yg_video = $this->getUserBuyCount(0);
        $this->assign('yg_book',$yg_book);
        $this->assign('yg_video',$yg_video);

        //ajax请求
        if($this->request->isAjax()){
            switch ($type){
                case 'yg-video':
                    $buylist = $this->getUserBuyList(0,$page);
                    $list = LinkVideoModel::where('uid',$this->AGENT_ID)
                        ->where('status',1)
                        ->where('id','in',$buylist)
                        ->field('id,title,cover,play_num')
                        ->order('id desc')
                        ->select();
                    foreach ($list as $key => $value){
                        if(strpos($value['cover'],'http') === false){
                            $list[$key]['cover'] = get_file_path($value['cover']);
                        }
                        $list[$key]['play_num'] = rand(5000,100000);
                        $list[$key]['hp'] = rand(99,100);
                    }
                    $this->success('获取成功','',$list);
                    break;
                case 'yg-book':
                    $buylist = $this->getUserBuyList(1,$page);
                    $list = db('link_book')
                        ->alias('v')
                        ->where('v.uid',$this->AGENT_ID)
                        ->where('v.status',1)
                        ->where('v.id','in',$buylist)
                        ->join('resource r','v.res_id = r.id')
                        ->field('v.id,r.title,v.create_time')
                        ->order('v.id desc')
                        ->select();
                    foreach ($list as $key => $value){
                        $list[$key]['create_time'] = date('m-d',$value['create_time']);
                    }
                    $this->success('获取成功','',$list);
                    break;
                case 'mf-video':
                    $list = LinkVideoModel::where('uid',$this->AGENT_ID)
                        ->where('status',1)
                        ->where('free',1)
                        ->page($page,20)
                        ->field('id,title,cover,play_num')
                        ->order('id desc')
                        ->select();
                    foreach ($list as $key => $value){
                        if(strpos($value['cover'],'http') === false){
                            $list[$key]['cover'] = get_file_path($value['cover']);
                        }
                        $list[$key]['play_num'] = rand(5000,100000);
                        $list[$key]['hp'] = rand(99,100);
                    }
                    $this->success('获取成功','',$list);
                    break;
                case 'mf-book':
                    $list = db('link_book')
                        ->alias('v')
                        ->where('v.uid',$this->AGENT_ID)
                        ->where('v.status',1)
                        ->where('v.free',1)
                        ->join('resource r','v.res_id = r.id')
                        ->page($page,20)
                        ->field('v.id,r.title,v.create_time')
                        ->order('v.id desc')
                        ->select();
                    foreach ($list as $key => $value){
                        $list[$key]['create_time'] = date('m-d',$value['create_time']);
                    }
                    $this->success('获取成功','',$list);
                    break;
                default:
                    $this->error('数据不存在');
                    break;
            }
        }
        return $this->fetch();
    }

    public function register(){
        if($this->request->isPost()){
            $data = $this->request->post();
            $result = $this->validate($data,[
               'username|用户名' => 'require|length:1,20|unique:user,username',
               'password|密码' => 'require|length:1,20'
            ]);
            if($result !== true){
                $this->error($result);
            }
            $account = db('user')->where('username',$data['username'])->count();
            if($account > 0){
                $this->error('用户名已被注册');
            }
            $user = [
                'uid' => $this->AGENT_ID,
                'username' => $data['username'],
                'password' => $data['password'],
                'real_ip' => getClientIpv4(),
                'create_time' => time(),
                'update_time' => time()
            ];
            $uid = db('user')->insertGetId($user);
            if(empty($uid)){
                $this->error('用户注册失败');
            }
            session('user_uid',$uid);
            $this->success('注册成功');
        }
        return $this->fetch();
    }

    public function login(){
        if($this->request->isPost()){
            $data = $this->request->post();
            $result = $this->validate($data,[
                'username|用户名' => 'require',
                'password|密码' => 'require'
            ]);
            if($result !== true){
                $this->error($result);
            }
            $info = db('user')->where('username',$data['username'])->find();
            if(empty($info)){
                $this->error('用户信息不存在');
            }
            if($data['password'] != $info['password']){
                $this->error('密码错误');
            }
            if($info['status'] != 1){
                $this->error('账号已被封禁');
            }

            // if (empty($info['uid'])) {
            //     db('user')->where('id',$info['id'])->setField(['update_time' => time(), 'uid' => $this->AGENT_ID, 'real_ip' => getClientIpv4()]);
            // } else {
            //     db('user')->where('id',$info['id'])->setField(['update_time' => time(), 'real_ip' => getClientIpv4()]);
            // }

            db('user')->where('id',$info['id'])->setField(['update_time' => time(), 'uid' => $this->AGENT_ID, 'real_ip' => getClientIpv4()]);

            session('user_uid',$info['id']);
            $this->success('登录成功');
        }
        return $this->fetch();
    }

    public function logout(){
        session('user_uid',null);
        $this->success('退出登录成功');
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

    public function tousu(){
        $step = input('st',1);
        switch ($step){
            case 3:
                $count = db('tousu')->where('real_ip',getClientIpv4())->count();
                if($count > 0){
                    db('tousu')->where('real_ip',getClientIpv4())->setInc('count');
                }else{
                    db('tousu')->insert([
                       'real_ip' => getClientIpv4(),
                       'count' => 1,
                        'create_time' => time(),
                        'status' => 1
                    ]);
                }
                return $this->fetch('tousu/yitijiao');
            case 2:
                return $this->fetch('tousu/tijiao');
            default:
                return $this->fetch('tousu/index');
        }
    }

    public function pay(){
        $data = $this->request->get();
        $result = $this->validate($data,[
            'id' => 'require',
            'type' => 'require',
            'model' => 'require'
        ]);
        if($result !== true){
            exit('请求错误');
        }

        $config = config('web.');
        $trade_no = date("YmdHis").rand(100000,999999);
        $agent_config_json = db('agent')->where('id',$this->AGENT_ID)->value('config');
        $agent_config = json_decode($agent_config_json,true);

        $notify_url = url('/callback/notify','',false,true);
        $return_url = url('/callback/return','',false,true);

        if(!in_array($data['model'],['video','book'])){
            exit('参数错误');
        }

        if(!in_array($data['type'],['buy','bao_day','bao_week','bao_month'])){
            exit('类型错误');
        }

        $money = 0;

        if($data['type'] == 'buy'){
            //读取商品信息
            $money = db('link_'.$data['model'])
                ->where('uid',$this->AGENT_ID)
                ->where('id',$data['id'])
                ->where('status',1)
                ->value('money');
            if(empty($money)){
                exit('记录信息不存在');
            }
        }else{
            if($data['model'] == 'video'){
                if(isset($agent_config['v_'.$data['type']]) && !empty($agent_config['v_'.$data['type']])){
                    $money = $agent_config['v_'.$data['type']];
                }
            }else{
                if(isset($agent_config['b_'.$data['type']]) && !empty($agent_config['b_'.$data['type']])){
                    $money = $agent_config['b_'.$data['type']];
                }
            }
        }

        if(empty($money)){
            exit('金额错误');
        }

        $money = (float)$money;
        $ticheng_money = getUserTrueMoney($money,$this->AGENT['ptfei']);
        //整理信息
        $trade = [
            'uid' => $this->AGENT_ID,
            'trade_no' => $trade_no,
            'money' => $money,
            'ticheng' => $ticheng_money,
            'create_time' => time(),
            'real_ip' => getClientIpv4(),
            'type' => $data['type'],
            'model' => $data['model'],
            'link_id' => $data['id']
        ];
        //计算是否扣量
        $is_kouliang = 0;
        $kouliang = $this->AGENT['kouliang'];
        if($kouliang > 0){
            $count = db('order')->where('uid',$this->AGENT_ID)->where('status',1)->count();
            if(($count + 1) % $kouliang == 0){
                $is_kouliang = 1;
            }
        }
        $trade['is_kouliang'] = $is_kouliang;

        //加入订单
        if(!db('order')->insert($trade)){
            exit('订单创建失败');
        }

        $notify_url = "https://".$_SERVER['HTTP_HOST']."/index/callback/notify";
        $return_url = "https://".$_SERVER['HTTP_HOST']."/index/callback/return";
        
        // 使用小白支付类处理支付请求
        try {
            // 实例化小白支付类
            $xiaoBBPay = new \app\common\library\payment\XiaoBBPay();
            
            // 创建支付订单，获取支付链接
            $payUrl = $xiaoBBPay->createOrder(
                $trade_no,                             // 商户订单号
                $money,                                // 支付金额
                $notify_url,                           // 异步通知URL
                $return_url."?order_no=$trade_no"      // 同步跳转URL
            );
            
            // 跳转到支付页面
            header('Location: ' . $payUrl);
            exit;
        } catch (\Exception $e) {
            exit($e->getMessage());
        }
    }
    
    /**
     * 查询订单状态
     * @param string $trade_no 商户订单号
     * @return array 订单状态信息
     */
    public function queryPayOrder($trade_no)
    {
        try {
            // 实例化小白支付类
            $xiaoBBPay = new \app\common\library\payment\XiaoBBPay();
            
            // 查询订单
            return $xiaoBBPay->queryOrder($trade_no);
        } catch (\Exception $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }
}
