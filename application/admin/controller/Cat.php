<?php

namespace app\admin\controller;

use app\common\builder\ZBuilder;

class Cat extends Admin{

    public function video(){
        $list = db('cat')->where('type',0)->order('sort asc')->paginate();
        return ZBuilder::make('table')
            ->addColumns([
                ['name','分类名称'],
                ['icon','分类图标','picture'],
                ['sort','排序'],
                ['create_time','创建时间','datetime'],
                ['right_button','操作']
            ])
            ->addTopButtons([
                'add' => [
                    'href' => url('add',['type' => 0])
                ],
                'delete'
            ])
            ->addRightButtons(['edit','delete'])
            ->setRowList($list)
            ->fetch();
    }

    public function book(){
        $list = db('cat')->where('type',1)->paginate();
        return ZBuilder::make('table')
            ->addColumns([
                ['name','分类名称'],
                ['sort','排序'],
                ['create_time','创建时间','datetime'],
                ['right_button','操作']
            ])
            ->addTopButtons([
                'add' => [
                    'href' => url('add',['type' => 1])
                ],
                'delete'
            ])
            ->addRightButtons(['edit','delete'])
            ->setRowList($list)
            ->fetch();
    }

    public function add(){
        $type = input('type');
        if($this->request->isPost()){
            $data = $this->request->post();
            $result = $this->validate($data,[
                'name|分类名称' => 'require',
                'sort|排序' => 'require'
            ]);
            if($result !== true){
                $this->error($result);
            }
            $data['type'] = $type;
            $data['create_time'] = time();
            if(db('cat')->insert($data)){
                if($type == 0){
                    $url = 'video';
                }else{
                    $url = 'book';
                }
                $this->success('添加成功',$url);
            }else{
                $this->error('添加失败');
            }
        }

        if($type == 0){
            $items = [
                ['text','name','分类名称'],
                ['image','icon','分类图标'],
                ['number','sort','排序','数字越小排序越靠前',10]
            ];
        }else{
            $items = [
                ['text','name','分类名称'],
                ['number','sort','排序','数字越小排序越靠前',10]
            ];
        }
        return ZBuilder::make('form')
            ->addFormItems($items)
            ->fetch();
    }

    public function edit($id = 0){
        $info = db('cat')->where('id',$id)->find();
        if(empty($info)) $this->error('数据不存在');
        if($this->request->isPost()){
            $data = $this->request->post();
            $result = $this->validate($data,[
                'name|分类名称' => 'require',
                'sort|排序' => 'require'
            ]);
            if($result !== true){
                $this->error($result);
            }
            if(db('cat')->where('id',$id)->update($data)){
                $this->success('修改成功');
            }else{
                $this->error('修改失败');
            }
        }
        if($info['type'] == 0){
            $items = [
                ['text','name','分类名称'],
                ['image','icon','分类图标'],
                ['number','sort','排序','数字越小排序越靠前',10]
            ];
        }else{
            $items = [
                ['text','name','分类名称'],
                ['number','sort','排序','数字越小排序越靠前',10]
            ];
        }
        return ZBuilder::make('form')
            ->addFormItems($items)
            ->setFormData($info)
            ->fetch();
    }

    public function delete($ids = null){
        if(!$ids) $this->error('请选择需要删除的内容');
        if(db('cat')->delete($ids)){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

}