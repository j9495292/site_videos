<?php

namespace app\agent\home;

use app\common\builder\ZBuilder;
use think\facade\Validate;
use app\common\model\Resource as ResourceModel;
use app\common\model\LinkVideo as LinkVideoModel;

class Resource extends Base{

    public function video(){
        $uid = $this->UID;
        $list = ResourceModel::alias('r')
            ->where('r.type',0)
            ->join('cat c','r.cat_id = c.id')
            ->field('r.*,c.name as cat_name')
            ->order('create_time desc')
            ->paginate();
        return ZBuilder::make('table')
            ->addColumns([
                ['is_tuig','是否推广','callback',function($data) use ($uid){
                    $tuiguangd = LinkVideoModel::where('uid',$uid)->where('res_id',$data['id'])->count();
                    if($tuiguangd > 0){
                        return '<span class="label label-success">已推广</span>';
                    }else{
                        return '<span class="label label-default">未推广</span>';
                    }
                },'__data__'],
                ['title','视频标题'],
                ['cat_name','分类'],
                ['cover','视频图片','callback',function($data){
                    if(stripos($data,'http') !== false){
                        return '<div class="js-gallery"><img class="image" data-original="'.$data.'" src="'.$data.'"></div>';
                    }else{
                        return '<div class="js-gallery"><img class="image" data-original="'.get_file_path($data).'" src="'.get_file_path($data).'"></div>';
                    }
                }],
                ['url','视频地址','popover',20],
                ['status','状态','status'],
                ['create_time','创建时间','datetime']
            ])
            ->addTopButton('custom',[
                'title' => '批量发布',
                'class' => 'btn btn-primary js-get',
                'target-form' => 'ids',
                'href' => url('video_push')
            ])
            ->addTopButton('custom',[
                'title' => '批量发布随机金额',
                'class' => 'btn btn-default js-get',
                'target-form' => 'ids',
                'href' => url('video_push_rand')
            ])
            ->addTopButton('custom',[
                'title' => '一键发布所有',
                'class' => 'btn btn-default',
                'href' => url('push_all',['type' => 0])
            ])
            ->addTopButton('custom',[
                'title' => '一键发布随机金额',
                'class' => 'btn btn-default',
                'href' => url('push_all_rand',['type' => 0])
            ])
            ->setRowList($list)
            ->fetch();
    }

    public function book(){
        $uid = $this->UID;
        //var_dump($uid);exit;
        $list = ResourceModel::alias('r')
            ->where('r.type',1)
            ->join('cat c','r.cat_id = c.id')
            ->field('r.*,c.name as cat_name')
            ->order('create_time desc')
            ->paginate();
        return ZBuilder::make('table')
            ->addColumns([
                ['is_tuig','是否推广','callback',function($data) use ($uid){
                    $tuiguangd = LinkVideoModel::where('uid',$uid)->where('res_id',$data['id'])->count();
                    if($tuiguangd > 0){
                        return '<span class="label label-success">已推广</span>';
                    }else{
                        return '<span class="label label-default">未推广</span>';
                    }
                },'__data__'],
                ['title','小说标题'],
                ['cat_name','分类'],
                ['status','状态','status'],
                ['create_time','创建时间','datetime']
            ])
            ->addTopButtons([
                'book_push' => [
                    'title' => '批量发布',
                    'class' => 'btn btn-primary js-get',
                    'target-form' => 'ids',
                    'href' => url('book_push')
                ]
            ])
            ->addTopButton('custom',[
                'title' => '批量发布随机金额',
                'class' => 'btn btn-default js-get',
                'target-form' => 'ids',
                'href' => url('book_push_rand')
            ])
            ->addTopButton('custom',[
                'title' => '一键发布所有',
                'class' => 'btn btn-default',
                'href' => url('push_all',['type' => 1])
            ])
            ->addTopButton('custom',[
                'title' => '一键发布随机金额',
                'class' => 'btn btn-default',
                'href' => url('push_all_rand',['type' => 1])
            ])
            ->setRowList($list)
            ->fetch();
    }

    public function video_push(){
        $ids = input('ids');
        if(empty($ids)){
            $this->error('请选择需要发布的数据');
        }
        if($this->request->isPost()){
            $data = ResourceModel::where('type',0)
                ->where('id','in',$ids)
                ->field('id,title,cover,url,cat_id')
                ->select();
            if(empty($data)){
                $this->error('数据不存在，请重新选择');
            }
            $money = (float)input('money');
            //最小
            if($money < (float)config('web.ag_ds_min')){
                $this->error('最小打赏金额不能小于'.config('web.ag_ds_min'));
            }
            //最大
            if($money > (float)config('web.ag_ds_max')){
                $this->error('最大打赏金额不能大于'.config('web.ag_ds_max'));
            }
            //判断是否发布过
            $push_data = [];
            foreach ($data as $item){
                if(LinkVideoModel::where('uid',$this->UID)->where('res_id',$item['id'])->count()){
                    continue;
                }else{
                    $push_data[] = [
                        'cat_id' => $item['cat_id'],
                        'uid' => $this->UID,
                        'title' => $item['title'],
                        'cover' => $item['cover'],
                        'url' => $item['url'],
                        'money' => $money,
                        'free' => 0,
                        'res_id' => $item['id'],
                        'create_time' => time(),
                        'status' => 1
                    ];
                }
            }
            $count = LinkVideoModel::insertAll($push_data);
            if($count > 0){
                $this->success('发布成功'.$count.'条','link/video');
            }else{
                $this->error('您选中的资源已经发布过了');
            }
        }
        return ZBuilder::make('form')
            ->addText('money','打赏金额','请输入小数或者整数，如0.01或者1')
            ->setBtnTitle('submit','立即发布')
            ->fetch();
    }

    public function book_push(){
        $ids = input('ids');
        if(empty($ids)){
            $this->error('请选择需要发布的数据');
        }
        if($this->request->isPost()){
            $data = ResourceModel::where('type',1)
                ->where('id','in',$ids)
                ->field('id,title,content,cat_id')
                ->select();
            if(empty($data)){
                $this->error('数据不存在，请重新选择');
            }
            $money = (float)input('money');
            //最小
            if($money < (float)config('web.ag_ds_min')){
                $this->error('最小打赏金额不能小于'.config('web.ag_ds_min'));
            }
            //最大
            if($money > (float)config('web.ag_ds_max')){
                $this->error('最大打赏金额不能大于'.config('web.ag_ds_max'));
            }
            //判断是否发布过
            $push_data = [];
            foreach ($data as $item){
                if(LinkVideoModel::where('uid',$this->UID)->where('res_id',$item['id'])->count()){
                    continue;
                }else{
                    $push_data[] = [
                        'cat_id' => $item['cat_id'],
                        'uid' => $this->UID,
                        'title' => $item['title'],
                        'content' => $item['content'],
                        'money' => $money,
                        'free' => 0,
                        'res_id' => $item['id'],
                        'create_time' => time(),
                        'status' => 1
                    ];
                }
            }
            $count = LinkVideoModel::insertAll($push_data);
            if($count > 0){
                $this->success('发布成功'.$count.'条','link/book');
            }else{
                $this->error('您选中的资源已经发布过了');
            }
        }
        return ZBuilder::make('form')
            ->addText('money','打赏金额','请输入小数或者整数，如0.01或者1')
            ->setBtnTitle('submit','立即发布')
            ->fetch();
    }

    public function video_push_rand(){
        $ids = input('ids');
        if(empty($ids)){
            $this->error('请选择需要发布的数据');
        }
        if($this->request->isPost()){
            $data = ResourceModel::where('type',0)
                ->where('id','in',$ids)
                ->field('id,title,cover,url,cat_id')
                ->select();
            if(empty($data)){
                $this->error('数据不存在，请重新选择');
            }
            $min = input('min');
            $max = input('max');

            if(!Validate::is($min,'number')){
                $this->error('最小金额不能为小数');
            }
            if(!Validate::is($max,'number')){
                $this->error('最大金额不能为小数');
            }
            //最小
            if($min < (float)config('web.ag_ds_min')){
                $this->error('最小打赏金额不能小于'.config('web.ag_ds_min'));
            }
            //最大
            if($max > (float)config('web.ag_ds_max')){
                $this->error('最大打赏金额不能大于'.config('web.ag_ds_max'));
            }
            //判断是否发布过
            $push_data = [];

            $link_data = LinkVideoModel::where(['uid'=>$this->UID])->field('res_id')->select();

            foreach ($data as $item){
                if(searchArray($link_data,'res_id',$item['id'])){
                    continue;
                }else{
                    $push_data[] = [
                        'cat_id' => $item['cat_id'],
                        'uid' => $this->UID,
                        'title' => $item['title'],
                        'cover' => $item['cover'],
                        'url' => $item['url'],
                        'money' => rand($min,$max),
                        'free' => 0,
                        'res_id' => $item['id'],
                        'create_time' => time(),
                        'status' => 1
                    ];
                }
            }
            $count = LinkVideoModel::insertAll($push_data);
            if($count > 0){
                $this->success('发布成功'.$count.'条','link/video');
            }else{
                $this->error('您选中的资源已经发布过了');
            }
        }
        return ZBuilder::make('form')
            ->addText('min','随机金额最小','请输入整数',3)
            ->addText('max','随机金额最大','请输入整数',10)
            ->layout([
                'min' => 4,
                'max' => 4
            ])
            ->setBtnTitle('submit','立即发布')
            ->fetch();
    }

    public function book_push_rand(){
        $ids = input('ids');
        if(empty($ids)){
            $this->error('请选择需要发布的数据');
        }
        if($this->request->isPost()){
            $data = ResourceModel::where('type',1)
                ->where('id','in',$ids)
                ->field('id,title,content,cat_id')
                ->select();
            if(empty($data)){
                $this->error('数据不存在，请重新选择');
            }
            $min = input('min');
            $max = input('max');

            if(!Validate::is($min,'number')){
                $this->error('最小金额不能为小数');
            }
            if(!Validate::is($max,'number')){
                $this->error('最大金额不能为小数');
            }
            //最小
            if($min < (float)config('web.ag_ds_min')){
                $this->error('最小打赏金额不能小于'.config('web.ag_ds_min'));
            }
            //最大
            if($max > (float)config('web.ag_ds_max')){
                $this->error('最大打赏金额不能大于'.config('web.ag_ds_max'));
            }
            //判断是否发布过
            $push_data = [];

            $link_data = LinkVideoModel::where(['uid'=>$this->UID])->field('res_id')->select();

            foreach ($data as $item){
                if(searchArray($link_data,'res_id',$item['id'])){
                    continue;
                }else{
                    $push_data[] = [
                        'cat_id' => $item['cat_id'],
                        'uid' => $this->UID,
                        'title' => $item['title'],
                        'content' => $item['content'],
                        'money' => rand($min,$max),
                        'free' => 0,
                        'res_id' => $item['id'],
                        'create_time' => time(),
                        'status' => 1
                    ];
                }
            }
            $count = LinkVideoModel::insertAll($push_data);
            if($count > 0){
                $this->success('发布成功'.$count.'条','link/book');
            }else{
                $this->error('您选中的资源已经发布过了');
            }
        }
        return ZBuilder::make('form')
            ->addText('min','随机金额最小','请输入整数',3)
            ->addText('max','随机金额最大','请输入整数',10)
            ->layout([
                'min' => 4,
                'max' => 4
            ])
            ->setBtnTitle('submit','立即发布')
            ->fetch();
    }

    public function push_all(){
        $type = input('type');
        //var_dump($type);exit;
        if($this->request->isPost()){
            $data = ResourceModel::where('type',$type)
                ->field('id,title,cover,url,content,cat_id')
                ->select();
            if(empty($data)){
                $this->error('数据不存在，请重新选择');
            }
            $money = (float)input('money');
            //最小
            if($money < (float)config('web.ag_ds_min')){
                $this->error('最小打赏金额不能小于'.config('web.ag_ds_min'));
            }
            //最大
            if($money > (float)config('web.ag_ds_max')){
                $this->error('最大打赏金额不能大于'.config('web.ag_ds_max'));
            }
            //判断是否发布过
            $push_data = [];

            if($type == 0){
                $link_data = LinkVideoModel::where(['uid'=>$this->UID])->field('res_id')->select();
            }else{
                $link_data = db('link_book')->where(['uid'=>$this->UID])->field('res_id')->select();
            }

            foreach ($data as $item){
                if(searchArray($link_data,'res_id',$item['id'])){
                    continue;
                }else{
                    if($type == 0){
                        $push_data[] = [
                            'cat_id' => $item['cat_id'],
                            'uid' => $this->UID,
                            'title' => $item['title'],
                            'cover' => $item['cover'],
                            'url' => $item['url'],
                            'money' => $money,
                            'free' => 0,
                            'res_id' => $item['id'],
                            'create_time' => time(),
                            'status' => 1
                        ];
                    }else{
                        $push_data[] = [
                            'cat_id' => $item['cat_id'],
                            'uid' => $this->UID,
                            // 'title' => $item['title'],
                            // 'content' => $item['content'],
                            'money' => $money,
                            'free' => 0,
                            'res_id' => $item['id'],
                            'create_time' => time(),
                            'status' => 1
                        ];
                    }
                }
            }
            if($type == 0){
                $count = LinkVideoModel::insertAll($push_data);
            }else{
                $count = db('link_book')->insertAll($push_data);
            }
            if($count > 0){
                $this->success('发布成功'.$count.'条','link/'.($type ? 'book' : 'video'));
            }else{
                $this->error('您选中的资源已经发布过了');
            }
        }
        return ZBuilder::make('form')
            ->addText('money','打赏金额','请输入小数或者整数，如0.01或者1')
            ->setBtnTitle('submit','立即发布')
            ->fetch();
    }

    public function push_all_rand(){
        $type = input('type');
        if($this->request->isPost()){
            $data = ResourceModel::where('type',$type)
                ->field('id,title,cover,url,content,cat_id')
                ->select();
            if(empty($data)){
                $this->error('数据不存在，请重新选择');
            }
            $min = input('min');
            $max = input('max');

            if(!Validate::is($min,'number')){
                $this->error('最小金额不能为小数');
            }
            if(!Validate::is($max,'number')){
                $this->error('最大金额不能为小数');
            }
            //最小
            if($min < (float)config('web.ag_ds_min')){
                $this->error('最小打赏金额不能小于'.config('web.ag_ds_min'));
            }
            //最大
            if($max > (float)config('web.ag_ds_max')){
                $this->error('最大打赏金额不能大于'.config('web.ag_ds_max'));
            }
            //判断是否发布过
            $push_data = [];

            if($type == 0){
                $link_data = LinkVideoModel::where(['uid'=>$this->UID])->field('res_id')->select();
            }else{
                $link_data = db('link_book')->where(['uid'=>$this->UID])->field('res_id')->select();
            }

            foreach ($data as $item){
                if(searchArray($link_data,'res_id',$item['id'])){
                    continue;
                }else{
                    if($type == 0){
                        $push_data[] = [
                            'cat_id' => $item['cat_id'],
                            'uid' => $this->UID,
                            'title' => $item['title'],
                            'cover' => $item['cover'],
                            'url' => $item['url'],
                            'money' => rand($min,$max),
                            'free' => 0,
                            'res_id' => $item['id'],
                            'create_time' => time(),
                            'status' => 1
                        ];
                    }else{
                        $push_data[] = [
                            'cat_id' => $item['cat_id'],
                            'uid' => $this->UID,
                            'title' => $item['title'],
                            'content' => $item['content'],
                            'money' => rand($min,$max),
                            'free' => 0,
                            'res_id' => $item['id'],
                            'create_time' => time(),
                            'status' => 1
                        ];
                    }
                }
            }
            if($type == 0){
                $count = LinkVideoModel::insertAll($push_data);
            }else{
                $count = db('link_book')->insertAll($push_data);
            }
            if($count > 0){
                $this->success('发布成功'.$count.'条','link/'.($type ? 'book' : 'video'));
            }else{
                $this->error('您选中的资源已经发布过了');
            }
        }
        return ZBuilder::make('form')
            ->addText('min','随机金额最小','请输入整数',3)
            ->addText('max','随机金额最大','请输入整数',10)
            ->layout([
                'min' => 4,
                'max' => 4
            ])
            ->setBtnTitle('submit','立即发布')
            ->fetch();
    }

}