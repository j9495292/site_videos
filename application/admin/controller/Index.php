<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\facade\Cache;
use think\facade\Env;
use think\helper\Hash;
use think\Db;
use app\common\builder\ZBuilder;
use app\user\model\User as UserModel;
use app\common\model\Resource as ResourceModel;

/**
 * 后台默认控制器
 * @package app\admin\controller
 */
class Index extends Admin
{
    /**
     * 后台首页
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    public function index()
    {
        //今日成交
        $today_money = db('order')
            ->where('status',1)
            ->whereTime('create_time', 'd')
            ->sum('money');

        $yesterday_money = db('order')
            ->where('status',1)
            ->whereTime('create_time', 'yesterday')
            ->sum('money');

        //代理
        $agent_count = db('agent')->count();
        $user_count = db('user')->count();

        //order统计
        $all_order = db('order')
            ->where('status',1)
            ->count();
        $all_money = db('order')
            ->where('status',1)
            ->sum('money');


        $today_order = db('order')
            ->where('status',1)
            ->whereTime('create_time', 'd')
            ->count();

        $yesterday_order = db('order')
            ->where('status',1)
            ->whereTime('create_time', 'yesterday')
            ->count();

        //今日扣量
        $kouliang_count = db('order')
            ->where('status',1)
            ->where('is_kouliang',1)
            ->whereTime('create_time', 'd')
            ->sum('money');
        //扣量订单
        $kouliang_order = db('order')
            ->where('status',1)
            ->where('is_kouliang',1)
            ->whereTime('create_time', 'd')
            ->count();

        //扣量总计
        $kouliang_zong_money = db('order')
            ->where('status',1)
            ->where('is_kouliang',1)
            ->sum('money');

        $kouliang_zong = db('order')
            ->where('status',1)
            ->where('is_kouliang',1)
            ->count();

        //今日访客
        $today_visitor =  db('agent_visitor')
            ->whereTime('create_time', 'today')
            ->count();
            
        //今日浏览量
        $today_browse =  db('agent_browse')
            ->whereTime('create_time', 'today')
            ->select();
        $browse_num = 0;
        foreach ($today_browse as $key => $value) {
            $browse_num = $browse_num + $value['browse_num'];
        }


        $this->assign('today_visitor',$today_visitor);
        $this->assign('today_browse',$browse_num);

        $this->assign('kouliang_count',$kouliang_count);
        $this->assign('kouliang_order',$kouliang_order);

        $this->assign('kouliang_zong',$kouliang_zong);
        $this->assign('kouliang_zong_money',$kouliang_zong_money);


        $this->assign('today_order',$today_order);
        $this->assign('yesterday_order',$yesterday_order);

        $this->assign('all_order',$all_order);
        $this->assign('all_money',$all_money);

        $this->assign('agent_count',$agent_count);
        $this->assign('user_count',$user_count);
        $this->assign('today_money',$today_money);
        $this->assign('yesterday_money',$yesterday_money);
        return $this->fetch();
    }

    /**
     * 清空系统缓存
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function wipeCache()
    {
        $wipe_cache_type = config('wipe_cache_type');
        if (!empty($wipe_cache_type)) {
            foreach ($wipe_cache_type as $item) {
                switch ($item) {
                    case 'TEMP_PATH':
                        array_map('unlink', glob(Env::get('runtime_path'). 'temp/*.*'));
                        break;
                    case 'LOG_PATH':
                        $dirs = (array) glob(Env::get('runtime_path') . 'log/*');
                        foreach ($dirs as $dir) {
                            array_map('unlink', glob($dir . '/*.log'));
                        }
                        array_map('rmdir', $dirs);
                        break;
                    case 'CACHE_PATH':
                        array_map('unlink', glob(Env::get('runtime_path'). 'cache/*.*'));
                        break;
                }
            }
            Cache::clear();
            $this->success('清空成功');
        } else {
            $this->error('请在系统设置中选择需要清除的缓存类型');
        }
    }

    /**
     * 个人设置
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function profile()
    {
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();

            $data['id'] = UID;

            // 如果没有填写密码，则不更新密码
            if ($data['password'] == '') {
                unset($data['password']);
            }

            $UserModel = new UserModel();
            if ($user = $UserModel->allowField(['username', 'password'])->update($data)) {
                // 记录行为
                $this->success('编辑成功');
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = UserModel::where('id', UID)->field('password', true)->find();

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->addFormItems([ // 批量添加表单项
                ['text', 'username', '用户名'],
                ['password', 'password', '密码', '必填，6-20位'],
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }
    
    // 删除订单
    public function orderDayDel()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            
            $day=$data['day'];
            
            if($day==1)
            {
                db('order')
                ->where('create_time','<',strtotime("-1 day"))
                ->delete();
            }
            
            if($day==3)
            {
                db('order')
                ->where('create_time','<',strtotime("-3 day"))
                ->delete();
            }
            
        }
    }
    
    // 删除视频
    public function spDel()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            
           ResourceModel::where('type',0)->delete();
            
        }
    }
    /**
     * 检查版本更新
     * @author 蔡伟明 <314013107@qq.com>
     * @return \think\response\Json
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     */
    public function checkUpdate()
    {
        $params = config('dolphin.');
        $params['domain']  = request()->domain();
        $params['website'] = config('web_site_title');
        $params['ip']      = $_SERVER['SERVER_ADDR'];
        $params['php_os']  = PHP_OS;
        $params['php_version'] = PHP_VERSION;
        $params['mysql_version'] = db()->query('select version() as version')[0]['version'];
        $params['server_software'] = $_SERVER['SERVER_SOFTWARE'];
        $params = http_build_query($params);

        $opts = [
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL            => config('dolphin.product_update'),
            CURLOPT_USERAGENT      => $_SERVER['HTTP_USER_AGENT'],
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => $params
        ];

        // 初始化并执行curl请求
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data  = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($data, true);

        if ($result['code'] == 1) {
            return json([
                'update' => '<a class="badge badge-primary" href="http://www.dolphinphp.com/download" target="_blank">有新版本：'.$result["version"].'</a>',
                'auth'   => $result['auth']
            ]);
        } else {
            return json([
                'update' => '',
                'auth'   => $result['auth']
            ]);
        }
    }
}