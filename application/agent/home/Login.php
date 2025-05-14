<?php

namespace app\agent\home;

class Login extends Base{

    public function index(){
        if($this->request->isPost()){
            $data = $this->request->post();
            $result = $this->validate($data,[
                'username' => 'require',
                'password' => 'require'
            ],[
                'username.require' => '请输入用户名',
                'password.require' => '密码不能为空'
            ]);
            if($result !== true){
                $this->error($result);
            }

            if (config('captcha_signin')) {
                $captcha = $this->request->post('captcha', '');
                $captcha == '' && $this->error('请输入验证码');
                if(!captcha_check($captcha, '')){
                    //验证失败
                    $this->error('验证码错误或失效');
                }
            }

            $info = db('agent')->where('username',$data['username'])->find();

            if(empty($info)){
                $this->error('账号不存在');
            }

            if($info['status'] != 1){
                $this->error('账号已被管理员禁止');
            }

            if(md5($info['password']) !== md5($data['password'])){
                $this->error('密码不正确');
            }

            db('agent')->where('id',$info['id'])->setField('update_time',time());
            
            session('agent_uid',$info['id']);
            $this->success('登录成功','Index/index');
        }
        return $this->fetch();
    }

    public function logout(){
        session('agent_uid',null);
        $this->success('退出登录成功','Login/index');
    }

}