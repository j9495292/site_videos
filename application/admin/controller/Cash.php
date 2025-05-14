<?php

namespace app\admin\controller;

use app\common\builder\ZBuilder;

class Cash extends Admin{

    public function index(){
        $map = $this->getMap();
        $list = db('cash')->where($map)->order('create_time desc')->paginate();
        return ZBuilder::make('table')
            ->addColumns([
                ['id','ID'],
                ['username','代理账号'],
                ['money','提现金额'],
                ['shouxu','提现费率','callback',function($d){
                    return $d.'%';
                }],
                ['pay_money','应付金额'],
                ['type','收款方式','callback',function($d){
                    $status = $d ? '支付宝' : '微信';
                    return '<span class="label label-primary">'.$status.'</span>';
                }],
                ['qrcode','提现收款码','picture'],
                ['remark','审核备注'],
                ['create_time','提交时间','datetime'],
                ['update_time','审核时间','datetime'],
                ['status','状态','status','',['待处理','已完成','不通过']],
                ['right_button','操作']
            ])
            ->setRowList($list)
            ->addRightButtons([
                'yes' => [
                    'title' => '审核通过',
                    'class' => 'btn btn-xs btn-primary ajax-get',
                    'icon' => 'fa fa-fw fa-check-circle-o',
                    'href' => url('change_status',['status' => 1,'id' => '__id__'])
                ],
                'no' => [
                    'title' => '审核不通过',
                    'icon' => 'fa fa-fw fa-times-circle-o',
                    'href' => url('change_status',['status' => 0,'id' => '__id__'])
                ],
                'delete'
            ])
            ->addTopSelect('status','审核状态',['待处理','已完成'])
            ->replaceRightButton(['status' => ['in', '1,2']], '',['yes','no'])
            ->setSearch('username','请输入代理用户账号','',true)
            ->fetch();
    }

    public function change_status(){
        $id = input('id');
        $status = input('status');
        $cash = db('cash')->where('id',$id)->find();
        if(empty($cash)){
            $this->error('数据不存在');
        }
        if(empty($status)){
            if($this->request->isPost()){
                $remark = input('remark');
                if(empty($remark)){
                    $this->error('不通过理由不能为空');
                }
                $up = [
                    'remark' => $remark,
                    'status' => 2,
                    'update_time' => time()
                ];
                if(db('cash')->where('id',$id)->update($up)){
                    db('agent')->where('id',$cash['uid'])->setInc('money',$cash['money']);
                    $this->success('审核成功','index');
                }else{
                    $this->error('审核失败');
                }
            }
            return ZBuilder::make('form')
                ->addTextarea('remark','不通过理由')
                ->fetch();
        }else{
            if(db('cash')->where('id',$id)->update(['update_time' => time(),'status' => 1])){
                $this->success('审核成功');
            }else{
                $this->error('审核失败');
            }
        }
    }

    public function delete($ids = null){
        if(empty($ids)){
            $this->error('数据不存在');
        }
        if(db('cash')->delete($ids)){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }
}