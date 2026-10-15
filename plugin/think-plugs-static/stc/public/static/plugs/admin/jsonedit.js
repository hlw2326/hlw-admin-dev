// +----------------------------------------------------------------------
// | HlwAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2026 HlwAdmin [ hlwadmin.top ]
// +----------------------------------------------------------------------

/**
 * JSON 编辑器，一句代码接入：
 *   require(['jsonedit'], function (json) { json('#BaseExtend'); });
 * 约定（均可选）：提示 #{id}Tip，格式化按钮 #{id}Format，压缩按钮 #{id}Minify
 * 支持：行号、折叠、括号高亮、宽松 JSON 自动修正（单引号、未加引号的键、尾逗号、注释）
 */
define(['codemirror'], function (CodeMirror) {

    var CSS = '.json-edit+.CodeMirror{height:260px;border:1px solid #e6e6e6;border-radius:2px;font:13px/1.5 Consolas,Menlo,monospace}'
        + '.json-edit+.CodeMirror.CodeMirror-focused{border-color:#16b777}'
        + '.json-edit+.CodeMirror.json-error{border-color:#ff5722}'
        + '.json-edit+.CodeMirror.json-warn{border-color:#ff9800}'
        + '.json-edit+.CodeMirror .CodeMirror-activeline-background{background:#f6f8fa}';

    // 宽松 JSON 修正：单引号、未加引号的键、尾逗号、注释 → 标准 JSON
    function fix(src) {
        var out = '', i = 0, n = src.length, ch, q, buf, id;
        while (i < n) {
            ch = src.charAt(i);
            if (ch === '"' || ch === "'") {
                q = ch, buf = '', i++;
                while (i < n && src.charAt(i) !== q) {
                    if (src.charAt(i) === '\\') {
                        buf += src.substr(i, 2), i += 2;
                    } else {
                        buf += src.charAt(i++);
                    }
                }
                i++;
                if (q === "'") buf = buf.replace(/\\'/g, "'").replace(/\\?"/g, '\\"');
                out += '"' + buf + '"';
            } else if (src.substr(i, 2) === '//') {
                while (i < n && src.charAt(i) !== '\n') i++;
            } else if (src.substr(i, 2) === '/*') {
                i = src.indexOf('*/', i + 2);
                if (i < 0) i = n; else i += 2;
            } else if (/[A-Za-z_$]/.test(ch)) {
                id = /^[A-Za-z_$][\w$]*/.exec(src.slice(i))[0], i += id.length;
                if (/^\s*:/.test(src.slice(i))) out += '"' + id + '"'; else out += id;
            } else if (ch === '}' || ch === ']') {
                out = out.replace(/,\s*$/, '') + ch, i++;
            } else {
                out += ch, i++;
            }
        }
        return out;
    }

    // 解析：state 为 ok(合法或为空)、fix(可自动修正)、err(无法解析)
    function parse(text) {
        var json, msg;
        text = $.trim(text);
        if (text === '') return {state: 'ok', json: null};
        try {
            json = JSON.parse(text);
            if (typeof json !== 'object') throw new Error('必须是 JSON 对象或数组');
            return {state: 'ok', json: json};
        } catch (e) {
            msg = e.message;
        }
        try {
            json = JSON.parse(fix(text));
            if (typeof json === 'object') return {state: 'fix', json: json};
        } catch (e) {
        }
        return {state: 'err', json: null, msg: msg};
    }

    return function (selector, options) {
        var $text = $(selector), id = $text.attr('id'), $tip = $('#' + id + 'Tip'), tip = $tip.text();
        if (!$('#json-edit-css').length) $('<style id="json-edit-css"></style>').text(CSS).appendTo('head');
        $text.addClass('json-edit');
        var cm = CodeMirror.fromTextArea($text[0], $.extend({
            mode: {name: 'javascript', json: true},
            lineNumbers: true, lineWrapping: true, tabSize: 2, indentUnit: 2,
            matchBrackets: true, autoCloseBrackets: true, styleActiveLine: true,
            foldGutter: true, gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter']
        }, options));

        function check() {
            var res = parse(cm.getValue());
            $(cm.getWrapperElement()).toggleClass('json-error', res.state === 'err').toggleClass('json-warn', res.state === 'fix');
            $tip.css('color', '');
            if (res.state === 'fix') {
                $tip.text('检测到非标准 JSON（单引号、未加引号的键、尾逗号等），失去焦点或点击“格式化”将自动修正 ~').css('color', '#ff9800');
            } else if (res.state === 'err') {
                $tip.text('JSON 格式错误：' + res.msg).css('color', '#ff5722');
            } else {
                $tip.text(tip);
            }
        }

        function render(space) {
            var res = parse(cm.getValue());
            if (res.state !== 'err' && res.json !== null) cm.setValue(JSON.stringify(res.json, null, space));
            check();
        }

        cm.on('change', function () {
            cm.save(), check();
        });
        cm.on('blur', function () {
            render(2);
        });
        $('#' + id + 'Format').on('click', function () {
            render(2);
        });
        $('#' + id + 'Minify').on('click', function () {
            render(0);
        });
        // 弹窗展示后重新计算尺寸
        setTimeout(function () {
            cm.refresh();
        }, 200);
        return cm;
    };
});
