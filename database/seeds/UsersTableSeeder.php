<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $id = DB::table('users')->insertGetId([
            'name'      =>  'Leighton Zaddock',
            'username'  =>  'lzaddock',
            'email'     =>  'admin@admin.com',
            'password'  =>  bcrypt('password'),
            'status'    =>  true,
            'created_at'=>  now(),
            'updated_at'=>  now(),
        ]);
        DB::table('user_roles')->insert([
            'user_id'       =>  $id,
            'access_name'   =>  'users',
            'access_value'  =>  serialize(['add','update','delete'])
        ]);
        DB::table('user_roles')->insert([
            'user_id'       =>  $id,
            'access_name'   =>  'super_admin',
            'access_value'  =>  '1'
        ]);
        $id = DB::table('users')->insertGetId([
            'name'      =>  'Timon Wasilwa',
            'username'  =>  'twasilwa',
            'email'     =>  'timwasilwa2013@gmail.com',
            'password'  =>  bcrypt('khaemba1994'),
            'status'    =>  true,

        ]);
        DB::table('user_roles')->insert([
            'user_id'       =>  $id,
            'access_name'   =>  'users',
            'access_value'  =>  serialize(['add','update','delete'])
        ]);
        DB::table('user_roles')->insert([
            'user_id'       =>  $id,
            'access_name'   =>  'super_admin',
            'access_value'  =>  '1'
        ]);



    }
}
