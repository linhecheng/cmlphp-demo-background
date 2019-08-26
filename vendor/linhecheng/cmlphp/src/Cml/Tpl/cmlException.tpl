<!doctype html>
<html>
<head>
    <meta charset='utf-8'>
    <title>{{lang _CML_ERROR_}}</title>
    <style type="text/css">
        body{font-family:'Microsoft YaHei';color:#333;background:white;}.link,.link a{text-align:center;text-decoration:none;color:#777;}.pure-table{border-collapse:collapse;border-spacing:0;empty-cells:show;border:1px solid #cbcbcb}.pure-table td,.pure-table th{border-left:1px solid #cbcbcb;border-width:0 0 0 1px;font-size:inherit;margin:0;overflow:visible;padding:6px 12px}.pure-table td:first-child,.pure-table th:first-child{border-left-width:0}.pure-table thead{background:#e0e0e0;color:#000;text-align:left;vertical-align:bottom}.pure-table td{background-color:transparent}.pure-table-odd td{background-color:#f2f2f2}
    </style>
</head>
<body>
<table class="pure-table">
    <thead>
        <tr>
            <th>{{lang _CML_ERROR_}}</th>
        </tr>
    </thead>

    <tbody>
        <tr>
            <td style="padding: 3px 12px;">
                <svg version="1.1" width="60" x="0px" y="0px" viewBox="0 0 612 792" enable-background="new 0 0 612 792" xml:space="preserve">
                    <path d="M204.6,394.1l42.1-42.1l-42.1-40.1l13.4-13.4l55.5,53.5L218,407.5L204.6,394.1z M210.4,510.8v19.1c0,0,38.2-38.2,105.2-38.2
                        s105.2,38.2,105.2,38.2v-19.1c0,0-38.2-38.2-105.2-38.2S210.4,510.8,210.4,510.8z M413.1,298.5L357.6,352l55.5,55.5l13.4-13.4
                        L384.4,352l42.1-40.1L413.1,298.5z M554.7,405.6c0,132-107.1,239.1-239.1,239.1S76.5,537.6,76.5,405.6s107.1-239.1,239.1-239.1
                        C447.5,166.5,554.7,273.6,554.7,405.6z M535.5,405.6c0-122.4-97.5-219.9-219.9-219.9S95.7,283.2,95.7,405.6s97.5,219.9,219.9,219.9
                        S535.5,528,535.5,405.6z"/>
                </svg>
            </td>
        </tr>
    {{if isset($error['files']) }}
        <tr class="pure-table-odd">
            <td><b><span style="font-size:25px;color:#350606;font-style:italic;">{{$error['exception']}}</span> {{echo htmlspecialchars($error['message']);}} </b></td>
        </tr>
        <tr>
            <td style="font-size:30px;">stack trace:</td>
        </tr>
        {{loop $error['files']  $val}}
            {{if isset($val['file'])}}
            <tr>
                <td>
                    <b>{{lang _ERROR_LINE_}}:</b> {{$val['file']}}&#12288;LINE: {{$val['line']}}&#12288;->&#12288;
                    【{{if isset($val['class']) }} {{echo $val['class'].$val['type']}} {{/if}} {{if isset($val['function']) }} {{$val['function']}} {{/if}}】
                    {{echo \Cml\Debug::codeSnippet($val['file'], $val['line']);}}
                </td>
            </tr>
            {{/if}}
        {{/loop}}
    {{/if}}
        <tr class="pure-table-odd">
            <td class="link"><a href="#"></a></td>
        </tr>
    </tbody>
</table>
</body>
</html>