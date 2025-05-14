<?php

namespace app\index\controller;

use think\Controller;

class Callback extends Controller
{

    /**
     * 小白支付 - 异步回调
     */
    public function notify()
    {
        // 实例化小白支付类
        $xiaoBBPay = new \app\common\library\payment\XiaoBBPay();

        // 接收回调参数
        $notifyData = input();

        // 处理回调数据
        $result = $xiaoBBPay->handleNotify($notifyData);

        // 处理结果
        if ($result['code'] != 1) { // 支付成功
            echo $result['msg']; // 返回失败原因
        }

        // 查询订单
        $order = db('order')->where('trade_no', $result['data']['trade_no'])->where('status', 0)->find();
        if (empty($order)) {
            echo "order_not_exist";
            exit;
        }

        // 验证金额
        $orderAmount = intval($order['money'] * 100); // 转换为分
        if ($orderAmount != $notifyData['amount']) {
            echo "amount_error";
            exit;
        }

        // 更新订单状态等相关处理
        if ($order['is_kouliang'] != 1) {
            $agentTop = getUserTopMoney($order['uid'], $order['money']);
            // 添加代理余额
            foreach ($agentTop as $key => $item) {
                db('agent')->where('id', $item['id'])->setInc('money', $item['money']);
            }
            $user_money = round($order['money'] - $order['ticheng'], 2);
            db('agent')->where('id', $order['uid'])->setInc('money', $user_money);
        }

        // 支付完成，更新订单状态
        db('order')->where('id', $order['id'])->update([
            'status' => 1,
            'out_trade_no' => $result['data']['out_trade_no'],
            'update_time' => time()
        ]);

        die('success');
    }

    /**
     * 小白支付 - 同步回调
     */
    public function return()
    {
        // 接收参数
        $order_no = input('order_no', '');
        $order = db('order')->where('trade_no', $order_no)->find();

        if (empty($order)) {
            $this->error('订单不存在或已过期');
        }
        switch ($order['type']) {
            case 'buy':
                $goto_type = 'buy';
                $url = agent_url($order['uid'], $order['model'] . '_detail', ['id' => $order['link_id'], 'ts_login' => 1]);
                break;
            default:
                $goto_type = 'vip';
                $url = agent_url($order['uid'], 'register');
                break;
        }
        $this->assign('type', $goto_type);
        $this->assign('url', $url);
        return $this->fetch('return');
    }
}