基于layui+cmlphp开发基础后台管理系统，提供用户权限管理、日志管理等基础模块。登录插件化。提供FormBuildServer和GridBuildServer。通过后端生成form表单及列表页面

#### 注意事项
##### 下载后请修改 
 * `projllnnzxleeggwsb/Config/common.php`中的 *auth_key*的值
 * 如果不能将站点根目录配置到public下。安全起见请修改目录`proj3e9xooelsxooeeewsb`为其它目录名。同时修改public/index.php入口文件中的相应的`proj3e9xooelsxooeeewsb`为新的目录名 
 
##### 相关的数据库文件为 根目录下的`db.sql`

##### 框架使用请参考[相关手册](http://cmlphp.com/) 

##### 初始用户名密码  admin 123456
##### 为了方便搭建demo cache驱动设置为File。建议改成Redis



#### 插件勾子
```php
admin_login_plugin。用于挂载第三方登录。如:
//qq登录
\Cml\Plugin::mount('admin_password_login_error', function($params = []) {
    return \qq\Interfaceqq::CheckQQUser($params[0], $params[1]);
});

admin_not_login 后台用户未登录。可用于跳转到未登录的中转提示页
admin_not_acl 后台用户没有权限访问该模块.可用于跳转到没权限的跳转提示页
before_add_user_save 保存新用户信息前，可用于判断用户是否存在。获取第三方用户信息等。传递给插件的第一个参数为用户名如:
    \Cml\Plugin::mount('before_add_user_save', function($params = []) {
        $user =  \qq\Interfaceqq::GetUserInfo($params[0]);
        $user || $this->renderJson(1, '该qq用户不存在!');

        $data['nickname'] = $user->UserName;
        return $data;
    });
before_add_or_edit_user渲染新增用户表单前。用于控制要隐藏的字段。有nickname 和password。return false。即不显示。昵称和密码都使用第三方的
。返回username=>'请输入用户名'则控制用户名提示框的信息。如：username字段除了在修改用户信息前的提示还用于控制登录表单中的提示
    \Cml\Plugin::mount('before_add_or_edit_user', function($params = []) {
       return ['nickname' => false, 'password' => false, 'username' => '请输入qq号'];
    });
```

### [使用说明](https://github.com/linhecheng/cmlphp-demo-background/wiki)

#### 以下为截图
![](http://cdn.cmlphp.com/cmlphp_layui_background.png)
![](http://cdn.cmlphp.com/cmlphp_layui_background_login.png)