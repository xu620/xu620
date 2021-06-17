<?php
/**
 * +----------------------------------------------------------------------
 * | [ WE CAN DO IT MORE SIMPLE]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2016-2020 All rights reserved.
 * +----------------------------------------------------------------------
 * | Author: Yida
 * +----------------------------------------------------------------------
 * | DateTime: 2021/01/18 1:29
 * +----------------------------------------------------------------------
 */
use think\facade\Route;

Route::group('api', function() {
    Route::get('/', 'api/IndexController/index');

    // 验证码
    Route::group('sms', function(){
        Route::post('sendCode','sendCode');
        Route::post('checkCode','checkCode');

    })->prefix('api/SmsController/');

    // 公共管理
    Route::group('common', function(){
        Route::get('init','init');
        Route::post('upload','upload');

    })->prefix('api/CommonController/');

    // 首页管理
    Route::group('index',function(){
        Route::get('index','index');
        Route::get('test','test');
        Route::get('test1','test1');

    })->prefix('api/IndexController/');


    // 账号管理
    Route::group('account', function (){
        Route::post('login','login');
        Route::post('register','register');
        Route::post('logout','logout');
        Route::post('resetPwd','resetPwd');
        Route::post('changeMobile','changeMobile');

    })->prefix('api/AccountController/');

    // 会员中心管理
    Route::group('user', function (){
        Route::get('index','index');


    })->prefix('api/UserController/');


    // 内容管理
    Route::group('article',function() {
        Route::get('userAgreement','detail')->append(['id'=>1]);
        Route::get('privacy','detail')->append(['id'=>2]);
        Route::get('aboutUs','detail')->append(['id'=>3]);
        Route::get('recharge','detail')->append(['id'=>4]);
        Route::get('member','detail')->append(['id'=>5]);
        Route::get('noviceGuide','noviceGuide');
        Route::get('detail','detail')->append(['show_title' => true]);

    })->prefix('api/ArticleController/');


})->middleware('Check');