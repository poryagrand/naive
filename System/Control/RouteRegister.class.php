<?php
namespace System\Controller;
use System\Controller\TemplateEngine\River;
use System\Communicate\Debug\Console;
use System\Security\Crypt;
use Carbon\Traits\Rounding;

class RouteRegisterShareData{
    protected $__refRoute;
    protected $__data;

    function __construct(&$ref,$data){
        $this->__refRoute = &$ref;
        $this->__data = $data;
    }

    function &route(){
        return $this->__refRoute;
    }

    function data($value=null){
        if( $value !== null ){
            $this->__data = $value;
        }
        return $this->__data;
    }
}

/**
 * controlling and managing registering a route in system
 * @property array $__route
 * @property array $__middlewares
 * @property callable $__handler
 * @property array $__methods
 * @property array $__onError
 */
class RouteRegister{
    protected $__route;
    protected $__file;
    protected $__middlewares;
    protected $__handler;
    protected $__methods;
    protected $__onError;
    protected $__outputFilter;

    protected $__minParams;
    protected $__maxParams;
    protected $__CSRF_name;
    protected $__isActive;

    protected $__req;
    protected $__res;
    protected $__river;
    protected $__middles = [];

    static protected $__shareFunctions = [];

    function __construct($detailsRoute,$filename=null,$init=null)
    {
        if( !is_array($detailsRoute) || ($filename !== null && !is_string($filename) ) ){
            throw  new RouteHandleException("Route arguments are not correct");
        }

        $this->__route = $detailsRoute;
        $this->__minParams = count($detailsRoute);
        $this->__maxParams = $this->__minParams;
        foreach( $detailsRoute as &$val ){
            if( !is_string($val)  ){
                if( $val["optional"] ){
                    $this->__minParams--;
                }
                if( $this->__maxParams !== PHP_INT_MAX ){
                    if( $val["count"] > 1 ){
                        $this->__maxParams++;
                    }
                    else if( $val["count"] <= 0 ){
                        $this->__maxParams = PHP_INT_MAX;
                    }
                }
                else{
                    throw new RouteHandleException("no params permitted to be exist after unlimited count param");
                }
            }
        }
        $this->__middlewares = ($init!==null && isset($init["middlewares"])) ? $init["middlewares"] : array();
        $this->__handler = function(){};
        $this->__file = $filename;
        $this->__methods = array(
            "get"=>array()
        );

        if( $init!==null && isset($init["get"]) ){
            $this->__methods["get"] = $init["get"];
        }

        if( $init!==null && isset($init["post"]) ){
            $this->__methods["post"] = $init["post"];
        }

        if( $init!==null && isset($init["file"]) ){
            $this->__methods["file"] = $init["file"];
        }

        if( $init!==null && isset($init["error"]) ){
            $this->onError(Route::MiddleWare|Route::MethodParams|Route::UrlParams|Route::Others|Route::Method,$init["error"]);
        }

        if( $init!==null && isset($init["output"]) ){
            $this->__outputFilter = $init["output"];
        }

        if( $init !== null && isset($init["attaches"]) ){
            foreach( $init["attaches"] as $key=>$val ){
                $this->attach($key,$val);
            }
        }
        

        $this->__CSRF_name = Crypt::hash( $this->id() );
        $this->__isActive = false;
    }


    /**
     * attach  any data in share space to access via other parts of application
     * @param string $name
     * @param mixed $data
     * @return RouteRegister
     */
    public function attach($name,$data){
        if( is_string($name) ){
            $temp = &self::$__shareFunctions;
            $id = $this->id();
            if( !isset($temp[$name]) || !is_array($temp[$name]) ){
                self::$__shareFunctions[$name] = [];
            }

            self::$__shareFunctions[$name][$id] = new RouteRegisterShareData($this,$data);
        }
        return $this;
    }

    /**
     * returns the attache data
     * @param string $name
     * @return null|RouteRegisterShareData
     */
    public function getAttach($name){
        return self::getAttaches($name,$this->id());
    }

    /**
     * returns the 'id' attache data
     * @param string $name
     * @param string $id
     * @return null|RouteRegisterShareData
     */
    static public function getAttaches($name,$id=null){

        
        if( $id === null ){
            $id = Route::currentID();
        }
        
        if( is_string($name) ){
            $temp = &self::$__shareFunctions;
            
            if( !isset($temp[$name]) || !is_array($temp[$name]) ){
                return null;
            }
            if( !isset($temp[$name][$id]) || !is_a($temp[$name][$id],RouteRegisterShareData::class) ){
                return null;
            }
            
            return self::$__shareFunctions[$name][$id];
        }
        return null;
    }

    /**
     * returns the list of attaches data
     * @param string $name
     * @return array
     */
    static public function getAttachesAll($name,$from){
        if( is_string($name) ){
            $temp = &self::$__shareFunctions;
            if( !isset($temp[$name]) || !is_array($temp[$name]) ){
                return [];
            }
            return self::$__shareFunctions[$name];
        }
        return [];
    }

    /**
     * return the path of view file of current controller
     * @return string
     */
    public function getViewPath(){
        return Route::path("view: ".$this->__file.".river.php");
    }

    /**
     * checks if current route accepts the method or not
     * @param string $method
     * @return bool
     */
    public function accept($method){
        $tmet = $this->__methods;
        if( isset( $tmet[strtolower($method)] ) ){
            return true;
        }
        return false;
    }

    /**
     * retrieve methods data list
     * @return array
     */
    public function getMethods(){
        return $this->__methods;
    }

    /**
     * retrieve route data list
     * @return array
     */
    public function getRoute(){
        return $this->__route;
    }

    /**
     * return the access id of route
     * @return array
     */
    public function id(){
        $id = [];
        $i = 1;
        foreach( $this->__route as $key=>$val ){
            if( is_string($val) ){
                $id[] = $val;
            }
            else{
                $id[] = "arg".$i;
            }
            $i++;
        }
        return implode(".",$id);
    }

    /**
     * return the current request instance
     * @return Request
     */
    public function req(){
        return $this->__req;
    }

    /**
     * return the current response instance
     * @return Response
     */
    public function res(){
        return $this->__res;
    }

    /**
     * return the current response instance
     * @return River
     */
    public function river(){
        return $this->__river;
    }

    /**
     * execute the route with parameter of url
     * @param string $method
     * @param array $params
     * @return bool
     * @throws RouteHandleException
     */
    public function call($method,$params=array()){

        
        if( !is_string($method) || !is_array($params) ){
            throw new RouteHandleException("arguments are not in correct format");
        }

        $__res = null;


        
        $methodParams = $this->validateMethod($method);
        if( $methodParams === false ){
            return false;
        }
        
        $req = new Request($method,$params,$methodParams,$this);
        $riv = new River($req);
        $res = new Response($method,$this,$riv);

        $this->__req = &$req;
        $this->__res = &$res;
        $this->__river = &$riv;

        try{            
            // check middlewares
            $this->__middles = [];
            foreach( $this->__middlewares as $middleware ){
                if( Route::hasMiddleWare($middleware) ){
                    $this->__middles[$middleware] = Route::callMiddleWare($middleware,$req,$res,$this->__middles);
                    if( $this->__middles[$middleware] === Route::NotValid ){
                        if( !$this->getAttach("no-csrf") || ( is_a($this->getAttach("no-csrf"),RouteRegisterShareData::class) && $this->getAttach("no-csrf")->data() !== true) ){
                            Crypt::generateCSRFToken($this->getCSRFName());
                        }
                        $this->__isActive = true;
                        return $this->fireError(Route::MiddleWare,$req,$res,$this->__middles);
                    }
                }
                else if( is_array($middleware) && count($middleware) >= 1 && Route::hasMiddleWare($middleware[0]) ){
                    $arg__ = array_slice($middleware,1);
                    $this->__middles[$middleware[0]] = Route::callMiddleWare($middleware[0],$req,$res,$this->__middles,$arg__);
                    if( $this->__middles[$middleware[0]] === Route::NotValid ){
                        if( !$this->getAttach("no-csrf") || ( is_a($this->getAttach("no-csrf"),RouteRegisterShareData::class) && $this->getAttach("no-csrf")->data() !== true) ){
                            Crypt::generateCSRFToken($this->getCSRFName());
                        }
                        $this->__isActive = true;
                        return $this->fireError(Route::MiddleWare,$req,$res,$this->__middles);
                    }
                }
                else{
                    if( !$this->getAttach("no-csrf") ||  (is_a($this->getAttach("no-csrf"),RouteRegisterShareData::class) && $this->getAttach("no-csrf")->data() !== true) ){
                        Crypt::generateCSRFToken($this->getCSRFName());
                    }
                    $this->__isActive = true;
                    return $this->fireError(Route::MiddleWare,$req,$res,$this->__middles);
                }
            }

            if( !$this->getAttach("no-csrf") || (is_a($this->getAttach("no-csrf"),RouteRegisterShareData::class) && $this->getAttach("no-csrf")->data() !== true) ){
                Crypt::generateCSRFToken($this->getCSRFName());
            }
            
            Route::fireEvent( "before" ,$this->id() , $this );

            $response = call_user_func($this->__handler,$req,$res,$this->__middles);

            
            
            if( $response === Route::NotValid ){
                return false;
            }

            if( is_callable($this->__outputFilter) ){
                $response = call_user_func($this->__outputFilter,$req,$res,$this->__middles,$response);
            }
            
            if( $response instanceof Response || is_callable($this->__outputFilter) ){
                $this->__isActive = true;
                return $response;
            }
            else{
                $this->__isActive = true;
                return true;
            }
        }
        catch(\Exception $e){
            Route::routeError();
            Console::error($e->getMessage(),"File: ".$e->getFile(),"Line: ".$e->getLine(),"\n Trace: ".$e->getTraceAsString());
            $__res = $this->fireError(Route::Others,$req,$res,$e);
        }

        
    }

    /**
     * checks if current route is active or not
     * @return bool
     */
    public function isActive(){
        return $this->__isActive;
    }

    public function middle($name){
        $tmp = &$this->__middles;
        if( isset($tmp[$name]) ){
            return $tmp[$name];
        }
        return null;
    }

    /**
     * get csrf name
     * @return string
     */
    public function getCSRFName(){
        if( is_a($this->getAttach("csrf-name"),RouteRegisterShareData::class) ){
            $this->__CSRF_name = Crypt::hash( $this->getAttach("csrf-name")->data() );
        }
        return $this->__CSRF_name;
    }

    /**
     * validate route details data
     * @param array $params
     * @return bool|array
     */
    public function validateRoute($url){
        
        if( !is_string($url) ){
            return false;
        }

        $params = $this->extractParams($url);
        
        if( $params === false ){
            return false;
        }

        $validatedUrl = [];
        
        foreach( $params as $val ){
            if( is_array($val) ){

                if( isset($val["filter"]) && Route::hasFilter($val["filter"]) ){
                    $res = Route::callFilter($val["filter"],$val["value"]);// (param value , is for check)
                    if( $res === Route::NotValid ){
                        return $this->fireError(Route::UrlParams);
                    }
                    else if( $res !== null ){
                        $validatedUrl[$val["name"]] = $res;
                    }
                }
                else{
                    return false;
                }
            }
        }
        
        return $validatedUrl;
    }

    /**
     * extract params from url
     * @param string $url
     * @return array|false
     */
    public function extractParams($url){
        $url = explode("/",trim(($url),"/"));
        $cnt = count($url);
        $usedCount = 0;
        if( $cnt >= $this->__minParams && $cnt <= $this->__maxParams ){
            $paramList = [];
            $pcount = count($this->__route);
            for( $key=0;$key<$pcount;$key++ ){
                $param = &$this->__route[$key];

                if( is_string( $param ) ){
                    if( $param != $url[$key] ){
                        return false;
                    }
                    $usedCount++;
                }
                else if( is_array( $param ) && isset($param["name"]) ){
                    if( isset($url[$key]) ){
                        $multi = $param["count"];
                        if( $multi <= 0 ){
                            $multi = $cnt-1;
                        }
                        else{
                            $multi = $key+$multi-1;
                        }
                        $newValue = [];
                        for($j=$key;$j<=$multi;$j++){
                            if( !isset($url[$j]) ){
                                return false;
                            }
                            $newValue[] =  $url[$j];
                            $usedCount++;
                        }

                        $paramExternal = $param;
                        $paramExternal["value"] = implode("/",$newValue);

                        $paramList[] = $paramExternal; 
                    }
                    else if( !$param["optional"] ){
                        return false;
                    }
                    else{
                        break;
                    }
                }
                else{
                    return false;
                }
            }

            if( $usedCount != count($url) ){
                return false;
            }

            return $paramList;
        }
        return false;
    }

    /**
     * check if the url is matched with this route or not
     * @param string $url
     * @return bool
     */
    public function isUrlAcceptable($url){
        if( $this->extractParams($url) === false ){
            return false;
        }
        return true;
    }

    /**
     * validate route details data
     * @param array $params
     * @return bool
     */
    public function validateMethod($reqMethod){
        $output = array();
        foreach( $this->__methods as $method=>&$list ){
            if( $method == "get" || $method == "post" || $method == "file" ){
                $param = ($method == "get"?$_GET:($method == "post"?$_POST:$_FILES));
                $output[$method] = array();
                foreach( $list as $key=>&$val ){
                    $output[$method][$key] = isset( $param[$key] )?$param[$key]:null;
                    if( Route::hasFilter($val) ){
                        $isArray = true;
                        if( !is_array($output[$method][$key]) || !isset($output[$method][$key][0]) ){
                            $output[$method][$key] = [$output[$method][$key]];
                            $isArray = false;
                        }
                        foreach($output[$method][$key] as $pkey=>$pval){
                            $res = Route::callFilter($val,$pval);
                            if( $res === Route::NotValid && ($reqMethod == $method || $method=="file") ){
                                $this->fireError(Route::MethodParams,$method,$key,$pval);
                                return false;
                            }
                            else{
                                $output[$method][$key][$pkey] = $res;
                            }
                        }
                        if( !$isArray ){
                            $paramCount = count($output[$method][$key]);
                            if( $paramCount > 0 && $paramCount <= 1 ){
                                $output[$method][$key] = $output[$method][$key][0];
                            }
                        }
                        
                    }
                    else{
                        return false;
                    }
                }
            }
        }
        return $output;
    }

    /**
     * call error
     * @param int $type
     * @param string $message
     * @return void
     */
    public function fireError($type,...$args){
        Route::routeError();
        $er = &$this->__onError;
        if( $args == null ){
            $args = array();
        }
        if( isset($er[$type]) && is_callable($er[$type]) ){
            $argList = array($type);
            $argList = array_merge($argList,$args);
            call_user_func_array($er[$type],$argList);
            return true;
        }
        return false;
    }


    /**
     * parse route and replace params in route
     * @param array $params
     * @return string
     */
    public function path($params=array()){
        $path = "";
        foreach( $this->__route as $val ){
            if( is_string($val) ){
                $path .= $val . "/";
            }
            else if( is_array($val) && isset($val["name"]) && isset($params[$val["name"]]) ){
                $path .= $params[$val["name"]] . "/";
            }
            else{
                throw new RouteHandleException("path param not found or the route details is not in correct format.");
            }
        }
        return rtrim($path ,"/");
    }

    /**
     * checks if the id is same as the route id
     * @param string $id
     * @return bool
     */
    public function is($id){
        $id = explode(".",$id);
        if( count($this->__route) >= $id ){
            return false;
        }
        foreach( $this->__route as $key=>$val ){
            if( is_string($val) && $val != $id[$key] && $id[$key] != "*" ){
                return false;
            }
            else if( !is_string($val) && $id[$key]!=("arg".($key+1)) && $id[$key] != "*" ){
                return false;
            }
            else if( $id[$key] == "*" ){
                return true;
            }
        }
        return true;
    }

    /**
     * set get paramteres validations
     * @param array $params
     * @return RouteRegister
     * @throws RouteHandleException
     */
    public function get( $params ){
        if( !is_array($params) ){
            throw new RouteHandleException("GET method params must be an assoc array!");
        }
        $tmp = &$this->__methods;
        if( !isset($tmp["get"]) ){
            $this->__methods["get"] = [];
        }
        $this->__methods["get"] = $this->__methods["get"] + $params;
        return $this;
    }

    /**
     * set post paramteres validations
     * @param array $params
     * @return RouteRegister
     * @throws RouteHandleException
     */
    public function post( $params ){
        if( !is_array($params) ){
            throw new RouteHandleException("POST method params must be an assoc array!");
        }
        $tmp = &$this->__methods;
        if( !isset($tmp["post"]) ){
            $this->__methods["post"] = [];
        }
        $this->__methods["post"] = $this->__methods["post"] + $params;
        return $this;
    }

    /**
     * set post paramteres validations
     * @param array $params
     * @return RouteRegister
     * @throws RouteHandleException
     */
    public function file( $params ){
        if( !is_array($params) ){
            throw new RouteHandleException("FILE params must be an assoc array!");
        }
        $tmp = &$this->__methods;
        if( !isset($tmp["file"]) ){
            $this->__methods["file"] = [];
        }
        $this->__methods["file"] = $this->__methods["file"] + $params;
        return $this;
    }

    /**
     * insert delete method
     * @return RouteRegister
     */
    public function delete(){
        $this->__methods["delete"] = array();
        return $this;
    }

    /**
     * insert put method
     * @return RouteRegister
     */
    public function put(){
        $this->__methods["put"] = array();
        return $this;
    }

    /**
     * add middleware between to do checks on request
     * @param array|string $midlware
     * @return RouteRegister
     * @throws RouteHandleException
     */
    public function middleWare($midlware){
        if( is_array($midlware) ){
            $this->__middlewares = array_merge($this->__middlewares,$midlware);
        }
        else if( is_string($midlware) ){
            $this->__middlewares[] = $midlware;
        }
        else{
            throw new RouteHandleException("middle ware must be a list on names or just be one name");
        }
        return $this;
    }

    /**
     * set handller to handle request
     * @param callable $callback
     * @return RouteRegister
     * @throws RouteHandleException
     */
    public function handle($callback){
        if( !is_callable($callback) ){
            throw new RouteHandleException("handller must be a function");
        }
        $this->__handler = $callback;
        return $this;
    }

    /**
     * set handller to handle request if the handller has error
     * @param callable $callback
     * @return RouteRegister
     * @throws RouteHandleException
     */
    public function onError($type,$callback){
        if( !is_callable($callback) ){
            throw new RouteHandleException("error handller must be a function");
        }
        $tmp = &$this->__onError;
        if( $type & Route::UrlParams ){
            $tmp[Route::UrlParams] = $callback;
        }
        if( $type & Route::MiddleWare ){
            $tmp[Route::MiddleWare] = $callback;
        }
        if( $type & Route::MethodParams ){
            $tmp[Route::MethodParams] = $callback;
        }
        if( $type & Route::Others ){
            $tmp[Route::Others] = $callback;
        }
        return $this;
    }
}

