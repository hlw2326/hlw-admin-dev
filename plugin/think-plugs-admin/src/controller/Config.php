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

namespace app\admin\controller;

use app\admin\service\UploadSecurity;
use think\admin\base\Controller;
use think\admin\facade\Plugin;
use think\admin\service\AdminService;
use think\admin\service\ModuleService;
use think\admin\service\RuntimeService;
use think\admin\service\SystemService;
use think\admin\facade\Ai;
use think\admin\facade\Notice;
use think\admin\facade\Payment;
use think\admin\facade\Qrcode;
use think\admin\facade\Sms;
use think\admin\facade\Storage;
use think\admin\storage\AliossStorage;
use think\admin\storage\Awss3Storage;
use think\admin\storage\MinioStorage;
use think\admin\storage\QiniuStorage;
use think\admin\storage\TxcosStorage;

/**
 * 系统参数配置.
 * @class Config
 */
class Config extends Controller
{
    public const themes = [
        'default' => '默认色0',
        'white' => '简约白0',
        'red-1' => '玫瑰红1',
        'blue-1' => '深空蓝1',
        'green-1' => '小草绿1',
        'black-1' => '经典黑1',
        'red-2' => '玫瑰红2',
        'blue-2' => '深空蓝2',
        'green-2' => '小草绿2',
        'black-2' => '经典黑2',
    ];

    /**
     * 系统参数配置.
     * @auth true
     * @menu true
     */
    public function index()
    {
        $this->title = '系统参数配置';
        $this->files = Storage::types();
        $this->ais = Ai::types();
        $this->payments = Payment::types();
        $this->smss = Sms::types();
        $this->smsReady = Sms::ready();
        $this->smsName = Sms::types()[Sms::config('type')] ?? '未设置';
        $this->notices = Notice::types();
        $this->noticeEnabled = explode(',', Notice::config('enable'));
        $this->noticeReady = [];
        foreach ($this->notices as $k => $v) {
            if (Notice::ready($k)) {
                $this->noticeReady[] = $k;
            }
        }
        $this->noticeTemplates = Notice::templates();
        $this->qrcodes = Qrcode::types();
        $this->qrcodeReady = [];
        foreach ($this->qrcodes as $k => $v) {
            if (Qrcode::ready($k)) {
                $this->qrcodeReady[] = $k;
            }
        }
        $this->plugins = Plugin::get(null, true);
        $this->issuper = AdminService::isSuper();
        $this->systemid = ModuleService::getRunVar('uni');
        $this->framework = ModuleService::getLibrarys('topthink/framework');
        $this->thinkadmin = ModuleService::getLibrarys('hlw2326/think-library');
        if (AdminService::isSuper() && $this->app->session->get('user.password') === md5('admin')) {
            $url = url('admin/index/pass', ['id' => AdminService::getUserId()]);
            $this->showErrorMessage = lang("超级管理员账号的密码未修改，建议立即<a data-modal='%s'>修改密码</a>！", [$url]);
        }
        uasort($this->plugins, static function ($a, $b) {
            if ($a['space'] === $b['space']) {
                return 0;
            }
            return $a['space'] > $b['space'] ? 1 : -1;
        });
        $this->fetch();
    }

    /**
     * 修改系统参数.
     * @auth true
     * @throws \think\admin\Exception
     */
    public function system()
    {
        if ($this->request->isGet()) {
            $this->title = '修改系统参数';
            $this->themes = static::themes;
            $this->fetch();
        } else {
            if (RuntimeService::check('demo')) {
                $this->error('演示环境禁止修改系统配置！');
            }
            $post = $this->request->post();
            // 修改网站后台入口路径
            if (!empty($post['xpath'])) {
                if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $post['xpath'])) {
                    $this->error('后台入口格式错误！');
                }
                if ($post['xpath'] !== 'admin') {
                    if (is_dir(syspath("app/{$post['xpath']}")) || !empty(Plugin::get($post['xpath']))) {
                        $this->error(lang('已存在 %s 应用！', [$post['xpath']]));
                    }
                }
                RuntimeService::set(null, [$post['xpath'] => 'admin']);
            }
            // 修改网站 ICON 图标，替换 public/favicon.ico
            if (preg_match('#^https?://#', $post['site_icon'] ?? '')) {
                try {
                    SystemService::setFavicon($post['site_icon'] ?? '');
                } catch (\Exception $exception) {
                    trace_file($exception);
                }
            }
            // 数据数据到系统配置表
            foreach ($post as $k => $v) {
                sysconf($k, $v);
            }
            sysoplog('系统配置管理', '修改系统参数成功');
            $this->success('数据保存成功！', admuri('admin/config/index'));
        }
    }

    /**
     * 修改文件存储.
     * @auth true
     * @throws \think\admin\Exception
     */
    public function storage()
    {
        if (!$this->request->isGet() && RuntimeService::check('demo')) {
            $this->error('演示环境禁止修改系统配置！');
        }
        $this->_applyFormToken();
        if ($this->request->isGet()) {
            $this->type = input('type', 'local');
            if ($this->type === 'alioss') {
                $this->points = AliossStorage::region();
            } elseif ($this->type === 'qiniu') {
                $this->points = QiniuStorage::region();
            } elseif ($this->type === 'txcos') {
                $this->points = TxcosStorage::region();
            } elseif ($this->type === 'awss3') {
                $this->points = Awss3Storage::region();
            } elseif ($this->type === 'minio') {
                $this->points = MinioStorage::region();
            }
            $this->fetch("config/storage/{$this->type}");
        } else {
            $post = $this->request->post();
            if (!empty($post['storage']['allow_exts'])) {
                $exts = array_unique(str2arr(strtolower($post['storage']['allow_exts'])));
                foreach ($exts as $extension) {
                    if (!UploadSecurity::isExtensionSafe($extension)) {
                        $this->error('禁止上传可执行的文件！');
                    }
                }
                $post['storage']['allow_exts'] = join(',', $exts);
            }
            foreach ($post as $name => $value) {
                sysconf($name, $value);
            }
            sysoplog('系统配置管理', '修改系统存储参数');
            $this->success('修改文件存储成功！');
        }
    }

    /**
     * AI 大模型配置.
     * @auth true
     * @throws \think\admin\base\Exception
     */
    public function ai()
    {
        if ($this->request->isGet()) {
            $type = strval($this->request->get('type'));
            if ($type === '') {
                $type = strval(sysconf('ai.type'));
                if ($type === '') {
                    $type = 'deepseek';
                }
            }
            $this->type = $type;
            $this->title = '配置' . (Ai::types()[$this->type] ?? 'AI 大模型');
            $this->ais = Ai::types();
            $this->fetch("config/ai/{$this->type}");
        } else {
            if (RuntimeService::check('demo')) {
                $this->error('演示环境禁止修改系统配置！');
            }
            $post = $this->request->post();
            foreach ($post['ai'] ?? [] as $name => $value) {
                sysconf("ai.{$name}", trim(strval($value)));
            }
            sysoplog('系统配置管理', '修改 AI 大模型配置');
            $this->success('修改 AI 大模型配置成功！');
        }
    }

    /**
     * 支付网关配置.
     * @auth true
     * @throws \think\admin\base\Exception
     */
    public function payment()
    {
        if ($this->request->isGet()) {
            $type = strval($this->request->get('type'));
            if ($type === '') {
                $type = strval(sysconf('payment.type'));
                if ($type === '') {
                    $type = 'wechat';
                }
            }
            $this->type = $type;
            $this->title = '配置' . (Payment::types()[$this->type] ?? '支付网关');
            $this->payments = Payment::types();
            $this->fetch("config/payment/{$this->type}");
        } else {
            if (RuntimeService::check('demo')) {
                $this->error('演示环境禁止修改系统配置！');
            }
            $post = $this->request->post();
            foreach ($post['payment'] ?? [] as $name => $value) {
                sysconf("payment.{$name}", trim(strval($value)));
            }
            sysoplog('系统配置管理', '修改支付网关参数');
            $this->success('修改支付网关参数成功！');
        }
    }

    /**
     * 通知渠道配置.
     * @auth true
     * @throws \think\admin\base\Exception
     */
    public function notice()
    {
        if ($this->request->isGet()) {
            $this->type = $this->request->get('type', 'feishu');
            $this->title = '配置' . (Notice::types()[$this->type] ?? '通知渠道');
            $this->notices = Notice::types();
            $this->smsReady = Sms::ready();
            $this->smsName = Sms::types()[Sms::config('type')] ?? '未设置';
            $this->fetch("config/notice/{$this->type}");
        } else {
            if (RuntimeService::check('demo')) {
                $this->error('演示环境禁止修改系统配置！');
            }
            $post = $this->request->post();
            foreach ($post['notice'] ?? [] as $name => $value) {
                if (is_array($value)) {
                    $value = join(',', $value);
                }
                sysconf("notice.{$name}", trim(strval($value)));
            }
            sysoplog('系统配置管理', '修改通知渠道配置');
            $this->success('修改通知渠道配置成功！');
        }
    }

    /**
     * 发送测试通知.
     * @auth true
     */
    public function test()
    {
        $type = strval($this->request->post('type', ''));
        $action = strval($this->request->post('action', 'text'));
        $priority = strval($this->request->post('priority', 'normal'));
        $template = strval($this->request->post('template', 'system_alert'));
        try {
            $inst = Notice::instance($type);
            $time = date('H:i:s');
            match ($action) {
                'card' => $inst->sendCard(
                    "测试卡片通知 ({$time})",
                    "这是一条来自 HlwAdmin 的卡片消息通知，点击下方按钮将跳转至演示地址。",
                    "https://hlwadmin.top",
                    "进入管理系统",
                    "",
                    $priority
                ),
                'image' => $inst->sendImage(
                    "https://v2.taoler.cn/static/theme/img/logo.png",
                    "测试图片通知 ({$time})",
                    "",
                    $priority
                ),
                'file' => $inst->sendFile(
                    "https://hlwadmin.top/favicon.ico",
                    "favicon.ico",
                    "",
                    $priority
                ),
                'template' => Notice::sendTemplate(
                    $template !== '' ? $template : 'system_alert',
                    ['title' => '系统测试告警', 'event' => '配置测试联调', 'detail' => "测试时间 {$time}"],
                    '',
                    $type,
                    $priority
                ),
                default => $inst->send("测试通知", "这是一条来自 HlwAdmin 的测试通知 {$time}", "", $priority),
            };
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
        }
        $this->success('测试通知已发送，请检查是否收到！');
    }

    /**
     * 手机短信配置.
     * @auth true
     * @throws \think\admin\base\Exception
     */
    public function sms()
    {
        if ($this->request->isGet()) {
            $type = strval($this->request->get('type'));
            if ($type === '') {
                $type = strval(sysconf('sms.type'));
                if ($type === '') {
                    $type = 'aliyun';
                }
            }
            $this->type = $type;
            $this->title = '配置' . (Sms::types()[$this->type] ?? '手机短信');
            $this->smss = Sms::types();
            $this->fetch("config/sms/{$this->type}");
        } else {
            if (RuntimeService::check('demo')) {
                $this->error('演示环境禁止修改系统配置！');
            }
            $post = $this->request->post();
            foreach ($post['sms'] ?? [] as $name => $value) {
                sysconf("sms.{$name}", trim(strval($value)));
            }
            sysoplog('系统配置管理', '修改手机短信配置');
            $this->success('修改手机短信配置成功！');
        }
    }

    /**
     * 小程序码参数配置.
     * @auth true
     * @throws \think\admin\base\Exception
     */
    public function qrcode()
    {
        if ($this->request->isGet()) {
            $type = strval($this->request->get('type'));
            if ($type === '') {
                $type = strval(sysconf('qrcode.type'));
                if ($type === '') {
                    $type = 'wechat';
                }
            }
            $this->type = $type;
            $this->title = '配置' . (Qrcode::types()[$this->type] ?? '小程序码');
            $this->qrcodes = Qrcode::types();
            $this->fetch("config/qrcode/{$this->type}");
        } else {
            if (RuntimeService::check('demo')) {
                $this->error('演示环境禁止修改系统配置！');
            }
            $post = $this->request->post();
            foreach ($post['qrcode'] ?? [] as $name => $value) {
                sysconf("qrcode.{$name}", trim(strval($value)));
            }
            sysoplog('系统配置管理', '修改小程序码配置');
            $this->success('修改小程序码配置成功！');
        }
    }
}
