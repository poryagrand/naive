<?php

namespace System\Communicate\Debug;

use System\Controller\Route;
use System\Communicate\BasicSession;

/**
 * @brief   console is a class to perform ability of *debugging* php in browser console the function '_log_' will store data and  the function '_flush_' will show data in console by converting them to *js* '_console.log_' function.
 */
class Console{

    /**
     * @brief tell system to flush/not flush data at end of each routing page
     */
    protected static $AutoFlush = true;

    protected static $showConsoles = true;

    protected static $allErrors = [];


    /**
     * @brief the console log storage 
     */  
    private static $Data = array(); 

    public static function getLastError(){
        if( count(self::$allErrors) <= 0 ){
            return null;
        }
        return self::$allErrors[count(self::$allErrors)-1];
    }

    /**
     * exec before script shut down
     * @return void
     */
    public static function fatalHandller(){
        $error = error_get_last();
        self::errorHandller( $error["type"], $error["message"], $error["file"], $error["line"] );
    }

    /**
     * handling built in errors and warning
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @return bool
     */
    public static function errorHandller($errno, $errstr, $errfile, $errline){
        self::$allErrors[] = [
            "type"=>$errno,
            "message"=>$errstr,
            "file"=>$errfile,
            "line"=>"$errline"
        ];
        if( preg_match("/^.+\.tmp$/",$errfile) !== false ){
            return false;
        }
        if (!(error_reporting() & $errno)) {
            // This error code is not included in error_reporting, so let it fall
            // through to the standard PHP error handler
            return false;
        }
   /*     
        $file = pathinfo($errfile);
        if( $file['extension'] == "cached" ){
            $file = explode('_',basename($file["basename"],".cached"),2);
            if( count($file) > 1 ){
                $errfile = Route::path("view: ".$file[1]);
            }
        }
*/
        $errfile = "file:///".$errfile;
        if( $errno == E_ERROR || $errno == E_USER_ERROR || $errno == E_CORE_ERROR || $errno == E_RECOVERABLE_ERROR  ){
            self::error("\t╔ Server Error:\n\t╠═ [$errno] $errstr\n\t╚═ Fatal error on line $errline in file $errfile , PHP " . PHP_VERSION . " (" . PHP_OS . ")");
            self::flush();
            exit(1);
        }
        else if( $errno == E_PARSE || $errno == E_COMPILE_ERROR ){
            self::error("\t╔ Server Error:\n\t╠═ [$errno] $errstr\n\t╚═ Compile error on line $errline in file $errfile , PHP " . PHP_VERSION . " (" . PHP_OS . ")");
            self::flush();
            exit(1);
        }
        else if( $errno == E_USER_WARNING || $errno == E_WARNING || $errno == E_CORE_WARNING || $errno == E_COMPILE_WARNING ){
            self::warn("\t╔ Server Error:\n\t╠═ [$errno] $errstr\n\t╚═ warning on line $errline in file $errfile , PHP " . PHP_VERSION . " (" . PHP_OS . ")");
        }
        else if( $errno == E_DEPRECATED || $errno == E_USER_DEPRECATED ){
            self::warn("\t╔ Deprecated :\n\t╠═ [$errno] $errstr\n\t╚═ warning on line $errline in file $errfile , PHP " . PHP_VERSION . " (" . PHP_OS . ")");
        }
        else if( $errno == E_USER_NOTICE || $errno == E_NOTICE ){
            self::info("\t╔ Server Error:\n\t╠═ [$errno] $errstr\n\t╚═ notice on line $errline in file $errfile , PHP " . PHP_VERSION . " (" . PHP_OS . ")");
        }
        else if( $errno == E_STRICT ){
            self::info("\t╔ Server Strict:\n\t╠═ [$errno] $errstr\n\t╚═ notice on line $errline in file $errfile , PHP " . PHP_VERSION . " (" . PHP_OS . ")");
        }
        else{
            self::debug("\t╔ Server Error:\n\t╠═ [$errno] $errstr\n\t╚═ a debug message on line $errline in file $errfile , PHP " . PHP_VERSION . " (" . PHP_OS . ")");
        }
        /* Don't execute PHP internal error handler */
        return true;
    }

    /**
     * initial console session from before data
     */
    public static function init(){
        
        if( count(self::$Data) <= 0 ){
            $tmp = BasicSession::get("ConsoleData");
            self::$Data = ( ( isset( $tmp ) && !empty($tmp) && count($tmp) > 0 ) ? $tmp : array() );
        }
    }

    /**
     * check if auto flush is on or not
     * @return bool
     */
    public static function isAutoFlush(){
        return self::$AutoFlush;
    }

    /**
     * flush data automaticly after routing
     */
    public static function autoFlush(){
        self::$AutoFlush = true;
    }

    /**
     * disable flush data automaticly after routing
     */
    public static function manualFlush(){
        self::$AutoFlush = false;
    }

    /**
     * enable/disable showing consoles
     */
    public static function enable($en){
        self::$showConsoles = !(!$en);
    }
 
    /**
     * @brief save input data in class storage as log
     * @return void
     * @param ... undefined and unlimited arguments
     */
    public static function log(){
        self::init();
        array_push(
            self::$Data,
            array(
                "type"=>"log",
                "value"=>func_get_args()
            )
        );
        BasicSession::set("ConsoleData",self::$Data);
    }

    /**
     * @brief save input data in class storage as halt error
     * @return void
     * @param ... undefined and unlimited arguments
     */
    public static function halt(){
        self::init();
        array_push(
            self::$Data,
            array(
                "type"=>"halt",
                "value"=>func_get_args()
            )
        );
        BasicSession::set("ConsoleData",self::$Data);
        self::flush();
    }

    /**
     * @brief save input data in class storage as error
     * @return void
     * @param ... undefined and unlimited arguments
     */
    public static function error(){
        self::init();
        array_push(
            self::$Data,
            array(
                "type"=>"error",
                "value"=>func_get_args()
            )
        );
        BasicSession::set("ConsoleData",self::$Data);
    }

    /**
     * @brief save input data in class storage as warning
     * @return void
     * @param ... undefined and unlimited arguments
     */
    public static function warn(){
        self::init();
        array_push(
            self::$Data,
            array(
                "type"=>"warn",
                "value"=>func_get_args()
            )
        );
        BasicSession::set("ConsoleData",self::$Data);
    }

    /**
     * @brief save input data in class storage as notice
     * @return void
     * @param ... undefined and unlimited arguments
     */
    public static function info(){
        self::init();
        array_push(
            self::$Data,
            array(
                "type"=>"info",
                "value"=>func_get_args()
            )
        );
        BasicSession::set("ConsoleData",self::$Data);
    }

     /**
     * @brief save input data in class storage as other debug data
     * @return void
     * @param ... undefined and unlimited arguments
     */
    public static function debug(){
        self::init();
        array_push(
            self::$Data,
            array(
                "type"=>"debug",
                "value"=>func_get_args()
            )
        );
        BasicSession::set("ConsoleData",self::$Data);
    }

    /**
     * @brief write out data in console
     * @return void
     */
    public static function flush(){
        if( Route::hasError() ){
            return;
        }
        //var_dump($_SESSION["ConsoleData"]);
        //die();
        if(!self::$showConsoles){
            BasicSession::remove("ConsoleData");
            BasicSession::set("ConsoleData",[]);
            return;
        }

        self::init();
        $isHalt = false;
        if(count(self::$Data) > 0){
            $consoles = "";
            foreach(self::$Data as $val){
                if( $val["type"] == "halt" ){
                    $isHalt = true;
                    $consoles.="console.error.apply(this,".json_encode(array_merge(array("> Server User Error: \n"),$val["value"])).");";
                }
                else{
                    $consoles.="console.".$val["type"].".apply(this,".json_encode($val["value"]).");";
                }
            }
            
            echo "<script>".$consoles."</script>";
        }

        //file_put_contents(__ROOT__."/debug.log","Console: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}".PHP_EOL , FILE_APPEND | LOCK_EX);
        BasicSession::remove("ConsoleData");
        BasicSession::set("ConsoleData",[]);
        if( $isHalt ){
            die();
        }
    }
}



register_shutdown_function( array(Console::class,"fatalHandller"));
set_error_handler(array(Console::class,"errorHandller"));
ini_set( "display_errors", "off" );
error_reporting( E_ALL );