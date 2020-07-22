<?php

use System\Controller\Language;
use System\Controller\Route;
use System\Controller\Response;
use System\Security\Crypt;
use System\Controller\Request;


Route::middleWare("UI.general", function (Request $req,Response $res,array $mid) {
    $res->share("sitename", Language::get("site.title"));
    return true;
});
