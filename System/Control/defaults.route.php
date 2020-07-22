<?php
use System\Controller\Route;
use System\Controller\Response;

Route::register("fault/403")
    ->attach("noConsole",true)
    ->handle(function($req,$res){
        $res->setHeaderHttpCode( Response::FORBIDDEN );
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8" />
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <title>403 Forbidden</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
        </head>
        <body style="margin: 0px;border-top: solid 4px #989898;font-family: monospace;">
            <div style="padding: 51px;color: #989898;">
                <h5 style="font-size: 30px; margin: 0px;">403 Forbidden</h5>
                <p style="font-size: 15px;margin: 0px;">you don't have the necessary permissions to access the resource.</p>
                <p style="font-size: 15px;font-weight: bolder;padding: 6px;border-left: solid 5px;margin: 41px 0px;">current time: <?php echo date("Y/m/d H:M:s"); ?></p>
            </div>
        </body>
        </html>
        <?php
    });

Route::register("fault/404")
    ->attach("noConsole",true)
    ->handle(function($req,$res){

        $res->setHeaderHttpCode( Response::NOT_FOUND );

        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8" />
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <title>404 Not Found</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
        </head>
        <body style="margin: 0px;border-top: solid 4px #989898;font-family: monospace;">
            <div style="padding: 51px;color: #989898;">
                <h5 style="font-size: 30px; margin: 0px;">404 Not Found</h5>
                <p style="font-size: 15px;margin: 0px;">the directory or file you looking for is not found!</p>
                <p style="font-size: 15px;font-weight: bolder;padding: 6px;border-left: solid 5px;margin: 41px 0px;">current time: <?php echo date("Y/m/d H:i:s"); ?></p>
            </div>
        </body>
        </html>
        <?php
    });