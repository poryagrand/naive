<?php

namespace System\Security;

use \System\Communicate\Session;
use System\Communicate\Cookie;
use System\Communicate\Debug\Console;
use System\Controller\Route;

class AuthHandleException extends \Exception{}

/**
 * control the auth and permitions state of users.
 * @property \System\Database\Model\Base $__table
 * @property \System\Database\Model\TableStructure $__user
 * @property string $__session_name
 * @property string $__cookie_name
 * @property string $__attempDate
 * @property int $__expireAfter  (in minutes)
 * @property bool $__cookieExpire (in days)
 * @property bool $__isLoggedIn
 * @property bool $__isAttempted
 * @property bool $__secureSession
 * @property array $__selectQuery
 * @property array $__eventOnLogOut
 * @property array $__eventOnLogin
 */
class Auth{

    protected $__table;
    /**
     * @var \System\Database\Model\TableStructure $__user
     */
    protected $__user;
 
    protected $__session_name;
    protected $__secureSession;
    protected $__cookie_name;

    protected $__attempDate;
    protected $__expireAfter;
    protected $__cookieExpire;
    protected $__expireAfter_default;
    protected $__cookieExpire_default;
    protected $__lastActivity;

    protected $__isLoggedIn;
    protected $__isAttempted;

    protected $__selectQuery;

    protected $__encUID;

    // events stack
    protected $__eventOnLogOut;
    protected $__eventOnLogin;
    protected $__eventOnLock;

    protected static $__instance;

    protected $__cookieRemove;

    /**
     * @param \System\Database\Model\Base $base
     * @param bool $secureSession storing session in secure mode is slower than raw session
     * @throws AuthHandleException
     */
    public function __construct($table,$sessionName=false,$cookieName=false,$secureSession = true,$logOut=true)
    {
        if( !\System\Database\Model\Base::is($table) ){
            throw new AuthHandleException("first argument must be instance of a Base class.(must be a table model)");
        }

        $this->__cookieRemove = !(!$logOut);

        $this->__table = $table;
        $this->__session_name = is_string($sessionName)?$sessionName:("AUTH_SESSION_DATA");
        $this->__cookie_name = is_string($cookieName)?$cookieName:("AUTH_SESSION_DATA");

        $this->__expireAfter = 10;//minutes
        $this->__cookieExpire = 365;//days

        $this->__expireAfter_default = 10;//minutes
        $this->__cookieExpire_default = 365;//days

        $this->__lastActivity = date("Y-m-d H:i:s");

        $this->__attempDate = false;
        $this->__user = false;
        $this->__isLoggedIn = false;
        $this->__isAttempted = false;
        $this->__secureSession = !(!$secureSession);
        $this->__selectQuery = array();


        $this->__encUID = Crypt::hash(
            Route::headers("User-Agent") . "|" . Route::ip()
        );

        $this->__eventOnLogOut = array(
            "before"=>array(),
            "after"=>array()
        );
        $this->__eventOnLogin = array(
            "before"=>array(),
            "after"=>array()
        );
        $this->__eventOnLock = array(
            "before"=>array(),
            "after"=>array()
        );
        $this->__eventOnCheck = array(
            "before"=>array(),
            "after"=>array()
        );
        $this->__eventOnInit = array();

        //initial the events from table
        if( method_exists($table,"onAuthInit") ){
            $this->__eventOnInit[] = array($table,"onAuthInit");
        }
        if( method_exists($table,"onBeforeLoginCheck") ){
            $this->__eventOnCheck["before"][] = array($table,"onBeforeLoginCheck");
        }
        if( method_exists($table,"onAfterLoginCheck") ){
            $this->__eventOnCheck["after"][] = array($table,"onAfterLoginCheck");
        }

        if( method_exists($table,"onBeforeLogOut") ){
            $this->__eventOnLogOut["before"][] = array($table,"onBeforeLogOut");
        }
        if( method_exists($table,"onAfterLogOut") ){
            $this->__eventOnLogOut["after"][] = array($table,"onAfterLogOut");
        }

        if( method_exists($table,"onBeforeLogin") ){
            $this->__eventOnLogin["before"][] = array($table,"onBeforeLogin");
        }
        if( method_exists($table,"onAfterLogin") ){
            $this->__eventOnLogin["after"][] = array($table,"onAfterLogin");
        }

        if( method_exists($table,"onBeforeLock") ){
            $this->__eventOnLock["before"][] = array($table,"onBeforeLock");
        }
        if( method_exists($table,"onAfterLock") ){
            $this->__eventOnLock["after"][] = array($table,"onBeforeLock");
        }

        $this->fireEvent("init","after");

        !$this->reload($logOut);

        self::$__instance = &$this;
    }

    /**
     * @return Auth
     */
    public static function instance(){
        return self::$__instance;
    }

    /**
     * fire events from event stack for each type
     * @param string $stackName
     * @param string $type
     * @return Auth
     */
    public function fireEvent($stackName,$type,$args=null){
        $type = $type == "after"?"after":"before";

        if( $stackName == "login" ){
            $stackName = &$this->__eventOnLogin[$type];
        }
        else if( $stackName == "lock" ){
            $stackName = &$this->__eventOnLock[$type];
        }
        else if( $stackName == "check" ){
            $stackName = &$this->__eventOnCheck[$type];
        }
        else if( $stackName == "init" ){
            $stackName = &$this->__eventOnInit;
        }
        else{
            $stackName = &$this->__eventOnLogOut[$type];
        }

        $output = $args;
        foreach( $stackName as &$val ){
            if( is_callable($val) ){
                $output = call_user_func($val,$this,!is_array($args) ? [$args] : $args);
            }
        }
        return $output;
    }



    /**
     * check if user is logged in before and has been saved in session
     * @return bool
     */
    protected function reload($logOut=true){
        $sessionData = $this->getSession();

        if( $sessionData !== null ){

            if( !isset( $sessionData["tableName"] ) || $sessionData["tableName"] !== $this->__table ){
                return false;
            }

            $where = [];
            if( isset($sessionData["user"]) && $sessionData["user"] !== null ){
                $where[$sessionData["user"]["name"]] = $sessionData["user"]["value"];
            }
            else if( is_array($sessionData["query"]) ){
                $where = $sessionData["query"];
            }
            if($this->attempt($where)){
                $this->__expireAfter = $sessionData["expire"];
                $this->__attempDate = $sessionData["attempt"];
                $this->__cookieExpire = $sessionData["cookie"];
                $this->__selectQuery = $sessionData["query"];
                $this->__lastActivity = $sessionData["lastActive"];
                if( $sessionData["encUID"] == $this->__encUID ){
                    $this->__encUID = $sessionData["encUID"];
                    $this->__isLoggedIn = true;
                    $this->__isAttempted = true;
                    $this->logInNoEvent();

                    if( $this->check() ){
                        $this->__lastActivity = date("Y-m-d H:i:s");
                        $this->save();
                        return true;
                    }
                }
                else{
                    $this->logOut();
                    return false;
                }
            }
        }

        if( !$this->check() && $logOut ){
            $this->lock();
        }
        return false;
    }

    /**
     * set the duration that user can be logged in
     * @param int $minutes
     * @return Auth
     */
    public function setAccessDuration($minutes){
        if( !is_numeric($minutes) || $minutes < 0 ){
            throw new AuthHandleException("expiration duration must be an positive integer number in minutes");
        }
        $this->__expireAfter = $minutes;
        return $this;
    }

        /**
     * set expiration time of login auth cookie data (in days)
     * @param int $days
     * @return Auth
     */
    public function setRememberDuration($days){
        if( !is_numeric($days) || $days <= 0 ){
            throw new AuthHandleException("expiration duration must be an positive integer number in days");
        }
        $this->__cookieExpire = $days;
        return $this;
    }


    /**
     * check user is logged in or not
     * @return bool
     */
    public function isLoggedIn(){
        return !(!$this->__isLoggedIn);
    }

    /**
     * check user is attempt in or not
     * @return bool
     */
    public function isAttempted(){
        return !(!$this->__isAttempted);
    }


    /**
     * return the remaining seconds to expiration time
     * @return int|false
     */
    public function expireRemain(){
        if( $this->isLoggedIn() ){
            $date = new \DateTime($this->__lastActivity);
            $date->add(new \DateInterval("PT".($this->__expireAfter)."M"));
            $rem = strtotime($date->format("Y-m-d H:i:s")) - strtotime(date("Y-m-d H:i:s"));
            if( $rem > 0 ){
                return $rem;
            }
        }
        
        return false;
    }

    /**
     * check user authentication 
     * @return bool
     */
    public function check(){
        $this->fireEvent("check","before");
        if( $this->expireRemain() !== false ){
            return $this->fireEvent("check","after",true);
        }
        return $this->fireEvent("check","after",false);
    }

    /**
     * checks user accessibility and logout if needed
     * @return bool
     */
    public function checkAndLogOut(){
        if( !$this->check() ){
            $this->logOut();
            return false;
        }
        return true;
    }

    /**
     * remove all auth data and loging out the user
     * @return Auth
     */
    public function logOut(){
        $this->fireEvent("logout","before");

        $this->__isLoggedIn = false;
        $this->__isAttempted = false;
        $this->__cookieExpire = $this->__cookieExpire_default;
        $this->__expireAfter = $this->__expireAfter_default;
        $this->__attempDate = false;
        $this->__user = false;
        $this->__selectQuery = array();
        if($this->__cookieRemove){
            Session::remove($this->getSessionName());
            Cookie::remove($this->getCookieName());
            Cookie::save();
        }
        
        $this->fireEvent("logout","after");
        return $this;
    }
    
    /**
     * remove all auth data and lock the user
     * @return Auth
     */
    public function lock(){
        $this->fireEvent("lock","before");

        $cookie = $this->getCookie();
        $this->logOut();
        if( $cookie !== null ){
            if( $this->attempt($cookie["query"]) ){
                $cookieData = array(
                    "query" => $this->__selectQuery,
                    "tableName"=>$this->__table
                );
    
                $this->setCookie($cookieData);
            }
        }
        
        $this->fireEvent("lock","after");
        return $this;
    }

    /**
     * checks if the session stored in  secure mode
     * @return bool
     */
    public function isSecSession(){
        return $this->__secureSession;
    }

    /**
     * set if the session stored in  secure mode
     * @param bool $bool
     * @return bool
     */
    public function setSecSession($bool){
        return $this->__secureSession = !(!$bool);
    }

    /**
     * retrieve the session data 
     * @return mixed
     */
    protected function getSession(){
        if( !Session::has($this->getSessionName()) ){
            return null;
        }
        if( $this->isSecSession() ){
            $temp = null;
            try{
                Session::openSecure($this->getSessionName());
                    $temp = Session::get()->value;
                Session::closeSecure();
            }
            catch(\Exception $e){
                return null;
            }
            return $temp;
        }

        if( Session::isEncrypted($this->getSessionName()) ){
            $this->setSession(null);
        }
        return Session::get($this->getSessionName())->value;
    }

    /**
     * set any data to session in secure or not depend on user 
     * @param mixed $obj
     * @return Auth
     */
    protected function setSession($obj){
        Session::remove($this->getSessionName());
        if( $this->isSecSession() ){
            Session::openSecure($this->getSessionName());
                Session::get()->value = $obj;
            Session::closeSecure();
        }
        else{
            Session::get($this->getSessionName())->value = $obj;
        }
        return $this;
    }

    /**
     * set session storage name
     * @param string $name
     * @return bool
     */
    public function setSessionName($name){
        if( is_string($name) && Session::has($name)){
            $this->__session_name = $name;
            return true;
        }
        else if( is_string($name) && $name !== $this->__session_name ){
            $data = $this->getSession();

            Session::remove($this->__session_name);

            $this->__session_name = $name;

            $this->setSession($data);

            return true;
        }
        return false;
    }

    /**
     * the name of session storage
     * @return string
     */
    public function getSessionName(){
        return $this->__session_name;
    }


    /**
     * retrieve the cookie data 
     * @return mixed
     */
    protected function getCookie(){
        if( Cookie::has($this->getCookieName()) ){
            return Cookie::get($this->getCookieName())->value;
        }
        return null;
    }

    /**
     * set any data to cookie
     * @param mixed $obj
     * @return Auth
     */
    protected function setCookie($obj){
        Cookie::get($this->getCookieName())->value = $obj;
        Cookie::save();
        return $this;
    }

    /**
     * set cookie storage name
     * @param string $name
     * @return bool
     */
    public function setCookienName($name){
        if( is_string($name) && Cookie::has($name)){
            $this->__cookie_name = $name;
            return true;
        }
        else if( is_string($name) && $name !== $this->__cookie_name ){
            $data = $this->getCookie();

            Cookie::remove($this->__cookie_name);

            $this->__cookie_name = $name;

            $this->setCookie($data);

            return true;
        }
        return false;
    }

    /**
     * the name of cookie storage
     * @return string
     */
    public function getCookieName(){
        return $this->__cookie_name;
    }


    /**
     * find the user to auth. with login , it can be logged in
     * @param array $where
     * @return bool
     * @throws AuthHandleException
     */
    public function attempt($where){
        $this->fireEvent("login","before");

        if( !is_array($where) ){
            throw new AuthHandleException("user select query condition must be an array.");
        }

        $cls = $this->__table;
        foreach( $where as $cond=>$val ){
            if( !$cls::hasColumn($cond) && !is_numeric($cond) ){
                return false;
            }
        }

        if( !$cls::hasHost() ){
            return false;
        }

        $users = $cls::where($where)->select();

        if( count($users) > 0 ){
            $this->__user = $users[0];
            $this->__attempDate = date("Y-m-d H:i:s");
            $this->__isLoggedIn = false;
            $this->__isAttempted = true;
            $this->__selectQuery = $where;
            return true;
        }

        return false;
    }

    /**
     * login user and save in session without event
     * @param array $where
     * @return Auth
     */
    protected function logInNoEvent($where=null){
        if( is_array($where) && $this->attempt($where) ){
            $this->__isLoggedIn = true;
            $this->save();
        }
        else if($this->isAttempted()){
            $this->__isLoggedIn = true;
            $this->save();
        }

        return $this;
    }

    /**
     * login user and save in session
     * @param array $where
     * @return Auth
     */
    public function logIn($where=null){
        if( is_array($where) && $this->attempt($where) ){
            $this->__isLoggedIn = true;
            $this->fireEvent("login","after");
            $this->save();
        }
        else if($this->isAttempted()){
            $this->__isLoggedIn = true;
            $this->fireEvent("login","after");
            $this->save();
        }

        return $this;
    }

    /**
     * login user for one request . no session will store
     * @param array $where
     * @return Auth
     */
    public function once( $where ){
        if( $this->attempt($where) ){
            $this->__isLoggedIn = true;
            $this->__isAttempted = true;
        }
        $this->fireEvent("login","after");
        return $this;
    }

    /**
     * backup data in session to use in next request
     * @return Auth
     */
    public function save(){
        $sessionData = array();
        $sessionData["user"] = $this->__user->getPrimaryKey();
        $sessionData["expire"] = $this->__expireAfter;
        $sessionData["attempt"] = $this->__attempDate;
        $sessionData["query"] = $this->__selectQuery;
        $sessionData["cookie"] = $this->__cookieExpire;
        $sessionData["lastActive"] = $this->__lastActivity;
        $sessionData["tableName"] = $this->__table;
        $sessionData["encUID"] = $this->__encUID;

        $this->setSession($sessionData);

        $this->__isLoggedIn = true;
        $this->__isAttempted = true;

        return $this;
    }

    /**
     * rememebr user access in cookie to load next time
     * @return Auth
     */
    public function remember(){
        if( $this->isLoggedIn() ){
            $cookieData = array(
                "query" => $this->__selectQuery,
                "tableName"=>$this->__table
            );

            $this->setCookie($cookieData);
        }

        return $this;
    }

    /**
     * remove cookie data
     * @return Auth
     */
    public function unRemember(){
        
        if( $this->isLoggedIn() && $this->__cookieRemove ){
            Cookie::remove($this->getCookieName());
        }

        return $this;
    }

    /**
     * checks if user has logged in before and the cookie exists
     * @return bool
     */
    public function isLocked(){
        $cookie = $this->getCookie();
        if( $cookie === null ){
            return false;
        }
        if( $cookie["tableName"] !== $this->__table ){
            return false;
        }
        return true;
    }

    /**
     * login remembered user via cookie if it is valid
     * @param array $check (optional) To verify user data accuracy
     * @return bool
     */
    public function viaRemember($check=null){
        $cookie = $this->getCookie();
        if( $cookie !== null && isset($cookie["query"]) && $this->attempt($cookie["query"]) && $cookie["tableName"] == $this->__table ){
            if( is_array($check) ){
                foreach( $check as $key=>$val ){
                    if( !$this->__user->hasColumn($key) || $this->__user->{$key} !== $val ){
                        return false;
                    }
                }
            }
            else if( is_callable($check) ){
                if( !call_user_func_array($check,[&$this->__user]) ){
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * return the user table row data model
     * @return \System\Database\Model\TableStructure
     */
    public function user(){
        return $this->__user;
    }

    // ========== events

    /**
     * add to stack to run at a user auth initilied
     * @param callable $fn
     * @return Auth
     */
    public function onInit($fn){
        if( !is_callable($fn) ){
            throw new AuthHandleException("auth event just can be callable function");
        }

        $this->__eventOnInit[] = $fn;

        return $this;
    }

    /**
     * add to stack to run before a user lock
     * @param callable $fn
     * @return Auth
     */
    public function onBeforeLock($fn){
        if( !is_callable($fn) ){
            throw new AuthHandleException("auth event just can be callable function");
        }

        $this->__eventOnLock["before"][] = $fn;

        return $this;
    }

    /**
     * add to stack to run after a user lock
     * @param callable $fn
     * @return Auth
     */
    public function onAfterLock($fn){
        if( !is_callable($fn) ){
            throw new AuthHandleException("auth event just can be callable function");
        }

        $this->__eventOnLock["after"][] = $fn;

        return $this;
    }

    /**
     * add to stack to run before a user log in
     * @param callable $fn
     * @return Auth
     */
    public function onBeforeLogIn($fn){
        if( !is_callable($fn) ){
            throw new AuthHandleException("auth event just can be callable function");
        }

        $this->__eventOnLogin["before"][] = $fn;

        return $this;
    }

    /**
     * add to stack to run after a user check
     * @param callable $fn
     * @return Auth
     */
    public function onAfterLoginCheck($fn){
        if( !is_callable($fn) ){
            throw new AuthHandleException("auth event just can be callable function");
        }

        $this->__eventOnCheck["after"][] = $fn;

        return $this;
    }

     /**
     * add to stack to run after a user check
     * @param callable $fn
     * @return Auth
     */
    public function onBeforeLoginCheck($fn){
        if( !is_callable($fn) ){
            throw new AuthHandleException("auth event just can be callable function");
        }

        $this->__eventOnCheck["before"][] = $fn;

        return $this;
    }

    /**
     * add to stack to run after a user log in
     * @param callable $fn
     * @return Auth
     */
    public function onAfterLogIn($fn){
        if( !is_callable($fn) ){
            throw new AuthHandleException("auth event just can be callable function");
        }

        $this->__eventOnLogin["after"][] = $fn;

        return $this;
    }

     /**
     * add to stack to run after a user log in
     * @param callable $fn
     * @return Auth
     */
    public function onBeforeLogOut($fn){
        if( !is_callable($fn) ){
            throw new AuthHandleException("auth event just can be callable function");
        }

        $this->__eventOnLogOut["before"][] = $fn;

        return $this;
    }

     /**
     * add to stack to run after a user log in
     * @param callable $fn
     * @return Auth
     */
    public function onAfterLogOut($fn){
        if( !is_callable($fn) ){
            throw new AuthHandleException("auth event just can be callable function");
        }

        $this->__eventOnLogOut["after"][] = $fn;

        return $this;
    }
    
}