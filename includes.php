<?php
use System\Controller\Language;
use System\Controller\Route;

date_default_timezone_set('UTC');

define("__ROOT__",rtrim(__DIR__,"/\\"));
define("__APP_FOLDER__","App");
define("__APP__",rtrim(__DIR__,"/\\") . DIRECTORY_SEPARATOR .__APP_FOLDER__);
define("__HOST__",(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}");
define("__HOSTWOS__",$_SERVER['HTTP_HOST']);

include_once( __ROOT__ . '/System/Communicate/qrcode/qrlib.php');
require_once( __ROOT__ . "/System/Database/connect.php" );
require_once( __ROOT__ . "/System/FileSystem/autoload.php" );
require_once( __ROOT__ . "/System/DateTime/autoload.php" );
require_once( __ROOT__ . "/System/Security/Auth.class.php" );
require_once( __ROOT__ . "/System/Security/Crypt.class.php" );
require_once( __ROOT__ . "/System/Security/Utils.class.php" );
require_once( __ROOT__ . "/System/Security/Roles.class.php" );
require_once( __ROOT__ . "/System/Communicate/Cache.class.php" );
require_once( __ROOT__ . "/System/Communicate/CookieData.class.php" );
require_once( __ROOT__ . "/System/Communicate/pdf/autoload.php" );
require_once( __ROOT__ . "/System/Communicate/Cookie.class.php" );
require_once( __ROOT__ . "/System/Communicate/SessionData.class.php" );
require_once( __ROOT__ . "/System/Communicate/Session.class.php" );
require_once( __ROOT__ . "/System/Communicate/Console.class.php" );
require_once( __ROOT__ . "/System/Communicate/Message.class.php" );
require_once( __ROOT__ . "/System/Control/River/Walker.class.php" );
require_once( __ROOT__ . "/System/Control/River/Evaluator.class.php" );
require_once( __ROOT__ . "/System/Control/River/Compiler.class.php" );
require_once( __ROOT__ . "/System/Control/River/CompilerMatcher.class.php" );
require_once( __ROOT__ . "/System/Control/River/Cache.class.php" );
require_once( __ROOT__ . "/System/Control/River/River.class.php" );
require_once( __ROOT__ . "/System/Control/River/Init.directives.php" );
require_once( __ROOT__ . "/System/Control/River/section.directives.php" );
require_once( __ROOT__ . "/System/Control/Request.class.php" );
require_once( __ROOT__ . "/System/Control/Response.class.php" );
require_once( __ROOT__ . "/System/Control/RouteRegister.class.php" );
require_once( __ROOT__ . "/System/Control/Route.class.php" );
require_once( __ROOT__ . "/System/Control/defaults.route.php" );
require_once( __ROOT__ . "/System/Control/River/Less/lessc.inc.php" );
require_once( __ROOT__ . "/System/Control/River/Less.class.php" );
require_once( __ROOT__ . "/System/Control/River/JsPacker/class.JavaScriptPacker.php" );
require_once( __ROOT__ . "/System/Control/Nodejs/Runner.class.php" );
require_once( __ROOT__ . "/System/Language/Lang.class.php" );
require_once( __ROOT__ . "/System/Communicate/Curl/autoload.php" );
require_once( __ROOT__ . "/System/Communicate/Cron/autoload.php" );

require_once( __ROOT__ . "/System/Mail/PHPMailer.class.php" );
require_once( __ROOT__ . "/System/Mail/POP3.class.php" );
require_once( __ROOT__ . "/System/Mail/SMTP.class.php" );
require_once( __ROOT__ . "/System/Mail/OAuth.class.php" );
require_once( __ROOT__ . "/System/Mail/Exception.class.php" );



$Servers = \System\Controller\Route::find("/.+\.db\.php/",__APP__.DIRECTORY_SEPARATOR."DataBase");
$Models = \System\Controller\Route::find("/.+\.model\.php/",__APP__.DIRECTORY_SEPARATOR."Model");
$controllers = \System\Controller\Route::find("/.+\.controller\.php/",__APP__.DIRECTORY_SEPARATOR."Controller");
$groups = \System\Controller\Route::find("/.+\.group\.php/",__APP__.DIRECTORY_SEPARATOR."Group");
$Filters = \System\Controller\Route::find("/.+\.filter\.php/",__APP__.DIRECTORY_SEPARATOR."Filter");
$MiddleWares = \System\Controller\Route::find("/.+\.middleware\.php/",__APP__.DIRECTORY_SEPARATOR."MiddleWare");

foreach( $Servers as $server ){
    require_once ($server);
}

foreach( $Filters as $filter ){
    require_once ($filter);
}

foreach( $MiddleWares as $midlleware ){
    require_once ($midlleware);
}

foreach( $Models as $model ){
    require_once ($model);
}


foreach( $groups as $group ){
    require_once ($group);
}


foreach( $controllers as $controller ){
    require_once ($controller);
}


if (!function_exists('getallheaders'))
{
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value)
        {
            if (substr($name, 0, 5) == 'HTTP_')
            {
                $headers[
                    str_replace(' ', '-', 
                        ucwords(
                            strtolower(
                                str_replace('_', ' ', substr($name, 5))
                            )
                        )
                    )
                ] = $value;
            }
        }
        return $headers;
    }
}

//(PECL apd >= 0.2)
//rename_function('header','original_header');
//override_function('header','','\System\Communicate\Debug\Console::log(debug_backtrace(),func_get_args());return call_user_func_array("original_header",func_get_args());');


Route::onStart(Route::ALL,function($route){
    Language::init();
});


