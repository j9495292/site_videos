<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\admin\controller;

use app\common\builder\ZBuilder;
use app\admin\model\Config as ConfigModel;
use app\admin\model\Module as ModuleModel;
use think\facade\Env;
use think\facade\Request;

/**
 * 系统模块控制器
 * @package app\admin\controller
 */
class System extends Admin{

    protected function initialize(){
        parent::initialize();
        if($this->request->isPost()){
            $data = $this->request->post();
            unset($data['__token__']);
            $path = str_replace('\\','/',Env::get('root_path')).'config/';
            $old_data = config('web.');
            
            if($this->request->action() == 'pays'){
                $pay_type = $this->request->param('group');
                
                if($pay_type == 'yx' && !isset($data['pay_yx_status'])){
                    $data['pay_yx_status'] = '0';
                }
                
                if($pay_type == 'ds' && !isset($data['pay_ds_status'])){
                    $data['pay_ds_status'] = '0';
                }
                
                if($pay_type == 'xiaobb' && !isset($data['pay_xiaobb_status'])){
                    $data['pay_xiaobb_status'] = '0';
                }
            }
            
            $data = array_merge($old_data,$data);
            $content = '<?php return '.var_export($data,true).';';
            if(file_put_contents($path.'web.php',$content)){
                $this->success('保存成功');
            }else{
                $this->error('保存失败');
            }
        }
    }

    public function index(){
        $data = config('web.');
        return ZBuilder::make('form')
            ->addFormItems([
                ['text','title','网站名称'],
                // ['text','kz_url','快站网址','请输入http://或者https://后面不需要带/,例如http://www.baidu.com'],
                ['text','kf_url','客服地址'],
                ['text','qr_url','收藏域名'],
                ['text','sc_tips','收藏提示信息'],
                ['textarea','gg_tips','网站公告'],
                // ['text','yd_title','引导页名称'],
                // ['image','yd_bg','引导页背景图'],
                // ['text','yg_btn_1','引导页按钮1标题'],
                // ['text','yg_btn_2','引导页按钮2标题'],
                // ['text','yg_btn_3','引导页按钮3标题'],
                // ['text','yg_btn_4','引导页按钮4标题'],
                // ['text','yg_btn_5','引导页按钮5标题'],
                // ['text','yg_hy_title','引导页欢迎信息'],
                // ['text','yg_tips','引导页提示信息'],
                ['text','zhuce_tips','注册页面的提示语']
            ])
            ->setFormData($data)
            ->hideBtn('back')
            ->fetch();
    }

    public function slide(){
        $data = config('web.');
        return ZBuilder::make('form')
            ->addFormItems([
                ['text','slide_img_1','轮播图1图片'],
                ['text','slide_url_1','轮播图1跳转地址'],
                ['text','slide_img_2','轮播图2图片'],
                ['text','slide_url_2','轮播图2跳转地址'],
                ['text','slide_img_3','轮播图3图片'],
                ['text','slide_url_3','轮播图3跳转地址'],
            ])
            ->setFormData($data)
            ->hideBtn('back')
            ->fetch();
    }

    public function pays($group = 'xiaobb'){
        $data = config('web.');
        $list_tab = [
            'xiaobb' => ['title' => '小白支付', 'url' => url('pays', ['group' => 'xiaobb'])],
        ];

        switch ($group){
            case 'xiaobb':
                return ZBuilder::make('form')
                    ->addFormItems([
                        ['switch', 'pay_xiaobb_status', '是否启用'],
                        ['text','pay_xiaobb_mchid','商户ID','由支付平台分配'],
                        ['text','pay_xiaobb_productid','支付产品ID','默认8000(支付宝)或8001(微信)'],
                        ['text','pay_xiaobb_key','商户密钥','用于签名验证，请妥善保管']
                    ])
                    ->setTabNav($list_tab, $group)
                    ->setFormData($data)
                    ->hideBtn('back')
                    ->fetch();
            default:
                return $this->error('未知支付类型');
        }
    }

    public function dialog(){
        $data = config('web.');
        return ZBuilder::make('form')
            ->addFormItems([
                ['text','dg_video_title','视频打赏框标题'],
                ['text','dg_book_title','小说打赏框标题'],
                ['image','dg_bg','打赏框背景图'],
                ['text','dg_tips','打赏框提示信息'],
                ['text','dg_btn_dan','单片按钮信息','必须包含{m}，系统会自动替换金额'],
                ['text','dg_btn_day','包天按钮信息','必须包含{m}，系统会自动替换金额'],
                ['text','dg_btn_week','包周按钮信息','必须包含{m}，系统会自动替换金额'],
                ['text','dg_btn_month','包月按钮信息','必须包含{m}，系统会自动替换金额'],
            ])
            ->setFormData($data)
            ->hideBtn('back')
            ->fetch();
    }

    public function agent(){
        $data = config('web.');
        return ZBuilder::make('form')
            ->addFormItems([
                ['number','ag_tixain','代理默认提现费率(百分比)','如果不单独设置代理提现费率，那默认这个设置生效'],
                // ['number','ag_shouxu','平台手续费（百分比）'],
                ['number','ag_lv1','一级代理提成(百分比)','不能超过平台手续费'],
                ['number','ag_lv2','二级代理提成(百分比)','不能超过平台手续费'],
                ['number','ag_lv3','三级代理提成(百分比)','不能超过平台手续费'],
                ['text','ag_ds_min','打赏最小金额(元)','最小0.01'],
                ['text','ag_ds_max','打赏最大设置金额(元)','最小0.01'],
                ['textarea','ag_tips','横幅小提示'],
            ])
            ->setFormData($data)
            ->layout([
                'ag_ds_min' => 6,
                'ag_ds_max' => 6
            ])
            ->hideBtn('back')
            ->fetch();
    }

    public function domain(){
        $token = config('web.url_check_token');
        return ZBuilder::make('form')
            ->addFormItems([
                ['text','url_check_token','域名检测Token','',$token],
                ['static','a','域名监控地址','监控周期3-5分钟',url('admin/Task/index','',false,true)]
            ])
            ->hideBtn(['back'])
            ->fetch();
    }

    public function qilin(){
        $data = config('web.');
        return ZBuilder::make('form')
            ->addFormItems([
                ['text','ql_username','用户名'],
                ['text','ql_key','账户密钥'],
            ])
            ->setFormData($data)
            ->hideBtn('back')
            ->fetch();
    }

}