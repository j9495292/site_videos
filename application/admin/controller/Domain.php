<?php

namespace app\admin\controller;

use app\common\builder\ZBuilder;

class Domain extends Admin{

    protected $tablename = 'domain';

    public function index(){
        $map = $this->getMap();
        $list = db('domain')->where($map)->paginate();
        $type_list = [
            '入口域名',
            '中转域名(已弃用)',
            '落地域名'
        ];
        return ZBuilder::make('table')
            ->setTableName('domain')
            ->addColumns([
                ['domain','域名'],
                ['type','类型','callback',function($type) use ($type_list){
                    return isset($type_list[$type]) ? $type_list[$type] : '';
                }],
                ['qq_status','QQ屏蔽','status','',['屏蔽','正常']],
                ['wx_status','微信屏蔽','status','',['屏蔽','正常']],
                ['dy_status','抖音屏蔽','status','',['屏蔽','正常']],
                ['create_time','添加时间','datetime'],
                ['status','状态','switch'],
                ['right_button','操作']
            ])
            ->addTopSelect('type','全部域名',$type_list)
            ->addTopButtons([
                'add',
                'batch_add' => [
                    'title' => '批量添加',
                    'class' => 'btn btn-warning',
                    'href' => url('batch_add')
                ],
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
                'domain|域名' => 'require',
                'type|域名类型' => 'require',
                'is_all|是否泛解析' => 'require'
            ]);
            if($result !== true){
                $this->error($result);
            }
            $data['create_time'] = time();
            if(db('domain')->insert($data)){
                $this->success('添加成功','index');
            }else{
                $this->error('添加失败');
            }
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['text','domain','域名','不能带有http://或https://'],
                ['select','type','域名类型','',['入口域名','中转域名(已弃用)','落地域名'],0],
                ['radio','is_all','是否泛解析','',['普通解析','泛解析'],0]
            ])
            ->fetch();
    }


    public function batch_add()
    {
        if($this->request->isPost()){
            $data = $this->request->post();
            $result = $this->validate($data,[
                'domain_txt|域名文本' => 'require',
                'type|域名类型' => 'require',
                'is_all|是否泛解析' => 'require'
            ]);
            if($result !== true){
                $this->error($result);
            }
            $domain_array = explode("\r\n",$data['domain_txt']);
            $array=array();
            $length=count($domain_array);
            for($i=0; $i<=$length-1; $i++){
                $array[$i]['domain']=$domain_array[$i];
                $array[$i]['type']=$data['type'];
                $array[$i]['is_all']=$data['is_all'];
                $array[$i]['create_time'] = time();
            }
            if(db('domain')->insertAll($array)){
                $this->success('添加成功','index');
            }else{
                $this->error('添加失败');
            }
            $this->success('添加成功','index');
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['textarea','domain_txt','域名文本','一行一个，不能带有http://或https://'],
                ['select','type','域名类型','',['入口域名','中转域名(已弃用)','落地域名'],2],
                ['radio','is_all','是否泛解析','',['普通解析','泛解析'],0]
            ])
            ->fetch();
    }

    public function edit($id = 0){
        $info = db('domain')->where('id',$id)->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        if($this->request->isPost()){
            $data = $this->request->post();
            $result = $this->validate($data,[
                'domain|域名' => 'require',
                'type|域名类型' => 'require',
                'is_all|是否泛解析' => 'require'
            ]);
            if($result !== true){
                $this->error($result);
            }
            if(db('domain')->where('id',$id)->update($data)){
                $this->success('修改成功','index');
            }else{
                $this->error('数据无改动');
            }

        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text','domain','域名','不能带有http://或https://'],
                ['select','type','域名类型','',['入口域名','中转域名(已弃用)','落地域名']],
                ['radio','is_all','是否泛解析','',['普通解析','泛解析']]
            ])
            ->setFormData($info)
            ->fetch();
    }

}