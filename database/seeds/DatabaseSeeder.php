<?php

use Illuminate\Database\Seeder;

/**
 * Class DatabaseSeeder
 * 为了测试jwt功能，我得注册几个用户吧？
 * 最快的方式就是使用seeder了。
 * 需要注意的是，密码是如何加密的。
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 命令行执行
     * ···
     *  php artisan db:seed
     * ···
     * @return void
     */
    public function run()
    {
        //这里参数是类名，需要提前定义，定义类名也是php artisan
         $this->call('UsersTableSeeder');
    }
}
