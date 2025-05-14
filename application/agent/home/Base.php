<?php

namespace app\agent\home;

use app\admin\model\Icon as IconModel;
use app\admin\model\Menu as MenuModel;
use app\common\controller\Common;
use think\Db;
use think\facade\App;
use think\helper\Hash;

class Base extends Common {

    protected $UID;

    protected $USER;

    protected $tablename = '';

    protected $noLogin = [
        'Login/index',
        'Login/logout'
    ];

    protected $noAuth = [
        'Admin/ajax',
        'Admin/attachment'
    ];

    protected function initialize(){
        parent::initialize();
        $router = $this->request->controller().'/'.$this->request->action();
        if(!in_array($router,$this->noLogin)){
            $agent_uid = session('agent_uid');
            if(empty($agent_uid)){
                $this->error('请先登录账号','login/index');
            }
            $this->UID = $agent_uid;
            $this->USER = db('agent')->where('id',$this->UID)->find();
        }

        if (!$this->request->isAjax() && !in_array($router,$this->noAuth)) {
            // 读取顶部菜单
            $this->assign('_top_menus', []);
            // 读取全部顶级菜单
            $this->assign('_top_menus_all', []);
            // 获取侧边栏菜单
            $this->assign('_sidebar_menus', MenuModel::getSidebarMenu('','','',true));
            // 获取面包屑导航
            $this->assign('_location', MenuModel::getLocation('', true));
            // 获取自定义图标
            $this->assign('_icons', IconModel::getUrls());
            // 插入代理标记
            $this->assign('_is_agent',true);
        }
        //设置分页参数
        $list_rows = input('?param.list_rows') ? input('param.list_rows') : config('list_rows');
        config('paginate.list_rows', $list_rows);
        config('paginate.query', input('get.'));
    }

    final protected function getCurrModel()
    {
        if(!empty($this->tablename)){
            return db($this->tablename);
        }
        $table_token = input('param._t', '');
        $module      = $this->request->module();
        $controller  = parse_name($this->request->controller());

        $table_token == '' && $this->error('缺少参数');
        !session('?'.$table_token) && $this->error('参数错误');

        $table_data = session($table_token);
        $table      = $table_data['table'];


        $Model = null;
        if ($table_data['prefix'] == 2) {
            // 使用模型
            try {
                $Model = App::model($table);
            } catch (\Exception $e) {
                $this->error('找不到模型：'.$table);
            }
        } else {
            // 使用DB类
            $table == '' && $this->error('缺少表名');
            if ($table_data['module'] != $module || $table_data['controller'] != $controller) {
                $this->error('非法操作');
            }

            $Model = $table_data['prefix'] == 0 ? Db::table($table) : Db::name($table);
        }

        return $Model;
    }

    final protected function setPageParam()
    {
        $list_rows = input('?param.list_rows') ? input('param.list_rows') : config('list_rows');
        config('paginate.list_rows', $list_rows);
        config('paginate.query', input('get.'));
    }

    public function quickEdit($record = [])
    {
        $field           = input('post.name', '');
        $value           = input('post.value', '');
        $type            = input('post.type', '');
        $id              = input('post.pk', '');
        $validate        = input('post.validate', '');
        $validate_fields = input('post.validate_fields', '');

        $field == '' && $this->error('缺少字段名');
        $id    == '' && $this->error('缺少主键值');

        $Model = $this->getCurrModel();

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
        $result = $Model->where($pk, $id)->setField($field, $value);

        if (false !== $result) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

}