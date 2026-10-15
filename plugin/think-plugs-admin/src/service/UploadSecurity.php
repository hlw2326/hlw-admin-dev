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

namespace app\admin\service;

use think\admin\facade\Storage;

/**
 * 上传文件安全检查.
 * @class UploadSecurity
 */
final class UploadSecurity
{
    /**
     * 可能被 Web 服务器、脚本解释器或操作系统执行的后缀.
     */
    private const EXECUTABLE_EXTENSIONS = [
        'asp', 'aspx', 'asa', 'asax', 'ashx', 'asmx',
        'bat', 'bash', 'cgi', 'cmd', 'com', 'dll', 'dylib', 'exe',
        'jar', 'jsp', 'jspx', 'msi', 'pht', 'phtm', 'phtml', 'phar',
        'php', 'php2', 'php3', 'php4', 'php5', 'php6', 'php7', 'php8', 'phps',
        'pl', 'py', 'rb', 'sh', 'shtm', 'shtml', 'so', 'zsh',
    ];

    /**
     * 检查上传键是否为规范的相对文件路径.
     */
    public static function isNameSafe(string $name, string $extension): bool
    {
        $extension = strtolower(trim($extension, ". \\/\\\t\n\r\0\x0B"));
        if ($extension === '' || !Storage::isPathSafe($name)) {
            return false;
        }
        return strtolower(pathinfo($name, PATHINFO_EXTENSION)) === $extension;
    }

    /**
     * 检查文件后缀是否允许存储.
     */
    public static function isExtensionSafe(string $extension): bool
    {
        $extension = strtolower(trim($extension, ". \\/\\\t\n\r\0\x0B"));
        if (preg_match('/^php\d*$/D', $extension)) {
            return false;
        }
        return $extension !== '' && !in_array($extension, self::EXECUTABLE_EXTENSIONS, true);
    }

    /**
     * 流式检查图片中是否夹带服务端脚本.
     */
    public static function isImageSafe(string $filename): bool
    {
        if (!is_file($filename) || !is_readable($filename)) {
            return false;
        }
        if (!is_resource($stream = @fopen($filename, 'rb'))) {
            return false;
        }
        $carry = '';
        try {
            while (!feof($stream)) {
                $chunk = fread($stream, 8192);
                if ($chunk === false) {
                    return false;
                }
                $buffer = $carry . $chunk;
                if (self::containsScript($buffer)) {
                    return false;
                }
                $carry = substr($buffer, -4096);
            }
            return true;
        } finally {
            fclose($stream);
        }
    }

    /**
     * 检查服务端脚本起始标签.
     */
    private static function containsScript(string $content): bool
    {
        // 1. 检查 <script 脚本标签 (支持 HTML/SVG 注入)
        if (preg_match('/<script[\s\r\n\t>]/i', $content)) {
            return true;
        }
        // 2. 检查 PHP 脚本起始标签：<?php 或 <?=
        if (preg_match('/<\?(?:php|=)/i', $content)) {
            return true;
        }
        // 3. 检查带空白符的短标签 <? (排除合法 XML 声明 <?xml 与 Adobe XMP 元数据 <?xpacket)
        if (preg_match('/<\?(?!xml\b|xpacket\b)[\s\r\n\t]+/i', $content)) {
            return true;
        }
        // 4. 检查 ASP 脚本标签 <% (要求紧随空白符、赋值或指令，避免误伤二进制熵值)
        if (preg_match('/<%[\s\r\n\t=]|<%(?:@|language)/i', $content)) {
            return true;
        }
        return false;
    }
}
