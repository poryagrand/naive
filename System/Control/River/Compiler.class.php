<?php

namespace System\Controller\TemplateEngine;

use System\Communicate\Debug\Console;
use System\Security\Crypt;

class RiverCompileHandleException extends \Exception{}

/**
 * compile river template files to php executable files
 */
class RiverCompiler{

    /*const DIRECTIVE = '/(@end([A-Za-z_][\.A-Za-z_0-9]+)|(?<!\\\)(@@|@)(([A-Za-z_][\.A-Za-z_0-9]*)[ \t]*(\(((\\@|(?>[^\(\@\)])|(?4))*)\))|([A-Za-z_][\.A-Za-z_0-9]*))|(@@|@!|@|!)?(?<!\\\)({{)|(}}))/';
*/
    protected static $__inlineStorage = array();
    protected static $__blockStorage = array();

    protected static $__tagBlockStorage = [];
    protected static $__tagInlineStorage = [];

    protected static $__attributeStorage = [];

    protected static $__preDefines = [];

    protected static $checkRegCash = [];
    protected static function checkPattern(&$array,$name,$type){
        $rf = &self::$checkRegCash;
        if( !isset($rf[$type]) ){
            $rf[$type] = [];
        }
        if( isset($rf[$type][$name]) ){
            return $rf[$type][$name];
        }

        $keys = array_keys($array);
        $rnd = mt_rand(25548,33599854);
        //$pattern = explode("*",$pattern);
        foreach( $keys as $val ){
            $val2 = str_replace("*","REPLACEWITHSTAR".$rnd,$val);
            $val2 = preg_quote($val2);
            $val2 = str_replace("REPLACEWITHSTAR".$rnd,"(.+?)",$val2);
            if( preg_match('/^'. $val2 .'$/',$name) ){
                $rf[$type][$name] = $val;
                return $val;
            }
        }
        return null;
    }

    public static function isInline($name){
        $rf = &self::$__inlineStorage;
        if( !isset($rf[$name]) ){
            $check = self::checkPattern($rf,$name,1);
            if( $check !== null ){
                return true;
            }
            return false;
        }
        return true;
    }

    public static function isAttribute($name){
        $rf = &self::$__attributeStorage;
        if( !isset($rf[$name]) ){
            $check = self::checkPattern($rf,$name,2);
            if( $check !== null ){
                return true;
            }
            return false;
        }
        return true;
    }

    public static function isBlock($name){
        $rf = &self::$__blockStorage;
        if( !isset($rf[$name]) ){
            $check = self::checkPattern($rf,$name,3);
            if( $check !== null ){
                return true;
            }
            return false;
        }
        return true;
    }

    public static function isTagInline($name){
        $rf = &self::$__tagInlineStorage;
        if( !isset($rf[$name]) ){
            $check = self::checkPattern($rf,$name,4);
            if( $check !== null ){
                return true;
            }
            return false;
        }
        return true;
    }

    public static function isTagBlock($name){
        $rf = &self::$__tagBlockStorage;
        if( !isset($rf[$name]) ){
            $check = self::checkPattern($rf,$name,5);
            if( $check !== null ){
                return true;
            }
            return false;
        }
        return true;
    }

    public static function getInlineFn($name){
        $rf = &self::$__inlineStorage;
        if( !isset($rf[$name]) ){
            $check = self::checkPattern($rf,$name,1);
            if( $check !== null ){
                return $rf[$check];
            }
            return function(){return "";};
        }
        return self::$__inlineStorage[$name];
    }

    public static function getAttributeFn($name){
        $rf = &self::$__attributeStorage;
        if( !isset($rf[$name]) ){
            $check = self::checkPattern($rf,$name,2);
            if( $check !== null ){
                return $rf[$check];
            }
            return function(){return "";};
        }
        return self::$__attributeStorage[$name];
    }

    public static function getBlockFn($name){
        $rf = &self::$__blockStorage;
        if( !isset($rf[$name]) ){
            $check = self::checkPattern($rf,$name,3);
            if( $check !== null ){
                return $rf[$check];
            }
            return function(){return "";};
        }
        return self::$__blockStorage[$name];
    }

    public static function getTagInlineFn($name){
        $rf = &self::$__tagInlineStorage;
        if( !isset($rf[$name]) ){
            $check = self::checkPattern($rf,$name,4);
            if( $check !== null ){
                return $rf[$check];
            }
            return function(){return "";};
        }
        return self::$__tagInlineStorage[$name];
    }

    public static function getTagBlockFn($name){
        $rf = &self::$__tagBlockStorage;
        if( !isset($rf[$name]) ){
            $check = self::checkPattern($rf,$name,5);
            if( $check !== null ){
                return $rf[$check];
            }
            return function(){return "";};
        }
        return self::$__tagBlockStorage[$name];
    }

    public static function getPreDefinedFn($name){
        $rf = &self::$__preDefines;
        if( !isset($rf[$name]) ){
            return function(){return "";};
        }
        return self::$__preDefines[$name];
    }


    public static function &getInlineStorage(){
        return self::$__inlineStorage;
    }

    public static function &getAttributeStorage(){
        return self::$__attributeStorage;
    }

    public static function &getBlockStorage(){
        return self::$__blockStorage;
    }

    public static function &getTagInlineStorage(){
        return self::$__tagInlineStorage;
    }

    public static function &getTagBlockStorage(){
        return self::$__tagBlockStorage;
    }

    public static function &getPreDefined(){
        return self::$__preDefines;
    }

     /**
     * do define the type directives  for dynamic creattion
     * @param string $type
     * @param RegExp $regex
     */
    public static function preDefine($type,$regex){
        if( array_search($type,["inline","block","attr","tag","blockTag"]) === false ){
            return null;
        }
        self::$__preDefines[$regex] = $type;
    }

    /**
     * call an specefic tag attribute
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public static function callAttribute($name,...$args){
        if( $args === null ){
            $args = [];
        }

        $tmp = &self::$__attributeStorage;
        try{
            if( isset($tmp[$name]) && is_callable($tmp[$name]) ){
                return call_user_func_array($tmp[$name],$args);
            }
        }
        catch(\Exception $e){
            Console::halt($e);
        }
        return null;
    }

    /**
     * call an specefic inline tag
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public static function callTagInline($name,...$args){
        if( $args === null ){
            $args = [];
        }

        $tmp = &self::$__tagInlineStorage;
        try{
            if( isset($tmp[$name]) && is_callable($tmp[$name]) ){
                return call_user_func_array($tmp[$name],$args);
            }
        }
        catch(\Exception $e){
            Console::halt($e);
        }
        return null;
    }

    /**
     * call an specefic block tag
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public static function callTagBlock($name,...$args){
        if( $args === null ){
            $args = [];
        }

        $tmp = &self::$__tagBlockStorage;
        try{
            if( isset($tmp[$name]) && is_callable($tmp[$name]) ){
                return call_user_func_array($tmp[$name],$args);
            }
        }
        catch(\Exception $e){
            Console::halt($e);
        }
        return null;
    }

    /**
     * call an specefic block directive
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public static function callBlockDirective($name,...$args){
        if( $args === null ){
            $args = [];
        }

        $tmp = &self::$__blockStorage;
        try{
            if( isset($tmp[$name]) && is_callable($tmp[$name]) ){
                return call_user_func_array($tmp[$name],$args);
            }
        }
        catch(\Exception $e){
            Console::halt($e);
        }
        return null;
    }

    /**
     * call an specefic inline directive
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public static function callInlineDirective($name,...$args){
        if( $args === null ){
            $args = [];
        }

        $tmp = &self::$__inlineStorage;
        try{
            if( isset($tmp[$name]) && is_callable($tmp[$name]) ){
                return call_user_func_array($tmp[$name],$args);
            }
        }
        catch(\Exception $e){
            Console::halt($e);
        }
        return null;
    }

    /**
     * add inline directive to stack 
     * @param string $name
     * @param callback $callback
     * @throws RiverCompileHandleException
     */
    public static function inlineDirective($name,$callback){
        if( !is_string($name) || !is_callable($callback) ){
            throw new RiverCompileHandleException("arguments are not in correct type.");
        }

        $temp = &self::$__inlineStorage;

        if( isset( $temp[$name] ) ){
            throw new RiverCompileHandleException("the inline directive `$name` has been defined before!");
        }

        self::$__inlineStorage[$name] = $callback;
    }

    /**
     * add block directive to stack 
     * @param string $name
     * @param callback $callback
     * @throws RiverCompileHandleException
     */
    public static function blockDirective($name,$callback){
        if( !is_string($name) || !is_callable($callback) ){
            throw new RiverCompileHandleException("arguments are not in correct type.");
        }

        $temp = &self::$__blockStorage;

        if( isset( $temp[$name] ) ){
            throw new RiverCompileHandleException("the block directive `$name` has been defined before!");
        }

        self::$__blockStorage[$name] = $callback;
    }

     /**
     * add block tag to stack 
     * @param string $name
     * @param callback $callback
     * @throws RiverCompileHandleException
     */
    public static function tagBlock($name,$callback){
        if( !is_string($name) || !is_callable($callback) ){
            throw new RiverCompileHandleException("arguments are not in correct type.");
        }

        $temp = &self::$__tagBlockStorage;

        if( isset( $temp[$name] ) ){
            throw new RiverCompileHandleException("the block tag `$name` has been defined before!");
        }

        self::$__tagBlockStorage[$name] = $callback;
    }

    /**
     * add inline tag to stack 
     * @param string $name
     * @param callback $callback
     * @throws RiverCompileHandleException
     */
    public static function tagInline($name,$callback){
        if( !is_string($name) || !is_callable($callback) ){
            throw new RiverCompileHandleException("arguments are not in correct type.");
        }

        $temp = &self::$__tagInlineStorage;

        if( isset( $temp[$name] ) ){
            throw new RiverCompileHandleException("the inline tag `$name` has been defined before!");
        }

        self::$__tagInlineStorage[$name] = $callback;
    }

    /**
     * add tag attribute to stack 
     * @param string $name
     * @param callback $callback
     * @throws RiverCompileHandleException
     */
    public static function attribute($name,$callback){
        if( !is_string($name) || !is_callable($callback) ){
            throw new RiverCompileHandleException("arguments are not in correct type.");
        }

        $temp = &self::$__attributeStorage;

        if( isset( $temp[$name] ) ){
            throw new RiverCompileHandleException("the attribute `$name` has been defined before!");
        }

        self::$__attributeStorage[$name] = $callback;
    }

    /**
     * compile file/string template
     * @param string $path
     * @param River $ref
     * @return string
     */
    public static function compile( $file , &$ref ){
        if( file_exists($file) ){
            $content = file_get_contents($file);
        }
        else{
            $content = $file;
        }

        $wl = new Walker($content);
        $wl->attachBlockDirective(self::getBlockStorage());
        $wl->attachInlineDirective(self::getInlineStorage());

        $wl->attachInlineTag(self::getTagInlineStorage());
        $wl->attachBlockTag(self::getTagBlockStorage());

        $wl->attachAttribute(self::getAttributeStorage());

        $wl->attachPreDefines(self::getPreDefined());

        $tree = $wl->parse();


        $ev = new Evaluator($tree,$ref);
        return $ev->eval();
    }

}