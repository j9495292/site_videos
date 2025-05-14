<?php
namespace app\admin\model;

use think\Model;

/**
 * 后台配置模型
 * @package app\admin\model
 */
class Agent extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'agent';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // Dmoney字段的获取器
    public function getDmoneyAttr($id)
    {
        return 'xx'.$id;
    }

}
