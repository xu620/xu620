<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\exception\UploadException;
use app\common\library\Upload;
use app\common\model\Version;

/**
 * 公共接口
 */
class CommonController extends Api
{
    protected $noNeedLogin = ['init'];
    protected $noNeedRight = '*';

    /**
     * @api {get} /api/common/init 加载初始化配置
     * @apiName init
     * @apiGroup 公共管理
     * @apiVersion 0.0.0
     * @apiDescription 加载初始化配置
     *
     * @apiHeader {String} [token]  令牌
     *
     * @apiParam {Number} platform  客户端类型 1 安卓 2 IOS
     *
     * @apiSuccess {Number} code  返回值 其他状态码提示错误  2000正常.
     * @apiSuccess {String} msg   提示语.
     * @apiSuccess {Object} [data] 返回数据
     * @apiSuccessExample {Object} 成功示例
     * "data": {
     *      "version": {                            // 版本信息
     *          "version_code": "1",                // 版本编号
     *          "old_version": "1.0.6",             // 旧版本号
     *          "new_version": "1.0.7",             // 新版本号
     *          "package_size": "10.8M",            // 安装包大小
     *          "content": "1111",                  // 更新内容
     *          "download_url": "https://xxx.apk",  // 下载地址
     *          "enforce": "1"                      // 是否强制更新 1是
     *      },
     * }
     *
     */
    public function init()
    {
        $platform = $this->request->param('platform/d',1);
        $version = Version::getVersion($platform);
        $data = [
            'version' => $version,
        ];

        $this->success(__('Request successful'), $data);
    }

    /**
     * @api {post} /api/common/upload 上传文件
     * @apiName upload
     * @apiGroup 公共管理
     * @apiVersion 0.0.0
     * @apiDescription 上传文件
     *
     * @apiHeader {String} token   令牌
     *
     * @apiParam {String} file 文件流 多张请传数组
     *
     * @apiSuccess {Number} code  返回值 其他状态码提示错误  2000正常.
     * @apiSuccess {String} msg   提示语.
     * @apiSuccess {Object} [data] 返回数据
     * @apiSuccessExample {Object} 成功示例
     * "data": {
     *      "url": "/dd54bb.jpg,/666868.jpg",  // 不带域名 多张用逗号隔开
     *      "full_url": [                      // 带域名
     *          "http://xxx/dd54bb.jpg",
     *          "http://xxx/666868.jpg"
     *      ],
     *      "name": [                          // 文件名称
     *          "33.jpg",
     *          "3.jpg"
     *      ]
     * }
     *
     */
    public function upload()
    {
        //默认普通上传文件
        $file = $this->request->file('file');

        $fileResult = [];
        try {
            $upload = new Upload();
            if (is_array($file)) {
                foreach ($file as $f) {
                    $upload->setFile($f);
                    $attachment = $upload->upload();
                    $fileResult[] = [
                        'url' => $attachment->url,
                        'full_url' => cdnurl($attachment->url, true),
                        'name' => $attachment->filename
                    ];
                }
            } else {
                $upload->setFile($file);
                $attachment = $upload->upload();
                $fileResult[] = [
                    'url' => $attachment->url,
                    'full_url' => cdnurl($attachment->url, true),
                    'name' => $attachment->filename
                ];
            }
        } catch (UploadException $e) {
            // $e->getMessage()
            $this->error(__('Uploaded failed'));
        }

        $url      = implode(',', array_column($fileResult, 'url'));
        $full_url = array_column($fileResult, 'full_url');
        $name     = array_column($fileResult, 'name');

        $this->success(__('Uploaded successful'), compact('url', 'full_url', 'name'));
    }
}
