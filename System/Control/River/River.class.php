<?php

namespace System\Controller\TemplateEngine;

use \System\Controller\Request;
use \System\Controller\Route;
use \System\Controller\Response;
use \System\Communicate\Debug\Console;

class RiverHandleException extends \Exception{}

/**
 * river template engine renderer. simple view renderer
 */
class River{

    protected $__shareData = array();
    protected $__extention = "river.php";
    protected $__req;
    protected static $__ins = null;

    function __construct($req,$extention=null)
    {
        if( is_string($extention) ){
            $this->__extention = $extention;
        }

        if( !($req instanceof Request) ){
            throw new RiverHandleException("argument is not in type of Request.");
        }
        $this->__req = &$req;

        self::$__ins = $this;
    }


    public static function instance(){
        return self::$__ins;
    }
    


    /**
     * return the current request
     * @return Request
     */
    public function &getReq(){
        return $this->__req;
    }


    /**
     * set or get shared values in view 
     * @param string $name
     * @param mixed $value
     * @return string|null
     */
    public function share($name=null,$value=null){
        if( $name===null ){
            return $this->__shareData;
        }
        else if( $value === null ){
            $temp = &$this->__shareData;
            if( isset($temp[$name]) ){
                return $temp[$name];
            }
            return null;
        }
        $this->__shareData[$name] = $value;
        return $this->__shareData[$name];
    }

    public function removeShare($name){
        $temp = &$this->__shareData;
        if( isset($temp[$name]) ){
            unset($this->__shareData[$name]);
            return true;
        }
        return false;
    }

    /**
     * set or get shared values in view  in bulk mode
     * @param array $arr
     * @return string|null
     */
    public function bulk_share($arr){
        if( is_array($arr) ){
            foreach( $arr as $key=>$val ){
                $this->share($key,$val);
            }
        }
    }

    /**
     * remove shared values in view  in bulk mode
     * @param array $arr
     * @return string|null
     */
    public function bulk_removeShare($arr){
        if( is_array($arr) ){
            foreach( $arr as $val ){
                $this->removeShare($val);
            }
        }
    }

    /**
     * call inline directive to stack 
     * @param string $name
     * @param callback $callback
     */
    public function callInlineDirective($name,$args=[]){
        $args = array_merge([&$this],$args);
        return call_user_func_array(array(RiverCompiler::class,"callInlineDirective"),array_merge([$name],$args));
    }

    /**
     * call block directive to stack 
     * @param string $name
     * @param callback $callback
     */
    public function callBlockDirective($name,$args=[]){
        $args = array_merge([&$this],$args);
        return call_user_func_array(array(RiverCompiler::class,"callBlockDirective"),array_merge([$name],$args));
    }

    /**
     * call attribute to stack 
     * @param string $name
     * @param callback $callback
     */
    public function callAttribute($name,$args=[]){
        $args = array_merge([&$this],$args);
        return call_user_func_array(array(RiverCompiler::class,"callAttribute"),array_merge([$name],$args));
    }

    /**
     * call block tag to stack 
     * @param string $name
     * @param callback $callback
     */
    public function callInlineTag($name,$args=[]){
        $args = array_merge([&$this],$args);
        return call_user_func_array(array(RiverCompiler::class,"callTagInline"),array_merge([$name],$args));
    }

    /**
     * call block tag to stack 
     * @param string $name
     * @param callback $callback
     */
    public function callBlockTag($name,$args=[]){
        $args = array_merge([&$this],$args);
        return call_user_func_array(array(RiverCompiler::class,"callTagBlock"),array_merge([$name],$args));
    }

    /**
     * add inline directive to stack 
     * @param string $name
     * @param callback $callback
     */
    public static function directiveInline($name,$callback){
        RiverCompiler::inlineDirective($name,$callback);
    }

    /**
     * add block directive to stack 
     * @param string $name
     * @param callback $callback
     */
    public static function directiveBlock($name,$callback){
        RiverCompiler::blockDirective($name,$callback);
    }

    /**
     * do define the type directives  for dynamic creattion
     * @param string $type
     * @param RegExp $regex
     */
    public static function preDefine($type,$regex){
        RiverCompiler::preDefine($type,$regex);
    }

    /**
     * add attribute to stack 
     * @param string $name
     * @param callback $callback
     */
    public static function attribute($name,$callback){
        if( is_array($name) ){
            foreach($name as $val){
                RiverCompiler::attribute($val,$callback);
            }
        }
        else{
            RiverCompiler::attribute($name,$callback);
        }
    }

    /**
     * add inline tag to stack 
     * @param string $name
     * @param callback $callback
     */
    public static function inlineTag($name,$callback){
        RiverCompiler::tagInline($name,$callback);
    }

    /**
     * add block tag to stack 
     * @param string $name
     * @param callback $callback
     */
    public static function blockTag($name,$callback){
        RiverCompiler::tagBlock($name,$callback);
    }


    public function render($path,$shareData=null,$evalData=true){
        $tpath = str_replace(".".$this->__extention,"",$path) . "." .  $this->__extention;
        
        if( is_array($shareData) ){
            $this->__shareData = $shareData;
        }

        if( !RiverCache::is($path) ){
            RiverCache::save(
                $path,
                call_user_func_array([RiverCompiler::class,"compile"],[$path, &$this ])
            );
        }
        
        $path = RiverCache::path($path);
 

        if( $evalData ){
            $content = $this->__getEvaluated($path);
        }
        else{
            $content = file_get_contents($path);
        }


        if( $content === null ){
            throw new RiverHandleException("there is an error in rendering file `$tpath`");
        }

        return $content;
    }

    /**
     * @brief get content of a php source after compiling php as html
     * @param[in] string $path the path of the desired file
     * @return string|null
     */
    protected function __getEvaluated($path){
        $output = "";
        if( is_file($path) && file_exists($path) ){
            ob_start();
            include($path);
            $output = ob_get_contents();
            ob_end_clean();
            return $output;
        }
        return null;
    }

    /**
     * @brief get content of a php source after compiling php as html (for use in outer)
     * @param[in] string $path the path of the desired file
     * @return string|null
     */
    public static function getEvaluated($path){
        $output = "";

        if( is_file($path) && file_exists($path) ){
            ob_start();
            include($path);
            $output = ob_get_contents();
            ob_end_clean();
            return $output;
        }
        return null;
    }
}