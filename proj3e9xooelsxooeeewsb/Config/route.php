<?php
//应用路由配置文件
use Cml\Route;

Route::any(
   'api/token', 'api/Index/index'
);

Route::any(
    '/api/tokens', 'api/Index/index'
);

