<?php
namespace System\Communicate;

use System\Security\Crypt;
use System\Controller\Route;
use System\Communicate\Debug\Console;

class CacheStorage{
    private $__create;
    private $__exp;
    private $__data;

    public function __construct($data,$exp){
        $this->__create = Crypt::currentTime();
        $this->__exp = (int)$exp <= 0 ? (60) : (int)$exp;
        $this->__data = $data;
    }
    
    public function isExpire(){
        if( ($this->__create + $this->__exp) - (Crypt::currentTime()) <= 0  ){
            return true;
        }
        return false;
    }

    public function remainTime(){
        return ($this->__create + $this->__exp) - (Crypt::currentTime());
    }

    public function data(){
        return $this->__data;
    }
}

class Cache
{
    /**
     * The path to the cache file folder
     *
     * @var string
     */
    const CACHE_PATH = __DIR__ . DIRECTORY_SEPARATOR . "cache" . DIRECTORY_SEPARATOR;
    /**
     * The  default name of the cache file
     *
     * @var string
     */
    const CACHE_NAME = 'SYSTEM_CACH_DATA';
    /**
     * The cache file extension
     *
     * @var string
     */
    const CACHE_EXT = '.cached';

    /**
     * memory to store loaded cahce files to access faster
     */
    private static $__memory = [];

    /**
     * Get the cache directory path
     * @param string|null $name
     * @return string
     */
    static public function getCacheDir($name=null) {
        $filename = $name !== null ? $name : self::CACHE_NAME;
        $filename = preg_replace('/[^0-9a-zA-Z\.\_\-]/i', '', $filename);
        return self::CACHE_PATH . Crypt::hash($filename) . "_" . $filename . self::CACHE_EXT;
    }

    /**
     * read cached file and return the object
     * @param string|null $name
     * @return CacheStorage|null
     */
    static public function readStorage($name=null){
        

        $pathAddr = self::getCacheDir($name);

        self::isCachedCreateIfNot($pathAddr);

        $tmp = &self::$__memory;
        if( isset( $tmp[$pathAddr] ) ){
            return $tmp[$pathAddr];
        }
        $content = file_get_contents($pathAddr);
        try{
            self::$__memory[$pathAddr] = [
                "edited"=>false,
                "data"=>unserialize($content)
            ];
            return self::$__memory[$pathAddr];
        }
        catch( \Exception $e ){
            unset($pathAddr);
        }

        return null;
    }

    /**
     * store data in cached file
     * @param array $dataList
     * @param string|null $name
     * @return bool
     */
    static public function store($dataList,$name=null){
        if( $name === null || !file_exists($name) ){
            $pathAddr = self::getCacheDir($name);
        }
        else{
            $pathAddr = $name;
        }
        try{

            $tmp = &self::$__memory;
            if( isset( $tmp[$pathAddr] ) ){
                unset(self::$__memory[$pathAddr]);
            }

            $dataList = serialize($dataList);
            file_put_contents($pathAddr, $dataList, LOCK_EX);
            return true;
        }
        catch( \Exception $e ){
            unlink($pathAddr);
        }
        return false;
    }

    /**
     * store all edited data in storages
     * @return void
     */
    static public function storeEdited(){
        foreach( self::$__memory as $path=>$storage ){
            if( $storage["edited"] ){
                self::store($storage["data"],$path);
            }
        }
    }

    /**
     * Check whether cache storage is exist or not
     *
     * @param string|null $file
     * @return boolean
     */
    static public function isCached($file=null){
        $pathAddr = self::getCacheDir($file);
        if( is_file($pathAddr) ){
            return true;
        }
        return false;
    }

    static private function isCachedCreateIfNot($pathAddr){
        if (!is_dir(self::CACHE_PATH)) {
            mkdir(self::CACHE_PATH);
        }

        if( !is_file($pathAddr) ){
            file_put_contents($pathAddr,"",LOCK_EX);
        }
    }

    /**
     * return the cahced data if exists
     * @param string $key
     * @param string $file
     * @param mixed|null
     */
    static public function retrive($key,$file=null){
        $storage = self::readStorage($file);

        if( $storage !== null ){
            if( isset( $storage["data"] ) && isset( $storage["data"][$key] ) ){
                if( $storage["data"][$key] instanceof CacheStorage && !$storage["data"][$key]->isExpire() ){
                    return $storage["data"][$key];
                }
                else if($storage["data"][$key]->isExpire()){
                    $storage["edited"] = true;
                    unset($storage["data"][$key]);
                }
            }
            
        }
        return null;
    }

    /**
     * return the cahced data if exists
     * @param string $key
     * @param string $file
     * @param mixed|null
     */
    static public function get($key,$file=null){
        $storage = self::readStorage($file);

        if( $storage !== null ){
            if( isset( $storage["data"] ) && isset( $storage["data"][$key] ) ){
                if( $storage["data"][$key] instanceof CacheStorage && !$storage["data"][$key]->isExpire() ){
                    return $storage["data"][$key]->data();
                }
                else if($storage["data"][$key]->isExpire()){
                    $storage["edited"] = true;
                    unset($storage["data"][$key]);
                }
            }
            
        }
        return null;
    }

    /**
     * return the cahced data if exists
     * @param string $key
     * @param mixed $value
     * @param int $exp
     * @param string $file
     * @param void
     */
    static public function set($key,$value,$exp=60,$file=null){
        $path = self::getCacheDir($file);
        $tmp = &self::$__memory;
        if( !isset($tmp[$path]) ){
            self::$__memory[$path] = [
                "edited"=>true,
                "data"=>[]
            ];
        }
        self::$__memory[$path]["data"][$key] = new CacheStorage($value,$exp);
        self::$__memory[$path]["edited"] = true;
    }


    /**
     * check if the key exist in the file cache
     * @param string $key
     * @param string $file
     * @return bool
     */
    static public function has($key,$file=null){
        if( self::get($key,$file) === null ){
            return false;
        }
        return true;
    }

    /**
     * remove all cached file storage
     * @return void
     */
    static public function clean(){
        $storages = Route::find("/.+?\\".(self::CACHE_EXT)."$/",self::CACHE_PATH);
        foreach( $storages as $path ){
            unlink($path);
        }
        self::$__memory = [];
    } 

    /**
     * remove an specefic storage
     * @param string $file
     * @return bool
     */
    static public function refresh($file=null){
        if( self::isCached($file) ){
            $path = self::getCacheDir($file);
            unset(self::$__memory[$path]);
            unlink($path);
            return true;
        }
        return false;
    }

    /**
     * remove a key from storage
     * @param string $key
     * @param string $file
     * @return bool
     */
    static public function remove($key,$file=null){
        $path = self::getCacheDir($file);
        $tmp = &self::$__memory;
        if( isset($tmp[$path]) && isset($tmp[$path]["data"][$key]) ){
            unset(self::$__memory[$path]["data"][$key]);
            self::$__memory[$path]["edited"] = true;
            return true;
        }
        return false;
    }
}
