<?php

namespace app\common\model;

use think\Model;

class BaseVideo extends Model
{
    // 关闭时间戳自动写入（依项目而定，这里保持默认 false）
    protected $autoWriteTimestamp = false;

    /**
     * url 字段获取器，统一经过 format_video_url 处理
     *
     * @param string $value 原始 url
     * @return string
     */
    public function getUrlAttr($value)
    {
        return format_video_url($value);
    }
} 