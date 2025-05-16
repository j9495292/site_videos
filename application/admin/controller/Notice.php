<?php

namespace app\admin\controller;

use app\common\builder\ZBuilder;

class Notice extends Admin{

    protected $tablename = 'agent_notice';

    public function index(){
        $list = db('agent_notice')
            ->field('id,title,content,create_time')
            ->paginate();
        return ZBuilder::make('table')
            ->setTableName($this->tablename)
            ->addColumns([
                ['id','编号'],
                ['title','标题'],
                ['content','内容'],
                ['create_time','添加时间','datetime'],
                ['right_button','操作']
            ])
            ->setColumnWidth([
                'id'  => 50,
                'title'  => 100,
                'content' => 200,
                'create_time' => 100,
                'right_button' => 50
            ])
            ->addTopButtons([
                'add',
                'delete',
            ])
            ->addRightButtons('edit,delete')
            ->setRowList($list)
            ->fetch();
    }

    public function add(){
        if($this->request->isPost()){
            $data = $this->request->post();
            $result = $this->validate($data,[
                'title|标题' => 'require',
                'content|内容' => 'require',
            ]);
            if($result !== true){
                $this->error($result);
            }
            $data['create_time'] = time();
            if(db('agent_notice')->insert($data)){
                $this->success('添加成功','index');
            }else{
                $this->error('添加失败');
            }
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['text','title','标题'],
                ['textarea','content','内容'],
            ])
            ->fetch();
    }

    public function edit($id = 0){
        $info = db('agent_notice')->where('id',$id)->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        if($this->request->isPost()){
            $data = $this->request->post();
            $result = $this->validate($data,[
                'title|标题' => 'require',
                'content|内容' => 'require',
            ]);
            if($result !== true){
                $this->error($result);
            }
            if(db('agent_notice')->where('id',$id)->update($data)){
                $this->success('修改成功','index');
            }else{
                $this->error('数据无改动');
            }

        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text','title','标题'],
                ['textarea','content','内容'],
            ])
            ->setFormData($info)
            ->fetch();
    }

}