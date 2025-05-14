<?php

namespace app\admin\controller;

use app\common\builder\ZBuilder;

class Tousu extends Admin{

    protected $tablename = 'tousu';

    public function index(){
        $list = db('tousu')->paginate();
        return ZBuilder::make('table')
            ->setTableName('tousu')
            ->addColumns([
                ['real_ip','IP地址'],
                ['count','投诉次数'],
                ['create_time','投诉时间','datetime'],
                ['status','封禁状态','switch']
            ])
            ->setRowList($list)
            ->hideCheckbox()
            ->fetch();
    }

}