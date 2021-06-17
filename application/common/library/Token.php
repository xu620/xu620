<?php
/**
 * +----------------------------------------------------------------------
 * | [ WE CAN DO IT MORE SIMPLE]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2016-2020 All rights reserved.
 * +----------------------------------------------------------------------
 * | Author: Yida
 * +----------------------------------------------------------------------
 * | DateTime: 2021/01/25 11:27
 * +----------------------------------------------------------------------
 */

namespace app\common\library;


use Firebase\JWT\JWT;
use think\facade\Request;

class Token
{

    const secret = "q2iF0%TjYugu!CEVqoo@NHs5a8@t17YX";     //密匙
    const exp    = 3600*24*7;  //过期时间  7天

    /**
     * 创建token
     *
     * @param int $user_id  用户ID
     * @param string $access_token 随机字符串
     * @param int $keepTime Token默认有效时长
     *
     * @return string
     */
    public static function createToken($user_id, $access_token, $keepTime = 0)
    {
        $exp = $keepTime ? $keepTime : self::exp;
        $payload = [
            'iss'     => Request::domain(),    //签发人(官方字段:非必需)
            'exp'     => time() + $exp,        //过期时间(官方字段:非必需)
            'aud'     => Request::domain(),    //受众(官方字段:非必需)
            'nbf'     => time(),               //生效时间(官方字段:非必需)
            'iat'     => time(),               //签发时间(官方字段:非必需)
            'user_id' => $user_id,             //自定义字段
            'access_token' => $access_token    //自定义字段
        ];

        $token = JWT::encode($payload,self::secret,'HS256');
        return $token;
    }

    /**
     * @param $token
     *
     * @return array/bool
     */
    public static function checkToken($token)
    {
        try{
            $result = JWT::decode($token,self::secret,['HS256']);
            return (array)$result;
        } catch(\Firebase\JWT\SignatureInvalidException $e) {  //签名不正确
        } catch(\Firebase\JWT\BeforeValidException $e) {  // 签名在某个时间点之后才能用
        } catch(\Firebase\JWT\ExpiredException $e) {  // token过期
        } catch(\Exception $e) {  //其他错误
        }
        return false;
    }
}