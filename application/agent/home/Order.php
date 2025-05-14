<?php

namespace app\agent\home;

use app\common\builder\ZBuilder;

class Order extends Base {

    public function index(){
        $list = db('order')
            ->where('uid',$this->UID)
            ->where('is_kouliang',0)
            ->where('status',1)
            ->order(['create_time' => 'desc'])
            ->paginate();

        return ZBuilder::make('table')
            ->addColumns([
                ['trade_no','订单编号'],
                ['money','金额(元)','callback',function($money,$data){
                    return $money - $data['ticheng'];
                },'__data__'],
                ['status','状态','status','',['未支付','已支付']],
                ['create_time','创建时间','datetime']
            ])
            ->setSearch('trade_no','请输入订单号','',true)
            ->setRowList($list)
            ->hideCheckbox()
            ->fetch();
    }

}