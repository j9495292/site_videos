<?php

namespace app\admin\controller;

use app\common\builder\ZBuilder;
use app\common\model\Resource as ResourceModel;
use app\common\model\LinkVideo as LinkVideoModel;

class Resource extends Admin{

    public function video(){
        // 获取筛选
        $map = $this->getMap();
        // $list = db('resource')
        //     ->alias('r')
        //     ->where($map)
        //     ->where('r.type',0)
        //     ->join('cat c','r.cat_id = c.id')
        //     ->field('r.*,c.name as cat_name')
        //     ->order('create_time desc')
        //     ->paginate();
        $list = ResourceModel::where($map)
            ->where('type',0)
            ->order('create_time desc')
            ->paginate();
        return ZBuilder::make('table')
            ->setSearch(['title' => '标题']) // 设置搜索参数
            ->addColumns([
                ['title','视频标题'],
                // ['cat_name','分类'],
                
                ['cat_name','分类','callback',function($data){
                    //今日收入
                    $cat = db('cat')
                        ->where('id',$data['cat_id'])
                        ->find();
                    return $cat['name'] ?? '';
                },'__data__'],
                
                
                ['cover','视频图片','callback',function($data){
                    if(stripos($data,'http') !== false){
                        return '<div class="js-gallery"><img class="image" data-original="'.$data.'" src="'.$data.'"></div>';
                    }else{
                        return '<div class="js-gallery"><img class="image" data-original="'.get_file_path($data).'" src="'.get_file_path($data).'"></div>';
                    }
                }],
                ['url','视频地址','popover',20],
                ['create_time','创建时间','datetime'],
                ['status','状态','status'],
                ['right_button','操作']
            ])
            ->addTopButtons([
                'add' => [
                    'title' => '添加视频',
                    'href' => url('add',['type' => 0])
                ],
                'batch' => [
                    'title' => '批量添加',
                    'class' => 'btn btn-warning',
                    'icon' => 'fa fa-fw fa-list-ol',
                    'href' => url('batch_add')
                ],
                'delete' => [
                    'title' => '批量删除'
                ]
            ])
            ->addRightButton('custom',[
                'title' => '播放',
                'icon' => 'fa fa-fw fa-play',
                'href' => url('play',['id' => '__id__']),
            ],true)
            ->addRightButtons([
                'edit',
                'delete'
            ])
            ->setRowList($list)
            ->fetch();
    }

    public function book(){
        // 获取筛选
        $map = $this->getMap();
        $list = ResourceModel::alias('r')
            ->where($map)
            ->where('r.type',1)
            ->join('cat c','r.cat_id = c.id')
            ->field('r.*,c.name as cat_name')
            ->order('create_time desc')
            ->paginate();
        return ZBuilder::make('table')
            ->setSearch(['title' => '标题'])
            ->addColumns([
                ['title','小说标题'],
                ['cat_name','分类'],
                ['create_time','创建时间','datetime'],
                ['status','状态','status'],
                ['right_button','操作']
            ])
            ->addTopButtons([
                'add' => [
                    'href' => url('add',['type' => 1])
                ],
                'delete'
            ])
            ->addRightButton('custom',[
                'title' => '预览',
                'icon' => 'fa fa-fw fa-eye',
                'href' => url('read',['id' => '__id__']),
            ],true)
            ->addRightButtons([
                'edit',
                'delete'
            ])
            ->setRowList($list)
            ->fetch();
    }

    public function add(){
        $type = input('type');
        if($this->request->isPost()){
            $data = $this->request->post();
            $result = $this->validate($data,['title|标题' => 'require']);
            if($result !== true){
                $this->error($result);
            }

            $save = [];
            $save['type'] = $type;
            if($type == 0){
                $save['title'] = $data['title'];
                if(empty($data['cat_id'])){
                    $this->error('请选择视频分类');
                }
                $save['cat_id'] = $data['cat_id'];
                if($data['pic_type'] == 0){
                    $save['cover'] = $data['cover_url'];
                }else{
                    $save['cover'] = $data['cover_upload'];
                }
                if($data['video_type'] == 0){
                    $save['url'] = $data['video_url'];
                }else{
                    $save['url'] = $data['video_upload'];
                }
                if(empty($save['cover']) || empty($save['url'])){
                    $this->error('图片地址或视频地址不能为空');
                }
            }else{
                $save['title'] = $data['title'];
                if(empty($data['cat_id'])){
                    $this->error('请选择小说分类');
                }
                $save['cat_id'] = $data['cat_id'];
                if(empty($data['content'])){
                    $this->error('请输输入小说内容');
                }
                $save['content'] = htmlspecialchars($data['content']);
            }

            $save['create_time'] = time();
            $save['status'] = 1;

            if(ResourceModel::insert($save)){
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            }
        }

        $cat = db('cat')->where('type',$type)->field('id,name')->select();
        $cat_list = array_column($cat,'name','id');

        if($type == 0){
            $trigger = [
                ['pic_type',1,'cover_upload'],
                ['pic_type',0,'cover_url'],
                ['video_type',1,'video_upload'],
                ['video_type',0,'video_url']
            ];
            $items = [
                ['text','title','视频标题'],
                ['select','cat_id','视频分类','',$cat_list],
                ['radio','pic_type','图片上传方式','',['图片地址','本地上传'],0],
                ['image','cover_upload','图片上传'],
                ['text','cover_url','图片地址'],
                ['radio','video_type','视频上传方式','',['视频地址','本地上传'],0],
                ['file','video_upload','视频上传'],
                ['text','video_url','视频地址']
            ];
        }else{
            $trigger = [];
            $items = [
                ['text','title','小说标题'],
                ['select','cat_id','小说分类','',$cat_list],
                ['wangeditor','content','小说内容']
            ];
        }
        return ZBuilder::make('form')
            ->addFormItems($items)
            ->setTrigger($trigger)
            ->fetch();
    }

    public function batch_add(){
        if($this->request->isPost()){
            $data = $this->request->post();
            $result = $this->validate($data,[
                'video_list|视频信息' => 'require',
                'cat_id|视频分类' => 'require'
            ]);
            if($result !== true){
                $this->error($result);
            }
            if(empty($data['video_list'])){
                $this->error('视频信息不能为空');
            }
            $video_list = explode("\r\n",trim($data['video_list']));
            if(empty($video_list)){
                $this->error('视频信息列表为空');
            }
            $video_info = [];
            foreach ($video_list as $item){
                list($title,$cover,$url) = explode('|',$item);
                if(!empty($title) && !empty($url) && !empty($cover)){
                    $video_info[] = [
                        'title' => $title,
                        'url' => $url,
                        'cover' => $cover,
                        'cat_id' => $data['cat_id'],
                        'create_time' => time(),
                        'status' => 1
                    ];
                }
            }
            if(empty($video_info)){
                $this->error('视频信息解析失败');
            }
            if(ResourceModel::insertAll($video_info)){
                $this->success('批量添加成功','video');
            }else{
                $this->error('批量添加失败');
            }
        }
        $cat = db('cat')->where('type',0)->field('id,name')->select();
        $cat_list = array_column($cat,'name','id');
        return ZBuilder::make('form')
            ->addFormItems([
                ['textarea','video_list','视频信息','视频信息格式：视频标题|图片地址|视频地址，每行一条'],
                ['select','cat_id','视频分类','',$cat_list]
            ])
            ->fetch();
    }

    public function play(){
        $id = input('id');
        $info = ResourceModel::where('type',0)
            ->where('id',$id)
            ->field('id,title,cover,url')
            ->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        if(stripos($info['cover'],'http') === false){
            $info['cover'] = get_file_path($info['cover']);
        }
        $info['url'] = format_video_url($info['url']);
        $this->assign('info',$info);
        return $this->fetch();
    }

    public function read(){
        $id = input('id');
        $info = ResourceModel::alias('r')
            ->where('type',1)
            ->where('id',$id)
            ->field('id,title,content')
            ->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        $this->assign('info',$info);
        return $this->fetch();
    }

    public function edit($id = 0){
        $info = ResourceModel::where('id',$id)->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        if($this->request->isPost()){
            $data = $this->request->post();
            $result = $this->validate($data,['title|标题' => 'require']);
            if($result !== true){
                $this->error($result);
            }
            $save = [];
            $save['type'] = $info['type'];
            $save['title'] = $data['title'];
            if($info['type'] == 0){
                if(empty($data['cat_id'])){
                    $this->error('请选择视频分类');
                }
                $save['cat_id'] = $data['cat_id'];
                if($data['pic_type'] == 0){
                    $save['cover'] = $data['cover_url'];
                }else{
                    $save['cover'] = $data['cover_upload'];
                }
                if($data['video_type'] == 0){
                    $save['url'] = $data['video_url'];
                }else{
                    $save['url'] = $data['video_upload'];
                }
                if(empty($save['cover']) || empty($save['url'])){
                    $this->error('图片地址或视频地址不能为空');
                }
            }else{
                if(empty($data['cat_id'])){
                    $this->error('请选择小说分类');
                }
                $save['cat_id'] = $data['cat_id'];
                if(empty($data['content'])){
                    $this->error('请输输入小说内容');
                }
                $save['content'] = htmlspecialchars($data['content']);
            }

            if(ResourceModel::where('id',$id)->update($save)){
                $url = $info['type'] ? 'book' : 'video';
                $this->success('修改成功',$url);
            }else{
                $this->error('数据无变动');
            }
        }
        if($info['type'] == 0){
            $cat = db('cat')->where('type',0)->field('id,name')->select();
            $cat_list = array_column($cat,'name','id');
            $trigger = [
                ['pic_type',1,'cover_upload'],
                ['pic_type',0,'cover_url'],
                ['video_type',1,'video_upload'],
                ['video_type',0,'video_url']
            ];

            if(strpos($info['cover'],'http') !== false){
                $pic_type = 0;
            }else{
                $pic_type = 1;
            }

            if(strpos($info['url'],'http') !== false){
                $video_type = 0;
            }else{
                $video_type = 1;
            }

            $items = [
                ['text','title','视频标题'],
                ['select','cat_id','视频分类','',$cat_list],
                ['radio','pic_type','图片上传方式','',['图片地址','本地上传'],$pic_type],
                ['image','cover_upload','图片上传','',$info['cover']],
                ['text','cover_url','图片地址','',$info['cover']],
                ['radio','video_type','视频上传方式','',['视频地址','本地上传'],$video_type],
                ['file','video_upload','视频上传','',$info['url']],
                ['text','video_url','视频地址','',$info['url']]
            ];
        }else{
            $cat = db('cat')->where('type',1)->field('id,name')->select();
            $cat_list = array_column($cat,'name','id');
            $trigger = [];
            $items = [
                ['text','title','小说标题'],
                ['select','cat_id','小说分类','',$cat_list],
                ['wangeditor','content','小说内容']
            ];
        }
        return ZBuilder::make('form')
            ->addFormItems($items)
            ->setTrigger($trigger)
            ->setFormData($info)
            ->fetch();
    }

    public function delete($ids = null){
        if(!$ids) $this->error('请选择需要删除的内容');
        if(ResourceModel::delete($ids)){
            db('link_book')->where('res_id','in',$ids)->delete();
            LinkVideoModel::where('res_id','in',$ids)->delete();
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

}