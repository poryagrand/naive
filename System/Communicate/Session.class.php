<?php
namespace System\Communicate;

use System\Communicate\Debug\Console;

class SessionHandleException extends \Exception{}

class BasicSession{
    public static $NULL = null;
    public static function &set($name,$value){
        if( session_status() == PHP_SESSION_NONE ){
            ini_set('session.gc_maxlifetime', 365*24*60*60);
            session_set_cookie_params(365*24*60*60);
            session_start();
        }
        $_SESSION[$name] = $value;
        return  $_SESSION[$name];
    }

    public static function &get($name){
        if( session_status() == PHP_SESSION_NONE ){
            ini_set('session.gc_maxlifetime', 365*24*60*60);
            session_set_cookie_params(365*24*60*60);
            session_start();
        }
        if( isset($_SESSION[$name]) ){
            return $_SESSION[$name];
        }
        return self::$NULL;
    }

    public static function remove($name){
        if( session_status() == PHP_SESSION_NONE ){
            ini_set('session.gc_maxlifetime', 365*24*60*60);
            session_set_cookie_params(365*24*60*60);
            session_start();
        }
        if( isset($_SESSION[$name]) ){
            unset($_SESSION[$name]);
        }
    }
}

/**
 * storing data in Session
 * all data will store in one Session in encrypted mode
 * 
 * @property string $_storageName
 * @property string $_secKey
 * @property int $_exprDurationDays
 * @property int $_DataexprDurationSeconds
 * @property bool $_lockInSecureRead
 * @property string $_lockInSecureReadKey
 */
class Session{
    private static $_storageName = "secure_session_data_storage";
    private static $_exprDurationDays = 365;
    private static $_DataexprDurationSeconds = 1*24*60*60;

    private static $_lockInSecureRead = false;
    private static $_lockInSecureReadKey = "";


    /**
     * return all sessions in raw mode
     * @return array
     */
    public static function all(){
        self::init();
        return array_keys(BasicSession::get(self::$_storageName)["body"]);
    }


    /**
     * set default expire duration of a data in seconds
     * @param int $minutes
     * @returns SessionHandleException
     */
    public static function setDefaultDataExpire($seconds){
        $seconds = (int)$seconds;
        if( gettype($seconds) !== "integer" || $seconds < 1 ){
            return new SessionHandleException("Expire duration(seconds) must be an integer bigger that one!");
        }

        self::$_DataexprDurationSeconds = $seconds;
    }


    /**
     * set expire duration in days
     * @param int $days
     * @returns SessionHandleException
     */
    public static function setExpireAfter($days){
        $days = (int)$days;
        if( gettype($days) !== "integer" || $days < 1 ){
            return new SessionHandleException("Expire duration(days) must be an integer bigger that one!");
        }

        self::$_exprDurationDays = $days;
    }

    /**
     * get random security encryption key
     * @return string
     */
    protected static function getSecKey(){      
        if( !(\System\Communicate\Cookie::has("__session_security_key__")) ){
            self::generateSecKey();
        }

        return \System\Communicate\Cookie::get("__session_security_key__")->value;
    }

    /**
     * generate random security encryption key on each initialization of session
     */
    protected static function generateSecKey(){      
        if( \System\Communicate\Cookie::has("__session_security_key__") ){
            \System\Communicate\Cookie::remove("__session_security_key__");
        }

        $sec = &\System\Communicate\Cookie::get("__session_security_key__");
        $sec->value = \System\Security\Crypt::generateKey(12);
        $sec->isLock = true;
        
        $exp = new \DateTime();
        $exp->add(new \DateInterval("P365D"));
        $sec->expire = $exp;
        \System\Communicate\Cookie::save();
    }


    /**
     * get or set if not exist a Session from/to storage
     * @param string $name
     * @return SessionData
     * @returns SessionHandleException
     */
    public static function &get($name=null)
    {
        self::init();
        $storage = &BasicSession::get(self::$_storageName);

        if( $name === null ){
            if( self::isInSecure() ){
                $name = self::$_lockInSecureReadKey;
            }
            else{
                return new SessionHandleException("name of session is not specified!");
            }
        }
        if( $name != self::$_lockInSecureReadKey && self::isInSecure() ){
            return new SessionHandleException("you can't access session except `".(self::$_lockInSecureReadKey)."` inside a `secure` block . first close it.");
        }

        if( isset($storage["body"][$name]) && gettype($storage["body"][$name]) == "string" ){
            if( !self::isInSecure() ){
                return new SessionHandleException("you can't read encrypted session out of `secure` block .");
            }
            else{
                if( $storage["body"][$name]->isExpired ){
                    $storage["body"][$name] = new SessionData($name,self::$_DataexprDurationSeconds);
                }
            }            
        }
        

        if( !(isset($storage["body"][$name]) && $storage["body"][$name] instanceof SessionData) ){
            $storage["body"][$name] = new SessionData($name,self::$_DataexprDurationSeconds);
        }
        else if( $storage["body"][$name]->isExpired ){
            $storage["body"][$name] = new SessionData($name,self::$_DataexprDurationSeconds);
        }

        return $storage["body"][$name];
    }

    public static function isEncrypted($name){
        self::init();
        $storage = &BasicSession::get(self::$_storageName);
        if( isset($storage["body"][$name]) && gettype($storage["body"][$name]) == "string" ){
            return true;         
        }
        return false;
    }

    public static function isInSecure(){
        return !(!self::$_lockInSecureRead);
    }

    /**
     * decrypt a session
     * @param string $name
     * @returns SessionHandleException
     */
    public static function openSecure($name)
    {

        if( self::isInSecure() ){
            return new SessionHandleException("a secure block has already opened!");
        }

        self::init();
        $storage = &BasicSession::get(self::$_storageName);

        if( !isset($storage["body"][$name]) ){
            $storage["body"][$name] = new SessionData($name,self::$_DataexprDurationSeconds);
            self::$_lockInSecureRead = true;
            self::$_lockInSecureReadKey = $name;
            return;
        }

        if( !( !($storage["body"][$name] instanceof SessionData) && gettype(($storage["body"][$name])) == "string" ) ){
            return new SessionHandleException("encrypted session with name `$name` doesn't exists.");
        }

        self::$_lockInSecureRead = true;
        self::$_lockInSecureReadKey = $name;
        $dcrpt = \System\Security\Crypt::decrypt($storage["body"][$name],self::getSecKey());

        $dcrpt = @json_decode($dcrpt,true);

        if( $dcrpt !== null ){
            try{
                $tempCookie = new SessionData($dcrpt["name"],$dcrpt["expireAt"]);
                $tempCookie->import($dcrpt);
                $stg = &BasicSession::get(self::$_storageName);
                $stg["body"][$name] = $tempCookie;
                return;
            }
            catch(SessionHandleException $e){return $e;}
        }
        else{
            self::closeSecure();
            $stg = &BasicSession::get(self::$_storageName);
            unset($stg["body"][$name]);
            return self::openSecure($name);
        }
        //return new SessionHandleException("encrypted session with name `$name` has mismatched value.");
    }

    /**
     * encrypt back a session
     * @returns SessionHandleException
     */
    public static function closeSecure(){
        if( !self::isInSecure() ){
            return new SessionHandleException("no secure block has opened.");
        }

        self::init();

        $name = self::$_lockInSecureReadKey;
        $storage = &BasicSession::get(self::$_storageName);

        if( !( isset($storage["body"][$name]) && $storage["body"][$name] instanceof SessionData) ){
            return new SessionHandleException("encrypted session with name `$name` doesn't exists.");
        }

        self::$_lockInSecureRead = false;
        self::$_lockInSecureReadKey = "";

        $enc = BasicSession::get(self::$_storageName)["body"][$name]->export();
        $enc = json_encode($enc);

        BasicSession::get(self::$_storageName)["body"][$name] = \System\Security\Crypt::encrypt($enc,self::getSecKey());
    }


    /**
     * remove all data from Session
     * @return void
     */
    public static function flush(){
        self::init();
        BasicSession::get(self::$_storageName)["body"] = array();
    }

    /**
     * refresh the Session expiration
     * @return void
     */
    public static function refresh(){
        self::init();

        $exp = new \DateTime();
        $exp->add(new \DateInterval("P".(self::$_exprDurationDays)."D"));

        BasicSession::get(self::$_storageName)["expireAt"] = $exp->format("Y-m-d H:i:s");
    }

    /**
     * remove a Session data 
     * @param string $name
     * @return bool
     */
    public static function remove($name){
        self::init();

        if( self::has($name) ){
            unset(BasicSession::get(self::$_storageName)["body"][$name]);
            return true;
        }
        return false;
    }

    /**
     * checks the existance of a Session
     * @param string $name
     * @return bool
     */
    public static function has($name){
        self::init();

        $storage = &BasicSession::get(self::$_storageName);

        if( isset($storage["body"][$name]) && ($storage["body"][$name] instanceof SessionData || is_string($storage["body"][$name]) ) ){
            return true;
        }
        return false;
    }


    /**
     * return the name of the saved Session
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
     * check if Sessions are initialized or not
     * @return bool
     */
    public static function isInit(){
        return !empty(BasicSession::get(self::$_storageName));
    }

    /**
     * check if the Sessions storage is expired or not
     * @return bool
     */
    public static function isExpired(){
        if( self::isInit() ){
            try{
                if( strtotime(BasicSession::get(self::$_storageName)["expireAt"]) - strtotime(date("Y-m-d H:i:s")) <= 0 ){
                    return true;
                }
                return false;
            }
            catch(SessionHandleException $e){}
        }
        return true;
    }

    /**
     * initialize storage from receiving Session if exists or create new storage
     * @return bool
     */
    public static function init(){
        if( session_status() == PHP_SESSION_NONE ){
            session_start();
        }

        if( self::isInit() && !self::isExpired() ){
            return false;
        } 
        else{
            self::generateSecKey();
            $exp = new \DateTime();
            $exp->add(new \DateInterval("P".(self::$_exprDurationDays)."D"));

            BasicSession::set(self::$_storageName,array(
                "body"=>array(),
                "lastUpdate"=>date("Y-m-d H:i:s"),
                "expireAt"=>$exp->format("Y-m-d H:i:s"),
                "createAt"=>date("Y-m-d H:i:s")
            ));
        }
        // remove expired sessions
        $ses = &BasicSession::get(self::$_storageName)["body"];
        foreach( $ses as $key=>&$val ){
            if( gettype($val) == "string" ){
                try{
                    self::openSecure($key);
                    if( self::get()->isExpired ){
                        unset(BasicSession::get(self::$_storageName)["body"][$key]);
                    }
                    self::closeSecure();
                }
                catch(\Exception $e){
                    unset(BasicSession::get(self::$_storageName)["body"][$key]);
                } 
            }
            else if( $val instanceof SessionData && $val->isExpired ){
                unset(BasicSession::get(self::$_storageName)["body"][$key]);
            }
        }

        return true;
    }
}