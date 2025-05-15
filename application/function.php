<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

// 为方便系统核心升级，二次开发中需要用到的公共函数请写在这个文件，不要去修改common.php文件


//url检测
function url_check($url, $type)
{
    $model = new \app\common\model\UrlCheck();
    $token = config('web.url_check_token');
    return $model->token($token)
        ->where($type)
        ->check($url);
}

function wxUrlCheck($url)
{
    $params = [
        'token' => config('web.url_check_token'),
        'url' => $url,
        'format' => 'json'
    ];
    $request_url = 'http://api.new.urlzt.com/api/vx?' . urldecode(http_build_query($params));
    $response = file_get_contents($request_url);
    $data = json_decode($response, true);
    if (empty($data)) {
        return false;
    }
    return $data['code'];
}

// 麒麟域名检测
function ql_check($url, $type)
{
    $key = config('web.ql_key');
    $username = config('web.ql_username');

    $arr = file_get_contents("https://api.uouin.com/index.php/index/Jiance/add?username=" . $username . "&key=" . $key . "&type=" . $type . "&url=" . urlencode($url));
    $data = json_decode($arr, true);

    // if($data['code'] == '1001'){
    //     return isset($data['short']) ? $data['msg'] : false;
    // }else{
    //     return false;
    // }
    // var_dump($json);
    return $data;
}

//快站Url生成
function quick_url($t)
{
    $kz = config('web.kz_url');
    return $kz . '/?t=' . base64_encode($t);
}

//url短连接生成
function url_short($url, $type = 2)
{
    $kz_url = quick_url($url);
    return \app\common\model\UrlShort::builder_baidu($url, $type);
}

function ql_url_short($url, $type)
{
    // https://api.uouin.com/index.php/index/$type
    // builder_qilin
    return $url;

    return \app\common\model\UrlShort::builder_qilin($url, $type);

}

//生成随机浮点数
function randomFloat($min = 0, $max = 1)
{
    return $min + mt_rand() / mt_getrandmax() * ($max - $min);
}

//获取VIP剩余时长
/*function getVipTime($viptime){
    $viptime = strtotime(date('Y-m-d',$viptime));
    $today = strtotime(date('Y-m-d'));
    if($viptime < $today){
        return 0;
    }else{
        return (int)ceil(($viptime - $today) / 86400);
    }
}*/

function getVipTime($viptime)
{
    $today = time();
    if ($viptime <= $today) {
        return 0;
    } else {
        return ceil(($viptime - $today) / 86400);
    }
}

//获取VIP时长
function getVipDay($day = 0)
{
    return strtotime(date('Y-m-d H:i:s', time() + (86400 * $day)));
}

//计算百分比
function calcPercentage($total, $num)
{
    return ($total / 100) * $num;
}

//加密
function id_encode($id)
{
    $key = config('app.secret_key');
    return urlsafe_b64encode2(think_encrypt2($id, $key));
}

//id解密
function id_decode($id)
{
    $key = config('app.secret_key');
    $id = think_decrypt2(urlsafe_b64decode2($id), $key);
    if (empty($id)) {
        return [];
    }
    return ['id' => $id];
}

//url安全的base64加密
function urlsafe_b64encode2($string)
{
    $data = base64_encode($string);
    $data = str_replace(array('+', '/', '='), array('-', '_', ''), $data);
    return $data;
}

//url安全的base64解密
function urlsafe_b64decode2($string)
{
    $data = str_replace(array('-', '_'), array('+', '/'), $string);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    return base64_decode($data);
}

//数据加密
function think_encrypt2($data, $key = '', $expire = 0)
{
    $key = md5(empty($key) ? '' : $key);
    $data = base64_encode($data);
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = '';
    for ($i = 0; $i < $len; $i++) {
        if ($x == $l)
            $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }
    $str = sprintf('%010d', $expire ? $expire + time() : 0);
    for ($i = 0; $i < $len; $i++) {
        $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1))) % 256);
    }
    return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($str));
}

//数据解密
function think_decrypt2($data, $key = '')
{
    $key = md5(empty($key) ? '' : $key);
    $data = str_replace(array('-', '_'), array('+', '/'), $data);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    $data = base64_decode($data);
    $expire = substr($data, 0, 10);
    $data = substr($data, 10);
    if ($expire > 0 && $expire < time()) {
        return '';
    }
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = $str = '';
    for ($i = 0; $i < $len; $i++) {
        if ($x == $l)
            $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }
    for ($i = 0; $i < $len; $i++) {
        if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        } else {
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return base64_decode($str);
}


//生成代理前台访问地址
function agent_url($id, $path, $vars = [])
{
    $key = id_encode($id);
    $url = empty($path) ? '/s/' . $key : '/s/' . $key . '/' . $path;
    if (empty($vars)) {
        return $url;
    } else {
        return $url . '?' . urldecode(http_build_query($vars));
    }
}


//随机获取入口域名
function entryDomain()
{
    $map = [];
    $map['wx_status'] = 1;
    $map['qq_status'] = 1;
    $map['dy_status'] = 1;
    $domain = db('domain')
        ->where('type', 0)
        ->where($map)
        ->where('status', 1)
        ->orderRand()
        ->field('domain,is_all')
        ->find();
    if ($domain['is_all'] == 1) {
        $domain['domain'] = getDomainPrefix() . '.' . $domain['domain'];
    }
    return empty($domain) ? [] : $domain;
}

//随机获取中转链接
function jumpDomain($type, $id = '')
{
    $key = request()->param('key');
    $map = [];
    if (isWx()) {
        $map['wx_status'] = 1;
    }
    if (isQQ()) {
        $map['qq_status'] = 1;
    }
    if (isDy()) {
        $map['dy_status'] = 1;
    }
    $domain = db('domain')
        ->where('type', 1)
        ->where($map)
        ->where('status', 1)
        ->orderRand()
        ->field('domain,is_all')
        ->find();
    if (empty($domain)) {
        return false;
    } else {
        if ($domain['is_all'] == 1) {
            $prefix = getDomainPrefix() . '.' . $domain['domain'];
        } else {
            $prefix = $domain['domain'];
        }
        if (empty($id)) {
            return 'http://' . $prefix . '/s/' . $key . '/jump?t=' . $type;
        } else {
            return 'http://' . $prefix . '/s/' . $key . '/jump?t=' . $type . '&id=' . $id;
        }
    }
}

// 获取落地网址
function lastDomain($type, $id = '')
{
    $key = request()->param('key');
    $map = [];
    if (isWx()) {
        $map['wx_status'] = 1;
    }
    if (isQQ()) {
        $map['qq_status'] = 1;
    }
    if (isDy()) {
        $map['dy_status'] = 1;
    }
    $domain = db('domain')
        ->where('type', 2)
        ->where($map)
        ->where('status', 1)
        ->orderRand()
        ->field('domain,is_all')
        ->find();
    if (empty($domain)) {
        return false;
    } else {
        if ($domain['is_all'] == 1) {
            $prefix = getDomainPrefix() . '.' . $domain['domain'];
        } else {
            $prefix = $domain['domain'];
        }
        if (empty($id)) {
            return 'http://' . $prefix . '/s/' . $key;
        } else {
            return 'http://' . $prefix . '/s/' . $key . '/' . $type . '_detail?id=' . $id;
        }

    }
}


//生成前台访问地址
function iurl($path = '', $vars = [])
{
    $key = request()->param('key');
    $url = empty($path) ? '/s/' . $key : '/s/' . $key . '/' . $path;
    if (empty($vars)) {
        return $url;
    } else {
        return $url . '?' . urldecode(http_build_query($vars));
    }
}

//获取随机可用域名
function getDomain($type)
{
    $map = [];
    if (isWx()) {
        $map['wx_status'] = 1;
    }
    if (isQQ()) {
        $map['qq_status'] = 1;
    }
    if (isDy()) {
        $map['dy_status'] = 1;
    }
    $domain = db('domain')
        ->where('type', $type)
        ->where($map)
        ->where('status', 1)
        ->orderRand()
        ->field('domain,is_all')
        ->find();
    return empty($domain) ? [] : $domain;
}

//获取可用域名
function getKYDomain()
{
    $map = [];
    $map['qq_status'] = 1;
    $map['wx_status'] = 1;
    $map['dy_status'] = 1;
    $domain = db('domain')
        ->where($map)
        ->where('status', 1)
        ->field('domain,is_all')
        ->find();
    return empty($domain) ? [] : $domain;
}

//获取随机域名前缀
function getDomainPrefix()
{
    $charlist = 'qwertyuiopasdfghjklzxcnmm';
    return substr(str_shuffle($charlist), 0, 5);
}

//获取总推广链接
function getPublicUrl($uid, $type = 0)
{
    $domain = getDomain($type);
    if (empty($domain)) {
        return false;
    } else {
        if ($domain['is_all'] == 1) {
            $prefix = getDomainPrefix() . '.' . $domain['domain'];
        } else {
            $prefix = $domain['domain'];
        }
        $ld = '';
        if ($type == 1) {
            $ld = '?t=Z' . getDomainPrefix();
        }
        if ($type == 2) {
            $ld = '?t=L' . getDomainPrefix();
        }
        $keys = id_encode($uid);
        return 'http://' . $prefix . '/s/' . $keys . '/entry' . $ld;
    }
}

//获取单独推广链接
function getPublicShare($uid, $type = 0, $model = 0, $link_id = 0)
{
    $domain = getDomain($type);
    if (empty($domain)) {
        return false;
    } else {
        if ($domain['is_all'] == 1) {
            $prefix = getDomainPrefix() . '.' . $domain['domain'];
        } else {
            $prefix = $domain['domain'];
        }
        $ld = '';
        if ($type == 1) {
            $ld = '&t=Z' . getDomainPrefix();
        }
        if ($type == 2) {
            $ld = '&t=L' . getDomainPrefix();
        }
        $keys = id_encode($uid);
        return 'http://' . $prefix . '/s/' . $keys . '/share?type=' . $model . '&id=' . $link_id . $ld;
    }
}

//是否qq访问
function isQQ()
{
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'QQ') !== false) {
        if (strpos($_SERVER['HTTP_USER_AGENT'], '_SQ_') !== false) {
            return true;  //QQ内置浏览器
        } else {
            return true;  //QQ浏览器
        }
    }
    return false;
}

//是否微信访问
function isWx()
{
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
        return true;
    } else {
        return false;
    }
}

//是否抖音访问
function isDy()
{
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'aweme') !== false) {
        return true;
    } else {
        return false;
    }
}

//获取用户上级
function getUserTop($uid)
{
    $tops = [];
    //获取1级上级
    $a_pid = db('agent')->where('id', $uid)->value('pid');
    if (!empty($a_pid)) {
        $tops['lv1'] = $a_pid;
        //获取二级上级
        $b_pid = db('agent')->where('id', $a_pid)->value('pid');
        if (!empty($b_pid)) {
            $tops['lv2'] = $b_pid;
            //获取三级上级
            $c_pid = db('agent')->where('id', $b_pid)->value('pid');
            if (!empty($c_pid)) {
                $tops['lv3'] = $c_pid;
            }
        }
    }
    return $tops;
}

//获取用户上级提成
function getUserTopMoney($uid, $money)
{
    $tops = getUserTop($uid);
    $shouxu = config('web.ag_shouxu');
    $lv1 = config('web.ag_lv1');
    $lv2 = config('web.ag_lv2');
    $lv3 = config('web.ag_lv3');
    //手续费
    $shouxu_money = calcPercentage((float) $money, (float) $shouxu);
    //实际收入
    $user_money = round((float) $money - $shouxu_money, 2);
    //记录
    $agent = [];
    //计算1级代理
    foreach ($tops as $key => $item) {
        if (isset($tops[$key])) {
            $agent[] = [
                'id' => $tops[$key],
                'money' => round(calcPercentage((float) $money, (float) ${$key}), 2)
            ];
        }
    }
    //返回结果
    return $agent;
}

//获取单个上级分成
function getUserTopMoneyFx($money, $lv)
{
    $shouxu = config('web.ag_lv' . $lv);
    return round(calcPercentage((float) $money, (float) $shouxu), 2);
}

//获取手续费
function getUserTrueMoney($money, $ptfei)
{
    $shouxu = config('web.ag_shouxu');
    if ($ptfei > 0) {
        return round(calcPercentage((float) $money, (float) $ptfei), 2);
    } else {
        return round(calcPercentage((float) $money, (float) $shouxu), 2);
    }
}

//保存用户购买信息
function setUserBuyLog($id, $uid, $type)
{
    db('user_buy')->insert([
        'uid' => $uid,
        'type' => $type,
        'link_id' => $id
    ]);
}

//自动开通VIP
function setUserVIP($uid, $type, $long_type)
{
    $openLongTime = 0;
    //开通时长
    $long = ['day' => 1, 'week' => 7, 'month' => 30];
    $openLongTime = getVipDay(isset($long[$long_type]) ? $long[$long_type] : 0);
    if ($openLongTime == 0)
        return false;
    //开通这个傻逼VIP
    $vip_type = $type ? 'xs_vip' : 'sp_vip';
    db('user')->where('id', $uid)->setField($vip_type, $openLongTime);
    return true;
}


/**
 * 修改扩展配置文件.
 *
 * @param string $file 配置文件名(不需要后辍)
 * @param array  $arr  需要更新或添加的配置
 *
 * @return bool
 */
function config_set($file = '', $arr = [])
{
    if (is_array($arr)) {
        // 文件路径
        $filepath = Env::get('config_path') . $file . '.php';
        // 检测是否存在,不存在新建
        if (!file_exists($filepath)) {
            $conf = '<?php return [];';
            file_put_contents($filepath, $conf);
        }
        // 添加配置项
        $conf = include $filepath;
        foreach ($arr as $key => $value) {
            $conf[$key] = $value;
        }
        // 修改配置项
        $str = "<?php\r\nreturn [\r\n";
        foreach ($conf as $key => $value) {
            // dump(gettype($value));
            switch (gettype($value)) {
                case 'string':
                    $str .= "\t'$key' => '$value'," . "\r\n";
                    break;
                case 'number':
                    $str .= "\t'$key' => $value," . "\r\n";
                    break;
                case 'boolean':
                    $str .= "\t'$key' => " . ($value ? 'true' : 'false') . "," . "\r\n";
                    break;
                default:
                    # code...
                    break;
            }
        }
        $str .= '];';
        // 写入文件
        // dump($str);exit;
        file_put_contents($filepath, $str);

        return true;
    } else {
        return false;
    }
}

function searchArray($array, $key, $value)
{
    foreach ($array as $keyp => $valuep) {
        if ($valuep[$key] == $value) {
            return true;
        }
    }
    return false;
}



/**
 * 聚龙支付---支付类型，h5或扫码
 * @return string
 */
function payType()
{
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
        return "qrcode";
    } else {
        return "wap";
    }
}

/**
 * 聚龙支付---请求方法
 */
function curl($url, $post_data)
{
    $ch = curl_init();
    $header = [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($post_data)
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

/**
 * 聚龙支付---签名方法
 */
function markSign($paydata, $signkey)
{
    ksort($paydata);
    $str = '';
    foreach ($paydata as $k => $v) {
        if ($k != "sign" && $v != "") {
            $str .= $k . "=" . $v . "&";
        }
    }
    return strtoupper(md5($str . "key=" . $signkey));
}

/**
 * 聚龙支付---表单跳转模式
 * $url 地址
 * $data 数据,支持数组或字符串，可留空
 * $target 是否新窗口提交，默认关闭
 */
function jumpPost($url, $data)
{
    $html = "<form id='form' name='form' action='" . $url . "' method='post'>";
    if (!empty($data)) {
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                $html .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
            }
        } else {
            $html .= "<input type='hidden' name='value' value='" . $data . "'/>";
        }
    }
    $html .= "</form>";
    $html .= "<script>document.forms['form'].submit();</script>";
    exit($html);
}

/**
 * 格式化视频URL
 * @param string $url 视频URL
 * @return string 
 */
function format_video_url($url)
{
    if (empty($url)) {
        return $url;
    }

    if (mb_strlen($url) < 10 && strval($url) === strval(intval($url))) {
        return get_file_path($url);
    }

    // 如果URL不包含 http:// 或 https:// 前缀
    if (stripos($url, 'http://') === false && stripos($url, 'https://') === false) {
        // 从配置中获取m3u8_domain
        $m3u8_domain = config('web.m3u8_domain');
        if (!empty($m3u8_domain)) {
            // 拼接域名和URL
            return rtrim($m3u8_domain, '/') . '/' . ltrim($url, '/');
        }

        // 如果没有配置m3u8_domain，仍然尝试使用get_file_path
        return get_file_path($url);
    }

    return $url;
}