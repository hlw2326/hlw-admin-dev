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

namespace plugin\center;

use think\admin\facade\Plugin;

/**
 * 组件注册服务
 * @class Service
 */
class Service extends Plugin
{
    /**
     * 定义插件名称.
     * @var string
     */
    protected $appName = '应用中心';

    /**
     * 定义插件入口.
     * @var string
     */
    protected $appCode = 'plugin-center';

    /**
     * 定义安装包名.
     * @var string
     */
    protected $package = 'hlw2326/think-plugs-center';

    /**
     * 定义插件菜单.
     */
    public static function menu(): array
    {
        return [];
    }
}
