<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * ```
     *  php artisan db:seed --class=UsersTableSeeder
     * ```
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'id' => '2',
            'name' => 'David',
            'email' => 'David@qq.com',
            //lumen默认的用户加密方式BcryptHasher就是它，所以
            'password' => password_hash('David',PASSWORD_BCRYPT,['cost'=>10]),
        ]);
    }
}
