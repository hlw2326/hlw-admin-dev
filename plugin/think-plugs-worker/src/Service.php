<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | HlwAdmin Plugin for HlwAdmin
 * +----------------------------------------------------------------------
 * | 版权所有 2014~2026 HlwAdmin [ hlwadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://hlwadmin.top
 * +----------------------------------------------------------------------
 */

namespace plugin\worker;

use plugin\worker\command\Worker;
use think\admin\facade\Plugin;

/**
 * 插件注册服务
 * @class Service
 */
class Service extends Plugin
{
    /**
     * 定义插件名称.
     * @var string
     */
    protected $appName = '网络服务';

    /**
     * 定义安装包名.
     * @var string
     */
    protected $package = 'hlw2326/think-plugs-worker';

    public function register()
    {
        $this->commands(['xadmin:worker' => Worker::class]);
    }

    public static function menu(): array
    {
        return [];
    }
}
