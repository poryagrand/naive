<?php

use System\Communicate\Debug\Console;
use System\Controller\Language;
use System\Controller\Request;
use System\Controller\Response;
use System\Controller\Route;

Route::group(["Dash"], function ($path, $error, $middlewares, $get, $post, $file) {
    Route::register("")
        ->post([])
        ->onError(Route::UrlParams | Route::MiddleWare | Route::MethodParams | Route::Others, function () {
            Console::log("Error");
        })
        ->handle(function (Request $req, Response $res, array $mid) {
            $res->share("title",Language::get("word.dashboard"));
            return $res->view();
        });
});
