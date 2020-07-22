<?php
namespace System\Communicate;

use System\Communicate\Debug\Console;

class CookieHandleException extends \Exception{}


/**
 * storing data in cookie
 * all data will store in one cookie in encrypted mode
 * 
 * @property string $_storageName
 * @property string $_secKey
 * @property int $_exprDurationDay
 * @property int $_DataexprDurationDay
 * @property array $_storage
 * @property bool $_autoSave
 */
class Cookie{
    private static $_storageName = "secure_cookie";
    private static $_secKey = "8f7sd76&6s8s0sfhJHGHs87faJKsfu978";
    private static $_exprDurationDay = 365;
    private static $_DataexprDurationDay = 10;

    private static $_storage = array();
    private static $_autoSave = false;


    /**
     * return the body
     * @return array
     */
    public static function all(){
        self::init();
        return array_keys(self::$_storage["body"]);
    }


    /**
     * set default expire duration of a data in day
     * @param int $days
     * @throws CookieHandleException
     */
    public static function setDefaultDataExpire($days){
        if( !is_numeric($days) || ((int)$days) < 1 ){
            throw new CookieHandleException("Expire duration(days) must be an integer bigger that one!");
        }

        self::$_DataexprDurationDay = (int)$days;
    }


    /**
     * set expire duration in day
     * @param int $days
     * @throws CookieHandleException
     */
    public static function setExpireAfter($days){
        if( !is_numeric($days) || ((int)$days) < 1 ){
            throw new CookieHandleException("Expire duration(days) must be an integer bigger that one!");
        }

        self::$_exprDurationDay = (int)$days;
    }

    /**
     * set security encryption key
     * @param string $key
     * @throws CookieHandleException
     */
    public static function setSecKey($key){
        if( gettype($key) !== "string" || strlen($key) <= 8 ){
            throw new CookieHandleException("the key must be string and the length must be more than 8 characters.");
        }

        self::$_secKey = $key;
    }


    /**
     * get or set if not exist a cookie from/to storage
     * @param string $name
     * @return CookieData
     */
    public static function &get($name)
    {
        self::init();
        $storage = &self::$_storage;

        if( !(isset($storage["body"][$name]) && $storage["body"][$name] instanceof CookieData) ){
            $storage["body"][$name] = new CookieData($name,self::$_DataexprDurationDay);
            self::saveOnAuto();
        }
        else if( $storage["body"][$name]->isExpired || !($storage["body"][$name] instanceof CookieData) ){
            $storage["body"][$name] = new CookieData($name,self::$_DataexprDurationDay);
        }

        return $storage["body"][$name];
    }

    /**
     * set and check the auto save of cookie on each change
     * @param null|bool $do
     * @return bool
     */
    public static function autoSave($do=null){
        if( $do !== null ){
            self::$_autoSave = !(!$do);
        }
        
        return self::$_autoSave;
    }

    /**
     * remove all data from cookie
     * @return void
     */
    public static function flush(){
        self::init();
        self::$_storage["body"] = array();
        
        self::saveOnAuto();
    }

    /**
     * refresh the cookie expiration
     * @return void
     */
    public static function refresh(){
        self::init();

        $exp = new \DateTime();
        $exp->add(new \DateInterval("P".(self::$_exprDurationDay)."D"));

        self::$_storage["expireAt"] = $exp->format("Y-m-d H:i:s");

        self::saveOnAuto();
    }

    /**
     * remove a cookie data 
     * @param string $name
     * @return bool
     */
    public static function remove($name){
        self::init();

        if( self::has($name) ){
            unset(self::$_storage["body"][$name]);

            self::saveOnAuto();

            return true;
        }
        return false;
    }

    /**
     * checks the existance of a cookie
     * @param string $name
     * @return bool
     */
    public static function has($name){
        self::init();

        $storage = &self::$_storage;

        if( isset($storage["body"][$name]) && $storage["body"][$name] instanceof CookieData ){
            return true;
        }
        return false;
    }


    /**
     * return the name of the saved cookie
     * @param string|null $name
     * @return string
     */
    public static function storageName($name=null){
        if( gettype( $name ) == "string" ){
            self::$_storageName = $name;
        }
        return self::$_storageName;
    }

    /**
     * store encrypted cookies 
     * 
     * @return bool
     */
    public static function save(){
        if( self::isInit() && !self::isExpired() ){
            try{
                self::$_storage["lastUpdate"] = date("Y-m-d H:i:s");
                $enc = \System\Security\Crypt::encrypt(
                    self::toJson(),
                    self::$_secKey
                );
    
                setcookie(self::$_storageName, $enc, time() + (86400 * 3650), "/"); // 10 years
                return true;
            }
            catch(CookieHandleException $e){}
        }
        return false;
    }

    public static function saveOnAuto(){
        if( self::autoSave() ){
            self::save();
        }
    }

    /**
     * check if cookies are initialized or not
     * @return bool
     */
    public static function isInit(){
        return !empty(self::$_storage);
    }

    /**
     * check if the cookies storage is expired or not
     * @return bool
     */
    public static function isExpired(){
        if( self::isInit() ){
            try{
                if( strtotime(self::$_storage["expireAt"]) - strtotime(date("Y-m-d H:i:s")) <= 0 ){
                    return true;
                }
                return false;
            }
            catch(CookieHandleException $e){}
        }
        return true;
    }

    /**
     * initialize storage from receiving cookie if exists or create new storage
     * @return bool
     */
    public static function init(){
        if( self::isInit() && !self::isExpired() ){
            return false;
        } 
        else if( !self::isInit() && isset( $_COOKIE[self::$_storageName] ) ){
            try{
                $dcrpt = \System\Security\Crypt::decrypt($_COOKIE[self::$_storageName],self::$_secKey);
                $obj = self::fromJson($dcrpt);
                if( $obj !== false ){
                    if( isset( $obj["createAt"] ) && isset( $obj["expireAt"] ) && isset( $obj["lastUpdate"] ) && isset( $obj["body"] ) ){
                        if( strtotime($obj["expireAt"]) - strtotime(date("Y-m-d H:i:s")) > 0 ){
                            self::$_storage = $obj;
                        }
                        else{
                            throw new CookieHandleException("Cookie Storage is expired!");
                        }
                    }
                    else{
                        throw new CookieHandleException("Cookie Storage is not in the correct format!");
                    }
                }
                else{
                    throw new CookieHandleException("Cookie Storage is empty!");
                }
            }
            catch(CookieHandleException $e){

                $exp = new \DateTime();
                $exp->add(new \DateInterval("P".(self::$_exprDurationDay)."D"));

                self::$_storage = array(
                    "body"=>array(),
                    "lastUpdate"=>date("Y-m-d H:i:s"),
                    "expireAt"=>$exp->format("Y-m-d H:i:s"),
                    "createAt"=>date("Y-m-d H:i:s")
                );
            }
        }
        else{
            $exp = new \DateTime();
            $exp->add(new \DateInterval("P".(self::$_exprDurationDay)."D"));

            self::$_storage = array(
                "body"=>array(),
                "lastUpdate"=>date("Y-m-d H:i:s"),
                "expireAt"=>$exp->format("Y-m-d H:i:s"),
                "createAt"=>date("Y-m-d H:i:s")
            );
        }
        return true;
    }

    /**
     * convert storage to json plain text
     * @return string
     */
    protected static function toJson(){
        $newObj = array(
            "body"=>array(),
            "lastUpdate"=>self::$_storage["lastUpdate"],
            "expireAt"=>self::$_storage["expireAt"],
            "createAt"=>self::$_storage["createAt"]
        );
        $body = &self::$_storage["body"];

        foreach( $body as $key=>&$val ){
            if( !$val->isExpired ){
                $newObj["body"][$key] = $val->export();
            }
        }

        return json_encode($newObj);
    }

    /**
     * convert json plain text to cookies data objects
     * @return array
     */
    protected static function fromJson($json){
        $obj = @json_decode($json,true);

        if( $obj !== null && isset($obj["body"]) && gettype($obj["body"]) == "array" ){
            foreach( $obj["body"] as $key=>&$val ){
                $tempCookie = new CookieData($val["name"],$val["expireAt"]);
                $tempCookie->import($val);
                if( !$tempCookie->isExpired ){
                    $obj["body"][$key] = $tempCookie;
                }
                else{
                    unset($obj["body"][$key]);
                }
            }
        }
        else{
            return false;
        }

        

        return $obj;
    }
}