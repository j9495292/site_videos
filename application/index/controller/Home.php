<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\index\controller;

use app\common\controller\Common;
use think\db\Where;

/**
 * 前台公共控制器
 * @package app\index\controller
 */
class Home extends Common
{

    protected $AGENT_ID;

    protected $AGENT_KEY;

    protected $AGENT;

    /**
     * 初始化方法
     * @author 蔡伟明 <314013107@qq.com>
     */
    protected function initialize()
    {
        // 系统开关
        if (!config('web_site_status')) {
            $this->error('站点已经关闭，请稍后访问~');
        }

        $tousu = db('tousu')
            ->where('real_ip',getClientIpv4())
            ->where('status',1)
            ->count();

        if($tousu){
            header('Location:https://weixin110.qq.com/cgi-bin/mmspamsupport-bin/newredirectconfirmcgi?main_type=2&evil_type=1&source=2&url=');
            exit;
        }

        $ip  = getClientIpv4();
        $online_expire = cache(md5($ip));

        if(empty($online_expire)){
            cache(md5($ip),'1',['expire' => 15]);
            if(db('online')->where('ip',$ip)->count() > 0){
                db('online')->where('ip',$ip)->update(['create_time' => time()]);
            }else{
                db('online')->insert(['ip' => $ip,'create_time' => time()]);
            }
        }

        $key = $this->request->param('key');
        $agent = id_decode($key);
    
        if(empty($agent)){
            http_response_code(404);
            exit;
        }
        $agent_info = db('agent')
            ->where('id',$agent['id'])
            ->where('status',1)
            ->find();

        if(empty($agent_info)){
            http_response_code(404);
            exit;
        }
        $this->AGENT_KEY = $key;
        $this->AGENT_ID = $agent_info['id'];
        $this->AGENT = $agent_info;
        $this->assign('conf',config('web.'));
        $this->assign('tousu',iurl('tousu',['st'=> 1]));
        session([
            'prefix'     => 'user',
            'type'       => '',
            'auto_start' => true,
            'expire' => 86400,
            'id' => md5(getClientIpv4())
        ]);

        //获取用户信息
        $uid = session('user_uid');
        //获取购买列表
        $buy_info = db('order')
            ->where('uid',$this->AGENT_ID)
            ->where('real_ip',getClientIpv4())
            ->where('status',1)
            ->where('is_log',0)
            ->field('id,type,model,link_id')
            ->select();
        //逐条处理
        if(!empty($buy_info) && !empty($uid)){
            foreach ($buy_info as $item){
                $type = $item['model'] == 'video' ? 0 : 1;
                switch ($item['type']){
                    case 'buy':
                        //保存购买记录
                        setUserBuyLog($item['link_id'],$uid,$type);
                        break;
                    case 'bao_day':
                        setUserVIP($uid,$type,'day');
                        break;
                    case 'bao_week':
                        setUserVIP($uid,$type,'week');
                        break;
                    case 'bao_month':
                        setUserVIP($uid,$type,'month');
                        break;
                }
                //设置完成
                db('order')->where('id',$item['id'])->setField('is_log',1);
            }
        }

    }

    protected function getUserInfo(){
        $uid = session('user_uid');
        $info = db('user')->where('id',$uid)->find();
        if(empty($info)){
            session('user_uid',null);
            return [];
        }else{
            if($info['status'] != 1){
                session('user_uid',null);
                return [];
            }else{
                return $info;
            }
        }
    }

    protected function isUserBuy($id,$type){
        $userinfo = $this->getUserInfo();
        //免登录获取
        if(empty($userinfo)){
            $num = db('order')
                ->where('uid',$this->AGENT_ID)
                ->where('real_ip',getClientIpv4())
                ->where('status',1)
                ->where('model',$type ? 'book' : 'video')
                ->where('link_id',$id)
                ->where('type','buy')
                ->where('is_log',0)
                ->count();
            return $num > 0;
        }
        //登录获取
        $num = db('user_buy')
            ->where('uid',$userinfo['id'])
            ->where('type',$type)
            ->where('link_id',$id)
            ->count();
        return $num > 0;
    }

    protected function getUserBuyCount($type){
        $userinfo = $this->getUserInfo();
        //免登录获取
        if(empty($userinfo)){
            return db('order')
                ->where('uid',$this->AGENT_ID)
                ->where('real_ip',getClientIpv4())
                ->where('status',1)
                ->where('model',$type ? 'book' : 'video')
                ->where('type','buy')
                ->where('is_log',0)
                ->count();
        }
        //登录获取
        return db('user_buy')
            ->where('uid',$userinfo['id'])
            ->where('type',$type)
            ->count();
    }

    protected function getUserBuyList($type,$page){
        $userinfo = $this->getUserInfo();
        //免登录获取
        if(empty($userinfo)){
            return db('order')
                ->where('uid',$this->AGENT_ID)
                ->where('real_ip',getClientIpv4())
                ->where('status',1)
                ->where('model',$type ? 'book' : 'video')
                ->where('type','buy')
                ->where('is_log',0)
                ->page($page,20)
                ->field('id,link_id')
                ->column('link_id','id');
        }
        //登录获取
        return db('user_buy')
            ->where('uid',$userinfo['id'])
            ->where('type',$type)
            ->page($page,20)
            ->field('id,link_id')
            ->column('link_id','id');
    }

    protected function isBookVip(){
        $userinfo = $this->getUserInfo();
        if(empty($userinfo)){
            return false;
        }
        return getVipTime($userinfo['xs_vip']) > 0;
    }

    protected function isVideoVip(){
        $userinfo = $this->getUserInfo();
        if(empty($userinfo)){
            return false;
        }
        return getVipTime($userinfo['sp_vip']) > 0;
    }

}
