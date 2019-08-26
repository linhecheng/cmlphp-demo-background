layui.use('jquery', function() {
    $ = layui.$;
    console.log($(window.parent.document).find('.admin-app-list'));
    if ($(window.parent.document).find('.layui-layout-admin').length == 1) {//在iframe中进入此页  刷新上级页面
        window.parent.location.reload();
    }

    $('img.code').click(function() {
        $('img.code').attr('src', veri_code_url + '/haha/' + Math.random());
    });

    var working = false;
    $('.login').on('submit', function(e) {
        e.preventDefault();
        if (working) return;
        working = true;
        var $this = $(this),
            $state = $this.find('button > .state');
        $this.addClass('loading');
        $state.html('Authenticating');

        var base64 = new Base64();

        $.post(login_url,
            {username:base64.encode($('input[name="username"]').val()), password:base64.encode($('input[name="password"]').val()), code:$('input[name="code"]').val()}
            , function(data) {
                if (data.code == 0) {
                    $this.addClass('ok');
                    $state.html('Login Success!');
                    setTimeout(function() {
                        window.location = jump_url;
                    }, 1500);
                } else {
                    $this.addClass('error');
                    $state.html(data.msg);
                    $('.wrapper').on('click', function(e) {
                        e.preventDefault();
                        $state.html('Log in');
                        $this.removeClass('error loading');
                        working = false;
                        $('.wrapper').off('click');

                        $('input.code').val('');
                        $('img.code').click();
                    });
                }
            });
    });
});


function Base64() {
    // private property
    _keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    // public method for encoding
    this.encode = function (input) {
        var output = "";
        var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
        var i = 0;
        input = _utf8_encode(input);
        while (i < input.length) {
            chr1 = input.charCodeAt(i++);
            chr2 = input.charCodeAt(i++);
            chr3 = input.charCodeAt(i++);
            enc1 = chr1 >> 2;
            enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
            enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
            enc4 = chr3 & 63;
            if (isNaN(chr2)) {
                enc3 = enc4 = 64;
            } else if (isNaN(chr3)) {
                enc4 = 64;
            }
            output = output +
                _keyStr.charAt(enc1) + _keyStr.charAt(enc2) +
                _keyStr.charAt(enc3) + _keyStr.charAt(enc4);
        }
        return output;
    };
    // private method for UTF-8 encoding
    _utf8_encode = function (string) {
        string = string.replace(/\r\n/g,"\n");
        var utftext = "";
        for (var n = 0; n < string.length; n++) {
            var c = string.charCodeAt(n);
            if (c < 128) {
                utftext += String.fromCharCode(c);
            } else if((c > 127) && (c < 2048)) {
                utftext += String.fromCharCode((c >> 6) | 192);
                utftext += String.fromCharCode((c & 63) | 128);
            } else {
                utftext += String.fromCharCode((c >> 12) | 224);
                utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                utftext += String.fromCharCode((c & 63) | 128);
            }

        }
        return utftext;
    };
}
