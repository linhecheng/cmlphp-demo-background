<!doctype html>
<html>
<head>
    <meta charset='utf-8'>
    <title>{{lang _CML_ERROR_}}</title>
    <style type="text/css">
        html,body{margin:0;padding:0}
        .main {border:1px solid #ebedf0;padding:5px;margin: 5px;}
        .main h2 {font-size:16px;margin:5px; padding-bottom:10px;}
        .error-text {font-weight:bold;line-height:30px;font-size:24px;margin-top:15px; border-bottom:1px solid #eee; padding-bottom:25px;}
        .error-text span {font-size:25px;color:#350606;font-style:italic;}
        .error-line {line-height:30px;height:35px;}
        .stack-trace h3 {font-size:24px; margin: 15px 0;}
        .error-detail {border: 1px solid #eee; border-radius:3px; padding:5px; margin-bottom:3px;}
        .code-line {display:inline-block;border-right:1px solid #91d5ff; padding-right:3px;color: #1890ffb8;margin-right:5px;}
    </style>
</head>
<body>
<div class="main">
    <h2>{{lang _CML_ERROR_}}</h2>

    <div class="content">
        {{if isset($error['files']) }}

            <div class="error-text">
                <span>{{$error['exception']}}</span> {{echo htmlspecialchars($error['message']);}}
            </div>

            <div class="stack-trace">
                <h3>stack trace:</h3>
            </div>

            {{loop $error['files']  $val}}
                {{if isset($val['file'])}}
                    <div class="error-detail">
                        <div class="error-line">
                            <b>{{lang _ERROR_LINE_}}:</b> {{$val['file']}}&#12288;LINE: {{$val['line']}}&#12288;->&#12288;
                            【{{if isset($val['class']) }} {{echo $val['class'].$val['type']}} {{/if}} {{if isset($val['function']) }} {{$val['function']}} {{/if}}】
                        </div>
                        {{echo \Cml\Debug::codeSnippet($val['file'], $val['line']);}}
                    </div>
                {{/if}}
            {{/loop}}
        {{/if}}
    </div>
</div>
</body>
</html>