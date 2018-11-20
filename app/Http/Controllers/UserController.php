<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    private $salt;

    public function __construct()
    {
        $this->salt="userloginregister";
    }

    /**
     *  https://www.cnblogs.com/duanweishi/p/6151721.html
     * 根据 凭证 完成登陆（登陆成功给出token)
     * Get a token given credentials.
     * 登陆方法是不需要走认证中间件的
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        
        if ($request->has('name') && $request->has('password')) {
            $name = $request->input('name');
            $password = $request->input('password');
            //attempt方法到底是否可以用，跟guard有关，lumen的api验证guard是RequestGuard，它没有。
            //SessionGuard是有这个方法的。
//            $token = Auth::attempt(['name'=>$name,'password'=>$password]);
            
            $user = User:: where("name",$name)
                //这里没有使用系统自带的加密算法，而是直接在这里写出sha1算法
                ->where("password", "=", sha1($this->salt.$password))
                ->first();

            if ($user) {
                //token每次登陆成功后都是随机生成
                $token=str_random(32);
                $user->api_token=$token;
                $user->save();
                return $user->api_token;
            } else {
                return "用户名或密码不正确，登录失败！";
            }
        } else {
            return "登录信息不完整，请输入用户名和密码登录！";
        }

        
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return Auth::user();
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * 注册
     * @param Request $request
     * @return string
     */
    public function register(Request $request)
    {
        if ($request->has('name') && $request->has('password') && $request->has('email')) {
            $user = new User;
            $user->name=$request->input('name');
            //注册加密和登陆加密要一致
            $user->password=sha1($this->salt.$request->input('password'));
            $user->email=$request->input('email');
//            $user->api_token=str_random(32);
            if($user->save()){
                return "用户注册成功！";
            } else {
                return "用户注册失败！";
            }
        } else {
            return "请输入完整用户信息！";
        }
    }
}