<div id="cmlphp_console_info" style="font-family:Microsoft YaHei;letter-spacing: -.0em;position: fixed;bottom:0;right:0;font-size:14px;width:100%;z-index: 999999;color: #000;text-align:left;">
    <div id="cmlphp_console_info_switch" style="box-sizing:border-box;height: 31px; bottom: 0; color: rgb(0, 0, 0); cursor: pointer; display: block; width: 100%; border-top: 3px rgb(255, 102, 0) solid;">
        <div style="box-sizing:border-box;background:#232323;color:#FFF;padding:2px 6px;height:32px;font-size:14px;">
            <span id="cmlphp_console_info_simpleinfo">CmlPHP: {{echo \Cml\Cml::VERSION}} &nbsp;&nbsp; {{lang _PHP_VERSION_}}:<i>{{echo phpversion();}}</i> &nbsp;&nbsp; {{lang _UPTIME_}}:<i>{{$usetime}}s</i> &nbsp;&nbsp; {{lang _USED_MEMORY_}}:<i>{{$usememory}}</i> {{if isset($_SERVER['SERVER_ADDR']) }} &nbsp;&nbsp;IP: {{echo $_SERVER['SERVER_ADDR']}} {{/if}}</span>
            <div style="float:right;margin:0 auto;width:110px;text-align:center;">
                <svg id="cmlphp_console_info_logo" width="85" height="25" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <g>
                        <rect fill="none" id="canvas_background" height="27" width="87" y="-1" x="-1"/>
                        <g display="none" overflow="visible" y="0" x="0" height="100%" width="100%" id="canvasGrid">
                            <rect fill="url(#gridpattern)" stroke-width="0" y="0" x="0" height="100%" width="100%"/>
                        </g>
                    </g>
                    <g>
                        <image xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAFUAAAAZCAYAAABAb2JNAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyFpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDE0IDc5LjE1MTQ4MSwgMjAxMy8wMy8xMy0xMjowOToxNSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIChXaW5kb3dzKSIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDowRjZFRTU5QUQ2MjAxMUU1QkFBREQ3NzMwM0IxOTZCRCIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDowRjZFRTU5QkQ2MjAxMUU1QkFBREQ3NzMwM0IxOTZCRCI+IDx4bXBNTTpEZXJpdmVkRnJvbSBzdFJlZjppbnN0YW5jZUlEPSJ4bXAuaWlkOjBGNkVFNTk4RDYyMDExRTVCQUFERDc3MzAzQjE5NkJEIiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOjBGNkVFNTk5RDYyMDExRTVCQUFERDc3MzAzQjE5NkJEIi8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+IZdrVgAAAzVJREFUeNrsmEtuFDEQhsvt7k4mbJgcYTgCOUGU7JEQWXEFFigSuQLMErGBNTv2KBKIEyRHGI5ALyHpdpv6bVfbE/LohozEwpbc73GVv/qr7ERZaym3+21FRpChZqgZam4Zaoaaoeb2r6288c3+8oSPr0kBPR+U9Gu/3iVLDfU9Eba92PvKeXpbsJ0zNyauC7VyT2H3Zh8a7ods85xtLrif8fXuYD91I/VJqR983PPyGmUn2jo9Pp8GdX85d0C/vVJ3Injylqi33lnDwjcMFnDxDF5NBesnM3fnQjof4Iku/HsKgY6QnjG493y95+57Ox98wmsJNp6p4JMfZx5tcD89HjNfb0uCMUGpc9JqHIStyjtt2NGi99HtMNn+75QqmQGA6JUGVHL+6GIdfoTKCqXHgz3AM30Mdtf7exX8lKaVH7sMdsa07crbmpz+xZUJ3Aq1lEksqDWrIX3we8CODelymzNH3D+5SYoqax671hFwqYP6gtL64RpAYzqCW2f8e/SLlqhNgu0EkACFMLbLcfOd1eu2RkOVSY2LnFcBuk8j5RQrk4+qWrlaRPT1mlEO+FtOX4YKuxJUjD2rcL1iJS0cZBtUKGksgJCOLr0D1MvOeqj87U/twUKxgN0ar0wECedZTbTD/cXHL3x/4OyI2iUbROEPd5owj6lKLcZD/dXS4LwNAOGouqJ8LDpE34dFIIWO5z0WGArpHmocJvtgC2M9cs+rMkJJJyrZIcHAdck3UlNlPi1LtU3KSqWtAwgb6Mujw82t/lOUetGlhT9OLAUrKU1paQhQhwUlAKhCuiuKCiJaHzP9Ldn1GitKV0lAbVAwUl13cSxXXkpvA1A3uqUqimZw8k6orZ+XOIoz6uysThaeBKrUR1lA1gBRrKE2lBb0PqQ4xpcyY5JAplslG3YdoljnT3hXSn3uIlCMKXY2CvXzy4aevntDzz9YF0UAQupcGtQqr07ATPepTqGqcWoQ52VrZd2uoBmUg94FkJ0RlTbD4lGXjXvm6h4H2Fj6c7+cAqWodl9qvC0XZBVXdm1iqai1tyOKrXRzX1BV/id1/jM1Q81Qc8tQM9QMNbcM9T9svwUYANvRWSdKGhh9AAAAAElFTkSuQmCC" id="svg_1" height="25" width="85" y="0" x="0"/>
                    </g>
                </svg><svg version="1.1" width=25 xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" height="24" viewBox="0 0 24 24" style="display:inline-block" enable-background="new 0 0 24 24" xml:space="preserve" id="cmlphp_console_info_minisize">
<path fill="#AAAAAA" d="M21.1,18.3c0.8,0.8,0.8,2,0,2.8c-0.4,0.4-0.9,0.6-1.4,0.6s-1-0.2-1.4-0.6L12,14.8l-6.3,6.3
    c-0.4,0.4-0.9,0.6-1.4,0.6s-1-0.2-1.4-0.6c-0.8-0.8-0.8-2,0-2.8L9.2,12L2.9,5.7c-0.8-0.8-0.8-2,0-2.8c0.8-0.8,2-0.8,2.8,0L12,9.2
    l6.3-6.3c0.8-0.8,2-0.8,2.8,0c0.8,0.8,0.8,2,0,2.8L14.8,12L21.1,18.3z"></path>
</svg>
            </div>
        </div>
    </div>
    <div id="cmlphp_console_info_content" style="box-sizing:border-box;display: none;background:white; margin:0; height: 461px; padding-bottom:8px; border-bottom: 3px solid #cddc39;">
        <div style="box-sizing:border-box;height:38px;padding: 6px 12px 0;border-bottom:1px solid #ececec;border-top:1px solid #ececec;font-size:16px">
            <span>{{lang _OPERATION_INFORMATION_}}</span>
        </div>
        <div style="overflow:auto;height:420px;padding: 0; line-height: 24px">
            <ul style="padding: 0; margin:0">

                {{if count($tipInfo) > 0 }}
                <li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px;font-weight:bold;"><b>{{lang _SYSTEM_INFORMATION_}}</b></li>
                {{loop $tipInfo $info}}
                <li style='font-size:14px;padding:0 0 0 60px;'>{{$info}}</li>
                {{/loop}}
                {{/if}}

                {{if count($sqls) > 0 }}
                <li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px;font-weight:bold;"><b>{{lang _SQL_STATEMENT_}}</b><span style="color:red">({{echo count($sqls);}})</span></li>
                {{loop $sqls $sql}}
                <li style='font-size:14px;padding:0 0 0 60px;'>{{$sql}}</li>
                {{/loop}}
                {{/if}}

                {{if count($includeLib) > 0 }}
                <li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px;font-weight:bold;"><b>{{lang _INCLUDE_LIB_}}</b><span style="color:red">dev({{echo count($includeLib);}}), online({{echo count($includeLib) - 5;}})</span></li>
                <li style="font-size:14px;padding:0 0 0 50px;">
                    {{loop $includeLib $file}}
                    <span style='padding-left:10px;'>【{{$file}}】</span>
                    {{/loop}}
                </li>
                {{/if}}

                {{if count($includeFile) > 0 }}
                <li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px;font-weight:bold;"><b>{{lang _INCLUDE_FILE_}}</b><span style="color:red">({{echo count($includeFile);}})</span></li>
                {{loop $includeFile $file}}
                <li style='font-size:14px;padding:0 0 0 60px;'>{{$file}}</li>
                {{/loop}}
                {{/if}}
            </ul>
        </div>
    </div>
</div>
<script type="text/javascript">
    (function(){
        var show = false;
        var switchShow  = document.getElementById('cmlphp_console_info_switch');
        var trace    = document.getElementById('cmlphp_console_info_content');
        var cmlphp_console_info_minisize = document.getElementById('cmlphp_console_info_minisize');
        var cmlphp_console_info = document.getElementById("cmlphp_console_info");
        var cmlphp_console_info_simpleinfo = document.getElementById("cmlphp_console_info_simpleinfo");
        var cmlphp_console_info_logo = document.getElementById("cmlphp_console_info_logo");

        cmlphp_console_info_minisize.onclick = function() {
            cmlphp_console_info_minisize.style.display = "none";
            show = true;
            trace.style.display = "none";
            cmlphp_console_info.style.width = "110px";
            cmlphp_console_info_simpleinfo.style.display="none"
        };

        var $showFunc = function() {
            cmlphp_console_info_minisize.style.display = "inline-block";
            cmlphp_console_info_simpleinfo.style.display = "inline-block";
            cmlphp_console_info.style.width = "100%";
        };
        cmlphp_console_info_logo.onclick = $showFunc;

        switchShow.onclick = function(){
            if (show) {
                trace.style.display = 'none';
            } else {
                $showFunc();
                trace.style.display = 'block';
            }
            show = !show;
        };
    })();
</script>