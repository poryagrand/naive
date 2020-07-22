<?php

namespace System\Database;

use System\Communicate\Debug\Console;
use System\Database\Model\Match;
use PDO;

class DatabaseException extends \PDOException
{
    // Default Exception class handles everything
}

class Gate
{
    private $sql;
    private $db;
    private $result;
    private $last_fetched;
    private $is_transAction;
    protected $dbName;


    // database attributes
    const ATTR_CASE = \PDO::ATTR_CASE; // Force column names to a specific case.
    // {
        const CASE_LOWER   = \PDO::CASE_LOWER; // Force column names to lower case.
        const CASE_NATURAL = \PDO::CASE_NATURAL; // Leave column names as returned by the database driver.
        const CASE_UPPER   = \PDO::CASE_UPPER; // Force column names to upper case.
    // }
    
    const ATTR_ERRMODE = \PDO::ATTR_ERRMODE; // Error reporting.
    // {
        const ERRMODE_SILENT    = \PDO::ERRMODE_SILENT; // Just set error codes.
        const ERRMODE_WARNING   = \PDO::ERRMODE_WARNING; // Raise E_WARNING.
        const ERRMODE_EXCEPTION = \PDO::ERRMODE_EXCEPTION; // Throw exceptions.
    // }

    const ATTR_ORACLE_NULLS = \PDO::ATTR_ORACLE_NULLS; // (available with all drivers, not just Oracle): Conversion of NULL and empty strings.
    // {
        const NULL_NATURAL      = \PDO::NULL_NATURAL; // No conversion.
        const NULL_EMPTY_STRING = \PDO::NULL_EMPTY_STRING; // Empty string is converted to NULL.
        const NULL_TO_STRING    = \PDO::NULL_TO_STRING; // NULL is converted to an empty string.
    // }

    const ATTR_STRINGIFY_FETCHES = \PDO::ATTR_STRINGIFY_FETCHES; // Convert numeric values to strings when fetching.
    // { true , false }

    const ATTR_STATEMENT_CLASS = \PDO::ATTR_STATEMENT_CLASS; // Set user-supplied statement class derived from \PDOStatement. Cannot be used with persistent \PDO instances. 
    // { array(string classname, array(mixed constructor_args)) }

    const ATTR_TIMEOUT = \PDO::ATTR_TIMEOUT; // Specifies the timeout duration in seconds. Not all drivers support this option, and its meaning may differ from driver to driver. For example, sqlite will wait for up to this time value before giving up on obtaining an writable lock, but other drivers may interpret this as a connect or a read timeout interva
    // { 1,2,3,... }

    const ATTR_AUTOCOMMIT = \PDO::ATTR_AUTOCOMMIT; // (available in OCI, Firebird and MySQL): Whether to autocommit every single statement.
    // { true , false }

    const ATTR_EMULATE_PREPARES  = \PDO::ATTR_EMULATE_PREPARES; // Enables or disables emulation of prepared statements. Some drivers do not support native prepared statements or have limited support for them. Use this setting to force \PDO to either always emulate prepared statements (if TRUE and emulated prepares are supported by the driver), or to try to use native prepared statements (if FALSE). It will always fall back to emulating the prepared statement if the driver cannot successfully prepare the current query
    // { true, false }

    const MYSQL_ATTR_USE_BUFFERED_QUERY = \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY; // (available in MySQL): Use buffered queries.
    // { true, false }

    const ATTR_DEFAULT_FETCH_MODE = \PDO::ATTR_DEFAULT_FETCH_MODE; // Set default fetch mode
    // {
        const FETCH_FUNC       = \PDO::FETCH_FUNC; // Returns the results of calling the specified function, using each row's columns as parameters in the call.
        const FETCH_GROUP      = \PDO::FETCH_GROUP; // make group | To return an associative array grouped by the values of a specified column, bitwise-OR \PDO::FETCH_COLUMN with \PDO::FETCH_GROUP.
        const FETCH_COLUMN     = \PDO::FETCH_COLUMN; // Returns the indicated 0-indexed column.
        const FETCH_ASSOC      = \PDO::FETCH_ASSOC; // returns an array indexed by column name as returned in your result set
        const FETCH_BOTH       = \PDO::FETCH_BOTH; // (default) returns an array indexed by both column name and 0-indexed column number as returned in your result set
        const FETCH_BOUND      = \PDO::FETCH_BOUND; // returns TRUE and assigns the values of the columns in your result set to the PHP variables to which they were bound with the \PDOStatement::bindColumn() method
        const FETCH_CLASS      = \PDO::FETCH_CLASS; // returns a new instance of the requested class, mapping the columns of the result set to named properties in the class, and calling the constructor afterwards, unless \PDO::FETCH_PROPS_LATE is also given. If fetch_style includes \PDO::FETCH_CLASSTYPE (e.g. \PDO::FETCH_CLASS | \PDO::FETCH_CLASSTYPE) then the name of the class is determined from a value of the first column.
        const FETCH_INTO       = \PDO::FETCH_INTO; // updates an existing instance of the requested class, mapping the columns of the result set to named properties in the class
        const FETCH_LAZY       = \PDO::FETCH_LAZY; // combines \PDO::FETCH_BOTH and \PDO::FETCH_OBJ, creating the object variable names as they are accessed
        const FETCH_NAMED      = \PDO::FETCH_NAMED; // returns an array with the same form as \PDO::FETCH_ASSOC, except that if there are multiple columns with the same name, the value referred to by that key will be an array of all the values in the row that had that column name
        const FETCH_NUM        = \PDO::FETCH_NUM; // returns an array indexed by column number as returned in your result set, starting at column 0
        const FETCH_OBJ        = \PDO::FETCH_OBJ; // returns an anonymous object with property names that correspond to the column names returned in your result set
        const FETCH_PROPS_LATE = \PDO::FETCH_PROPS_LATE; // when used with \PDO::FETCH_CLASS, the constructor of the class is called before the properties are assigned from the respective column values.
    // }

    /**
     * Database() constructor
     *
     * @param string $arguments
     * @throws DatabaseException
     */
    function __construct(...$arguments )
    {
        $PDOReflect = new \ReflectionClass("\PDO"); 
        $this->is_transAction = false;
        try{
            $this->db = $PDOReflect->newInstanceArgs($arguments===null?array():$arguments);
        }
        catch(DatabaseException $e){
            throw new DatabaseException($e->getMessage(), (int)$e->getCode());
        }

    }


    public function getDbName(){
        return $this->dbName;
    } 

    /**
     * set atrributes to PDO connection
     * 
     * @param int $key
     * @param mixed $val
     * @return bool
     * @throws \Exception
     */
    public function setAttribute($key,$val){
        if( !is_numeric($key) ){
            throw new \Exception("Arguments are not in correct format!");
        }

        return $this->db->setAttribute($key,$val);
    }

    /**
     * Helper for throwing exceptions
     *
     * @param $error
     * @throws DatabaseException
     */
    private function _error($error)
    {
        throw new DatabaseException($error);
    }
    /**
     * Turn an array into a where statement
     *
     * @param mixed $where
     * @param string $where_mode
     * @return Array
     * @throws Exception
     */
    public function process_where($where, $where_mode = null)
    {
        $query = '';
        $isNot = false;
        $valuesList = array();

        if (is_array($where)) {
            $num = 0;
            $where_count = count($where);
            foreach ($where as $k => $v) {
                if( $v instanceof Match ){
                    $querygen = $v->generateQuery();
                    if( $querygen !== null ){
                        $query .= ' ' . $querygen . ' ';
                    }
                }
                else if (!is_numeric($k) && is_array($v)) {
                    $w = array_keys($v);
                    if (reset($w) != 0) {
                        throw new \Exception('Can not handle associative arrays');
                    }
                    // add data list to values list to bind in params
                    $valuesList = array_merge($valuesList,array_values($v));

                    $tv = count($v)-1;

                    $questionmarks = str_repeat("?,", $tv < 0 ? 0 : $tv) . "?";
                    $query .= ((strpos($k,"`") === false && strpos($k,".") === false)?(" `" . $k . "`"):(" ".$k)).($isNot?"NOT":"")." IN (" . $questionmarks . ")";
                    $isNot = false;
                }
                else if( is_null($v) ){
                    $query .= ((strpos($k,"`") === false && strpos($k,".") === false)?(" `" . $k . "`"):(" ".$k)) . " is".($isNot?" NOT":"")." null";
                    $isNot = false;
                }
                else if (!is_numeric($k)) {
                    array_push($valuesList,$v);
                    $query .= ((strpos($k,"`") === false && strpos($k,".") === false)?(" `" . $k . "`"):(" ".$k)).($isNot?"<>":"=")." ?";
                    $isNot = false;
                } else {
                    if( is_array($v) ){
                        $inside = $this->process_where($v, $where_mode);
                        $query .= ' (' . $inside["query"] . ') ';
                        $valuesList = array_merge($valuesList,array_values($inside["values"]));
                        unset($inside);
                    }
                    else{
                        if( trim(strtolower($v)) == "not" ){
                            $isNot = true;
                        }
                        else{
                            $query .= ' ' . $v . ' ';
                        }
                    }
                }
                if(!is_null($where_mode)){
                    $num++;
                    if ( $num != $where_count ) {
                        $query .= ' ' . $where_mode;
                    }
                }
                
            }
        } else {
            $query .= ' ' . $where;
        }

        return array(
            "query"=>$query,
            "values"=>$valuesList
        );
    }
    /**
     * Perform a SELECT operation
     *
     * @param string $table
     * @param array $where
     * @param bool|int $limit
     * @param bool|string $order
     * @param string $where_mode
     * @param string $select_fields
     * @param array $fetchSetting
     * @param array $joins
     * @return Database
     * @throws DatabaseException|\Exception
     */
    public function select($table, $where = array(), $limit = false, $order = false, $where_mode = null, $select_fields = '*',$fetchSetting = array(),$joins=[],$having=[],$group=[],$distinct=false)
    {
        if( !is_string($table) || !is_array($where) || !is_array($fetchSetting) ){
            throw new \Exception("Arguments are not in correct format!");
        }

        $this->result = null;
        $this->sql = null;
        $params = [];
        if (is_array($select_fields)) {
            foreach( $select_fields as $key=>$val ){
                if( strpos($val,"`") === false && strpos($val,".") === false && strpos($val," ") === false ){
                    $select_fields[$key] = "`".$select_fields[$key]."`";
                }
            }
            $select_fields = implode(",",$select_fields);
        }
        $table =  '' . $this->dbName.".".$table . "";
        if( is_array($joins) ){
            foreach( $joins as $val ){
                if( isset($val["table"]) && isset($val["on"]) && isset($val["type"]) ){
                    $onQuery = $this->process_where($val["on"], $where_mode);
                    $table .= " " . $val["type"] . " " . $val["table"] . " on " . $onQuery["query"];
                    $params = array_merge($params,$onQuery["values"]);
                }
            }
        }
        $query = 'SELECT ' . ($distinct?" DISTINCT ":"") . $select_fields . ' FROM ' . $table;
        
        if (!empty($where)) {
            $whereQuery = $this->process_where($where, $where_mode);
            $params = array_merge($params,$whereQuery["values"]);
            $query .= ' WHERE ' . $whereQuery["query"];
            unset($whereQuery);
        }

        if( is_array($group) && !empty($group) ){
            $query = $query . " GROUP BY " . implode(",",$group) . " ";
        }

        if( is_array($having) && !empty($having) ){
            $havingQuery = $this->process_where($having, $where_mode);
            $params = array_merge($params,$havingQuery["values"]);
            $query .= ' HAVING ' . $havingQuery["query"];
            unset($havingQuery);
        }

        if ($order) {
            $query .= ' ORDER BY ' . $order;
        }
        if ($limit) {
            $query .= ' LIMIT ' . $limit;
        }

        if( !empty($joins) ){
            //var_dump($query);die();
        }

        return $this->query($query,$params,$fetchSetting);
    }


    // ================================
    // transactions and rollback data
    // function to act on the transaction to start and commit

    /**
     * check if current queries are inside a transaction or not
     * @return bool
     */
    public function isTransAction(){
        return !(!$this->is_transAction);
    }

    /**
     * start a transaction
     * @return bool
     */
    public function startTransAction(){
        if( !$this->isTransAction() ){
            try{
                $ans = $this->db->beginTransaction();
                if( !$ans ){
                    return false;
                }
                $this->is_transAction = true;
                return true;
            }
            catch(DatabaseException $e)
            {
                // roll back the transaction if something failed
                $this->db->rollBack();
                $this->_error($e->getMessage());
                return false;
            }
        }
        return false;
    }

    /**
     * commit started transaction to database
     * @return bool
     */
    public function commit(){
        if( $this->isTransAction() ){
            try{
                $this->db->commit();
                $this->is_transAction = false;
                return true;
            }
            catch(DatabaseException $e)
            {
                // roll back the transaction if something failed
                $this->db->rollBack();
                $this->_error($e->getMessage());
            }
        }
        return false;
    }

    /**
     * rollback failed transaction
     * @return bool
     * @throws DatabaseException
     */
    public function rollBack(){
        if( $this->isTransAction() ){
            try{
                $this->db->rollBack();
                $this->is_transAction = false;
                return true;
            }
            catch(DatabaseException $e)
            {
                $this->_error($e->getMessage());
            }
        }
        return false;
    }
    // ================================



    /**
     * Perform a query
     *
     * @param string $query
     * @param array $params
     * @return $this|Database
     * @throws DatabaseException|\Exception
     */
    public function query($query, $params = array(), $fetchSetting = array())
    {
        if( !is_string($query) || !is_array($params) || !is_array($fetchSetting) ){
            throw new \Exception("Arguments are not in correct format!");
        }
        $this->sql = $query;
        //Console::log( $query,$params);
        //Console::error($query);
        try{
            if( !empty($this->result) ){
                $this->result->closeCursor();
            }
            $this->result = $this->db->prepare($query);

            $this->exec($params);
            if( isset($fetchSetting["key"]) ){
                $this->result->setFetchMode($fetchSetting["key"],isset($fetchSetting["value"])?$fetchSetting["value"]:null);
            }
        }
        catch(DatabaseException $e){
            $this->_error($e->getMessage());
            $this->result = null;
            $this->sql = null;
            return $this;
        }
        return $this;
    }


    private function exec($params){
        if( $this->result !== null && is_array($params) ){
            foreach( $params as $key=>$val ){
                if( is_numeric($key) ){
                    $key = $key+1;
                }
                if( is_bool($val) ){
                    $val = $val ? 1 : 0;
                    $this->result->bindValue( $key,$val, PDO::PARAM_BOOL);
                    continue;
                }
                else if( is_string($val) ){
                    //$val = strtolower($val);
                }
                $this->result->bindValue( $key,$val );
            }
            $this->result->execute();
        }
    }

    /**
     * setting fetch mode of result 
     * @param array $arguments
     * @return bool|null
     */
    public function setFetchMode(...$args){
        if( $this->result !== null ){
            $fmode = array($this->result,"setFetchMode");
            return call_user_func_array($fmode,$args===null?array():$args);
        }
        return null;
    }

    /**
     * Get last executed query
     *
     * @return string|null
     */
    public function sql()
    {
        return $this->sql;
    }

    /**
     * fetch all data from requested query
     *
     * @param int $fetchStyle
     * @param mixed $fetchArg
     * @return array
     * @throws DatabaseException|\Exception
     */
    public function fetchAll($fetchStyle = 4, ...$fetchArg)
    {
        if( !is_numeric($fetchStyle) ){
            throw new \Exception("Arguments are not in correct format!");
        }
        $fech = array($this->result,"fetchAll");
        try{
            if( $fetchArg === null ){
                return call_user_func_array($fech,array($fetchStyle) );
            }
            return call_user_func_array($fech,array_merge(array($fetchStyle),$fetchArg) );
        }
        catch(DatabaseException $e){
            $this->_error($e->getMessage());
        }
    }

    /**
     * fetch all data from requested query
     *
     * @param int $fetchStyle
     * @param mixed $fetchArg
     * @return mixed
     * @throws DatabaseException|\Exception
     */
    public function fetch($fetchStyle = 4, ...$fetchArg)
    {
        if( !is_numeric($fetchStyle) ){
            throw new \Exception("Arguments are not in correct format!");
        }

        $fech = array($this->result,"fetch");
        try{
            if( $fetchArg === null ){
                $this->last_fetched = call_user_func_array($fech,array($fetchStyle) );
            }
            else{
                $this->last_fetched = call_user_func_array($fech,array_merge(array($fetchStyle),$fetchArg) );
            }
            return $this->last_fetched;
        }
        catch(DatabaseException $e){
            $this->last_fetched = false;
            $this->_error($e->getMessage());
        }
    }

    /**
     * return the last fetched row
     * @return mixed
     */
    public function fetched(){
        return $this->last_fetched;
    }

    /**
     * Returns a single column from the next row of a result set or FALSE if there are no more rows.
     * @param int $columnNumber
     * @return mixed
     * @throws DatabaseException
     */
    public function fetchColumn($columnNumber = 0)
    {
        try{
            $this->last_fetched = $this->result->fetchColumn($columnNumber);
            return $this->last_fetched;
        }
        catch(DatabaseException $e){
            $this->last_fetched = false;
            $this->_error($e->getMessage());
        }
    }

    /**
     * fetch rows as class object
     * @param string $className
     * @return object
     * @throws \Exception
     */
    public function fetchAsClass($className,$args=null)
    {
        if( $args === null ){
            $args = [];
        }
        if( !class_exists ($className) ){
            throw new \Exception("Arguments are not in correct format!");
        }

        $this->result->setFetchMode(self::FETCH_CLASS|self::FETCH_PROPS_LATE, $className,$args);
        return $this->result->fetch();
    }

    /**
     * fetch row as class object
     * @param string $className
     * @return array
     * @throws \Exception
     */
    public function fetchAllAsClass($className)
    {
        if( !class_exists ($className) ){
            throw new \Exception("Arguments are not in correct format!");
        }
        return $this->result->fetchAll(self::FETCH_CLASS,$className);
    }


    /**
     * fetch rows in function
     * @param string $funcName
     * @return array
     * @throws \Exception
     */
    public function fetchAllAsFunc($funcName)
    {
        if( !is_callable($funcName) ){
            throw new \Exception("Arguments are not in correct format!");
        }
        return $this->fetchAll(self::FETCH_FUNC,$funcName);
    }


    /**
     * Get the number of effected rows in update/delete/insert some db's select
     *
     * @return bool|int
     */
    public function rowCount(){
        if ($this->result) {
            return $this->result->rowCount();
        }else {
            return false;
        }
    }

    public function schema($table){
        try{
            if( $this->tableIsExist($table) ){
                $this->query("SHOW COLUMNS FROM `{$table}`");
                return $this->fetchAll(self::FETCH_GROUP|self::FETCH_ASSOC);
            }
            return array();
        }
        catch(DatabaseException $e){
            return array();
        }
    }

    /**
     * Execute a SELECT COUNT(*) query on a table
     *
     * @param string $table
     * @param array $where
     * @param bool $limit
     * @param bool $order
     * @param string $where_mode
     * @return mixed
     */
    public function count($table = null, $where = array(), $limit = false, $order = false, $where_mode = null, $joins=[],$having=[],$group=[])
    {
        if (!empty($table)) {
            $this->select($table, $where, $limit, $order, $where_mode, 'COUNT(*)',[],$joins,$having,$group);
        }

        return (int)$this->fetchColumn();
    }

    /**
     * Execute a SELECT [func]([*]) query on a table
     *
     * @param string $table
     * @param array $where
     * @param bool $limit
     * @param bool $order
     * @param string $where_mode
     * @return mixed
     */
    public function func($fn="COUNT(*)",$table = null, $where = array(), $limit = false, $order = false, $where_mode = null, $joins=[],$having=[],$group=[])
    {
        if (!empty($table)) {
            $this->select($table, $where, $limit, $order, $where_mode, $fn,[],$joins,$having,$group);
        }

        return (int)$this->fetchColumn();
    }

 
    /**
     * Check if a table with a specific name exists
     *
     * @param $name
     * @return bool
     */
    public function tableIsExist($name)
    {
        try{
            $results = $this->db->prepare("SHOW TABLES LIKE ?");
            $results->execute([$name]);
            if(!$results) {
                return false;
            }
            if($results->rowCount()>0){
                return true;
            }
        }
        catch(\Exception $e){
            $this->_error($e->getMessage());
        }
        return false;
    }

        /**
     * Check if a table with a specific name exists
     *
     * @param $name
     * @return bool
     */
    public function getTables($name)
    {
        try{
            $results = $this->db->prepare("SHOW TABLES LIKE ?");
            $results->execute([$name]);
            if(!$results) {
                return [];
            }
            if($results->rowCount()>0){
                return $results->fetchAll();
            }
        }
        catch(\Exception $e){
            $this->_error($e->getMessage());
        }
        return [];
    }

    /**
     * Insert ignore rows in a table
     *
     * @param string $table
     * @param array $fields
     * @param array ...$values
     * @return Database
     * @throws DatabaseException|\Exception
     */
    function insertIgnore($table,$fields, ...$values)
    {
        if( !is_string($table) || !is_array($fields) || $values === null ){
            throw new \Exception("Arguments are not in correct format!");
        }
        $fieldStr = implode("`,`",$fields);
        $fieldValues = str_repeat("? , ",count($fields)-1) . "?";
        $this->sql = "INSERT IGNORE INTO " .$table." (`$fieldStr`)  VALUES($fieldValues)";
        try{
            $this->result = $this->db->prepare($this->sql);
            foreach($values as $val){
                $this->exec($val);
            }
        }
        catch(DatabaseException $e){
            $this->result = null;
            $this->sql = null;
            $this->_error($e->getMessage());
        }
        return $this;
    }

    /**
     * Insert rows in a table
     *
     * @param string $table
     * @param array $fields
     * @param array ...$values
     * @return Database
     * @throws DatabaseException|\Exception
     */
    function insert($table,$fields, ...$values)
    {
        if( !is_string($table) || !is_array($fields) || $values === null ){
            throw new \Exception("Arguments are not in correct format!");
        }
        $fieldStr = implode('`,`',$fields);
        $fieldValues = str_repeat("? , ",count($fields)-1) . "?";
        $this->sql = "INSERT INTO ".$table." (`$fieldStr`)  VALUES($fieldValues)";
        try{
            $this->result = $this->db->prepare($this->sql);
            foreach($values as $val){
                $this->exec($val);
            }
        }
        catch(DatabaseException $e){
            $this->result = null;
            $this->sql = null;
            $this->_error($e->getMessage());
        }
        return $this;
    }

    /**
     * Execute an UPDATE statement
     *
     * @param $table
     * @param array $fields
     * @param array $where
     * @param string $where_mode
     * @return Database|bool|string
     * @throws DatabaseException
     */
    function update($table, $fields = array(), $where = array(),$where_mode = null)
    {
        if (empty($where)) {
            $this->_error('Where clause is empty for update method');
        }

        $query = 'UPDATE `' . $table . '` SET';
        $whereData = array(
            "query"=>"",
            "values"=>array()
        );
        if (is_array($fields)) {
            $nr = 0;
            foreach ($fields as $k => $v) {
                if (is_object($v) || is_array($v)) {
                    $v = serialize($v);
                }
                if( is_numeric($k) ){
                    $query .= " ".$v;
                }
                else if($v === null) {
                    $query .= ' `' . $k . "`=NULL";
                }
                else if( is_bool($v) ){
                    $query .= ' `' . $k . "`=" . ($v?1:0);
                }
                else if( is_numeric($v) && !is_string($v) ){
                    $query .= ' `' . $k . "`=" . self::escape($v);
                }
                else {
                    $query .= ' `' . $k . "`='" . self::escape($v) . "'";
                }
                $nr++;
                if ($nr != count($fields)) {
                    $query .= ',';
                }
            }
        } else {
            $query .= ' ' . $fields;
        }

        if (!empty($where)) {
            $whereData = $this->process_where($where,$where_mode);
            $query .= ' WHERE ' . $whereData["query"];
        }
        
        return $this->query($query,$whereData["values"],array());
    }


    /**
     * Execute a DROP statement
     *
     * @param $table
     * @param array $where
     * @param string $where_mode
     * @return Database|bool
     * @throws DatabaseException
     * @throws Exception
     */
    function drop($tables)
    {
        if( !is_array($tables) ){
            return $this->_error('drop tables must be in array form');
        }

        $whereData = array(
            "query"=>"",
            "values"=>array()
        );
        $query = 'DROP TABLE IF EXISTS '.(implode(",",$tables)).';';

        return $this->query($query,$whereData["values"],array());
    }


    /**
     * Execute a DELETE statement
     *
     * @param $table
     * @param array $where
     * @param string $where_mode
     * @return Database|bool
     * @throws DatabaseException
     * @throws Exception
     */
    function delete($table, $where = array(), $where_mode = null)
    {
        if (empty($where)) {
            $this->_error('Where clause is empty for delete method');
        }

        $whereData = array(
            "query"=>"",
            "values"=>array()
        );
        $query = 'DELETE FROM `' . $table . '`';
        if (!empty($where)) {
            $whereData = $this->process_where($where,$where_mode);
            $query .= ' WHERE ' . $whereData["query"];
        }

        return $this->query($query,$whereData["values"],array());
    }

    /**
     * Get the primary key of the last inserted row
     * call this function before commit on transactions
     * 
     * @param string $name (optional)
     * @return string
     */
    public function id($name=null)
    {
        return $this->db->lastInsertId($name);
    }

    static public function op($key,$op,$value){
        switch( gettype($value) ){
            case "string" : $value = "'".(self::escape($value))."'"; break;
            case "array"  : $value = "(".(implode(",",array_values($value))).")"; break;
            case "integer": $value = $value; break;
            case "double" : $value = $value; break;
            case "boolean": $value = $value ? "1" : "0"; break;
            default: return false;
        }

        return [ $key . " " . $op . " " . $value ];
    }

    /**
     * Escape a parameter
     *
     * @param $str
     * @return string
     */
    static public function escape($str)
    {
        return preg_replace('~[\x00\x0A\x0D\x1A\x22\x27\x5C]~u', '\\\$0', self::clean($str));
    }

    /**
     * Get the last error message
     *
     * @return string
     */
    public function error()
    {
        return $this->db->errorInfo();
    }
    /**
     * Fix UTF-8 encoding problems
     *
     * @param $str
     * @return string
     */
    static private function clean($str)
    {
        if (is_string($str)) {
            if (!mb_detect_encoding($str, 'UTF-8', TRUE)) {
                $str = utf8_encode($str);
            }
        }
        return $str;
    }

    /**
     * close database connection
     * @return void
     */
    public function close(){
        $this->db->close();
        $this->db = null;
    }

    public function isClose(){
        return empty($this->db);
    }

    /**
     * Check if a variable is serialized
     *
     * @param mixed $data
     * @param null $result
     * @return bool
     */
    public function is_serialized($data, &$result = null)
    {
        if (!is_string($data)) {
            return false;
        }
        $data = trim($data);
        if (empty($data)) {
            return false;
        }
        if ($data === 'b:0;') {
            $result = false;
            return true;
        }
        if ($data === 'b:1;') {
            $result = true;
            return true;
        }
        if ($data === 'N;') {
            $result = null;
            return true;
        }
        if (strlen($data) < 4) {
            return false;
        }
        if ($data[1] !== ':') {
            return false;
        }
        $lastc = substr($data, -1);
        if (';' !== $lastc && '}' !== $lastc) {
            return false;
        }
        $token = $data[0];
        switch ($token) {
            case 's' :
                if ('"' !== substr($data, -2, 1)) {
                    return false;
                }
                break;
            case 'a' :
            case 'O' :
                if (!preg_match("/^{$token}:[0-9]+:/s", $data)) {
                    return false;
                }
                break;
            case 'b' :
            case 'i' :
            case 'd' :
                if (!preg_match("/^{$token}:[0-9.E-]+;/", $data)) {
                    return false;
                }
        }
        try {
            if (($res = @unserialize($data)) !== false) {
                $result = $res;
                return true;
            }
            if (($res = @unserialize(utf8_encode($data))) !== false) {
                $result = $res;
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }
}