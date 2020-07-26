<?php

namespace System\Communicate\Debug;

use System\Controller\Route;
use System\Communicate\BasicSession;

/**
 * @brief   console is a class to perform ability of *debugging* php in browser console the function '_log_' will store data and  the function '_flush_' will show data in console by converting them to *js* '_console.log_' function.
 */
class Console{

    protected static $CustomConsoles = [
        "console"=>[],
        "log"=>[],
        "info"=>[],
        "error"=>[],
        "warn"=>[],
        "halt"=>[],
        "debug"=>[]
    ];

    protected static $justCustom = false;

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

    /**
     * tells to system to run console event instead of write out in console of browser
     * @param bool $is
     */
    public static function onlyFireCustomEvent($is){
        self::$justCustom = !(!$is);
    }

    /**
     * return the last error details
     * @return Array
     */
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
        $args = func_get_args();
        self::init();

        foreach(self::$CustomConsoles["log"] as $event){
            if( is_callable($event) ){
                call_user_func($event,$args);
            }
        }

        array_push(
            self::$Data,
            array(
                "type"=>"log",
                "value"=>$args
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
        $args = func_get_args();
        self::init();

        foreach(self::$CustomConsoles["halt"] as $event){
            if( is_callable($event) ){
                call_user_func($event,$args);
            }
        }

        array_push(
            self::$Data,
            array(
                "type"=>"halt",
                "value"=>$args
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
        $args = func_get_args();
        self::init();

        foreach(self::$CustomConsoles["error"] as $event){
            if( is_callable($event) ){
                call_user_func($event,$args);
            }
        }

        array_push(
            self::$Data,
            array(
                "type"=>"error",
                "value"=>$args
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
        $args = func_get_args();
        self::init();

        foreach(self::$CustomConsoles["warn"] as $event){
            if( is_callable($event) ){
                call_user_func($event,$args);
            }
        }

        array_push(
            self::$Data,
            array(
                "type"=>"warn",
                "value"=>$args
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
        $args = func_get_args();
        self::init();

        foreach(self::$CustomConsoles["info"] as $event){
            if( is_callable($event) ){
                call_user_func($event,$args);
            }
        }

        array_push(
            self::$Data,
            array(
                "type"=>"info",
                "value"=>$args
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
        $args = func_get_args();
        self::init();

        foreach(self::$CustomConsoles["debug"] as $event){
            if( is_callable($event) ){
                call_user_func($event,$args);
            }
        }

        array_push(
            self::$Data,
            array(
                "type"=>"debug",
                "value"=>$args
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

        self::init();

        foreach(self::$CustomConsoles["console"] as $event){
            if( is_callable($event) ){
                call_user_func($event,self::$Data);
            }
        }

        if(!self::$showConsoles || self::$justCustom){
            BasicSession::remove("ConsoleData");
            BasicSession::set("ConsoleData",[]);
            return;
        }

        
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


    public static function onConsole($call){
        if(is_callable($call)){
            self::$CustomConsoles["console"][] = $call;
        }
    }

    public static function onWarn($call){
        if(is_callable($call)){
            self::$CustomConsoles["warn"][] = $call;
        }
    }
    public static function onError($call){
        if(is_callable($call)){
            self::$CustomConsoles["error"][] = $call;
        }
    }
    public static function onLog($call){
        if(is_callable($call)){
            self::$CustomConsoles["log"][] = $call;
        }
    }
    public static function onDebug($call){
        if(is_callable($call)){
            self::$CustomConsoles["debug"][] = $call;
        }
    }
    public static function onHalt($call){
        if(is_callable($call)){
            self::$CustomConsoles["halt"][] = $call;
        }
    }
    public static function onInfo($call){
        if(is_callable($call)){
            self::$CustomConsoles["info"][] = $call;
        }
    }
}



register_shutdown_function( array(Console::class,"fatalHandller"));
set_error_handler(array(Console::class,"errorHandller"));
ini_set( "display_errors", "off" );
error_reporting( E_ALL );