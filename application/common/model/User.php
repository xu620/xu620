<?php

namespace app\common\model;

use think\Model;

/**
 * 会员模型
 */
class User extends Model
{

    protected $name = 'user';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    // 追加属性
    protected $append = [

    ];

    /**
     * 变更会员余额
     *
     * @param float $money 余额
     * @param int $user_id 会员ID
     * @param string $memo 备注
     * @param string $remark 管理员备注
     */
    public static function money(float $money, int $user_id, string $memo, string $remark = '')
    {
        $user = self::get($user_id);
        if ($user && $money != 0) {
            $before = $user['money'];
            $after = function_exists('bcadd') ? bcadd($user['money'], $money, 2) : $user['money'] + $money;
            //更新会员信息
            $user->save(['money' => $after]);
            //写入日志
            MoneyLog::create(['user_id' => $user_id, 'money' => $money, 'before' => $before, 'after' => $after, 'memo' => $memo, 'remark' => $remark]);
        }
    }

    /**
     * 变更会员积分
     * @param int    $score   积分
     * @param int    $user_id 会员ID
     * @param string $memo    备注
     */
    public static function score($score, $user_id, $memo)
    {
        $user = self::get($user_id);
        if ($user && $score != 0) {
            $before = $user->score;
            $after = $user->score + $score;
            //更新会员信息
            $user->save(['score' => $after]);
            //写入日志
            ScoreLog::create(['user_id' => $user_id, 'score' => $score, 'before' => $before, 'after' => $after, 'memo' => $memo]);
        }
    }

    protected function setBirthdayAttr($value)
    {
        return $value ? $value : null;
    }
}
