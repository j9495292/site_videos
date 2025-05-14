<?php

namespace app\admin\controller;

use app\common\builder\ZBuilder;

class Order extends Admin{

    protected $tablename = 'order';

    public function index(){
        $map = $this->getMap();
        $list = db('order')
                ->alias('v')
                ->join('agent a','v.uid = a.id')
                ->where($map)
                ->where('v.status',1)
                ->order('v.create_time desc')
                // ->field('v.id,trade_no,out_trade_no,v.money,v.status,username,uid,is_kouliang,v.create_time')
                ->field('v.*,v.money as money')
                ->field('v.*,a.username as username')
                ->paginate();
        return ZBuilder::make('table')
            ->setSearch(['trade_no' => '订单号', 'out_trade_no' => '接口订单号', 'uid' => '代理ID', 'username' => '代理用户名'])
            ->addColumns([
                ['trade_no','订单号'],
                ['out_trade_no','接口订单'],
                ['money','支付金额'],
                ['status','支付状态','status','',['未支付','已支付']],
                ['username','代理名称'],
                ['uid','代理ID'],
                ['is_kouliang','扣量','status','',['不扣量','扣量']],
                ['create_time','创建时间','datetime'],
                ['right_button','操作']
            ])
            ->hideCheckbox()
            ->addRightButtons('delete')
            ->setRowList($list)
            ->fetch();
    }

}