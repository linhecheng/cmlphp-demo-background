<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
* @Date: 2016/12/28 11:28
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 自动加载
 * *********************************************************** */
spl_autoload_register(function ($class) {
    /*    "Cml\\": "src/Cml/",
          "Cml\\Vendor\\": "src/Vendor/"*/
    $class = explode('\\', $class);
    $topNamespace = array_shift($class);
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src';
    switch ($topNamespace) {
        case 'Cml':
            $dir .= DIRECTORY_SEPARATOR . ($class[0] == 'Vendor' ? 'Vendor' : 'Cml');
            break;
        case'tests':
            $dir .= DIRECTORY_SEPARATOR . 'tests';
            break;
    }
    $file = $dir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $class) . '.php';
    is_file($file) && require($file);
}, true, true);