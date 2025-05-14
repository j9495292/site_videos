<?php

namespace app\agent\home;

use app\common\builder\ZBuilder;
use app\common\model\LinkVideo as LinkVideoModel;

class Agent extends Base{

    public function index(){
        $agent_list = [];
        $list = db('agent')->where('pid',$this->UID)->select();
        //获取一级
        foreach ($list as $key => $item){
            $item['lv'] = 1;
            array_push($agent_list,$item);
            //获取二级代理
            $ag2 = db('agent')->where('pid',$item['id'])->select();
            foreach ($ag2 as $key2 => $item2){
                $item2['lv'] = 2;
                array_push($agent_list,$item2);
                //获取三级代理
                $ag3 = db('agent')->where('pid',$item2['id'])->select();
                foreach ($ag3 as $key3 => $item3){
                    $item3['lv'] = 3;
                    array_push($agent_list,$item3);
                }
            }
        }
        return ZBuilder::make('table')
            ->addColumns([
                ['id','ID'],
                ['lv','代理级别','callback',function($data){
                    $names = [1 => '一级代理',2 => '二级代理',3 => '三级代理'];
                    $color = [1 =>'label-success',2=>'label-danger',3=>'label-default'];
                    return '<span class="label '.$color[$data].'">'.$names[$data].'</span>';
                }],
                ['username','代理账号'],
                ['money','余额'],
                ['status','状态','status',['禁止','正常']],
                ['right_button','操作']
            ])
            ->addRightButton('delete')
            ->addTopButton('add')
            ->setRowList($agent_list)
            ->hideCheckbox()
            ->noPages()
            ->fetch();
    }

    public function add(){
        if($this->request->isPost()){
            $data = $this->request->post();
            $result = $this->validate($data,[
               'username|代理用户名' => 'require|unique:agent,username',
                'password|代理密码' => 'require'
            ]);
            if($result !== true){
                $this->error($result);
            }

            $user = [
                'username' => $data['username'],
                'password' => $data['password'],
                'status' => 1,
                'create_time' => time(),
                'pid' => $this->UID
            ];
            if(db('agent')->insert($user)){
                $this->success('注册成功','index');
            }else{
                $this->error('注册失败');
            }
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text','username','代理用户名','',date('YmHi').rand(0,999)],
                ['text','password','代理密码','',time().rand(0,99)]
            ])
            ->setBtnTitle('submit','新增代理')
            ->fetch();
    }

    public function order(){
        //获取所有代理ID与级别
        $agent_list = [];
        $list = db('agent')->where('pid',$this->UID)->field('id')->select();
        //获取一级
        foreach ($list as $key => $item){
            $item['lv'] = 1;
            array_push($agent_list,$item);
            //获取二级代理
            $ag2 = db('agent')->where('pid',$item['id'])->field('id')->select();
            foreach ($ag2 as $key2 => $item2){
                $item2['lv'] = 2;
                array_push($agent_list,$item2);
                //获取三级代理
                $ag3 = db('agent')->where('pid',$item2['id'])->field('id')->select();
                foreach ($ag3 as $key3 => $item3){
                    $item3['lv'] = 3;
                    array_push($agent_list,$item3);
                }
            }
        }
        //获取所有代理ID
        $agent_id = array_column($agent_list,'id');
        //查询代理ID下所有的订单
        $order_list = db('order')
            ->where('uid','in',$agent_id)
            ->where('is_kouliang',0)
            ->where('status',1)
            ->order('create_time desc')
            ->paginate();

        return ZBuilder::make('table')
            ->addColumns([
                ['trade_no','订单编号'],
                ['money','金额(元)'],
                ['fx','分成奖励','callback',function($data) use ($agent_list){
                    //获取代理级别
                    $ag_key = array_search($data['uid'], array_column($agent_list, 'id'));
                    $agent_in = $agent_list[$ag_key];
                    //获取分成奖励
                    $money = getUserTopMoneyFx($data['money'],$agent_in['lv']);
                    //代理级别显示
                    $names = [1 => '一级代理',2 => '二级代理',3 => '三级代理'];
                    $color = [1 =>'label-success',2=>'label-danger',3=>'label-default'];
                    if($data['status'] == 1){
                        return '<span class="label '.$color[$agent_in['lv']].'">'.$names[$agent_in['lv']].'</span>'.'&nbsp;'.number_format($money,2).'￥';
                    }else{
                        return '无';
                    }
                },'__data__'],
                ['status','状态','status','',['未支付','已支付']],
                ['create_time','创建时间','datetime']
            ])
            ->setRowList($order_list)
            ->hideCheckbox()
            ->fetch();
    }


    public function delete($ids = null){
        if(empty($ids)){
            $this->error('数据不存在');
        }

        if(db('agent')->where('pid',$this->UID)->delete($ids)){
            $book_del = LinkVideoModel::where('uid',$ids)->delete();
            $video_del = db('link_book')->where('uid',$ids)->delete();
            if ($book_del >= 0 && $video_del  >= 0) {
                $this->success('删除成功');
            } else {
                $this->error('代理删除成功，推广数据删除失败，请手动清除！');
            }
        }else{
            $this->error('删除失败');
        }
    }

}