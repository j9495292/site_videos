<?php
// +----------------------------------------------------------------------
// | 小白支付处理类
// +----------------------------------------------------------------------

namespace app\common\library\payment;

/**
 * 小白支付处理类
 * 封装小白支付相关功能，降低代码耦合性
 */
class XiaoBBPay
{
    /**
     * 配置信息
     * @var array
     */
    protected $config = [];

    /**
     * 支付请求URL
     * @var string
     */
    protected $payUrl = 'http://pay.xiaobb.cfd/api/pay/create_order';

    /**
     * 订单查询URL
     * @var string
     */
    protected $queryUrl = 'http://pay.xiaobb.cfd/api/pay/query_order';

    /**
     * 构造函数，初始化配置信息
     * @param array $config 配置信息，为空则自动从系统配置获取
     */
    public function __construct($config = [])
    {
        if (!empty($config)) {
            $this->config = $config;
            return;
        }
        // 从系统配置获取
        $sysConfig = config('web.');
        $this->config = [
            'status' => isset($sysConfig['pay_xiaobb_status']) ? $sysConfig['pay_xiaobb_status'] : 'off',
            'mchId' => $sysConfig['pay_xiaobb_mchid'] ?? '',
            'productId' => $sysConfig['pay_xiaobb_productid'] ?? '',
            'key' => $sysConfig['pay_xiaobb_key'] ?? '',
        ];
    }

    /**
     * 创建支付订单
     * @param string $orderNo 商户订单号
     * @param float $amount 支付金额，单位元
     * @param string $notifyUrl 异步通知URL
     * @param string $returnUrl 同步跳转URL
     * @param array $extraParams 额外参数
     * @return string 跳转URL
     * @throws \Exception 支付异常
     */
    public function createOrder($orderNo, $amount, $notifyUrl, $returnUrl, $extraParams = [])
    {
        // 检查支付是否开启
        if ($this->config['status'] != 'on') {
            throw new \Exception('支付功能未开启');
        }

        // 构建支付请求参数
        $payParams = [
            'mchId' => $this->config['mchId'],
            'productId' => $this->config['productId'],
            'mchOrderNo' => $orderNo,
            'amount' => intval($amount * 100), // 转换为分
            'notifyUrl' => $notifyUrl,
            'returnUrl' => $returnUrl,
        ];

        // 合并额外参数
        if (!empty($extraParams)) {
            $payParams = array_merge($payParams, $extraParams);
        }

        // 记录原始参数
        $this->log('请求参数', $payParams);

        // 生成签名
        $sign = $this->generateSign($payParams);
        $payParams['sign'] = $sign;

        // 记录签名结果和完整参数
        $this->log('签名字符串', "待签名字符串: " . $this->getSignString($payParams) . " 签名结果: " . $sign);
        $this->log('完整请求参数', $payParams);
        $result = $this->sendGetRequest($this->payUrl, $payParams);
        $this->log('请求结果', $result);
        $result = json_decode($result, true);
        if ($result['retCode'] !== "SUCCESS") {
            $this->log('创建支付订单失败', $result['retMsg']);
            throw new \Exception('创建支付订单失败');
        }
        return $result['payParams']['payUrl'];
    }

    /**
     * 验证回调签名
     * @param array $params 回调参数
     * @return bool 验证结果
     */
    public function verifySign($params)
    {
        if (empty($params) || !isset($params['sign'])) {
            return false;
        }

        $sign = $params['sign'];
        $calcSign = $this->generateSign($params);

        return $sign === $calcSign;
    }

    /**
     * 处理支付回调
     * @param array $params 回调参数
     * @return array 处理结果 ['code' => 1, 'msg' => '成功', 'data' => []]
     */
    public function handleNotify($params)
    {
        // 记录回调参数
        $this->log('回调参数', $params);

        // 验证必要参数
        if (empty($params['mchOrderNo'])) {
            return ['code' => -1, 'msg' => '商户订单号为空'];
        }

        // 验证签名
        if (!$this->verifySign($params)) {
            $this->log('验证失败', '签名错误');
            return ['code' => -2, 'msg' => '签名验证失败'];
        }

        // 验证支付状态
        if (isset($params['status']) && $params['status'] == 2) {
            // 支付成功
            return [
                'code' => 1,
                'msg' => '支付成功',
                'data' => [
                    'trade_no' => $params['mchOrderNo'],
                    'out_trade_no' => $params['payOrderId'],
                    'amount' => $params['amount'] / 100, // 转换为元
                    'status' => $params['status'],
                    'pay_time' => $params['paySuccTime'] ?? 0
                ]
            ];
        }
        return ['code' => 0, 'msg' => '支付未完成'];
    }

    /**
     * 查询订单状态
     * @param string $orderNo 商户订单号
     * @return array 订单信息
     * @throws \Exception 查询异常
     */
    public function queryOrder($orderNo)
    {
        // 检查支付是否开启
        if ($this->config['status'] != 'on') {
            throw new \Exception('支付功能未开启');
        }

        // 构建查询参数
        $queryParams = [
            'mchId' => $this->config['mchId'],
            'mchOrderNo' => $orderNo
        ];

        // 生成签名
        $sign = $this->generateSign($queryParams);
        $queryParams['sign'] = $sign;

        // 发送查询请求 (使用 GET)
        $result = $this->sendGetRequest($this->queryUrl, $queryParams);

        return json_decode($result, true);
    }

    /**
     * 生成签名
     * @param array $params 参数数组
     * @return string 签名结果
     */
    public function generateSign($params)
    {
        // 1. 去除不参与签名的参数
        if (isset($params['sign'])) {
            unset($params['sign']);
        }

        // 2. 排序
        ksort($params);

        // 3. 拼接字符串
        $stringA = '';
        foreach ($params as $k => $v) {
            if ($v !== '' && $v !== null) {
                $stringA .= $k . '=' . $v . '&';
            }
        }

        // 4. 拼接key
        $stringSignTemp = $stringA . 'key=' . $this->config['key'];

        // 5. MD5运算并转换为大写
        return strtoupper(md5($stringSignTemp));
    }

    /**
     * 获取签名字符串，用于调试
     * @param array $params 参数数组
     * @return string 签名字符串
     */
    public function getSignString($params)
    {
        // 去除sign参数
        if (isset($params['sign'])) {
            unset($params['sign']);
        }

        // 排序
        ksort($params);

        // 拼接字符串
        $stringA = '';
        foreach ($params as $k => $v) {
            if ($v !== '' && $v !== null) {
                $stringA .= $k . '=' . $v . '&';
            }
        }

        // 拼接key
        return $stringA . 'key=' . $this->config['key'];
    }

    /**
     * 发送POST请求
     * @param string $url 请求URL
     * @param array $data 请求数据
     * @return string 响应结果
     */
    protected function sendPostRequest($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    /**
     * 发送GET请求
     * @param string $url 请求URL
     * @param array $data 请求数据
     * @return string 响应结果
     */
    protected function sendGetRequest($url, $data)
    {
        $queryString = http_build_query($data);
        $fullUrl = $url . '?' . $queryString;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 根据需要设置
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 根据需要设置
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    /**
     * 记录日志
     * @param string $type 日志类型
     * @param array|string $data 日志数据
     */
    protected function log($type, $data)
    {
        $logFile = app()->getRuntimePath() . 'log/xiaobb_pay/' . date('Ymd') . '.log';
        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = "[" . date('Y-m-d H:i:s') . "] [{$type}] " .
            (is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data) .
            PHP_EOL;

        file_put_contents($logFile, $content, FILE_APPEND);
    }
}