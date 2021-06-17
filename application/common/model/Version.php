<?php

namespace app\common\model;

use think\Model;

class Version extends Model
{

    protected $name = 'version';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    // 定义字段类型
    protected $type = [

    ];

    public function getPlatformList()
    {
        return ['1' => __('Android'), '2' => __('IOS')];
    }

    /**
     * 获取最新版本
     *
     * @param $platform
     *
     * @return Version|null
     */
    public static function getVersion($platform)
    {
        $version = self::where(['status' => 1, 'platform' => $platform])
                       ->cache(300)
                       ->visible(['version_code', 'old_version', 'new_version', 'package_size', 'content', 'download_url', 'enforce'])
                       ->order('id desc')
                       ->find();

        return $version ? $version : null;
    }
}
