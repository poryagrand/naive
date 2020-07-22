<?php
namespace System\Controller\Nodejs;

use System\Communicate\Debug\Console;
use System\Controller\TemplateEngine\RiverCache;

class Box{
    protected static $watch = true;
    protected static $source = (__APP__) . "/nodejs";
    protected static $errorLog = (__APP__) . "/nodejs/error.log";
    protected static $outLog = (__APP__) . "/nodejs/out.log";

    const UID = "RiverFrameWorkNodejs";
    const checkNode = "node -v";
    const checkNpm = "npm -v";
    const checkForever = "forever --version";
    const Script = (__ROOT__) . "/System/Control/Nodejs";
    const Config = (__ROOT__) . "/System/Control/Nodejs/Config.json";
    const NodeIsRunning = (__ROOT__) . "/System/Control/Nodejs/instance.running";
    const PidFile = (__ROOT__) . "/System/Control/Nodejs/pid";
    const LogFile = (__ROOT__) . "/System/Control/Nodejs/Logs/forever.log";
    const foreverIgnoreFile = "/.foreverignore";
    const foreverIgnore = 'node_modules/**
    public/**
    *.log
    **/*.log';
    const command = "forever start";
    const tempConfigs = '{
        "uid": "'.self::UID.'",
        "append": true,
        "watch": {WATCH},
        "script": "wrapper.js",
        "sourceDir": "{SCRIPT}",
        "logFile": "{LOG}",
        "outFile": "{OUT}",
        "errFile": "{ERROR}",
        "command": "node",
        "killTree":true,
        "args":{ARGS},
        "pidFile":"{PIDFILE}",
        "watchDirectory":"{SOURCE}"
    }';

    public static function watch($is){
        self::$watch = !(!$is);
    }

    /**
     * set the source path
     * @return bool
     */
    public static function setSource($path){
        if( is_dir($path) ){
            self::$source = $path;
            return true;
        }
        return false;
    }

    /**
     * set the std out log path
     * @return bool
     */
    public static function setOutLog($path){
        if( is_dir(dirname($path)) ){
            self::$outLog = $path;
            return true;
        }
        return false;
    }

    /**
     * set the std error log path
     * @return bool
     */
    public static function setErrorLog($path){
        if( is_dir(dirname($path)) ){
            self::$errorLog = $path;
            return true;
        }
        return false;
    }

    /**
     * checks the node instance is running
     * @return bool
     */
    public static function isRunning(){
        if( is_file(self::Config) && is_file(self::NodeIsRunning) ){
            return true;
        }
        return false;
    }

    /**
     * check the node and npm and the forever module is installed
     * @return bool
     */
    public static function checkRequirements(){
        $out = exec(self::checkNode . " && " . self::checkNpm . " && " . self::checkForever);
        if( empty($out) ){
            Console::error("the node instance could not start. check the `nodejs` and `npm` and `forever` is installed.");
            return false;
        }
        return true;
    }

    /**
     * write the config file
     * @return bool
     */
    public static function setConfig($args){
        $argsStr = [];
        foreach($args as $key=>$val){
            $argsStr[] = $key ;
            $argsStr[] = json_encode($val);
        }

        $temp = self::tempConfigs;
        $temp = str_replace("{SOURCE}",addslashes(self::$source),$temp);
        $temp = str_replace("{LOG}",addslashes(self::LogFile),$temp);
        $temp = str_replace("{OUT}",addslashes(self::$outLog),$temp);
        $temp = str_replace("{ERROR}",addslashes(self::$errorLog),$temp);
        $temp = str_replace("{ARGS}",json_encode($argsStr),$temp);
        $temp = str_replace("{SCRIPT}",addslashes(self::Script),$temp);
        $temp = str_replace("{WATCH}",self::$watch?"true":"false",$temp);
        $temp = str_replace("{PIDFILE}",addslashes(self::PidFile),$temp);
        



        if( file_put_contents(self::Config,$temp) !== false ){
            file_put_contents(self::$source . self::foreverIgnoreFile,self::foreverIgnore);
            return false;
        }
        return true;
    }

    /**
     * start the node instance
     * @return bool
     */
    public static function start($args = []){
        $args = is_array($args) ? $args : [];

        if( !self::isRunning() && !isset($_GET["nodejs"]) ){
            if( self::checkRequirements() ){

                $args["__ROOT__"] = (__ROOT__);
                $args["__APP__"] = (__APP__);
                $args["__SOURCE__"] = (self::$source);
                $args["__HOST__"] = (__HOST__);
                $args["__HOSTWOS__"] = (__HOSTWOS__);
                $args["__NODEJS__WRAPPER__"] = self::Script;
                $args["__UID__"] = self::UID;

                $args["__STDER__"] = self::$errorLog;
                $args["__STDOUT__"] = self::$outLog;


                self::setConfig($args);

                $out = exec(self::command . " " . addslashes(self::Config));

                
                if( !empty($out) ){
                    file_put_contents(self::NodeIsRunning,date("Y-m-d H:i:s e"));
                    return true;
                }
            }
        }
        else{
            if( RiverCache::isUnderDevelope() ){
                if( isset($_GET["nodejs"]) ){
                    if( $_GET["nodejs"] == "stop" ){
                        return self::stop();
                    }
                    else if( $_GET["nodejs"] == "restart" ){
                        return self::reStart($args);
                    }
                }
            }
        }
        return false;
    }

    /**
     * kill the node proccess
     * @return bool
     */
    public static function stop(){
        //unlink(self::NodeIsRunning);
        unlink(self::Config);
        if( !empty(exec("forever stop RiverFrameWorkNodejs")) ){
            return true;
        }
        return false;
    }

    /**
     * return the start time of instance
     * @return string|null
     */
    public static function startTime(){
        if( self::isRunning() ){
            return file_get_contents(self::NodeIsRunning);
        }
        return null;
    }

    /**
     * restart the nodejs instance
     * @return bool
     */
    public static function reStart($args){
        self::stop();
        return self::start($args);
    }
}