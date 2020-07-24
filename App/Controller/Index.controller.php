<?php

use System\Communicate\Debug\Console;
use System\Controller\Language;
use System\Controller\Request;
use System\Controller\Response;
use System\Controller\Route;
Route::group(["Dash"], function ($path, $error, $middlewares,$output, $methods) {
    Route::register("")
        ->post([])
        ->put([
            "ab"=>"system.free.max255"
        ])
        ->onError(Route::UrlParams | Route::MiddleWare | Route::MethodParams | Route::Others, function () {
            echo "Error";
        })
        ->onError(Route::Method,function(){
            echo "Method Not Exist";
        })
        ->handle(function (Request $req, Response $res, array $mid) {
            $res->share("title",Language::get("word.dashboard"));
            return $res->view();
        });
});
