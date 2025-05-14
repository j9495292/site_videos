<?php

namespace app\agent\home;

use app\common\builder\ZBuilder;

class Cash extends Base{

    public function index(){
        $list = db('cash')->where('uid',$this->UID)->order('id desc')->paginate();
        return ZBuilder::make('table')
            ->addColumns([
                ['username','账号'],
                ['money','提现金额'],
                ['shouxu','提现费率','callback',function($d){
                    return $d.'%';
                }],
                ['pay_money','到账金额'],
                ['type','收款方式','callback',function($d){
                    $status = $d ? '支付宝' : '微信';
                    return '<span class="label label-primary">'.$status.'</span>';
                }],
                ['create_time','提交时间','datetime'],
                ['qrcode','提现收款码','picture'],
                ['status','审核状态','status','',['待处理','已打款','审核不通过']],
                ['remark','审核备注'],
                ['update_time','审核时间','datetime']
            ])
            ->addTopButton('cash',[
                'title' => '申请提现',
                'class' => 'btn btn-primary',
                'href' => url('cash')
            ])
            ->setRowList($list)
            ->fetch();
    }

    public function cash(){
        if($this->request->isPost()){
            $data = $this->request->post();
            $result = $this->validate($data,[
                'money|提现金额' => 'require|float',
                'qrcode|收款二维码' => 'require',
                'type|收款类型' => 'require'
            ]);
            if($result !== true){
                $this->error($result);
            }

            if(empty($data['money'])){
                $this->error('提现金额不能为0');
            }

            if(empty($data['qrcode'])){
                $this->error('请上传收款二维码');
            }

            //提现金额为50或者100的倍数
            if(!(($data['money'] % 50) == 0 || ($data['money'] % 100) == 0)){
                $this->error('提现金额必须为50或者100的倍数');
            }

            if(($this->USER['money'] - $data['money']) < 0){
                $this->error('您的可提现余额不足');
            }

            if(empty($this->USER['shouxu'])){
                $shouxufei = config('web.ag_tixain');
            }else{
                $shouxufei = $this->USER['shouxu'];
            }

            $shouxu = calcPercentage($data['money'],$shouxufei);

            $pay_money = $data['money'] - $shouxu;

            $tixian = [
                'uid' => $this->UID,
                'username' => $this->USER['username'],
                'money' => $data['money'],
                'shouxu' => $shouxufei,
                'pay_money' => $pay_money,
                'create_time' => time(),
                'type' => $data['type'],
                'qrcode' => $data['qrcode']
            ];
            db('agent')->where('id',$this->UID)->setDec('money',$data['money']);
            if(db('cash')->insert($tixian)){
                $this->success('申请提现成功','index');
            }else{
                $this->error('申请提现失败');
            }
        }

        $cash = db('cash')->where('uid',$this->UID)->order('id desc')->find();
        $qrcode = isset($cash['qrcode']) ? $cash['qrcode'] : '';
    
        
        
        return ZBuilder::make('form')
            ->addFormItems([
                ['text','money','提现金额','您当前可提现金额'.number_format($this->USER['money'],2).'￥'],
                ['image','qrcode','收款二维码','',$qrcode],
                ['radio','type','收款类型','',['微信','支付宝'],0]
            ])
            ->setPageTips('提现金额请输入50或者100的倍数,如50,100,150,200,250等等')
            ->setBtnTitle('submit','申请提现')
            ->fetch();
    }

}