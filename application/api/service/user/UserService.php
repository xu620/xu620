<?php

namespace app\api\service\user;

use app\common\model\User;

/**
 * 会员模型
 */
class UserService extends User
{

    // 追加属性
    protected $append = [

    ];

    /**
     * 会员数据
     */
    public static function getTotal()
    {
        if(!cache('_user_data_')){
            $num = self::cache(600)->count();
            // 虚拟数量
            $initial = config('site.user_num_initial') > 0 ? config('site.user_num_initial') : 0;
            // 昨日新增人数
            $yesterday = self::cache(600)->whereTime('create_time', 'yesterday')->count();
            // 今日新增人数
            $today = self::cache(600)->whereTime('create_time', 'today')->count();
            // 昨日新增为0 的话 今日新增就是百分比了
            if($yesterday > 0){
                // 增长比例
                $rate = bcsub(bcdiv($today, $yesterday,2), 1, 2);
                $rate = bcmul($rate, 100, 2);
            }else{
                $rate = 100;
            }

            $data =  [
                'num'  => bcadd($num, $initial),
                'rate' => floatval($rate)
            ];
            cache('_user_data_',$data,600);
        }

        return cache('_user_data_');
    }

    /**
     * 获取直推下级成功充值的人数
     *
     * @param $user_id
     *
     * @return int
     */
    public static function getSpreadRechargeNum($user_id)
    {
        $num = self::cache(120)->where(['parent_id' => $user_id, 'is_recharge' => 1])->count('id');
        return $num > 0 ? $num : 0;
    }

    /**
     * 获取头像
     *
     * @param $value
     *
     * @return string
     */
    public function getAvatarAttr($value)
    {
        if (!$value) {
            //如果不需要启用首字母头像，请使用
            $value = '/assets/img/avatar.png';
        }
        return get_domain_paths($value);
    }


    public function level()
    {
        return $this->belongsTo(LevelService::class,'level_id','level_id');
    }
}
