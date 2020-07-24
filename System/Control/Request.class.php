<?php
namespace System\Controller;

use System\Security\Crypt;
use System\Communicate\Debug\Console;

/**
 * request controling
 * @property RouteRegister $__route
 * @property array $__methodsData
 * @property array $__urlDate
 * @property string $__method
 */
class Request{
    
    protected $__route;
    protected $__methodsData;
    protected $__urlDate;
    protected $__method;

    function __construct($method,$params,$methodParams,&$routeReg)
    {
        if( !is_string($method) || !is_array($params) || !is_array($methodParams) || !($routeReg instanceof RouteRegister) ){
            throw new RouteHandleException("Request arguments are not in correct format");
        }

        $this->__method = $method;
        $this->__route = &$routeReg;
        $this->__methodsData = $methodParams;
        $this->__urlDate = $params;
    }

    /**
     * returns the current route reffrence
     * @return RouteRegister
     */
    public function &route(){
        return $this->__route;
    }

    /**
     * return the csrf value
     * @return string|null
     */
    public function getCSRF(){
        if( isset($_POST["X-Csrf-Token"]) ){
            if( Crypt::getCSRF($this->__route->getCSRFName()) == $_POST["X-Csrf-Token"] ){
                return $_POST["X-Csrf-Token"];
            }
        }
        else if( $this->header("X-Csrf-Token") !== null ){
            if( Crypt::getCSRF($this->__route->getCSRFName()) == $this->header("X-Csrf-Token") ){
                return $this->header("X-Csrf-Token");
            }
        }
        return null;
    }
    

    /**
     * check if csrf exist and check if exist , be correct
     * @return bool
     */
    public function checkCSRF(){
        if( isset($_POST["X-Csrf-Token"]) ){
            if( Crypt::getCSRF($this->__route->getCSRFName()) == $_POST["X-Csrf-Token"] ){
                return true;
            }
        }
        else if( $this->header("X-Csrf-Token") !== null ){
            if( Crypt::getCSRF($this->__route->getCSRFName()) == $this->header("X-Csrf-Token") ){
                return true;
            }
        }
        return false;
    }

    /**
     * check if the request is a post form submit
     * @return bool
     */
    public function isPostSubmit(){
        if( strtolower($this->__method) == "post" ){
            return true;
        }
        return false;
    }

    /**
     * get csrf name
     * @return string
     */
    public function getCSRFName(){
        return $this->__route->getCSRFName();
    }

    /**
     * get current id
     * @return string
     */
    public function id(){
        return $this->__route->id();
    }

    /**
     * server request data
     * @return object
     */
    public function server(){
        $software = $_SERVER["SERVER_SOFTWARE"];
        if( preg_match("/apache/i",$software) ){
            $software = "apache";
        }
        else if( preg_match("/(nginx|cgi|fastcgi|fcgi)/i",$software) ){
            $software = "nginx";
        }
        else{
            $software = "others";
        }

        return (object)array(
            "execFile"=>rtrim($_SERVER["DOCUMENT_ROOT"],"/") . "/" . trim($_SERVER["PHP_SELF"],"/"),
            "gateInterface"=>$_SERVER["GATEWAY_INTERFACE"],
            "ip"=>$_SERVER["SERVER_ADDR"],
            "name"=>$_SERVER["SERVER_NAME"],
            "protocolType"=>$_SERVER["SERVER_PROTOCOL"],
            "protocol"=>$_SERVER["REQUEST_SCHEME"],
            "method"=>$_SERVER["REQUEST_METHOD"],
            "startTime"=>$_SERVER["REQUEST_TIME"],
            "startTimeFloat"=>$_SERVER["REQUEST_TIME_FLOAT"],
            "httpAccept"=>$_SERVER["HTTP_ACCEPT"],
            "httpEncoding"=>$_SERVER["HTTP_ACCEPT_ENCODING"],
            "httpLanguage"=>$_SERVER["HTTP_ACCEPT_LANGUAGE"],
            "httpConnection"=>$_SERVER["HTTP_CONNECTION"],
            "host"=>$_SERVER["HTTP_HOST"],
            "userAgent"=>$_SERVER["HTTP_USER_AGENT"],
            "isHttps"=>$_SERVER["HTTPS"]=="on"?true:false,
            "port"=>$_SERVER["SERVER_PORT"],
            "signature"=>$_SERVER["SERVER_SIGNATURE"],
            "requestedURL"=>$_SERVER["REQUEST_URI"],
            "software"=>$software
        );
    }

    /**
     * returns the name of request method
     * @return string
     */
    public function method(){
        return $this->__method;
    }

    /**
     * returns the list of each method data
     * @param string $cat
     * @param string $key
     * @param mixed $value
     * @return mixed|null
     * @throws RouteHandleException
     */
    public function methodsData($cat,$key=null,$value=null){
        if( !is_string($cat) ){
            throw new RouteHandleException("arguments are not in correct format");
        }

        $tmp = &$this->__methodsData;
        if( $key !== null && isset( $tmp[$cat] ) && isset( $tmp[$cat][$key] ) ){
            if( $value !== null ){
                $this->__methodsData[$cat][$key] = $value;
            }
            return $this->__methodsData[$cat][$key];
        }
        else if( isset( $tmp[$cat] ) && $key === null ){
            return $this->__methodsData[$cat];
        }
        return null;
    }

    /**
     * the view data list
     * @param string $key
     * @param mixed $value
     * @return mixed|null
     */
    public function view($key=null,$value=null){
        return $this->methodsData("view",$key,$value);
    }

    /**
     * the propfind data list
     * @param string $key
     * @param mixed $value
     * @return mixed|null
     */
    public function propfind($key=null,$value=null){
        return $this->methodsData("propfind",$key,$value);
    }

    /**
     * the unlock data list
     * @param string $key
     * @param mixed $value
     * @return mixed|null
     */
    public function unlock($key=null,$value=null){
        return $this->methodsData("unlock",$key,$value);
    }

    /**
     * the lock data list
     * @param string $key
     * @param mixed $value
     * @return mixed|null
     */
    public function lock($key=null,$value=null){
        return $this->methodsData("lock",$key,$value);
    }

    /**
     * the purge data list
     * @param string $key
     * @param mixed $value
     * @return mixed|null
     */
    public function purge($key=null,$value=null){
        return $this->methodsData("purge",$key,$value);
    }

    /**
     * the unlink data list
     * @param string $key
     * @param mixed $value
     * @return mixed|null
     */
    public function unlink($key=null,$value=null){
        return $this->methodsData("unlink",$key,$value);
    }

    /**
     * the link data list
     * @param string $key
     * @param mixed $value
     * @return mixed|null
     */
    public function link($key=null,$value=null){
        return $this->methodsData("link",$key,$value);
    }

    /**
     * the options data list
     * @param string $key
     * @param mixed $value
     * @return mixed|null
     */
    public function options($key=null,$value=null){
        return $this->methodsData("options",$key,$value);
    }

    /**
     * the copy data list
     * @param string $key
     * @param mixed $value
     * @return mixed|null
     */
    public function copy($key=null,$value=null){
        return $this->methodsData("copy",$key,$value);
    }

    /**
     * the patch data list
     * @param string $key
     * @param mixed $value
     * @return mixed|null
     */
    public function patch($key=null,$value=null){
        return $this->methodsData("patch",$key,$value);
    }


    /**
     * the delete data list
     * @param string $key
     * @param mixed $value
     * @return mixed|null
     */
    public function delete($key=null,$value=null){
        return $this->methodsData("delete",$key,$value);
    }


    /**
     * the put data list
     * @param string $key
     * @param mixed $value
     * @return mixed|null
     */
    public function put($key=null,$value=null){
        return $this->methodsData("put",$key,$value);
    }

    /**
     * the GET data list
     * @param string $key
     * @param mixed $value
     * @return mixed|null
     */
    public function get($key=null,$value=null){
        return $this->methodsData("get",$key,$value);
    }

    /**
     * the POST data list
     * @param string $key
     * @param mixed $value
     * @return mixed|null
     */
    public function post($key=null,$value=null){
        return $this->methodsData("post",$key,$value);
    }

    /**
     * the FILE data list
     * @param string $key
     * @param mixed $value
     * @return mixed|null
     */
    public function file($key=null,$value=null){
        return $this->methodsData("file",$key,$value);
    }

    /**
     *  get a header data 
     * @param  string $name
     * @return string|null
     */
    public function header($name = null){
        return Route::headers($name);
    } 

    /**
     * the url param data list
     * @param string $key
     * @param mixed $value
     * @return mixed|null
     * @throws RouteHandleException
     */
    public function param($key,$value=null){
        if( !is_string($key) ){
            throw new RouteHandleException("arguments are not in correct format");
        }

        $tmp = &$this->__urlDate;
        if( isset( $tmp[$key] ) ){
            if( $value !== null ){
                $this->__urlDate[$key] = $value;
            }
            return $this->__urlDate[$key];
        }
        return null;
    }
}