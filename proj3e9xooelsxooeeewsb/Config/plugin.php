<?php
\Cml\Plugin::mount('cml.after_parse_path_info', function () {
    $pathInfo = \Cml\Route::getPathInfo();
    if (
    in_array($pathInfo[0], ['cusadmin'])
    ) {
        \Cml\Config::set('route_app_hierarchy', 2);
    }
});
