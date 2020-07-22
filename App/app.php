<?php

use System\Controller\Route;
use System\Controller\TemplateEngine\RiverCache;
use System\Communicate\Debug\Console;
use System\Controller\RouteRegister;
use App\Toast;
use System\Communicate\Cookie;
use System\Communicate\Session;


require_once(Route::APP . "/Others/Toast.class.php");
require_once(Route::APP . "/Others/Email.class.php");


RiverCache::underDevelope(true);
Console::enable(true);


Cookie::storageName("secure_cookie");
Cookie::setSecKey("4f7sd765s89HgEcfN76ds82faJKsfu978");
Cookie::setExpireAfter(365);
Cookie::setDefaultDataExpire(10);

Session::storageName("secure_session_data_storage");
Session::setExpireAfter(7);
Session::setDefaultDataExpire(1 * 60 * 60);


Email::setHost("localhost");
Email::setUserName("username");
Email::setPassword("*******");
Email::setPort("5038");
Email::setFrom("no-reply");
Email::setName("sample name");
Email::setSMTPAutoTLS(true);
Email::setSMTPAuth(true);


if (!Cookie::has("site_language")) {
    Cookie::get("site_language")->value = "fa";
    Cookie::save();
}



Route::onBefore(Route::ALL, function ($route) {
    $route->res()->header("X-Powered-By", "PoryaGrand");
});


Route::onEnd(Route::ALL, function () {
    if (Toast::isAutoFlush() && RouteRegister::getAttaches("api") === null && !RouteRegister::getAttaches("noConsole")) {
        Toast::flush();
    }
});
