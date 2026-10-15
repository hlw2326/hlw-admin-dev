/*! CodeMirror 5 统一入口：一次性加载样式、核心、JSON 模式与常用插件 */
define([
    'css!' + window.baseRoot + 'plugs/codemirror/lib/codemirror.css',
    'css!' + window.baseRoot + 'plugs/codemirror/addon/fold/foldgutter.css',
    'plugs/codemirror/lib/codemirror',
    'plugs/codemirror/mode/javascript/javascript',
    'plugs/codemirror/addon/edit/matchbrackets',
    'plugs/codemirror/addon/edit/closebrackets',
    'plugs/codemirror/addon/fold/foldcode',
    'plugs/codemirror/addon/fold/foldgutter',
    'plugs/codemirror/addon/fold/brace-fold',
    'plugs/codemirror/addon/selection/active-line'
], function (css1, css2, CodeMirror) {
    return CodeMirror;
});
