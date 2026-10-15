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

namespace plugin\helper;

use Ergebnis\Classy\Constructs;
use think\admin\extend\ToolsExtend;
use think\console\input\Argument;
use think\console\input\Option;
use think\ide\console\ModelCommand;

/**
 * 创建模型注释.
 * @class ModelGen
 */
class DbModelStruct extends ModelCommand
{
    public function handle()
    {
        $this->dirs = array_merge(['app', 'plugin'], $this->input->getOption('dir'));

        $model = $this->input->getArgument('model');
        $ignore = $this->input->getOption('ignore');

        $this->overwrite = $this->input->getOption('overwrite');

        $this->reset = $this->input->getOption('reset');

        $this->generateDocs($model, $ignore);
    }

    protected function configure()
    {
        $this->setName('xadmin:helper:model')
            ->addArgument('model', Argument::OPTIONAL | Argument::IS_ARRAY, 'Which models to include', [])
            ->addOption('dir', 'D', Option::VALUE_OPTIONAL | Option::VALUE_IS_ARRAY, 'The model dir', [])
            ->addOption('ignore', 'I', Option::VALUE_OPTIONAL, 'Which models to ignore', '')
            ->addOption('reset', 'R', Option::VALUE_NONE, 'Remove the original phpdocs instead of appending')
            ->addOption('overwrite', 'O', Option::VALUE_NONE, 'Overwrite the phpdocs');
        $this->setDescription('自动生成用于IDE提示的模型注释');
    }

    protected function loadModels(): array
    {
        $models = [];
        foreach ($this->dirs as $dir) {
            iterator_to_array(ToolsExtend::findFilesYield($this->app->getRootPath() . $dir, null, function (\SplFileInfo $info) use (&$models) {
                if ($info->isDir() && $info->getFilename() === 'model') {
                    foreach (Constructs::fromDirectory($info->getRealPath()) as $construct) {
                        $models[] = $construct->name();
                    }
                    return false;
                }
                return true;
            }));
        }
        return $models;
    }
}
