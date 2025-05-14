<?php

namespace app\admin\controller;

use app\common\builder\ZBuilder;
use app\admin\model\Agent as AgentModel;
use app\common\model\LinkVideo as LinkVideoModel;

class Agent extends Admin{

    protected $tablename = 'agent';

    public function index(){
        // $list = db('agent')
        //     ->field('id,username,password,pid,money,status,create_time,update_time,kouliang')
        //     ->order($this->getOrder('id DESC'))
        //     ->paginate();
        $map = $this->getMap();
        $list = AgentModel::where($map)
                    ->field('id,username,password,pid,money,status,create_time,update_time,kouliang')
                    ->order($this->getOrder('id DESC'))
                    ->paginate();

        return ZBuilder::make('table')
            ->setSearch(['id' => '代理ID', 'username' => '代理用户名'])
            ->addOrder('id,create_time,update_time') // 添加排序
            ->addColumns([
                ['id','ID'],
                ['username','用户名'],
                ['password','密码'],
                ['pid','上级代理','callback',function($data){
                    if(empty($data)){
                        return '总台';
                    }else{
                        return db('agent')->where('id',$data)->value('username');
                    }
                }],
                ['money','余额'],
                // ['Dmoney','今日收入'],
                ['today_money','今日收入','callback',function($data){
                    //今日收入
                    $today_money_a = db('order')
                        ->where('uid',$data['id'])
                        ->whereTime('create_time', 'today')
                        ->where('is_kouliang', 0)
                        ->where('status',1)
                        ->sum('money');
                    $today_money_ticheng = db('order')
                        ->where('uid',$data['id'])
                        ->whereTime('create_time', 'today')
                        ->where('is_kouliang', 0)
                        ->where('status',1)
                        ->sum('ticheng');

                    $today_money = (float)$today_money_a - (float)$today_money_ticheng;

                    return number_format($today_money,2);
                },'__data__'],
                ['kouliang','扣量'],
                // ['money_today','昨日收入','callback',function(){
                //     return 0;
                // }],
                ['yes_money','昨日收入','callback',function($data){
                    $yes_money_a = db('order')
                        ->where('uid',$data['id'])
                        ->whereTime('create_time', 'yesterday')
                        ->where('is_kouliang', 0)
                        ->where('status',1)
                        ->sum('money');
                    $yes_money_ticheng = db('order')
                        ->where('uid',$data['id'])
                        ->whereTime('create_time', 'yesterday')
                        ->where('is_kouliang', 0)
                        ->where('status',1)
                        ->sum('ticheng');
                    $yes_money = (float)$yes_money_a - (float)$yes_money_ticheng;
                    
                    return number_format($yes_money,2);
                },'__data__'],

                ['status','状态','switch'],
                ['create_time','注册时间','datetime'],
                ['update_time','登陆时间','datetime'],
                ['right_button','操作']
            ])
            // ->raw('Dmoney') // 使用原值
            ->setColumnWidth('id,right_button,kouliang', 50)
            ->addTopButtons(['add','delete'])
            ->addTopButton('custom',[
                'title' => '批量设置扣量',
                'icon'  => 'fa fa-check-circle-o',
                'class' => 'btn btn-warning pop',
                'href' => url('set_kouliang')
            ],['area' => ['780px','300px']])
            ->addRightButtons('edit,delete')
            ->setRowList($list)
            ->fetch();
    }

    public function add(){
        if($this->request->isPost()){
            $data = $this->request->post();
            $result = $this->validate($data,[
                'username|用户名' => 'require|unique:agent,username',
                'password|密码' => 'require',
                'kouliang|扣量' => 'require',
                'ticheng|提成' => 'require',
                'shouxu|手续费' => 'require'
            ]);
            if($result !== true){
                $this->error($result);
            }
            $data['status'] = 1;
            $data['create_time'] = time();
            if(db('agent')->insert($data)){
                $this->success('注册成功','index');
            }else{
                $this->error('注册失败');
            }
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text','username','用户名','',date('YmdHis',time())],
                ['text','password','密码','',date('His',time()).rand(0,9999)],
                ['number','kouliang','扣量(百分比)','',0],
                ['number','ticheng','下级提成(百分比)','',0],
                ['number','shouxu','手续费(百分比)','',0]
            ])
            ->fetch();
    }

    public function edit($id = 0){
        $info = db('agent')
            ->where('id',$id)
            ->field('username,password,money,kouliang,shouxu,ptfei')
            ->find();
        if($this->request->isPost()){
            $data = $this->request->post();
            if(db('agent')->where('id',$id)->update($data)){
                $this->success('修改成功','index');
            }else{
                $this->error('数据无变动');
            }
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['static','username','用户名'],
                ['text','password','密码'],
                ['text','money','余额','请填写整数或者小数'],
                ['number','ptfei','平台手续费（百分比）','请填写整数'],
                ['number','kouliang','扣量(百分比)'],
                ['number','shouxu','提现手续费(百分比)','单独设置代理的提现费率']
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function delete($ids = null){
        if(empty($ids)){
            $this->error('数据不存在');
        }
        if(db('agent')->delete($ids)){
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


    public function set_kouliang()
    {
        if($this->request->isPost()){
            $kouliang = $this->request->post('kouliang');
            if(empty($kouliang)){
                $this->error('扣量(百分比)不能为空');
            }
            $kouliang = (int)$kouliang;
            if(db('agent')->where('status',1)->setField('kouliang',$kouliang)){
                $this->success('修改成功',null, ['_parent_reload' => 1]);
            }else{
                $this->error('修改失败');
            }
        }
        return ZBuilder::make('form')
            ->addText('kouliang','扣量(百分比)')
            ->fetch();
    }

}