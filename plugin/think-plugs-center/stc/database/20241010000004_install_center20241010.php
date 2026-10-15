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

use think\admin\database\migration\PhinxExtend;
use think\migration\Migrator;

class InstallCenter20241010 extends Migrator
{
    /**
     * 获取脚本名称.
     */
    public function getName(): string
    {
        return 'CenterPlugin';
    }

    public function change()
    {
        set_time_limit(0);
        @ini_set('memory_limit', '-1');
        $this->insertMenu();
    }

    private function insertMenu()
    {
        PhinxExtend::write2menu([
            [
                'name' => '插件入口',
                'sort' => '999',
                'node' => 'plugin-center/index/index',
            ],
        ], [
            'url|node' => 'plugin-center/index/index',
        ]);
    }
}
