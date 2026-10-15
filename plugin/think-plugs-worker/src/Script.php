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

/**
 * 插件安装器事件处理.
 * @class Script
 */
abstract class Script
{
    public static function onRemove()
    {
        @unlink('config/worker.php');
    }
}
