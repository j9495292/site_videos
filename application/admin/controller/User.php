<?php

namespace app\admin\controller;

use app\common\builder\ZBuilder;

class User extends Admin{

    protected $tablename = 'user';

    public function index(){
        $map = $this->getMap();
        $list = db('user')
                ->alias('v')
                ->where($map)
                ->join('agent a','v.uid = a.id')
                ->field('v.*,a.username as agent_username')
                
                ->order('v.create_time desc')

                ->paginate();

        return ZBuilder::make('table')
            ->addColumns([
                ['agent_username','代理名称'],
                ['uid','代理ID'],
                ['username','用户名'],
                ['password','密码'],
                ['xs_vip','小说VIP','callback',function($vip){
                    $long_time = getVipTime($vip);
                    if($long_time > 0){
                        return '<span class="label label-success">'.$long_time.'天</span>';
                    }else{
                        return '<span class="label label-default">过期</span>';
                    }
                }],
                ['sp_vip','视频VIP','callback',function($vip){
                    $long_time = getVipTime($vip);
                    if($long_time > 0){
                        return '<span class="label label-success">'.$long_time.'天</span> &nbsp;&nbsp;'.date('m-d H:i',$vip);
                    }else{
                        return '<span class="label label-default">过期</span>';
                    }
                }],
                ['real_ip','登录IP地址'],
                ['create_time','创建时间','datetime'],
                ['update_time','最近登录时间','datetime'],
                ['status','状态','switch'],
                ['right_button','操作']
            ])
            ->setColumnWidth('uid,status', 50)
            ->addTopButtons('add,delete')
            ->addRightButtons('edit,delete')
            ->setRowList($list)
            ->fetch();
    }

    public function add(){
        if($this->request->isPost()){
            $data = $this->request->post();
            $result = $this->validate($data,[
                'uid|所属代理ID' => 'require',
                'username|用户名' => 'require|length:1,20|unique:user,username',
                'password|密码' => 'require|length:1,18',
                'xs_vip|小说VIP' => 'require',
                'sp_vip|视频VIP' => 'require'
            ]);
            if($result !== true){
                $this->error($result);
            }
            $data['xs_vip'] = getVipDay($data['xs_vip']);
            $data['sp_vip'] = getVipDay($data['sp_vip']);
            $data['create_time'] = time();
            if(db('user')->insert($data)){
                $this->success('添加成功','index');
            }else{
                $this->error('添加失败');
            }
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text','uid','所属代理ID'],
                ['text','username','用户名'],
                ['text','password','密码'],
                ['number','xs_vip','小说VIP时间','单位：天',0],
                ['number','sp_vip','视频VIP时间','单位：天',0]
            ])
            ->fetch();
    }

    public function edit($id = 0){
        $info = db('user')->where('id',$id)->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        if($this->request->isPost()){
            $data = $this->request->post();
            $result = $this->validate($data,[
                'password|密码' => 'require|length:1,18',
                'xs_vip|小说VIP' => 'require',
                'sp_vip|视频VIP' => 'require'
            ]);
            if($result !== true){
                $this->error($result);
            }
            $data['xs_vip'] = getVipDay($data['xs_vip']);
            $data['sp_vip'] = getVipDay($data['sp_vip']);
            if(db('user')->where('id',$id)->update($data)){
                $this->success('修改成功','index');
            }else{
                $this->error('修改失败');
            }
        }
        $info['xs_vip'] = getVipTime($info['xs_vip']);
        $info['sp_vip'] = getVipTime($info['sp_vip']);
        return ZBuilder::make('form')
            ->addFormItems([
                ['static','username','用户名'],
                ['text','password','密码'],
                ['number','xs_vip','小说VIP时间','单位：天'],
                ['number','sp_vip','视频VIP时间','单位：天']
            ])
            ->setFormData($info)
            ->fetch();
    }

}