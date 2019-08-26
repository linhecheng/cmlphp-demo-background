# CmlPHP-快速、稳定、易维护的php框架

## 简介

> CmlPHP从12年开始开发。从最早追求尽可能轻量，php5.2-的语法。到后面不断总结工作中碰到的实际的问题，加入工程化的一些东西。加入Composer的支持。加入了很多可以减少程序员开发时间的一些特性。现在发布了v2.x。提供了API快速开发的组件或者说基于CmlPHP v2.x的一个项目演示(自动从注释生成接口文档)。不说什么跟xx框架比。比来比去可一点都不好玩，适合就好。这个框架是我到目前总结的尽可能提高自己开发效率的工具集(或者有更好的说法？)。提供给需要它的朋友，希望它可以帮助大家更轻松的完成开发的工作.

关于cmlphp的介绍也可以看看我的这篇文章:[再来聊聊cmlphp](http://www.jianshu.com/p/b03b3d72108c)

## v2.x

> CmlPHP v2.x 是一个免费的遵循apache协议的全能型php开源框架

> CmlPHP v2.x 是基于php5.3+(v2.7+要求php5.4+)版本(已经测试过php7)开发的MVC/HMVC/MVSC/HMVSC框架,支持composer、分布式数据库、分布式缓存，支持文件、memcache、redis、apc等缓存，支持多种url模式、URL路由[RESTful]，支持多项目集成、第三方扩展、支持插件。

> CmlPHP v2.x 在底层数据库查询模块做了缓存集成，开发者无需关注数据缓存的问题，按照相应的API调用即可获得最大性能。从而从根本上避免了新手未使用缓存，或缓存使用不当造成的性能不佳的问题。也杜绝了多人协同开发缓存同步及管理的问题

> CmlPHP v2.x 支持根目录、子目录，单入口、多入口部署、支持独立服务器、虚拟主机、VPS等多种环境，绝大部分开发环境可直接运行，无需配置伪静态规则(部分低版本server只要修改框架URL配置即可，框架会自动处理)，快速上手开发。线上环境对SEO有要求时再配置伪静态即可。

> CmlPHP v2.x 自带强大的安全机制，支持多种缓存并可轻松切换,帮你解决开发中各种安全及性能问题，保证站点稳定、安全、快速运行

> CmlPHP v2.x 提供了详细的开发文档，方便新手快速入门

> CmlPHP v2.x 拥有灵活的扩展机制，自带了常用的扩展

> CmlPHP v2.x 拥有灵活配置规则，开发、线上互不干扰

> CmlPHP v2.x 拥有简单高效的插件机制，方便你对系统功能进行扩展

> CmlPHP v2.x 提供了简单方便的debug相关工具方便开发调试。线上模式提供了详细的错误log方便排查

> CmlPHP v2.x 适用于大、中、小各种类型的Web应用开发。API接口开发

> CmlPHP v2.x 支持Session分布式存储

> CmlPHP v2.x 支持守护工作进程

> CmlPHP v2.x 提供了命令运行支持

## v2.7.x
> 服务化。各个组件使用容器来管理、注入依赖。封装了FastRoute、blade、whoops的服务可在入口中注入替换内置的相关组件(默认还是使用框架内置的)

## v2.6.x
> 从v2.6.0 正式引入MongoDB的支持

## 开发手册
开发手册使用gitbook编写
[CmlPHP v2.x开发手册](http://doc.cmlphp.com "CmlPHP v2.x开发手册")

## 你们想要的Api文档
> 部分看了开发手册的朋友给我发邮件希望我提供一份详细的Api文档,以便更深入的学习CmlPHP，现在它来啦!! [CmlPHP v2.x Api](http://api.cmlphp.com)。

## 项目推荐目录骨架
> 提供了基础目录结构及示例，[点击这里查看](https://github.com/linhecheng/cmlphp-demo)。

## Api项目示例
> web开发中很大一部分是接口开发，本示例包含了api开发的两个接口示例以及根据代码注释自动生成文档的示例。 [点击这里查看](https://github.com/linhecheng/cmlphp-api-demo)


## 视频教程
> [CmlPHP简介](http://v.youku.com/v_show/id_XMTQwNTc1MTI0MA==.html)
> 
> [CmlPHP项目目录骨架及api项目演示](http://v.youku.com/v_show/id_XMTQwNTc4MDk2OA==.html)

## 联系我
因为工作的原因QQ用得很少，所以也就不建qq群了。有任何建议或问题欢迎给我发邮件。 linhechengbush@live.com
