<?php
use System\Controller\Route;
use System\Database\Model\Base;
use System\Communicate\Debug\Console;
use System\Communicate\Cache;
use System\Controller\TemplateEngine\RiverCache;

include_once( "includes.php" );

if( RiverCache::isUnderDevelope() && isset($_GET["migrate"]) ){
    Base::migrateAll();
}

if( Route::htaccess("/") ){
    Route::redirect("/");
}

if( file_exists(__ROOT__ . "/App/app.php") ){
    include_once( __ROOT__ . "/App/app.php" );
}
else{
    Route::register("")
        ->handle(function($req,$res){
            echo "<p style=\"color: #949494;font-family: monospace;font-size: 16px;border-left: solid 5px;padding: 5px 17px;\">no template index found! please create an <b><em>index.php</em></b> file in <b><em>template</em></b> folder!</p>";
        });
}


Route::listen();
Cache::storeEdited();
