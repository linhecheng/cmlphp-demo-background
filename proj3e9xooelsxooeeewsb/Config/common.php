<?php
//应用公共配置文件
return [
    'auth_key' => 'xoll-lximf-elxe',
    'userauthid' => 'lbauth',
    'url_default_action' => CML_URL_DEFAULT_ACTION,
    'cmlframework_system_route' => [
        'cmlframeworkstaticparse' => '\\Cml\\Tools\\StaticResource::parseResourceFile',
        'cml_calc_veryfy_code' => '\\Cml\\Vendor\\VerifyCode::calocVerify'
    ],
    //'static__path' => '/',
    'url_html_suffix' => '.shtml',
    'url_model' => 1,
];
