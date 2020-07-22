<?php

namespace System\Database\Model;

use System\Communicate\Debug\Console;
use System\Database\Gate;

/**
 * handle the models to connect to database and run commands on tables 
 * 
 * @property string $tableName
 * @property string $instance
 * @property string $server
 * @property array $columns
 * @property string $engine
 * @param string $charset
 * @param array $attributes
 * @param array $columnDetails
 */
class Base{

    const NoChange = "DO_NO_CHANGE_VALUE";

    /**
     * migrate all extendeds tables
     */
    public static function migrateAll(){
        foreach(get_declared_classes() as $class){
            if(is_subclass_of($class,"\System\Database\Model\Base")){
                $class::migrate();
            }
        }
    }

    /**
     * checks if the object is a instanceof a subclass of base or the base class itself
     * @param object $obj
     * @return bool
     */
    public static function is($obj){
        if( $obj instanceof Base || is_subclass_of($obj,Base::class) || $obj === Base::class ){
            return true;
        }
        return false;
    }

    public static function createConnection(){
        $servr = static::$server;
        if( empty($servr) ){
            return false;
        }

        if( is_callable($servr) ){
            $servr = call_user_func($servr);
        }
        else if(is_string($servr) && is_subclass_of($servr,Gate::class)){
            $servr = new $servr;
        }
        else if($servr !== null){
            $servr = $servr;
        }
        else{
            return false;
        }
        static::$server = $servr;
        return $servr;
    }

    /**
     * migration of tables to new sets
     * @throws \Exception
     */
    public static function migrate(){

        $servr = static::createConnection();

        if( !$servr ){
            return false;
        }

        try{
            static::onBeforeMigrate();
        }
        catch(\Exception $e){}
        
        $schema = static::schema();

        // if be empty , means the table is not exists
        // so it must be created
        if( empty($schema) ){

            $servr->create(
                static::getTableName(),
                static::$columns,
                static::$engine,
                static::$charset,
                static::$attributes,
                static::$columnDetails
            );
        }
        else{
            // if exists check the table general details 
            // change them if needed
            $alterModify = array();
            $alterAdd = array();
            $dropPrimaryKeys = false;
            
            foreach(static::$columns as $key=>$details){
                if( isset($schema[$key]) ){
                    if( isset($details["primary"]) && $details["primary"] ){
                        if( $schema[$key][0]["Key"] != "PRI" ){
                            $alterAdd[$key] = array(
                                "primary"=>true
                            );
                        }
                    }
                    else{
                        if( $schema[$key][0]["Key"] == "PRI" ){
                            $dropPrimaryKeys = true;
                        }
                    }

                    $alterModify[$key] = $details;
                    unset($schema[$key]);
                }
                else{
                    $alterAdd[$key] = $details;
                }
            }

            if( $dropPrimaryKeys ){
                $schema["PRIMARY KEY"] = ""; 
            }
            
            $schema = array_keys($schema);
            if( count($schema)>0 ){
                $servr->alterDrop( static::getTableName(), $schema );
            }

            if( count($alterModify) ){
                $servr->alterChnage( static::getTableName(), $alterModify );
            }
            
            if( count($alterAdd) ){
                $servr->alterAdd( static::getTableName(), $alterAdd );
            }



            
        }

        try{
            static::onAfterMigrate();
        }
        catch(\Exception $e){Console::error((string)$e);}

    }

    static public function getDbName(){
        $servr = static::createConnection();
        if( !$servr ){
            return "";
        }
        return $servr->getDbName();
    }

    // evaluate when a migration has fire
    // this funtion is used to do actions like alter table to add keys or indexes or etc...
    static protected function onBeforeMigrate(){}
    static protected function onAfterMigrate(){}

    
    static protected $tableName;
    static protected $instance;
    static protected $server;
    static protected $prefix;
    static protected $defaultWhere = array();

    static public function hasHost(){
        return !empty(static::$server);
    }

    static public function getHost(){
        if( is_callable(static::$server) ){
            return call_user_func(static::$server);
        }
        else if(is_string(static::$server) && is_subclass_of(static::$server,Gate::class)){
            return new static::$server();
        }
        return static::$server;
    }


    static public function setPreFix($pref){
        static::$prefix = $pref;
        return static::class;
    }

    static public function getPreFix(){
        return static::$prefix;
    }

    static public function getTableName(){
        if( static::$prefix ){
            return (static::$prefix) . (static::$tableName);
        }
        return static::$tableName;
    }
    /**
     * the column must be like this:
     * = array(
     *      "id" => array(
     *                  "attributes"=>[],
     *                  "type"=>"int",
     *                  "cast"=>"int",
     *                  "isNull"=>false,
     *                  "auto"=>true,
     *                  "default"=>null
     *              ),
     *      "date" => array(
     *                  "attributes"=>[],
     *                  "type"=>"datetime",
     *                  "cast"=>function($val,$onSave){ if(!$onSave){return new DateTime($val);}else{return $val->format('Y/m/d H:i:s');} },
     *                  "isNull"=>true,
     *                  "auto"=>false,
     *                  "default"=>"0/0/0 0:0:0"
     *              )
     * )
     */
    static protected $columns = array();
    static protected $engine = "";
    static protected $charset = "";
    static protected $attributes = [];
    static protected $columnDetails = [];

    static protected $filters = [];

    /**
     * add filter to use in table column
     * @param string $name
     * @param callable $callback
     * @return void
     * @throws \Eception
     */
    public static function filter($name,$callback){
        if( !is_string($name) ){
            throw new \Exception("Database filter name must be string");
        }
        if( !is_callable($callback) ){
            throw new \Exception("Database filter callback must be function");
        }
        static::$filters[$name] = $callback;
    }

    /**
     * check that there is filter or not
     * @param string $name
     * @return bool
     * @throws \Eception
     */
    public static function hasFilter($name){
        if( !is_string($name) ){
            throw new \Exception("Database filter name must be string");
        }
        $tmp = &static::$filters;
        if( isset($tmp[$name]) ){
            return true;
        }
        return false;
    }

    /**
     * call a filter
     * @param string $name
     * @param array $args
     * @return mixed|null
     * @throws \Eception
     */
    public static function callFilter($name,$args=[]){
        if( !is_string($name) ){
            throw new \Exception("Database filter name must be string");
        }
        if( !is_array($args) ){
            throw new \Exception("Database arguments name must be array");
        }

        if( static::hasFilter($name) ){
            return call_user_func_array(static::$filters[$name],$args);
        }
        return null;
    }

    /**
     * set server and use the class
     * @param mixed $serv
     * @return QueryExec
     */
    public static function host($serv){
        static::$server = $serv;
        return static::class;
    }

    public static function hasColumn($name){
        $tmp = &static::$columns;
        if( isset($tmp[$name]) ){
            return true;
        }
        return false;
    }

    /**
     * run sql query functions
     * @param string $name
     * @param array $arguments
     * @return QueryExec
     */
    public static function __callStatic($name, $arguments=[])
    {
        if( method_exists(self::class,"onBefore".ucwords(strtolower($name))) ){
            call_user_func_array(array(self::class,"onBefore".ucwords(strtolower($name))),$arguments);
        }
        $qe = new QueryExec(static::getTableName(),static::$instance,static::$server,static::$columns,static::$prefix,static::$defaultWhere);
        $ret = call_user_func_array(array($qe,$name),$arguments);
        if( method_exists(self::class,"onAfter".ucwords(strtolower($name))) ){
            $tmp = call_user_func(array(self::class,"onAfter".ucwords(strtolower($name))),$ret);
            if( $tmp !== null ){
                return $tmp;
            }
        }
        return $ret;
    }
}
