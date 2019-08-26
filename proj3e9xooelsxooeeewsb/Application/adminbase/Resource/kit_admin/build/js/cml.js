/**
 * 定义cml模块
 *
 */
layui.define(['layer', 'laydate', 'laypage', 'form', 'tab', 'jquery'], function (exports) {
    "use strict";
    var layer = layui.layer;
    var laypage = layui.laypage;
    var form = layui.form;
    window.$ = layui.$;
    form.verify({
        length: function (value, item) {
            if (value.length < $(item).attr('lay-min')) {
                return '必须大于' + $(item).attr('lay-min') + '个字符'
            }

            if (value.length > $(item).attr('lay-max')) {
                return '必须小于' + $(item).attr('lay-max') + '个字符'
            }
        },
        gte: function (value, item) {
            if (value.length < $(item).attr('lay-gte')) {
                return '不小于' + $(item).attr('lay-gte') + '个字符'
            }
        },
        lte: function (value, item) {
            if (value.length > $(item).attr('lay-lte')) {
                return '不多于' + $(item).attr('lay-lte') + '个字符'
            }
        },
        eq: function (value, item) {
            if (value.length != $(item).attr('lay-eq')) {
                return '必须为' + $(item).attr('lay-eq') + '个字符'
            }
        },
        eq_or_not: function (value, item) {
            if (value.length != 0 && value.length != $(item).attr('lay-len')) {
                return '必须为' + $(item).attr('lay-len') + '个字符'
            } else {
                return false;
            }
        },
        zh_cn: function (value, item) {
            if (!/^([\u4E00-\u9FA5\uF900-\uFA2D])+$/.test(value)) {
                return '必须为中文'
            }
        },
        en: function (value, item) {
            if (!/^([a-zA-Z])+$/.test(value)) {
                return '只能为英文字母'
            }
        },
        en_number: function (value, item) {
            if (!/^([a-zA-Z_0-9])+$/.test(value)) {
                return '只能为字母数字和下划线'
            }
        },
        repeat: function (value, item) {
            /* if (value.length < 1) {
             return '必填项不能为空'
             }*/

            if (value != $($(item).attr('lay-repeat')).val()) {
                return $(item).attr('lay-repeat-text')
            }
        }
    });

    var cml = {
        layeditIndex: [],
        loadingIndex: 0,
        currentDataPageUrl: '',
        currentPage: 1,
        initPageUrl: '',
        layerWidth: "auto",
        preLayerWidth: "auto",
        dontReloadAjax: false,
        layerOffset:'auto',
        reloadScrollTop:true,
        checkId:undefined,

        vueCreatedHook:function() {

        },
        vueMountedHook:function() {

        },
        vueUpdatedHook:function() {

        },

        initPage: function (url, layerWidth, checkId,selector) {
            this.checkId=checkId;

            cml.layerWidth = layerWidth;
            cml.preLayerWidth = layerWidth;

            if (!url) {
                cml.bindReloadAndFullScreen();
                return;
            }

            this.initVueInst(selector?selector:".data_form_box", undefined,undefined,undefined,undefined,undefined, function(){cml.bindReloadAndFullScreen();cml.bindSearchBtn(url)})

            this.initPageUrl = url;

            this.loadAjaxPage(url, 1);
        },

        bindReloadAndFullScreen : function() {
            $('fieldset > legend > button.reload_page').on('click', function () {
                window.location.reload();
            });

            $('fieldset > legend > button.open_full_screen').on('click', function () {
                window.parent.openFullScreen(window.location.href);
            });
        },

        bindSearchBtn : function(url) {
            $('.btn-search').unbind('keydown').bind('keydown', function (event) {
                if (event.keyCode == "13") {
                    event.preventDefault();
                }
            }).unbind('click').click(function (e) {
                e.preventDefault();
                cml.loadAjaxPage(url + 'page=1&' + $('.search_form').serialize(), 1);
            });

            $('.search_form').unbind('keydown').bind('keydown', function (event) {
                if (event.keyCode == "13") {
                    event.preventDefault();
                    $('.btn-search').click();
                }
            });
        },

        initVueInst: function (dom, vm, func, data, method, update, mounted) {
            var checkId=this.checkId;
            var vmData = typeof(data) == 'undefined' ? {
                checkBtnValue: [],
                checkedIds: [],
                list: [],
                totalNum: 0
            } : data;

            var vmMethod = typeof(method) == 'undefined' ? {
                view: function (title, url, width) {
                    cml.form.view(title, url, width);
                },
                add: function (title, form_url, save_url, width, func, showok, cancelCallback) {
                    cml.form.add(title, form_url, save_url, width, func, showok, cancelCallback);
                },
                edit: function (title, form_url, save_url, width, func, showok, cancelCallback) {
                    cml.form.edit(title, form_url, save_url, width, func, showok, cancelCallback);
                },
                del: function (url, id, msg, func) {
                    cml.form.del(url, id, msg, func);
                },
                disable: function (url, msg) {
                    cml.form.disable(url, msg);
                },
                newTab: function (url, id, title) {
                    window.parent.tab.tabAdd({
                        url: url,
                        title: title,
                        id: id
                    });
                },
                checkAll: function () {
                    this.cCheckAll = !this.cCheckAll;
                },
                allDel: function (url,func) {
                    if (this.checkedIds.length < 1) {
                        cml.showTip('请选择要删除的项!');
                    } else {
                        cml.form.del(url + checkId + '=' + this.checkedIds.join('|'), checkId, '确认要删除' + this.checkedIds.length + '条数据吗?',func);
                    }
                },
                allDisable: function (url) {
                    if (this.checkedIds.length < 1) {
                        cml.showTip('请选择要禁用的项!');
                    } else {
                        cml.form.disable(url + checkId + '=' + this.checkedIds.join('|'), '确认要禁用' + this.checkedIds.length + '条数据吗?');
                    }
                },
                location : function(url) {
                    cml.form.location(url);
                }
            } : method;

            var vmUpdate = typeof(update) == 'undefined' ? this.vueUpdatedHook : update;
            var vmMounted = typeof(mounted) == 'undefined' ? this.vueMountedHook: mounted;
            var vmMCreated = typeof(func) == 'undefined' ? this.vueCreatedHook: func;

            var myvm = new Vue({
                el: dom,
                data: vmData,
                created: function () {
                    vmMCreated();
                    $('.template').show();
                },
                computed: {
                    cCheckAll: {
                        get: function () {
                            return this.checkedIds.length == this.list.length;
                        },
                        set: function (value) {
                            // console.log(value);
                            var self = this;
                            self.checkedIds = [];
                            this.list.forEach(function (item) {
                                value ? self.checkedIds.push(item[checkId]) : self.checkedIds = [];
                                value ? self.checkBtnValue = 1 : self.checkBtnValue = [];
                            });
                            return value;
                        }
                    }
                },
                methods: vmMethod,
                updated:vmUpdate,
                mounted:vmMounted,
                filters : {
                    date : /**
                     * 时间戳格式化函数
                     * @param  {string} format    格式
                     * @param  {int}    timestamp 要格式化的时间 默认为当前时间
                     * @return {string}           格式化的时间字符串
                     */
                    function (timestamp, format){
                        if (timestamp == null) {
                            return '';
                        }
                        var a, jsdate=((timestamp) ? new Date(timestamp*1000) : new Date());
                        var pad = function(n, c){
                            if((n = n + "").length < c){
                                return new Array(++c - n.length).join("0") + n;
                            } else {
                                return n;
                            }
                        };
                        var txt_weekdays = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
                        var txt_ordin = {1:"st", 2:"nd", 3:"rd", 21:"st", 22:"nd", 23:"rd", 31:"st"};
                        var txt_months = ["", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                        var f = {
                            // Day
                            d: function(){return pad(f.j(), 2)},
                            D: function(){return f.l().substr(0,3)},
                            j: function(){return jsdate.getDate()},
                            l: function(){return txt_weekdays[f.w()]},
                            N: function(){return f.w() + 1},
                            S: function(){return txt_ordin[f.j()] ? txt_ordin[f.j()] : 'th'},
                            w: function(){return jsdate.getDay()},
                            z: function(){return (jsdate - new Date(jsdate.getFullYear() + "/1/1")) / 864e5 >> 0},

                            // Week
                            W: function(){
                                var a = f.z(), b = 364 + f.L() - a;
                                var nd2, nd = (new Date(jsdate.getFullYear() + "/1/1").getDay() || 7) - 1;
                                if(b <= 2 && ((jsdate.getDay() || 7) - 1) <= 2 - b){
                                    return 1;
                                } else{
                                    if(a <= 2 && nd >= 4 && a >= (6 - nd)){
                                        nd2 = new Date(jsdate.getFullYear() - 1 + "/12/31");
                                        return date("W", Math.round(nd2.getTime()/1000));
                                    } else{
                                        return (1 + (nd <= 3 ? ((a + nd) / 7) : (a - (7 - nd)) / 7) >> 0);
                                    }
                                }
                            },

                            // Month
                            F: function(){return txt_months[f.n()]},
                            m: function(){return pad(f.n(), 2)},
                            M: function(){return f.F().substr(0,3)},
                            n: function(){return jsdate.getMonth() + 1},
                            t: function(){
                                var n;
                                if( (n = jsdate.getMonth() + 1) == 2 ){
                                    return 28 + f.L();
                                } else{
                                    if( n & 1 && n < 8 || !(n & 1) && n > 7 ){
                                        return 31;
                                    } else{
                                        return 30;
                                    }
                                }
                            },

                            // Year
                            L: function(){var y = f.Y();return (!(y & 3) && (y % 1e2 || !(y % 4e2))) ? 1 : 0},
                            //o not supported yet
                            Y: function(){return jsdate.getFullYear()},
                            y: function(){return (jsdate.getFullYear() + "").slice(2)},

                            // Time
                            a: function(){return jsdate.getHours() > 11 ? "pm" : "am"},
                            A: function(){return f.a().toUpperCase()},
                            B: function(){
                                // peter paul koch:
                                var off = (jsdate.getTimezoneOffset() + 60)*60;
                                var theSeconds = (jsdate.getHours() * 3600) + (jsdate.getMinutes() * 60) + jsdate.getSeconds() + off;
                                var beat = Math.floor(theSeconds/86.4);
                                if (beat > 1000) beat -= 1000;
                                if (beat < 0) beat += 1000;
                                if ((String(beat)).length == 1) beat = "00"+beat;
                                if ((String(beat)).length == 2) beat = "0"+beat;
                                return beat;
                            },
                            g: function(){return jsdate.getHours() % 12 || 12},
                            G: function(){return jsdate.getHours()},
                            h: function(){return pad(f.g(), 2)},
                            H: function(){return pad(jsdate.getHours(), 2)},
                            i: function(){return pad(jsdate.getMinutes(), 2)},
                            s: function(){return pad(jsdate.getSeconds(), 2)},
                            //u not supported yet

                            // Timezone
                            //e not supported yet
                            //I not supported yet
                            O: function(){
                                var t = pad(Math.abs(jsdate.getTimezoneOffset()/60*100), 4);
                                if (jsdate.getTimezoneOffset() > 0) t = "-" + t; else t = "+" + t;
                                return t;
                            },
                            P: function(){var O = f.O();return (O.substr(0, 3) + ":" + O.substr(3, 2))},
                            //T not supported yet
                            //Z not supported yet

                            // Full Date/Time
                            c: function(){return f.Y() + "-" + f.m() + "-" + f.d() + "T" + f.h() + ":" + f.i() + ":" + f.s() + f.P()},
                            //r not supported yet
                            U: function(){return Math.round(jsdate.getTime()/1000)}
                        };

                        return format.replace(/[\\]?([a-zA-Z])/g, function(t, s){
                            var ret = '';
                            if( t!=s ){
                                // escaped
                                ret = s;
                            } else if( f[s] ){
                                // a date function exists
                                ret = f[s]();
                            } else{
                                // nothing special
                                ret = s;
                            }
                            return ret;
                        });
                    }
                }
            });
            if (typeof(vm) == 'undefined') {
                window.list = myvm;
            } else {
                window[vm] = myvm;
            }
        },

        loadAjaxPage: function (url, currentPage) {
            if (typeof(loadIndexUrl) != 'undefined') {
                cml.reloadCurrentIframe();
                return true;
            }

            if (url == false) {
                url = cml.currentDataPageUrl;
                currentPage = 1;
            }

            if (typeof(currentPage) != 'undefined') {
                cml.currentPage = currentPage;
            }

            cml.currentDataPageUrl = url;
            cml.loadUrl(url, 'json', function (res) {
                if (res.code != 0) {
                    cml.showTip(res.msg);
                    return;
                }

                if (res.data.list.length == 0 && cml.currentPage > 1) {
                    cml.loadAjaxPage(cml.initPageUrl + $('.search_form').serialize() + '&page=' + (cml.currentPage - 1), cml.currentPage - 1);
                    return;
                }

                window.list.$data.list = res.data.list;
                window.list.$data.totalNum = res.data.totalCount;
                window.list.$data.checkedIds = [];
                window.list.$data.checkBtnValue = [];
                laypage.render({
                    elem: $('.show_page'), //容器。值支持id名、原生dom对象，jquery对象,
                    count: res.data.totalCount,
                    limit: res.data.limit, //总页数
                    curr: cml.currentPage,
                    groups: 5,//连续显示分页数
                    jump: function (e, first) { //触发分页后的回调
                        if (!first) {
                            cml.loadAjaxPage(cml.initPageUrl + $('.search_form').serialize() + '&page=' + e.curr, e.curr);
                        }
                    }
                });
                if (cml.reloadScrollTop) {
                    setTimeout(function () {
                        window.scrollTo(0, 0);
                    }, 10);
                }

            });
        },

        /**
         * 加载页面并弹窗
         *
         * @param url 要加载的页面的url
         * @param title 弹窗的标题
         * @param func 弹窗里确定按钮执行的操作
         * @param showok 是否显示确定按钮 传入数组的时候为btn配置项
         * @param cancelCallback 取消按钮的回调
         * @param type 1/3为layer.open 1有绑定form.submit 2为layer.alert
         */
        getDataShowPop: function (url, title, func, showok, cancelCallback, type) {
            this.loadUrl(url, 'html', function (data) {
                cml.showPopBox(data, type, title, func, showok, cancelCallback, type);
            });
        },

        /**
         * 加载一个页面
         * @param url
         * @param type html /json
         * @param func
         */
        loadUrl: function (url, type, func) {
            cml.showLoading();
            $.ajax({
                url: url,
                dataType: type,
                success: function (data) {
                    cml.closeLoading();
                    if (type == 'json' && data.code == -1000000) {
                        window.location.href = data.msg;
                    }
                    func(data);
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    cml.closeLoading();
                    func(XMLHttpRequest.responseText);
                }
            });
        },

        showTip: function (tip, func) {
            cml.showPopBox(tip, 2, '提示', func, 0);
        },

        /**
         * 显示提示信息
         * @param content 弹窗的内容
         * @param type 1/3为layer.open 2为layer.alert
         * @param title 弹窗的标题
         * @param func 确认按钮的回调
         * @param showok 是否显示确认按钮 传入数组的时候为btn配置项
         * @param cancelCallback 取消按钮的回调
         */
        showPopBox: function (content, type, title, func, showok, cancelCallback) {
            if (typeof(type) == 'undefined') {
                type = 1;
            }
            var okfunc = function () {

            };

            if (typeof(func) == 'undefined') {
                okfunc = function (index) {
                    cml.closePopBox(index);
                };
            } else if (!isNaN(func)) {
                okfunc = function (index) {
                    setTimeout(function () {
                        cml.closePopBox(index);
                    }, func);
                }
            } else {
                okfunc = function (index) {
                    if (type == 1) {
                        form.on('submit', function (data) {
                            if (!cml.form.okBtnIsSubmitFormCallBack(index)) {
                                return false;
                            }

                            var $res = func(index);
                            if (typeof($res) == 'undefined' || $res) {
                                cml.closePopBox(index);
                            }
                            return false;
                        });
                        if (cml.layeditIndex.length > 0) {
                            layui.use('layedit', function () {
                                var layedit = layui.layedit;
                                for (var i in cml.layeditIndex) {
                                    layedit.sync(cml.layeditIndex[i]);
                                }
                            });
                        }

                        $('.lay-submit').click();
                    } else {
                        var $res = func(index);
                        if (typeof($res) == 'undefined' || $res) {
                            cml.closePopBox(index);
                        }
                    }
                }
            }

            if (type == 1 || type == 3) {
                var open = {
                    type: 1,
                    skin: 'layui-layer-molv',
                    maxmin: true,
                    area: cml.layerWidth,
                    offset: cml.layerOffset,
                    title: title,
                    content: content,
                    btn: ['确认', '取消'],
                    cancel: function (index) {
                        cml.closePopBox(index)
                    }
                };
                if (typeof(showok) == 'undefined' || showok) {
                    open.btn = ['确认', '关闭'];
                    if (typeof(showok) == 'object') {
                        open.btn = showok;
                    }
                    open.yes = okfunc;
                } else {
                    open.btn = ['关闭'];
                }

                if (typeof(cancelCallback) == 'function') {
                    open.btn2 = function (index, layero) {
                        cancelCallback(index, okfunc);
                        return false;
                    }
                }
                layer.open(open);
            } else {
                var option = {};
                if (typeof(showok) == 'undefined') {
                    option = {closeBtn: 1}
                } else if (typeof(showok) == 'object') {
                    option = showok;
                }
                layer.alert(content, option, function (index) {
                    okfunc(index);
                });
            }
        },

        /**
         * 关闭弹窗
         */
        closePopBox: function (index) {
            layer.close(index);
        },

        /**
         * 保存或更新数据后重新载入当前iframe
         */
        reloadCurrentIframe: function (url, data) {
            window.location.reload();
        },

        /**
         * 显示确认框
         *
         * @param tip 确认文字
         * @param func 点击确认执行的函数
         * @param option layer的配置项
         */
        showConfirm: function (tip, func, option) {
            cml.showPopBox(tip, 2, '确认?', func, option);
        },
        /**
         * 显示Loading
         */
        showLoading: function () {
            cml.loadingIndex = layer.load(2, {shade: 0.001});
        },
        /**
         * 关闭loading
         */
        closeLoading: function () {
            cml.loadingIndex > 0 && layer.close(cml.loadingIndex);
            cml.loadingIndex = 0;
        },

        //显示提示
        showMsg: function (msg, autoCloseTime) {
            if (typeof (autoCloseTime) == 'undefined') {
                autoCloseTime = 10000;
            }
            cml.showPopBox(msg, 2, '提示', autoCloseTime, false, false);
        },

        form: {
            //表单操作成功后的回调
            operateSuccessCallBack: function () {
            },

            //表单确认按钮的回调, 默认提交表单保存数据,如果这边return false。则不提交表单-用于二次确认的场景
            okBtnIsSubmitFormCallBack: function () {
                return true;
            },

            /**
             * 添加数据
             * @param title 表单标题
             * @param url 加载表单的url
             * @param purl 保存表单数据的url
             */
            add: function (title, url, purl, width, func, showok, cancelCallback) {
                return cml.form.edit(title, url, purl, width, func, showok, cancelCallback);
            },

            /**
             * 列表页面中打开子页面-没有确定取消按钮
             * @param title
             * @param url
             * @param width
             */
            view: function (title, url, width) {
                if (typeof (width) == 'undefined') {
                    width = false;
                }
                if (width) {
                    cml.layerWidth = width;
                } else {
                    cml.layerWidth = cml.preLayerWidth;
                }

                if (typeof(event) != "undefined") {
                    if (typeof(event.preventDefault) == 'function') {
                        event.preventDefault();
                    } else {
                        window.event.returnValue = false;
                    }
                }

                cml.getDataShowPop(url, title, undefined, false);
            },

            /**
             * 修改数据
             * @param title 表单标题
             * @param url 加载表单的url
             * @param purl 保存表单数据的url
             * @param width 弹出弹的宽高
             */
            edit: function (title, url, purl, width, func, showok, cancelCallback) {
                if (typeof (width) == 'undefined') {
                    width = false;
                }
                if (width) {
                    cml.layerWidth = width;
                } else {
                    cml.layerWidth = cml.preLayerWidth;
                }

                if (typeof(event) != "undefined") {
                    if (typeof(event.preventDefault) == 'function') {
                        event.preventDefault();
                    } else {
                        window.event.returnValue = false;
                    }
                }

                cml.getDataShowPop(url, title, function (index) {
                    if (!purl) {
                        cml.form.operateSuccessCallBack();
                        return;
                    }
                    var form = $('form.data_forum');
                    cml.showLoading();
                    $.ajax({
                        url: purl,
                        type: 'post',
                        dataType: 'json',
                        data: form.serialize(),
                        success: function (data) {
                            cml.closeLoading();
                            if (data.code == 0) {
                                cml.showTip(data.msg, function () {
                                    if (purl.replace('.html', '').substr(-12) != 'saveSelfInfo') {
                                        if (typeof(cml.currentDataPageUrl) != 'undefined' && cml.currentDataPageUrl != "" && !cml.dontReloadAjax) {
                                            cml.loadAjaxPage(cml.currentDataPageUrl);
                                        }
                                    }
                                    if (typeof (func) == 'function') {
                                        func(data.data);
                                    }
                                    cml.form.operateSuccessCallBack();
                                    cml.closePopBox(index);
                                });
                            } else if (data.code == -1000000) {
                                window.location.href = data.msg;
                            } else {
                                cml.showTip(data.msg);
                                return false;
                            }
                        },
                        error: function (XMLHttpRequest, textStatus, errorThrown) {
                            cml.closeLoading();
                            cml.showTip(XMLHttpRequest.responseText);
                        }
                    });
                    return false;
                }, showok, cancelCallback);
            },

            /**
             * 删除数据的url
             *
             * @param url
             * @param id
             * @param msg
             */
            del: function (url, id, msg, func) {
                if (typeof(msg) == 'undefined' || msg == '') {
                    msg = '确定要删除ID为' + id + '的记录么？';
                }

                if (typeof(event) != "undefined") {
                    if (typeof(event.preventDefault) == 'function') {
                        event.preventDefault();
                    } else {
                        window.event.returnValue = false;
                    }
                }

                cml.showConfirm(msg, function () {
                    cml.loadUrl(url, 'json', function (data) {
                        cml.showTip(data.msg, function () {
                            if (!cml.dontReloadAjax) {
                                if (typeof (window.list) != 'undefined') {
                                    cml.loadAjaxPage(cml.currentDataPageUrl);
                                } else if (typeof(window.dontreload) == 'undefined') {
                                    cml.reloadCurrentIframe();
                                }
                            }
                            if (typeof (func) == 'function') {
                                func();
                            }

                            cml.form.operateSuccessCallBack();
                        });
                    });
                });
            },

            /**
             * 禁用解禁的url
             *
             * @param url
             * @param msg
             */
            disable: function (url, msg) {
                if (typeof(msg) == 'undefined' || msg == '') {
                    msg = '确定要操作么？';
                }
                cml.showConfirm(msg, function () {
                    cml.closePopBox(2);
                    cml.loadUrl(url, 'json', function (data) {
                        cml.showTip(data.msg, function (index) {
                            cml.loadAjaxPage(cml.currentDataPageUrl);
                        });
                        cml.form.operateSuccessCallBack();
                    });
                });
            },
            location : function(url) {
                window.location.href=url;
            }
        }
    };

    exports('cml', cml);
});
