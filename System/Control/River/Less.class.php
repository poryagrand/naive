<?php
namespace System\Controller\TemplateEngine;

use System\Communicate\Debug\Console;

class Less{
    private static $object = null;
    private static $cssRoot = [];

    public static function &instance(){
        if( self::$object === null  ){
            self::$object = new \lessc;
            self::$object->setFormatter("compressed");
            self::$object->setImportDir(self::$cssRoot);
        }
        return self::$object;
    }

    public static function importDir($dir){
        self::$cssRoot[] = $dir;
    }

    public static function variable($name,$value){
        $less = self::instance();

        $less->setVariables(array(
            $name => $value,
        ));
    }

    public static function remVar($name){
        $less = self::instance();

        $less->unsetVariable($name);
    }

    private static function compileCache($inputFile) {

        $cacheFile = $inputFile.".cache";
      
        if (file_exists($cacheFile)) {
            $cache = unserialize(file_get_contents($cacheFile));
        } else {
            $cache = $inputFile;
        }
      
        $less = self::instance();
        $newCache = $less->cachedCompile($cache);
        
        try{
            if (!is_array($cache) || $newCache["updated"] > $cache["updated"]) {
                file_put_contents($cacheFile, serialize($newCache));
                return $newCache['compiled'];
            }
            else{
                return $less->compileFile($inputFile);
            }
        }
        catch(\Exception $e){
            return null;
        }
        
    }

    public static function compile($content){
        $less = self::instance();
        try{
            return $less->compile($content);
        }
        catch(\Exception $e){
            Console::log($e);
            return null;
        }
    }


    public static function compileFile($file){
        return self::compileCache($file);
    }

    public static function func($name,$call){
        $less = self::instance();

        $less->registerFunction($name, $call);
    }
}