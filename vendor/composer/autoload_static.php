<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit9a39925464c3a10f52b57f15fc8e0d69
{
    public static $prefixLengthsPsr4 = array (
        'C' => 
        array (
            'Cml\\Vendor\\' => 11,
            'Cml\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Cml\\Vendor\\' => 
        array (
            0 => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Vendor',
        ),
        'Cml\\' => 
        array (
            0 => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml',
        ),
    );

    public static $classMap = array (
        'Cml\\Cache\\Apc' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Cache/Apc.php',
        'Cml\\Cache\\Base' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Cache/Base.php',
        'Cml\\Cache\\File' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Cache/File.php',
        'Cml\\Cache\\Memcache' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Cache/Memcache.php',
        'Cml\\Cache\\Redis' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Cache/Redis.php',
        'Cml\\Cml' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Cml.php',
        'Cml\\Config' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Config.php',
        'Cml\\Console\\Command' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Command.php',
        'Cml\\Console\\Commands\\ApiAutoTest' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Commands/ApiAutoTest.php',
        'Cml\\Console\\Commands\\CreateSymbolicLink' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Commands/CreateSymbolicLink.php',
        'Cml\\Console\\Commands\\DaemonProcessManage\\AddTask' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Commands/DaemonProcessManage/AddTask.php',
        'Cml\\Console\\Commands\\DaemonProcessManage\\Reload' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Commands/DaemonProcessManage/Reload.php',
        'Cml\\Console\\Commands\\DaemonProcessManage\\RmTask' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Commands/DaemonProcessManage/RmTask.php',
        'Cml\\Console\\Commands\\DaemonProcessManage\\Start' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Commands/DaemonProcessManage/Start.php',
        'Cml\\Console\\Commands\\DaemonProcessManage\\Status' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Commands/DaemonProcessManage/Status.php',
        'Cml\\Console\\Commands\\DaemonProcessManage\\Stop' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Commands/DaemonProcessManage/Stop.php',
        'Cml\\Console\\Commands\\Help' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Commands/Help.php',
        'Cml\\Console\\Commands\\Make\\Controller' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Commands/Make/Controller.php',
        'Cml\\Console\\Commands\\Make\\Model' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Commands/Make/Model.php',
        'Cml\\Console\\Commands\\Migrate\\AbstractCommand' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Commands/Migrate/AbstractCommand.php',
        'Cml\\Console\\Commands\\Migrate\\Breakpoint' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Commands/Migrate/Breakpoint.php',
        'Cml\\Console\\Commands\\Migrate\\Create' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Commands/Migrate/Create.php',
        'Cml\\Console\\Commands\\Migrate\\Migrate' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Commands/Migrate/Migrate.php',
        'Cml\\Console\\Commands\\Migrate\\Rollback' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Commands/Migrate/Rollback.php',
        'Cml\\Console\\Commands\\Migrate\\SeedCreate' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Commands/Migrate/SeedCreate.php',
        'Cml\\Console\\Commands\\Migrate\\SeedRun' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Commands/Migrate/SeedRun.php',
        'Cml\\Console\\Commands\\Migrate\\Status' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Commands/Migrate/Status.php',
        'Cml\\Console\\Commands\\RunAction' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Commands/RunAction.php',
        'Cml\\Console\\Component\\Box' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Component/Box.php',
        'Cml\\Console\\Component\\Dialog' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Component/Dialog.php',
        'Cml\\Console\\Component\\Progress' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Component/Progress.php',
        'Cml\\Console\\Console' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Console.php',
        'Cml\\Console\\Format\\Colour' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Format/Colour.php',
        'Cml\\Console\\Format\\Format' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/Format/Format.php',
        'Cml\\Console\\IO\\Input' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/IO/Input.php',
        'Cml\\Console\\IO\\Output' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Console/IO/Output.php',
        'Cml\\Container' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Container.php',
        'Cml\\Controller' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Controller.php',
        'Cml\\Db\\Base' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Db/Base.php',
        'Cml\\Db\\MongoDB\\MongoDB' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Db/MongoDB/MongoDB.php',
        'Cml\\Db\\MySql\\Pdo' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Db/MySql/Pdo.php',
        'Cml\\Debug' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Debug.php',
        'Cml\\Encry' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Encry.php',
        'Cml\\ErrorOrException' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/ErrorOrException.php',
        'Cml\\Exception\\CacheConnectFailException' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Exception/CacheConnectFailException.php',
        'Cml\\Exception\\ConfigNotFoundException' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Exception/ConfigNotFoundException.php',
        'Cml\\Exception\\ControllerNotFoundException' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Exception/ControllerNotFoundException.php',
        'Cml\\Exception\\FileCanNotReadableException' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Exception/FileCanNotReadableException.php',
        'Cml\\Exception\\MkdirErrorException' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Exception/MkdirErrorException.php',
        'Cml\\Exception\\PdoConnectException' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Exception/PdoConnectException.php',
        'Cml\\Exception\\PhpExtendNotInstall' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Exception/PhpExtendNotInstall.php',
        'Cml\\Http\\Cookie' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Http/Cookie.php',
        'Cml\\Http\\Input' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Http/Input.php',
        'Cml\\Http\\Request' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Http/Request.php',
        'Cml\\Http\\Response' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Http/Response.php',
        'Cml\\Http\\Session' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Http/Session.php',
        'Cml\\Interfaces\\Cache' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Interfaces/Cache.php',
        'Cml\\Interfaces\\Console' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Interfaces/Console.php',
        'Cml\\Interfaces\\Db' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Interfaces/Db.php',
        'Cml\\Interfaces\\Debug' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Interfaces/Debug.php',
        'Cml\\Interfaces\\Environment' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Interfaces/Environment.php',
        'Cml\\Interfaces\\ErrorOrException' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Interfaces/ErrorOrException.php',
        'Cml\\Interfaces\\Lock' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Interfaces/Lock.php',
        'Cml\\Interfaces\\Logger' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Interfaces/Logger.php',
        'Cml\\Interfaces\\Queue' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Interfaces/Queue.php',
        'Cml\\Interfaces\\Route' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Interfaces/Route.php',
        'Cml\\Interfaces\\View' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Interfaces/View.php',
        'Cml\\Lang' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Lang.php',
        'Cml\\Lock' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Lock.php',
        'Cml\\Lock\\Base' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Lock/Base.php',
        'Cml\\Lock\\File' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Lock/File.php',
        'Cml\\Lock\\Memcache' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Lock/Memcache.php',
        'Cml\\Lock\\Redis' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Lock/Redis.php',
        'Cml\\Log' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Log.php',
        'Cml\\Logger\\Base' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Logger/Base.php',
        'Cml\\Logger\\Db' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Logger/Db.php',
        'Cml\\Logger\\File' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Logger/File.php',
        'Cml\\Logger\\Redis' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Logger/Redis.php',
        'Cml\\Model' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Model.php',
        'Cml\\Plugin' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Plugin.php',
        'Cml\\Queue' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Queue.php',
        'Cml\\Queue\\Base' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Queue/Base.php',
        'Cml\\Queue\\Redis' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Queue/Redis.php',
        'Cml\\Route' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Route.php',
        'Cml\\Secure' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Secure.php',
        'Cml\\Service' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Service.php',
        'Cml\\Service\\Blade' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Service/Blade.php',
        'Cml\\Service\\Environment' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Service/Environment.php',
        'Cml\\Service\\FastRoute' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Service/FastRoute.php',
        'Cml\\Service\\Route' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Service/Route.php',
        'Cml\\Service\\Whoops' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Service/Whoops.php',
        'Cml\\Session' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Session.php',
        'Cml\\Tools\\Apidoc\\AnnotationToDoc' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Tools/Apidoc/AnnotationToDoc.php',
        'Cml\\Tools\\Apidoc\\AutoTest' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Tools/Apidoc/AutoTest.php',
        'Cml\\Tools\\Daemon\\ProcessManage' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Tools/Daemon/ProcessManage.php',
        'Cml\\Tools\\StaticResource' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Tools/StaticResource.php',
        'Cml\\Vendor\\Acl' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Vendor/Acl.php',
        'Cml\\Vendor\\Email' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Vendor/Email.php',
        'Cml\\Vendor\\Excel' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Vendor/Excel.php',
        'Cml\\Vendor\\File' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Vendor/File.php',
        'Cml\\Vendor\\Ftp' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Vendor/Ftp.php',
        'Cml\\Vendor\\Html' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Vendor/Html.php',
        'Cml\\Vendor\\Http' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Vendor/Http.php',
        'Cml\\Vendor\\Image' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Vendor/Image.php',
        'Cml\\Vendor\\Ip2Region' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Vendor/Ip2Region.php',
        'Cml\\Vendor\\IpLocation' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Vendor/IpLocation.php',
        'Cml\\Vendor\\Page' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Vendor/Page.php',
        'Cml\\Vendor\\PhpThread' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Vendor/PhpThread.php',
        'Cml\\Vendor\\Pingyin' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Vendor/Pingyin.php',
        'Cml\\Vendor\\Quenue' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Vendor/Quenue.php',
        'Cml\\Vendor\\Socket' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Vendor/Socket.php',
        'Cml\\Vendor\\StringProcess' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Vendor/StringProcess.php',
        'Cml\\Vendor\\Tree' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Vendor/Tree.php',
        'Cml\\Vendor\\UploadFile' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Vendor/UploadFile.php',
        'Cml\\Vendor\\Validate' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Vendor/Validate.php',
        'Cml\\Vendor\\VerifyCode' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Vendor/VerifyCode.php',
        'Cml\\View' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/View.php',
        'Cml\\View\\Base' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/View/Base.php',
        'Cml\\View\\Excel' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/View/Excel.php',
        'Cml\\View\\Html' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/View/Html.php',
        'Cml\\View\\Json' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/View/Json.php',
        'Cml\\View\\Xml' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/View/Xml.php',
        'Cml\\dBug' => __DIR__ . '/..' . '/linhecheng/cmlphp/src/Cml/Debug.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit9a39925464c3a10f52b57f15fc8e0d69::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit9a39925464c3a10f52b57f15fc8e0d69::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit9a39925464c3a10f52b57f15fc8e0d69::$classMap;

        }, null, ClassLoader::class);
    }
}
