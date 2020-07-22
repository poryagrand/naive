<?php

use System\Controller\Route;


Route::createGroup("Dash", [
    "path" => "Dashboard/",
    "middlewares" => [
        "UI.general"
    ],
    "attaches" => [],
    "get" => [
        "lang" => "system.lang"
    ],
    "post" => [],
    //"file"=>[],
    "error" => null,
    "output" => function ($req, $res, $mid, $ret) {
        return $ret;
    }
]);
