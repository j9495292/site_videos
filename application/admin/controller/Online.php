<?php

namespace app\admin\controller;

use app\common\builder\ZBuilder;

class Online extends Admin{

    public function index(){
        db('online')->whereTime('create_time','<=',time() - (60*5))->delete();
        $list = db('online')->paginate();
        return ZBuilder::make('table')
            ->addColumns([
                ['ip','访问IP'],
                ['create_time','访问时间','datetime']
            ])
            ->setRowList($list)
            ->hideCheckbox()
            ->fetch();
    }

}