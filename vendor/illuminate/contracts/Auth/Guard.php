<?php

namespace Illuminate\Contracts\Auth;

interface Guard
{
    /**
     * Determine if the current user is authenticated.
     * 是否是认证的（认证就是确定对方是谁在访问，这里其实就是是否登陆状态）
     * @return bool
     */
    public function check();

    /**
     * Determine if the current user is a guest.
     * 当前用户非登陆（未登陆就不知道是谁，就是未认证）
     * @return bool
     */
    public function guest();

    /**
     * Get the currently authenticated user.
     * 当前登陆用户
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user();

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|null
     */
    public function id();

    /**
     * Validate a user's credentials.
     * 验证用户的凭证（就是看看它的密码对不对，多半在登陆时使用）
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = []);

    /**
     * Set the current user.
     * 设置认证用户（用户既要继承Model,又有实现Authenticatable接口）
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    public function setUser(Authenticatable $user);
}
