<?php

namespace app\common\model;

use think\Exception;

class Curl {

    //单例对象
    private static $ins = null;

    //请求结果
    private $body = null;

    //cookie文件
    private $cookieFile = null;

    //支持的请求方法
    private $method = array('get','post');

    //禁用初始化方法
    final private function __construct()
    {
    }

    public static function exec()
    {
        if (self::$ins) {
            return self::$ins;
        }
        return self::$ins = new self();
    }


    public function __clone()
    {
        throw new Exception('错误：不能克隆对象');
    }


    public function __call($method, $args)
    {
        if(!in_array($method,$this->method)) {
            throw new Exception("错误:不支持{$method}方法,支持的方法有"
                . join(',',$this->method));
        }
        return $this->request($method, $args);
    }

    public function body()
    {
        return $this->body;
    }

    private function request($method, $args)
    {

        if (isset($args[1]['multi'])) {
            $this->body = $this->multiExecCurl($method, $args);
        } else {
            $this->body = $this->execCurl($method, $args);
        }
        return $this;
    }

    public function multiExecCurl($method, $args)
    {
        $urls      = isset($args[0])            ? $args[0]            : "";
        $data      = isset($args[1]['data'])    ? $args[1]['data']    : "";
        $ajax      = isset($args[1]['ajax'])    ? $args[1]['ajax']    : "";
        $timeout   = isset($args[1]['timeout']) ? $args[1]['timeout'] : 30;
        $referer   = isset($args[1]['referer']) ? $args[1]['referer'] : "";
        $proxy     = isset($args[1]['proxy'])   ? $args[1]['proxy']   : "";
        $headers   = isset($args[1]['headers']) ? $args[1]['headers'] : "";

        if (!is_array($urls) || (is_array($urls) && empty($urls))) {
            throw new Exception("错误信息:批处理url必须是数组并且不能为空");
        }

        //创建批处理cURL句柄
        $queue   = curl_multi_init();

        //取得cookie文件路径
        if(!$this->cookieFile) {
            $this->cookieFile = isset($args[1]['cookie_file'])
                ? $args[1]['cookie_file'] : "";
        }

        //如果未获取到浏览器环境信息，就手动指定一个
        $userAgent = isset($_SERVER['HTTP_USER_AGENT'])
            ? $_SERVER['HTTP_USER_AGENT']
            : 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:23.0) '
            .'Gecko/20100101 Firefox/23.0';

        //设置CURL OPT选项
        $options = array(
            CURLOPT_TIMEOUT        => $timeout,  //超时
            CURLOPT_RETURNTRANSFER => 1,         //输出数据流
            CURLOPT_HEADER         => 0,         //禁止头文件数据流输出
            CURLOPT_FOLLOWLOCATION => 1,         //自动跳转追踪
            CURLOPT_AUTOREFERER    => 1,         //自动设置来路信息
            CURLOPT_SSL_VERIFYPEER => 0,         //认证证书检查
            CURLOPT_SSL_VERIFYHOST => 0,         //检查SSL加密算法
            CURLOPT_NOSIGNAL       => 1,         //忽略php所有的传递信号
            CURLOPT_USERAGENT      => $userAgent,//浏览器环境字符串
            CURLOPT_IPRESOLVE	   => CURL_IPRESOLVE_V4, //ipv4寻址方式
            CURLOPT_ENCODING       => 'gzip',    //解析使用gzip压缩的网页
        );

        //检测是否存在代理请求
        if (is_array($proxy) && !empty($proxy)) {

            $options[CURLOPT_PROXY]        = $proxy['host'];
            $options[CURLOPT_PROXYPORT]    = $proxy['port'];

            $options[CURLOPT_PROXYUSERPWD] =
                $proxy['user'] . ':' . $proxy['pass'];
        }

        //header选项
        $headerOptions = array();

        //模拟AJAX请求
        if ($ajax) {
            $headerOptions['X-Requested-With']  = 'XMLHttpRequest';
        }

        if ($this->cookieFile) {
            $options[CURLOPT_COOKIEFILE] = $this->cookieFile;
            $options[CURLOPT_COOKIEJAR]  =  $this->cookieFile;
        }

        if ($referer) {
            $options[CURLOPT_REFERER] = $referer;
        }

        if (!empty($headerOptions)) {
            $options[CURLOPT_HTTPHEADER] = $headerOptions;
        }

        //循环的进行初始化一个cURL会话
        foreach ($urls as  $k => $url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);

            if ($method == 'post') {
                //发送一个常规的POST请求，
                //类型为：application/x-www-form-urlencoded，就像表单提交的一样
                $options[CURLOPT_POST]       = 1;

                //使用HTTP协议中的"POST"操作来发送数据,支持键值对数组定义
                //注意：即使不使用http_build_query也能自动编码
                $options[CURLOPT_POSTFIELDS] = $data[$k];
            }

            curl_setopt_array($ch,$options);
            curl_multi_add_handle($queue, $ch);
        }

        //初始化变量
        $responses = array();
        $active    = null;

        //循环运行当前 cURL 句柄的子连接
        do {
            while (($code = curl_multi_exec($queue,
                    $active)) == CURLM_CALL_MULTI_PERFORM);

            if ($code != CURLM_OK) {
                break;
            }

            //循环获取当前解析的cURL的相关传输信息
            while ($done = curl_multi_info_read($queue)) {

                //获取最后一次传输的相关信息
                $info = curl_getinfo($done['handle']);

                //从最后一次传输的相关信息中找 http_code 等于200
                if ($info['http_code'] == 200) {
                    //如果设置了CURLOPT_RETURNTRANSFER，获取的输出的文本流
                    $responses[] = curl_multi_getcontent($done['handle']);
                }

                //移除curl批处理句柄资源中的某个句柄资源
                curl_multi_remove_handle($queue, $done['handle']);

                //关闭某个批处理句柄会话
                curl_close($done['handle']);
            }

            if ($active > 0) {
                //等待所有cURL批处理中的活动连接
                curl_multi_select($queue, 0.5);
            }

        } while ($active);

        //关闭一组cURL句柄
        curl_multi_close($queue);

        //返回结果
        return $responses;
    }

    private function execCurl($method, $args)
    {
        //解析参数
        $url       = isset($args[0])            ? $args[0]            : "";
        $multi     = isset($args[1]['multi'])   ? $args[1]['multi']   : "";
        $data      = isset($args[1]['data'])    ? $args[1]['data']    : "";
        $ajax      = isset($args[1]['ajax'])    ? $args[1]['ajax']    : "";
        $timeout   = isset($args[1]['timeout']) ? $args[1]['timeout'] : 30;
        $files     = isset($args[1]['files'])   ? $args[1]['files']   : "";
        $referer   = isset($args[1]['referer']) ? $args[1]['referer'] : "";
        $proxy     = isset($args[1]['proxy'])   ? $args[1]['proxy']   : "";
        $headers   = isset($args[1]['headers']) ? $args[1]['headers'] : "";

        //如果环境变量的浏览器信息不存在，就是用手动设置的浏览器信息
        $userAgent = isset($_SERVER['HTTP_USER_AGENT'])?
            $_SERVER['HTTP_USER_AGENT']:
            'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:23.0) 
                    Gecko/20100101 Firefox/23.0';

        //检测url必须参数 不能为空
        if (!$url) {
            throw new Exception("错误：curl请求地址不能为空");
        }

        //设置curl选项
        $options = array(
            CURLOPT_URL            => $url,      //目标url
            CURLOPT_TIMEOUT        => $timeout,  //超时
            CURLOPT_RETURNTRANSFER => 1,         //输出数据流
            CURLOPT_FOLLOWLOCATION => 1,         //自动跳转追踪
            CURLOPT_AUTOREFERER    => 1,         //自动设置来路信息
            CURLOPT_SSL_VERIFYPEER => 0,         //认证证书检查
            CURLOPT_SSL_VERIFYHOST => 0,         //检查SSL加密算法
            CURLOPT_HEADER         => 0,         //禁止头文件输出
            CURLOPT_NOSIGNAL       => 1,         //忽略所有传递的信号
            CURLOPT_USERAGENT      => $userAgent,//浏览器环境字符串
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4, //ipv4寻址方式
            CURLOPT_ENCODING       => 'gzip',    //解析使用gzip压缩的网页
        );

        //获取cookie文件地址路径
        if(!$this->cookieFile) {
            $this->cookieFile = isset($args[1]['cookie_file'])
                ? $args[1]['cookie_file'] : "";
        }

        //设置代理 必须是数组并且非空
        if (is_array($proxy) && !empty($proxy)) {
            $options[CURLOPT_PROXY]        = $proxy['host'];
            $options[CURLOPT_PROXYPORT]    = $proxy['port'];
            $options[CURLOPT_PROXYUSERPWD] =
                $proxy['user'] . ':' . $proxy['pass'];
        }

        //检测是否未启用自定义urlencode编码
        if (!isset($args[1]['build'])) {
            if ($data && $method == "post" && is_array($data)) {
                $data = http_build_query($data, '', '&');
            }
        }

        //检测是否含有上传文件
        if ($files && $method == "post" && is_array($files)) {
            foreach ($files as $k => $v) {
                $files[$k] = '@' . $v;
            }

            parse_str($data, $data);
            $data = $data + $files;
        }

        //检测判断是否是post请求
        if ($method == 'post') {
            //发送一个常规的POST请求
            $options[CURLOPT_POST]       = 1;

            //使用HTTP协议中的"POST"操作来发送数据,支持键值对数组定义
            $options[CURLOPT_POSTFIELDS] = $data;
        }

        //初始化header数组
        $headerOptions = array();

        //检测是否是ajax提交
        if ($ajax) {
            $headerOptions['X-Requested-With']  = 'XMLHttpRequest';
        }


        //设置cookie
        if ($this->cookieFile) {
            $options[CURLOPT_COOKIEFILE] = $this->cookieFile;
            $options[CURLOPT_COOKIEJAR]  = $this->cookieFile;
        }

        //设置来路
        if ($referer) {
            $options[CURLOPT_REFERER] = $referer;
        }

        //合并header
        if (!empty($headers) && is_array($headers)) {
            foreach ($headers as $k => $v) {
                $headerOptions[$k] = $v;
            }
        }

        //转换header选项为浏览器header格式
        if (!empty($headerOptions) && is_array($headerOptions)) {
            $array = array();

            foreach($headerOptions as $k => $v) {
                $array[] = $k . ": " . $v;
            }

            $options[CURLOPT_HTTPHEADER] = $array;
        }

        //创建curl句柄
        $ch = curl_init();

        //设置curl选项
        curl_setopt_array($ch, $options);

        //获取返回内容
        $content = curl_exec($ch);

        //关闭curl句柄
        curl_close($ch);

        //返回内容
        return $content;

    }

    public function __toString()
    {
        return $this->body();
    }

}