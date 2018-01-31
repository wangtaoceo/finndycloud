<?php
namespace app\admin\controller;
use think\Session;
use think\Db;

class User extends Base
{

    public function Index()
    {
        $title = '我的面板';
        $this->setPageSeo();
        $params['op'] = 'getuserinfo';
        $params['uid'] = $this->getSysConfValue('app_key');
        $res = api_request('get' ,api_build_url('api.php',$params));
        $finndy_info = check_api_result($res);
        if(!empty($finndy_info['robotversion'])){
            $params['op'] = 'getversiontolv';
            $params['version'] = $finndy_info['robotversion']; //$userinfo['finndy_uid'];
            $res_lv = api_request('get' ,api_build_url('api.php',$params));
            $result_lv = check_api_result($res_lv);
        }
        $this->assign('finndy',$finndy_info);
        $this->assign('lv',$result_lv);
        return $this->fetch('index',['title'=>$title]);
    }

    public function Resetpwd()
    {
        $title = '修改密码';
        if(request()->isPost()){
            $postdata = input();
            if(empty($postdata['set_oldpass']) || empty($postdata['set_newpass']) || empty($postdata['set_okpass'])){$this->error('密码不能为空!');}
            if($postdata['set_newpass'] != $postdata['set_okpass']){$this->error('新密码两次输入不一致!');}
            if(strlen($postdata['set_okpass'])<6){$this->error('密码长度至少6位!');}

            $userinfo = Db::name('users')->where(['username'=>Session::get('username'),'password'=>passwordencrypt($postdata['set_oldpass'])])->find();
            if(empty($userinfo)){$this->error('原始密码错误!');}

            $updage_res = Db::name('users')->where('uid',$userinfo['uid'])->setField('password',passwordencrypt($postdata['set_okpass']));
            if(!$updage_res){
                $this->error('网络原因稍后再试!');
            }
            $this->success('密码修改成功,请重新登录','login/loginout');

        }
        return view('resetpwd',['title'=>$title]);
    }
    public function UserList()
    {
        $title = '用户列表';
        $userlist = Db::name('users')->select();
        $this->assign('title',$title);
        $this->assign('userlist',$userlist);
        return $this->fetch('userlist');
    }

    //增加用户
    public function AddUser()
    {
        if(request()->isPost()) {
            $data = input();
            $validate = $this->validate($data, 'User.AddUser');
            if (true !== $validate) {
                // 验证失败 输出错误信息
                $this->error($validate);
            }
            $data['password'] = passwordencrypt($data['password']);
            $user_info = Db::name('users')->where('username',$data['username'])->find();
            if($user_info){
                $this->error('用户名已存在!');
            }
            $data['create_time'] = time();
            $inser_res = Db::name('users')->insert($data);
            if(!$inser_res){
                $this->error('网络原因稍后再试!');
            }
            $this->success('添加成功','user/userlist');
        }
    }
    //修改用户
    public function EditUser()
    {
        if(request()->isPost()) {
            $data = input();
            $uid = $data['uid'];
            unset($data['uid']);
            if(empty($uid)){$this->error('参数错误!');}
            $password = $data['password'];
            if(empty($password)){$data['password'] = 123456;} //默认填充密码，提交时不修改

            $validate = $this->validate($data, 'User.AddUser');
            if (true !== $validate) {
                // 验证失败 输出错误信息
                $this->error($validate);
            }
            if(empty($password)){
                unset($data['password']); //剔除默认密码，不提交
            }else{
                $data['password'] = passwordencrypt($password);
            }


            $inser_res = Db::name('users')->where('uid',$uid)->update($data);
            if(!$inser_res){
                $this->error('网络原因稍后再试!');
            }
            $this->success('修改成功','user/userlist');
        }
    }

    //禁用用户
    public function BanUser()
    {

        $data = input();
        $uid = $data['uid'];
        $status = $data['status'];
        if(strlen($status) != 1 || !intval($status)){$this->error('参数错误!');}
        if($status  != 0){
            $status = 0;
        }else{
            $status = 1;
        }
        if(empty($uid)){$this->error('参数错误!');}
        $status_res = Db::name('users')->where('uid',$uid)->setField('status',$status);
        if(!$status_res){
            $this->error('网络原因稍后再试!');
        }
        $this->success('修改成功','user/userlist');


    }




}
