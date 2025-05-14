<?php

namespace app\agent\home;

use app\common\builder\ZBuilder;
use think\helper\Hash;
use app\common\model\LinkVideo as LinkVideoModel;

class Link extends Base{

    public function video(){

        // $list = db('link_video')
        //     ->alias('v')
        //     ->where('v.uid',$this->UID)
        //     ->join('cat c','v.cat_id = c.id')
        //     ->field('v.*,c.name as cat_name')
        //     ->order('create_time desc')
        //     ->paginate();
        
        $list = LinkVideoModel::where('uid',$this->UID)
            ->order('create_time desc')
            ->paginate();

        return ZBuilder::make('table')
            ->setPageTips(config('web.ag_tips'), 'danger')
            ->addColumns([
                ['id','ID'],
                // ['cat_name','分类名称'],
                
                ['cat_name','分类','callback',function($data){
                    //今日收入
                    $cat = db('cat')
                        ->where('id',$data['cat_id'])
                        ->find();
                    return $cat['name'];
                },'__data__'],
                
                ['title','标题'],
                ['money','金额','text.edit'],
                ['free','是否免费','switch'],
                ['shikan','试看时间（秒）','text.edit'],
                ['status','状态','switch'],
                // ['read_num','访问量'],
                ['create_time','添加时间','datetime'],
                ['right_button','操作']
            ])
            ->addTopButton('custom',[
                'title' => '一键修改金额',
                'href' => url('video_money')
            ],['area' => ['100%','100%']])
            ->addTopButton('custom',[
                'title' => '一键设置试看/免费',
                'href' => url('set_free',['type' => 0])
            ],['area' => ['100%','100%']])
            ->addTopButton('custom',[
                'title' => '修改VIP金额',
                'class' => 'btn btn-primary',
                'href' => url('edit_sp_vip')
            ],['area' => ['100%','100%']])
            ->addTopButtons([
                'delete' => [
                    'title' => '批量删除',
                    'href' => url('delete',['type' => 'video']),
                    'class' => 'btn btn-warning ajax-post confirm'
                ],
                'del_all' => [
                    'title' => '删除全部',
                    'class' => 'btn btn-danger ajax-get confirm',
                    'href' => url('del_all',['type' => 'video'])
                ]
            ])

            ->addTopButton('custom',[
                'title' => '推广总链接',
                'class' => 'btn btn-primary ',
                'href' => url('entry_link', ['type' => 'wx'])
            ],['area' => ['100%','100%']])
            // ->addTopButton('custom',[
            //     'title' => '新微博备用链接2',
            //     'class' => 'btn btn-primary',
            //     'href' => url('entry_link', ['type' => 'qq'])
            // ],['area' => ['100%','100%']])
            // ->addTopButton('custom',[
            //     'title' => '微博新直连3',
            //     'class' => 'btn btn-primary',
            //     'href' => url('entry_link', ['type' => 'dy'])
            // ],['area' => ['100%','100%']])
            ->addRightButton('custom',[
                'title' => '推广',
                'icon' => 'fa fa-fw fa-weixin',
                'href' => url('short_link',['id' => '__id__','type' => 'video','name' => 'wx']),
            ],['area' => ['780px','400px']])
            // ->addRightButton('custom',[
            //     'title' => 'QQ短链接',
            //     'icon' => 'fa fa-fw fa-qq',
            //     'href' => url('short_link',['id' => '__id__','type' => 'video','name' => 'qq']),
            // ],['area' => ['780px','400px']])
            // ->addRightButton('custom',[
            //     'title' => '抖音/快手短链接',
            //     'icon' => 'fa fa-fw fa-link',
            //     'href' => url('short_link',['id' => '__id__','type' => 'video','name' => 'dy']),
            // ],['area' => ['780px','400px']])

            ->setRowList($list)
            ->setColumnWidth([
                'id' => 80,
                'cat_name' => 80,
                'title'  => 400,
                'money'  => 90,
                'free' => 90,
                'status' => 90,
                // 'read_num' => 90,
                'create_time' => 120
            ])
            ->fetch();
    }

    public function entry(){
        $domain = config('web.kz_url');

        if(empty($domain)){
            $this->assign('tips','快站入口网址为空，请联系管理员');
            $this->assign('is_ok',1);
            return $this->fetch();
        }else{
            // if($domain['is_all'] == 1){
            //     $prefix = getDomainPrefix().'.'.$domain['domain'];
            // }else{
            //     $prefix = $domain['domain'];
            // }
            $prefix = $domain;
            $keys = id_encode($this->UID);
            // https://woaishangav.kuaizhan.com/?type=0&s=TURBd01EQXdNREF3TUg2MWhIQQ&id=0
            $url = $prefix.'/?type=0&k='.$keys.'&ls='.getDomainPrefix();
            // $url = "http://baidu.com";
            $this->assign('y_url',$url);
            $this->assign('url',url_short($url));
            $this->assign('is_ok',1);
            return $this->fetch();
            // var_dump($url,url_short($url));
        }
    }

    // 获取总推广链接
    public function entry_link()
    {
        $type = input('type');
        // $domain = config('web.kz_url');

        $domain = entryDomain();
        if(empty($domain)){
            $this->error('入口域名无可用，请联系管理员');
        } else {
            $keys = id_encode($this->UID);
            $url = $domain['domain'].'/s/'.$keys.'/entry?t=all';

            $this->assign('y_url',$url);

            // if ($type == 'dy') {
            //     $this->assign('url',url_short($url));
            // } else {
            //     $this->assign('url',ql_url_short($url,$type));
            // }
            $this->assign('url',ql_url_short($url,$type));
            $this->assign('is_ok',1);
            return $this->fetch('entry');
        }
    }

    public function short_link(){
        $id = input('id');
        $type = input('type');
        $name = input('name');
        if($type == 'video'){
            $info = LinkVideoModel::where('id',$id)->find();
            // $type = 1;
        }else{
            $info = db('link_book')->where('id',$id)->find();
            // $type = 2;
        }
        if(empty($info)){
            $this->error('数据不存在');
        }

        $domain = entryDomain();
        if(empty($domain)){
            $this->assign('tips','入口域名为空，请联系管理员');
            $this->assign('is_ok',1);
            return $this->fetch('short');
        }else{
            $keys = id_encode($this->UID);
            $url = $domain['domain'].'/s/'.$keys.'/entry?t='.$type.'&id='.$id;
            // $this->assign('y_url',$url);
            // if ($name == 'dy') {
            //     $this->assign('url',url_short($url));
            // } else {
            //     $this->assign('url',ql_url_short($url,$name));
            // }
            $this->assign('url',ql_url_short($url,$name));
            $this->assign('is_ok',1);
            return $this->fetch('short');
        }
    }

    public function book(){
        $list = db('link_book')
            ->alias('v')
            ->where('v.uid',$this->UID)
            ->join('cat c','v.cat_id = c.id')
            ->field('v.*,c.name as cat_name')
            ->join('resource r','v.res_id = r.id')
            ->field('v.*,r.title as res_title')
            ->order('create_time desc')
            ->paginate();

        return ZBuilder::make('table')
            ->setPageTips(config('web.ag_tips'), 'danger')
            ->addColumns([
                ['id','ID'],
                ['cat_name','分类名称'],
                ['res_title','标题'],
                ['money','金额','text.edit'],
                ['free','是否免费','switch'],
                ['status','状态','switch'],
                // ['read_num','访问量'],
                ['create_time','添加时间','datetime'],
                ['right_button','操作']
            ])
            ->addTopButton('custom',[
                'title' => '一键修改金额',
                'href' => url('book_money')
            ],['area' => ['780px','300px']])
            ->addTopButton('custom',[
                'title' => '一键设置试看/免费',
                'href' => url('set_free',['type' => 1])
            ],['area' => ['780px','500px']])
            ->addTopButton('custom',[
                'title' => '修改VIP金额',
                'class' => 'btn btn-primary',
                'href' => url('edit_xs_vip')
            ],['area' => ['780px','600px']])
            ->addTopButtons([
                'delete' => [
                    'title' => '批量删除',
                    'href' => url('delete',['type' => 'book']),
                    'class' => 'btn btn-warning ajax-post confirm'
                ],
                'del_all' => [
                    'title' => '删除全部',
                    'class' => 'btn btn-danger ajax-get confirm',
                    'href' => url('del_all',['type' => 'book'])
                ]
            ])
            // ->addTopButton('custom',[
            //     'title' => '推广总链接',
            //     'class' => 'btn btn-default',
            //     'href' => url('entry')
            // ],['area' => ['600px','400px']])
            ->addTopButton('custom',[
                'title' => '推广总链接',
                'class' => 'btn btn-default',
                'href' => url('entry_link', ['type' => 'wx'])
            ],['area' => ['600px','400px']])
            // ->addTopButton('custom',[
            //     'title' => 'QQ推广总链接',
            //     'class' => 'btn btn-default',
            //     'href' => url('entry_link', ['type' => 'qq'])
            // ],['area' => ['600px','400px']])
            // ->addTopButton('custom',[
            //     'title' => '抖音/快手推广总链接',
            //     'class' => 'btn btn-default',
            //     'href' => url('entry_link', ['type' => 'dy'])
            // ],['area' => ['600px','400px']])
            ->addRightButton('custom',[
                'title' => '预览',
                'icon' => 'fa fa-fw fa-eye',
                'href' => url('read',['id' => '__id__']),
            ],true)
            // ->addRightButton('edit',['href' => url('edit',['id' => '__id__','type' => 'book'])])
            // ->addRightButton('custom',[
            //     'title' => '短连接生成',
            //     'icon' => 'fa fa-fw fa-link',
            //     'href' => url('short',['id' => '__id__','type' => 'book']),
            // ],['area' => ['780px','400px']])
            ->addRightButton('custom',[
                'title' => '推广链接',
                'icon' => 'fa fa-fw fa-weixin',
                'href' => url('short_link',['id' => '__id__','type' => 'book','name' => 'wx']),
            ],['area' => ['780px','400px']])
            // ->addRightButton('custom',[
            //     'title' => 'QQ短链接',
            //     'icon' => 'fa fa-fw fa-qq',
            //     'href' => url('short_link',['id' => '__id__','type' => 'book','name' => 'qq']),
            // ],['area' => ['780px','400px']])
            // ->addRightButton('custom',[
            //     'title' => '抖音/快手短链接',
            //     'icon' => 'fa fa-fw fa-link',
            //     'href' => url('short_link',['id' => '__id__','type' => 'book','name' => 'dy']),
            // ],['area' => ['780px','400px']])
            ->setColumnWidth([
                'id' => 80,
                'cat_name' => 80,
                'title'  => 400,
                'money'  => 90,
                'free' => 90,
                'status' => 90,
                'read_num' => 90,
                'create_time' => 120
            ])
            ->setRowList($list)
            ->fetch();
    }
    
    public function set_free(){
        $type = input('type',0);
        
        if($this->request->isPost()){
            
            if($type == 0){
                $set_type = input('set_type',1);
                $time = input('time',0);
                $free = input('video_free',0);
                if($set_type == 0){
                    LinkVideoModel::where('uid',$this->UID)->setField('shikan',$time);
                    $this->success('设置成功',null, ['_parent_reload' => 1]);
                }else{
                    LinkVideoModel::where('uid',$this->UID)->setField('free',$free);
                    $this->success('设置成功',null, ['_parent_reload' => 1]);
                }
            }else{
                $free = input('book_free',0);
                db('link_book')->where('uid',$this->UID)->setField('free',$free);
                $this->success('设置成功',null, ['_parent_reload' => 1]);
            }
        }
        
        if($type == 0){
            return Zbuilder::make('form')
            ->addFormItems([
                ['radio','set_type','设置类型','',['一键试看','一键免费'],0],
                ['number','time','试看时长','单位：秒',15],
                ['radio','video_free','是否免费','',['不免费','免费'],0]
            ])
            ->setTrigger([
                ['set_type',0,'time'],
                ['set_type',1,'video_free']
            ])
            ->fetch();
        }else{
            return Zbuilder::make('form')
            ->addFormItems([
                ['radio','book_free','设置类型','',['不免费','免费'],0]
            ])
            ->fetch();
        }
        
    }
    
    public function set_tongji(){
        return Zbuilder::make('form')
            ->addFormItems([
                ['textarea','script','统计脚本','']
            ])
            ->fetch();
    }

    public function play(){
        $id = input('id');
        $info = LinkVideoModel::where('id',$id)
            ->field('id,title,cover,url')
            ->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        if(stripos($info['cover'],'http') === false){
            $info['cover'] = get_file_path($info['cover']);
        }
        // 使用format_video_url函数处理url字段
        $info['url'] = format_video_url($info['url']);
        $this->assign('info',$info);
        return $this->fetch();
    }

    public function read(){
        $id = input('id');
        $info = db('link_book')
            ->alias('v')
            ->where('v.id',$id)
            ->join('resource r','v.res_id = r.id')
            ->field('v.*,r.title as title')
            ->field('v.*,r.content as content')
            ->find();

        if(empty($info)){
            $this->error('数据不存在');
        }
        $this->assign('info',$info);
        return $this->fetch();
    }

    public function edit(){
        $id = input('id');
        $type = input('type');
        if(empty($type)){
            if($this->request->isPost()){
                $data = $this->request->post();
                $result = $this->validate($data,['title|标题' => 'require']);
                if($result !== true){
                    $this->error($result);
                }
                $save = [];
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

                if(LinkVideoModel::where('id',$id)->update($save)){
                    $this->success('修改成功','video');
                }else{
                    $this->error('数据无变动');
                }
            }
            $cat = db('cat')->where('type',0)->field('id,name')->select();
            $cat_list = array_column($cat,'name','id');
            $info = LinkVideoModel::where('id',$id)->find();
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

            return ZBuilder::make('form')
                ->addFormItems($items)
                ->setTrigger($trigger)
                ->setFormData($info)
                ->fetch();
        }else{
            if($this->request->isPost()){
                $data = $this->request->post();
                $result = $this->validate($data,['title|标题' => 'require']);
                if($result !== true){
                    $this->error($result);
                }
                if(empty($data['cat_id'])){
                    $this->error('请选择小说分类');
                }
                if(empty($data['content'])){
                    $this->error('请输输入小说内容');
                }
                $data['content'] = htmlspecialchars($data['content']);
                if(db('link_book')->where('id',$id)->update($data)){
                    $this->success('修改成功','book');
                }else{
                    $this->error('数据无变动');
                }
            }
            $cat = db('cat')->where('type',1)->field('id,name')->select();
            $cat_list = array_column($cat,'name','id');
            $info = db('link_book')->where('id',$id)->find();
            $items = [
                ['text','title','小说标题'],
                ['select','cat_id','小说分类','',$cat_list],
                ['wangeditor','content','小说内容']
            ];
            return ZBuilder::make('form')
                ->addFormItems($items)
                ->setFormData($info)
                ->fetch();
        }
    }

    public function edit_xs_vip(){
        $info = db('agent')->where('id',$this->UID)->value('config');
        $config = json_decode($info,true);
        if(empty($config)){
            $config = [];
        }
        if($this->request->isPost()){
            $data = $this->request->post();
            $result = $this->validate($data,[
                'b_bao_day|包天VIP金额' => 'require',
                'b_bao_week|包周VIP金额' => 'require',
                'b_bao_month|包月VIP金额' => 'require'
            ]);
            if($result !== true){
                $this->error($result);
            }
            if(isset($data['token'])){
                unset($data['__token__']);
            }
            $data = array_merge($config,$data);
            if(db('agent')->where('id',$this->UID)->setField('config',json_encode($data))){
                $this->success('修改成功',null, ['_parent_reload' => 1]);
            }else{
                $this->error('设置无变动');
            }
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['text','b_bao_day','包天VIP金额','请输入整数或者小数',0],
                ['text','b_bao_week','包周VIP金额','请输入整数或者小数',0],
                ['text','b_bao_month','包月VIP金额','请输入整数或者小数',0],
            ])
            ->setPageTips('如果不设置VIP金额，或者金额为0，那么前台将不会显示VIP购买', 'danger')
            ->setFormData($config)
            ->fetch();
    }

    public function edit_sp_vip(){
        $info = db('agent')->where('id',$this->UID)->value('config');
        $config = json_decode($info,true);
        if(empty($config)){
            $config = [];
        }
        if($this->request->isPost()){
            $data = $this->request->post();
            $result = $this->validate($data,[
                'v_bao_day|包天VIP金额' => 'require',
                'v_bao_week|包周VIP金额' => 'require',
                'v_bao_month|包月VIP金额' => 'require'
            ]);
            if($result !== true){
                $this->error($result);
            }
            if(isset($data['token'])){
                unset($data['__token__']);
            }
            $data = array_merge($config,$data);
            if(db('agent')->where('id',$this->UID)->setField('config',json_encode($data))){
                $this->success('修改成功',null, ['_parent_reload' => 1]);
            }else{
                $this->error('设置无变动');
            }
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['text','v_bao_day','包天VIP金额','请输入整数或者小数',0],
                ['text','v_bao_week','包周VIP金额','请输入整数或者小数',0],
                ['text','v_bao_month','包月VIP金额','请输入整数或者小数',0],
            ])
            ->setPageTips('如果不设置VIP金额，或者金额为0，那么前台将不会显示VIP购买', 'danger')
            ->setFormData($config)
            ->fetch();
    }

    public function quickEdit($record = []){
        $field           = input('post.name', '');
        $value           = input('post.value', '');
        $type            = input('post.type', '');
        $id              = input('post.pk', '');
        $validate        = input('post.validate', '');
        $validate_fields = input('post.validate_fields', '');

        $field == '' && $this->error('缺少字段名');
        $id    == '' && $this->error('缺少主键值');

        if(stripos($this->request->header('referer'),'book') === false){
            $Model = LinkVideoModel::where('id',$id);
        }else{
            $Model = db('link_book')->where('id',$id);
        }

        // 验证器
        if ($validate != '') {
            $validate_fields = array_flip(explode(',', $validate_fields));
            if (isset($validate_fields[$field])) {
                $result = $this->validate([$field => $value], $validate.'.'.$field);
                if (true !== $result) $this->error($result);
            }
        }

        switch ($type) {
            // 日期时间需要转为时间戳
            case 'combodate':
                $value = strtotime($value);
                break;
            // 开关
            case 'switch':
                $value = $value == 'true' ? 1 : 0;
                break;
            // 开关
            case 'password':
                $value = Hash::make((string)$value);
                break;
        }

        // 主键名
        $pk     = $Model->getPk();
        $result = $Model->setField($field, $value);

        if (false !== $result) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    public function short(){
        $id = input('id');
        $type = input('type');
        if($type == 'video'){
            $info = LinkVideoModel::where('id',$id)->find();
            $type = 1;
        }else{
            $info = db('link_book')->where('id',$id)->find();
            $type = 2;
        }
        if(empty($info)){
            $this->error('数据不存在');
        }

        $domain = config('web.kz_url');
        if(empty($domain)){
            $this->assign('tips','入口域名为空，请联系管理员');
            $this->assign('is_ok',1);
            return $this->fetch();
        }else{
            // if($domain['is_all'] == 1){
            //     $prefix = getDomainPrefix().'.'.$domain['domain'];
            // }else{
            //     $prefix = $domain['domain'];
            // }
            $prefix = $domain;
            $keys = id_encode($this->UID);
            // $url = 'http://'.$prefix.'/s/'.$keys.'/share?type='.$type.'&id='.$id;
            $url = $prefix.'/?type='.$type.'&k='.$keys.'&id='.$id.'&ls='.getDomainPrefix();
            $this->assign('y_url',$url);
            $this->assign('url',url_short($url));
            $this->assign('is_ok',1);
            return $this->fetch();
        }
    }

    public function video_money(){
        if($this->request->isPost()){
            $money = $this->request->post('money');
            if(empty($money)){
                $this->error('金额不能为空');
            }
            $money = (int)$money;

            //最小
            if($money < (int)config('web.ag_ds_min')){
                $this->error('最小打赏金额不能小于'.config('web.ag_ds_min'));
            }
            //最大
            if($money > (int)config('web.ag_ds_max')){
                $this->error('最大打赏金额不能大于'.config('web.ag_ds_max'));
            }

            if(LinkVideoModel::where('uid',$this->UID)->setField('money',$money)){
                $this->success('修改成功',null, ['_parent_reload' => 1]);
            }else{
                $this->error('修改失败');
            }
        }
        return ZBuilder::make('form')
            ->addText('money','金额')
            ->fetch();
    }

    public function book_money(){
        if($this->request->isPost()){
            $money = $this->request->post('money');
            if(empty($money)){
                $this->error('金额不能为空');
            }
            $money = (int)$money;
            //最小
            if($money < (int)config('web.ag_ds_min')){
                $this->error('最小打赏金额不能小于'.config('web.ag_ds_min'));
            }
            //最大
            if($money > (int)config('web.ag_ds_max')){
                $this->error('最大打赏金额不能大于'.config('web.ag_ds_max'));
            }
            if(db('link_book')->where('uid',$this->UID)->setField('money',$money)){
                $this->success('修改成功',null, ['_parent_reload' => 1]);
            }else{
                $this->error('修改失败');
            }
        }
        return ZBuilder::make('form')
            ->addText('money','金额')
            ->fetch();
    }

    public function delete(){
        $ids = input('ids');
        $type = input('type');
        if(empty($ids)){
            $this->error('请选择需要删除的数据');
        }
        if($type == 'video'){
            if(LinkVideoModel::where('uid',$this->UID)->delete($ids)){
                $this->success('删除成功','video');
            }else{
                $this->error('删除失败');
            }
        }else{
            if(db('link_book')->where('uid',$this->UID)->delete($ids)){
                $this->success('删除成功','book');
            }else{
                $this->error('删除失败');
            }
        }
    }

    public function del_all(){
        $type = input('type');
        if($type == 'video'){
            if(LinkVideoModel::where('uid',$this->UID)->delete()){
                $this->success('删除成功');
            }else{
                $this->error('数据为空');
            }
        }else{
            if(db('link_book')->where('uid',$this->UID)->delete()){
                $this->success('删除成功');
            }else{
                $this->error('数据为空');
            }
        }
    }

}