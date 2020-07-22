<?php

namespace System\Database\Model;

use System\Communicate\Debug\Console;
use System\Database\Gate;

/**
 * the fulltext search class to determine in query generator
 */
class Match{

    private $_cols = [];
    private $_type = "";
    private $_against = "";

    public function __construct($cols_){
        $this->_cols = $cols_;
    }

    public function generateQuery(){
        if( !empty($this->_cols) && trim($this->_type) !== "" && trim($this->_against) !== "" ){
            $colNames = implode(",",$this->_cols);
            return " Match($colNames) AGAINST ('{$this->_against}' {$this->_type}) ";
        }
        return null;
    }

    /**
     * set the against string
     * @param string $str
     * @return Match 
     */
    public function against($str){
        if( is_string($str) ){
            $this->_against = Gate::escape($str);
        }
        return $this;
    }

    /**
     * set the full text search type to natural language
     * @return Match 
     */
    public function inNL(){
        $this->_type = "IN NATURAL LANGUAGE MODE";
        return $this;
    }

    /**
     * set the full text search type to natural language with query expantion
     * @return Match 
     */
    public function inNLQE(){
        $this->_type = "IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION";
        return $this;
    }

    /**
     * set the full text search type to Boolean
     * @return Match 
     */
    public function inBOOL(){
        $this->_type = "IN BOOLEAN MODE";
        return $this;
    }

    /**
     * set the full text search type to query expansion
     * @return Match 
     */
    public function inQE(){
        $this->_type = "WITH QUERY EXPANSION";
        return $this;
    }

    /**
     * create instance with the cols
     * @param array $cols_
     * @return Match
     * @throws \Exception
     */
    static public function cols(...$cols_){
        if( $cols_ !== null ){
            $filteredCols = [];
            foreach( $cols_ as $col ){
                if( is_string($col) ){
                    $filteredCols[] = Gate::escape($col);
                }
            }
            return new Match($filteredCols);
        }
        throw new \Exception("Match cols are not mentioned!");
    }
}

/**
 * prepare commands to select and update , insert and delete queries
 * @property \System\Database\Gate $con 
 * @property string $tableName
 * @property callable $instance
 * @property array $columns
 * 
 */
class QueryExec{

    private $con;
    private $tableName;
    private $instance;
    private $columns;
    private $preFix = "";
    private $defaultWhere = array();

    public function getConnection(){
        return $this->con;
    }

    /**
     * initializer
     * @param string $table
     * @param string $ins
     * @param string $server
     * @param array $cols
     * @throws \Exception
     */
    function __construct($table,$ins,$server,$cols,$pfix="",$defaultWhere)
    {
        if( !is_string($table) || !is_string($ins) ||  !is_array($cols) ){
            throw new \Exception("Arguments are not in correct format!");
        }
        // initialize the properties
        try{
            if( is_callable($server) ){
                $this->con = call_user_func($server);
            }
            else if(is_string($server) && is_subclass_of($server,Gate::class)){
                $this->con = new $server();
            }
            else if($server !== null){
                $this->con = $server;
            }

            $this->tableName = $table;
            $this->instance = $ins;
            $this->columns = $cols;
            $this->preFix = $pfix;
            $this->defaultWhere = empty($defaultWhere)? [] : [$defaultWhere];
        }
        catch(\Exception $e){
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * retrieve the schema of the table
     * @return array
     */
    public function schema(){
        return $this->con->schema($this->tableName);
    }

    /**
     * open hand query executer
     * @param string $query
     */
    public function query($query,$params=[]){
        if( !is_string($query) ){
            throw new \Exception("Argument is not in the correct format!");
        }

        $query = str_ireplace("<TABLE>",'`'.$this->tableName.'`',$query);
        return $this->con->query($query,$params);
    }

    /**
     * execute alter commands
     * @param array $queries
     */
    public function alter(...$queries){
        if( !is_array($queries) ){
            throw new \Exception("Argument is not in the correct format!");
        }

        $query = "ALTER TABLE `{$this->tableName}` ".(implode(",",$queries));
        return $this->con->query($query);
    }

    /**
     * specifying the range of data to act on them
     * @param array $conditions
     * @return QueryExecStatement
     * @throws \Exception
     */
    public function where($conditions){
        if( !is_array($conditions) ){
            throw new \Exception("Arguments are not in correct format! 'conditions' must be an array");
        }
        $cond = [];
        if( !empty($this->defaultWhere) && !empty($conditions) ){
            $cond = $this->defaultWhere;
            array_push($cond,"AND");
            array_push($cond,$conditions);
        }
        else if( empty($this->defaultWhere) ){
            $cond = $conditions;
        }
        else{
            $cond = $this->defaultWhere;
        }
        
        return new QueryExecStatement($this->con,$this->tableName,$this->instance,$this->columns ,$cond,$this->preFix);
    } 

    /**
     * specifying all data to act on them
     * @param array $conditions
     * @return QueryExecStatement
     */
    public function all(){
        $cond = $this->defaultWhere;
        return new QueryExecStatement($this->con,$this->tableName,$this->instance,$this->columns ,$cond,$this->preFix);
    } 

    /**
     * insert new row of data
     */
    public function create(){
        $ins = $this->instance;
        $obj = new $ins($this->tableName,$this->con,$this->columns,true);
        foreach($this->columns as $key=>$col){
            $obj->$key = isset($col["default"])?$col["default"]:"";
        }

        return $obj;
    }

    public function pip(){
        return new PipQueryExecuter($this->con,$this->tableName,$this->columns);
    }

    
    public function seed($count=10,$callback){
        if( $this->con->count($this->tableName) > 0 ){
            return false;
        }
        
        if( !is_callable($callback) ){
            return false;
        }
        if( !is_numeric($count) || $count <= 0 ){
            $count = 10;
        }

        $query = [];
        $values = [];
        foreach($this->columns as $key=>$col){
            if( !$col["auto"] ){
                $query[] = "`$key`";
            }
        }
        $query = "INSERT INTO `".($this->tableName)."` (".(implode(",",$query)).") VALUES ";

        for($i=0;$i<$count;$i++){
            $data = call_user_func($callback);
            if( is_array($data) ){
                $values[] = "('".implode("','",$data)."')";
            }
            else{
                return false;
            }
        }

        $query .= implode(",",$values);

        $this->con->query($query);

        return true;
    }
}

/**
 * run sets of update/insert/remove commands in once with transaction
 * @property \System\Database\Gate $con
 * @property string $tableName
 * @property array $columns
 */
class PipQueryExecuter{
    private $con;
    private $tableName;
    private $columns;
    private $TA = false;

    public function __construct(\System\Database\Gate $con, $tName,$cols)
    {
        if( !is_string($tName) || !is_array($cols) ){
            throw new \Exception("Arguments are not in correct format!");
        }

        $this->con = $con;
        $this->tableName = $tName;
        $this->columns = $cols;

        $this->TA = $this->con->startTransAction();
    }

    /**
     * checks if pip is ready to use or not
     * @return bool
     */
    public function can(){
        return !(!$this->TA);
    }


    /**
     * insert new data row
     * @param array $values
     * @return PipQueryExecuter
     */
    public function insert($values){
        if( $this->can() && is_array($values) ){
            $this->con->insert(
                $this->tableName,
                array_keys($values),
                array_values($values)
            );
        }
        return $this;
    }

    /**
     * insert ignore new data row
     * @param array $values
     * @return PipQueryExecuter
     */
    public function insertIgnore($values){
        if( $this->can() && is_array($values) ){
            $this->con->insertIgnore(
                $this->tableName,
                array_keys($values),
                array_values($values)
            );
        }
        return $this;
    }

    /**
     * update data row
     * @param array $where
     * @param array $values
     * @return PipQueryExecuter
     */
    public function update($values,$where=[]){
        if( $this->can() && is_array($values) && is_array($where) ){
            $this->con->update(
                $this->tableName,
                $values,
                $where
            );
        }
        return $this;
    }

    /**
     * remove data row
     * @param array $where
     * @return PipQueryExecuter
     */
    public function delete($where){
        if( $this->can() && is_array($where) ){
            $this->con->delete(
                $this->tableName,
                $where
            );
        }
        return $this;
    }

    public function exec(){
        if( $this->can() ){
            return $this->con->commit();
        }
        return false;
    }
}

/**
 * query connector to access data with simple functional command
 * @property \System\Database\Gate $con
 * @property string $tableName
 * @property callable $instance
 * @property array $columns
 * @property array $_where
 * @property int $_limit
 * @property array $_order
 */
class QueryExecStatement{

    private $con;
    private $tableName;
    private $instance;
    private $columns;
    private $_where = array();
    private $_limit = -1;
    private $_cols = [];
    private $_order = array();
    private $_group = [];
    private $_having = [];
    private $_joins = [];
    private $_preFix = "";
    private $_asName = "";
    private $_distinct = false;

    public function distinct($is=true){
        $this->_distinct = !(!$is);
        return $this;
    }

    public function getConnection(){
        return $this->con;
    }

    /**
     * @property \System\Database\Gate $con
     * @property string $tName
     * @property callable $instance
     * @property array $cols
     * @property array $conditions
     * @throws \Exception
     */
    function __construct(\System\Database\Gate $con, $tName, $instance,$cols, $conditions,$prefix)
    {
        if( !is_string($tName) || !is_string($instance) || !is_array($conditions) ){
            throw new \Exception("Arguments are not in correct format!");
        }

        $this->con = $con;
        $this->tableName = $tName;
        $this->instance = $instance;

        $this->columns = $cols;
        $this->_preFix = $prefix;
        $this->_asName = "";

        $conditions = json_encode($conditions);
        $conditions = preg_replace('/\`(.+?)\`/',"`".$this->_preFix."$1`",$conditions);
        $conditions = str_replace('<TABLE>','`'. $this->tableName.'`', $conditions);
        $conditions = str_replace('<PF>', $this->_preFix, $conditions);
        $this->_where = json_decode($conditions,true);
    }

    public function cols(...$cols){
        if( is_array($cols) ){

            $tt = json_encode($cols);
            $tt = preg_replace('/\`(.+?)\`/',"`".$this->_preFix."$1`",$tt);
            $tt = str_replace('<TABLE>', '`'.$this->tableName.'`', $tt);
            $tt = str_replace('<PF>', $this->_preFix, $tt);
            $cols = json_decode($tt,true);

            foreach( $cols as $col ){
                if( is_string($col) ){
                    $this->_cols[] = $col;
                }
            }
        }
        return $this;
    }

    public function join($type,$table,$on,$as=null){
        if( (!is_string($table) && is_subclass_of($table,\System\Database\Model\Base::class)) || !is_array($on) || !is_string($type) ){
            return $this;
        }

        if( is_subclass_of($table,\System\Database\Model\Base::class) ){
            $table = $table::getDbName() . "." . $table::getTableName() . (is_string($as) ? (" as ".$as) : "");
        }

        $table = str_replace("<PF>",$this->_preFix,$table);

        $tt = json_encode($on);
        $tt = preg_replace('/\`(.+?)\`/',"`".$this->_preFix."$1`",$tt);
        $tt = str_replace('<TABLE>', '`'.$this->tableName.'`', $tt);
        $tt = str_replace('<T>', '`'.$table.'`', $tt);
        $tt = str_replace('<PF>', $this->_preFix, $tt);
        $on = json_decode($tt,true);

        array_push($this->_joins,[
            "on"=>$on,
            "table"=>$table,
            "type"=>$type
        ]);
        return $this;
    }

    public function innerJoin($table,$on,$as=null){
        return $this->join("inner join",$table,$on,$as);
    }

    public function leftJoin($table,$on,$as=null){
        return $this->join("left join",$table,$on,$as);
    }

    public function rightJoin($table,$on,$as=null){
        return $this->join("right join",$table,$on,$as);
    }   

    

    /**
     * sets limit statement to query
     * @param int $limitNum
     * @return QueryExecStatement
     */
    public function limit($from,$count=-1){
        if( $from >= 0 ){
            $this->_limit = $from . ( $count > 0  ?( "," . $count) : "" );
        }
        else{
            $this->_limit = -1;
        }
        return $this;
    }

    public function as($name){
        if( is_string($name) && !preg_match('/[ \s\v\t\r\n\@\!\#\~\$\%\^\&\*\(\)\_\+\=\|\/\.\<\>\,]+/',$name) ){
            $this->_asName = " as ".$name;
        }
        return $this;
    }

    /**
     * sets retrieving data order
     * @param string $by
     * @param string $dir
     * @return QueryExecStatement
     * @throws \Exception
     */
    public function order($by, $dir = "desc"){
        if( !is_string($by) ){
            throw new \Exception("Arguments are not in correct format!");
        }

        $tt = json_encode($by);
        $tt = preg_replace('/\`(.+?)\`/',"`".$this->_preFix."$1`",$tt);
        $tt = str_replace('<TABLE>','`'. $this->tableName.'`', $tt);
        $tt = str_replace('<PF>', $this->_preFix, $tt);
        $by = json_decode($tt,true);

        $this->_order[] = $by . " " . ($dir == "desc"?"desc":"asc");
        return $this;
    }

    /**
     * sets retrieving data grouping
     * @param string $by
     * @param string $dir
     * @return QueryExecStatement
     * @throws \Exception
     */
    public function group(){
        $args = func_get_args();
        if( !is_array($args) ){
            throw new \Exception("Arguments are not in correct format!");
        }

        $tt = json_encode($args);
        $tt = preg_replace('/\`(.+?)\`/',"`".$this->_preFix."$1`",$tt);
        $tt = str_replace('<TABLE>','`'. $this->tableName.'`', $tt);
        $tt = str_replace('<PF>', $this->_preFix, $tt);

        $this->_group = array_merge($this->_group , json_decode($tt,true));

        return $this;
    }

    /**
     * sets condition on gathering data (having)
     * @param array $condition
     * @return QueryExecStatement
     * @throws \Exception
     */
    public function having($condition){
        if( !is_array($condition) ){
            throw new \Exception("Arguments are not in correct format!");
        }

        if( !empty( $this->_having ) ){
            $this->_having[] = "AND";
        }

        $tt = json_encode($condition);
        $tt = preg_replace('/\`(.+?)\`/',"`".$this->_preFix."$1`",$tt);
        $tt = str_replace('<TABLE>','`'. $this->tableName.'`', $tt);
        $tt = str_replace('<PF>', $this->_preFix, $tt);
        $condition = json_decode($tt,true);

        $this->_having = array_merge($this->_having , $condition);

        return $this;
    }

    /**
     * sets condition on gathering data
     * @param array $condition
     * @return QueryExecStatement
     * @throws \Exception
     */
    public function where($condition){
        if( !is_array($condition) ){
            throw new \Exception("Arguments are not in correct format!");
        }

        
        if( !empty( $this->_where ) && strtoupper(end($this->_where)) !== "OR" && strtoupper(end($this->_where)) !== "AND" ){
            $this->_where[] = "AND";
        }

        $tt = json_encode($condition);
        $tt = preg_replace('/\`(.+?)\`/',"`".$this->_preFix."$1`",$tt);
        $tt = str_replace('<TABLE>','`'. $this->tableName.'`', $tt);
        $tt = str_replace('<PF>', $this->_preFix, $tt);
        $condition = json_decode($tt,true);

        $this->_where = array_merge($this->_where , $condition);

        return $this;
    }

    public function or(){
        
        if( !empty( $this->_where ) && strtoupper(end($this->_where)) !== "OR" && strtoupper(end($this->_where)) !== "AND" ){
            $this->_where[] = "OR";
        }
        return $this;
    }

    public function and(){
        
        if( !empty( $this->_where ) && strtoupper(end($this->_where)) !== "OR" && strtoupper(end($this->_where)) !== "AND" ){
            $this->_where[] = "AND";
        }
        return $this;
    }

    public function fullText($type,$search,...$cols){
        if( $cols !== null ){

            if( is_array($cols) ){
                $tt = json_encode($cols);
                $tt = preg_replace('/\`(.+?)\`/',"`".$this->_preFix."$1`",$tt);
                $tt = str_replace('<TABLE>', '`'.$this->tableName.'`', $tt);
                $tt = str_replace('<PF>', $this->_preFix, $tt);
                $cols = json_decode($tt,true);
            }

            $search = preg_replace('/\`(.+?)\`/',"`".$this->_preFix."$1`",$search);
            $search = str_replace('<TABLE>', '`'.$this->tableName.'`', $search);
            $search = str_replace('<PF>', $this->_preFix, $search);



            $mtch = new Match($cols);
            $type = strtoupper($type);
            $mtch->against($search);
            if( method_exists($mtch,"in".$type) ){
                call_user_func(array($mtch,"in".$type));
                
                
                if( !empty( $this->_where ) && strtoupper(end($this->_where)) !== "OR" && strtoupper(end($this->_where)) !== "AND" ){
                    $this->_where[] = "AND";
                }

                $this->_where[] = $mtch->generateQuery();
            }
        }
        return $this;
    }

    /**
     * get count of filtered data
     * @return int
     * @throws System\Database\DatabaseException
     */
    public function count(){
        $c = $this->con->count(
            $this->tableName.$this->_asName,
            $this->_where,
            $this->_limit==-1?false:$this->_limit,
            false,
            null,
            $this->_joins
            ,$this->_having,$this->_group
        );

        return $c;
    }

    /**
     * get a func of filtered data
     * @return int
     * @throws System\Database\DatabaseException
     */
    public function func($fn){
        $c = $this->con->func(
            $fn,
            $this->tableName.$this->_asName,
            $this->_where,
            $this->_limit==-1?false:$this->_limit,
            false,
            null,
            $this->_joins
            ,$this->_having,$this->_group
        );

        return $c;
    }

    /**
     * calls select command to select data from database and return data as array of the class instance or one data if it is unique
     * @param int $elm (optional)
     * @return array
     * @throws System\Database\DatabaseException
     */
    public function select($elm=null){
        $order = implode(",",$this->_order);
        if( count($this->_cols) <= 0 ){
            foreach( $this->columns as $key=>$val ){
                $this->_cols[] = $this->tableName . ".`" . $key . "` as `" . $key."`";
            }
        }

        $list = $this->con->select(
            $this->tableName.$this->_asName,
            $this->_where,
            $this->_limit==-1?false:$this->_limit,
            empty($order)?false:($order),
            null,$this->_cols,[],$this->_joins
            ,$this->_having,$this->_group
        );

        $list = $list->fetchAll(
            \System\Database\Gate::FETCH_CLASS | \System\Database\Gate::FETCH_PROPS_LATE,
            $this->instance
            ,[$this->tableName,$this->con,$this->columns,false]
        );

        if( is_numeric($elm) && $elm >= 0 ){
            if( isset($list[$elm]) ){
                return $list[$elm];
            }
            else{
                return null;
            }
        }
        return $list;
    }

     /**
     * calls select command to select data from database and return data of the class instance on each fetch
     * @return callable
     * @throws System\Database\DatabaseException
     */
    public function row(){
        if( count($this->_cols) <= 0 ){
            foreach( $this->columns as $key=>$val ){
                $this->_cols[] = $this->tableName . ".`" . $key . "` as `" . $key."`";
            }
        }
        
        $this__ = &$this;
        $order = implode(",",$this->_order);
        $list = $this->con->select(
            $this->tableName.$this->_asName,
            $this->_where,
            $this->_limit==-1?false:$this->_limit,
            empty($order)?false:($order),
            null,$this->_cols,[],$this->_joins
            ,$this->_having,$this->_group
        );

        return function() use(&$list,&$this__){
            return $list->fetchAsClass(
                $this__->instance
                ,[$this__->tableName,$this__->con,$this__->columns,false]
            );
        };
    }

    /**
     * return one row with type of instance
     * @return <instance>
     */
    public function rowSelect(){
        $row = $this->row();
        $row = call_user_func($row);
        return $row;
    }


    /**
     * calls select command to select data from database and return data as array one data if it is unique
     * @return array
     * @throws System\Database\DatabaseException
     */
    public function rawSelectAll(){
        if( count($this->_cols) <= 0 ){
            $this->_cols = ["*"];
        }
        $order = implode(",",$this->_order);
        $list = $this->con->select(
            $this->tableName.$this->_asName,
            $this->_where,
            $this->_limit==-1?false:$this->_limit,
            empty($order)?false:($order),
            null,
            implode(",",$this->_cols),
            [],$this->_joins
            ,$this->_having,$this->_group
        );


        $list = $list->fetchAll(
            \System\Database\Gate::FETCH_ASSOC
        );

        return $list;
    }

    /**
     * calls select command to select data from database and return data
     * @return callable
     * @throws System\Database\DatabaseException
     */
    public function rawSelect(){
        if( count($this->_cols) <= 0 ){
            $this->_cols = ["*"];
        }
        $order = implode(",",$this->_order);
        $list = $this->con->select(
            $this->tableName.$this->_asName,
            $this->_where,
            $this->_limit==-1?false:$this->_limit,
            empty($order)?false:($order),
            null,
            implode(",",$this->_cols),
            [],$this->_joins
            ,$this->_having,$this->_group
        );

        return function() use(&$list){
                return $list->fetch(
                    \System\Database\Gate::FETCH_ASSOC
                );
        };
    }

    /**
     * calls select command to select data from database and return data and proccess in a function
     * @param callback $fn
     * @return callable
     * @throws System\Database\DatabaseException
     */
    public function rowFunc($fn){
        if( count($this->_cols) <= 0 ){
            $this->_cols = ["*"];
        }
        $order = implode(",",$this->_order);
        $list = $this->con->select(
            $this->tableName.$this->_asName,
            $this->_where,
            $this->_limit==-1?false:$this->_limit,
            empty($order)?false:($order),
            null,
            implode(",",$this->_cols),
            [],$this->_joins
            ,$this->_having,$this->_group
        );

        return function() use(&$list,$fn){
            return $list->fetchAsFunc(
                $fn
            );
        };
    }

    /**
     * calls select command to select data from database and return data as array and procces in a function
     * @param callback $fn
     * @return array
     * @throws System\Database\DatabaseException
     */
    public function selectFunc($fn){
        if( count($this->_cols) <= 0 ){
            $this->_cols = ["*"];
        }
        $order = implode(",",$this->_order);
        $list = $this->con->select(
            $this->tableName.$this->_asName,
            $this->_where,
            $this->_limit==-1?false:$this->_limit,
            empty($order)?false:($order),
            null,
            implode(",",$this->_cols),
            [],$this->_joins
            ,$this->_having,$this->_group
        );


        $list = $list->fetchAllAsFunc(
            $fn
        );

        return $list;
    }

    /**
     * update the data range
     * @param array $data
     * @return int
     * @throws \Exception|System\Database\DatabaseException
     */
    public function update($data){
        if( !is_array($data) ){
            throw new \Exception("Arguments are not in correct format!");
        }

        $updateColumns = array();

        foreach( $data as $key=>$val ){
            if( is_numeric($key) ){
                $updateColumns[] = $val;
            }
            else{
                $cols = &$this->columns;
                if( isset($cols[$key]) ){
                    $updateColumns[$key] = $val;
                }
            }
        }
        
        /*
        foreach( $this->columns as $key=>$col ){
            if( isset($data[$key]) ){
                $updateColumns[$key] = $data[$key];
            }
        }
        */


        $this->con->update(
            $this->tableName,
            $updateColumns,
            $this->_where
        );

        return $this->con->rowCount();
    }

    /**
     * delete one or multiple row/s 
     * @return int
     * @throws System\Database\DatabaseException
     */
    public function delete(){

        $this->con->delete(
            $this->tableName,
            $this->_where
        );

        return $this->con->rowCount();
    }
}