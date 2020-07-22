<?php

namespace System\Database\Model;

use System\Communicate\Debug\Console;

/**
 * represent of each row of table in database
 * 
 * @property \System\Database\Gate $db
 * @property bool $__isModified
 * @property mixed $__primaryKey
 * @property string $__tableName
 * @property array $__FKeys
 * @property array $__columnsDetails
 */
trait TableStructure{
    protected $db;
    protected $__isModified;
    protected $__primaryKey;
    protected $__tableName;
    protected $__FKeys = array();
    protected $__columnsDetails = array();
    protected $isNew;

    protected $privateStorage = [];


    

    final function __construct($tName, \System\Database\Gate $db, $table,$isnew=true)
    {
        if( !is_string($tName) ||  !is_array($table) ){
            throw new \Exception("Arguments are not in correct format!");
        }

        $this->__tableName = $tName;
        $this->__isModified = false;
        $this->isNew = !(!$isnew);
        $this->__primaryKey = null;
        $this->setDB($db);

        foreach( $table as $col=>$details ){
            $this->__columnsDetails[$col] = array(
                "isNull"=>isset($details["isNull"])?(!(!$details["isNull"])):true,
                "cast"=>isset($details["cast"])?$details["cast"]:"string",
                "auto"=>isset($details["auto"])?(!(!$details["auto"])):true,
                "default"=>isset($details["default"])?($details["default"]):null
            );

            if( isset($details["primary"]) && (!(!$details["primary"])) ){
                $this->setPrimaryKey($col);
            }
            else if( isset($details["foreign"])  && (!(!$details["foreign"])) ){
                $this->setFK($col);
            }
        }

        if( $this->__primaryKey === null ){
            throw new \Exception("one primary key is needed! better to set an `id` as an auto incremental primary key.");
        }

        if( method_exists($this,"init") ){
            call_user_func(array($this,"init"));
        }

    }


    final public function toArray(){
        $data = [];
        foreach( $this->__columnsDetails as $name=>$column ){
            $data[$name] = $this->{$name};
        }
        return $data;
    }

    /**
     * storage of the model to store custom data
     * @param string $name
     * @param object $value
     * @return mixed
     */
    public function storage($name,$value=null){
        if( $value === null ){
            $tmp = &self::$privateStorage;
            return isset($tmp[$name]) ? $tmp[$name] : null;
        }
        self::$privateStorage[$name] = $value;
    }

    /**
     * get the column details to set in its column to validate
     * 
     * @param string $name
     * @return array
     */
    private function getColumnDetail($name){
        if( isset( $this->__columnsDetails[$name] ) ){
            return $this->__columnsDetails[$name];
        }
        return array(
            "isNull"=>true,
            "cast"=>"string",
            "auto"=>false,
            "default"=>""
        );
    }

    /**
     * introduce a column as a foreign key
     * 
     * @param string $name
     * @return void
     */
    final protected function setFK($name){
        if( !is_string($name)){
            throw new \Exception("Arguments are not in correct format! 'name' must be string");
        }

        // generate new name for storing in object
        $newName = $name . "__FK__" . mt_rand(200,1000);
        // change until a name be free
        while( property_exists($this,$newName)  ){
            $newName = $name . "__FK__" . mt_rand(200,1000);
        }
        $this->__FKeys[$name] = $newName;
    }


    /**
     * get the foreign key name if exist otherwise return false
     * 
     * @param string $name
     * @return string|false
     */
    final protected function getFK($name){
        $tmp = $this->__FKeys;
        if( isset($tmp[$name]) ){
            return $tmp[$name];
        }
        return false;
    }

    /**
     * set the source database server refrence
     * 
     * @param \System\Database\Gate $connect
     * @return void
     */
    final protected function setDB(\System\Database\Gate $connect)
    {
        $this->db = $connect;
    }

    /**
     * access to database to run queries
     * @return \System\Database\Gate
     */
    final protected function getDB(){
        return $this->db;
    }

    /**
     * set the primary key name 
     * 
     * @param string $prmKey
     * @return void
     * @throws \Exception
     */
    final protected function setPrimaryKey($prmKey){
        if( !is_string($prmKey) ){
            throw new \Exception("Arguments are not in correct format! 'prmKey' must be string ");
        }
        // throw error when the column was not exist
        if( !property_exists($this,$prmKey."_") ){
            throw new \Exception("column '$prmKey' is not exist . inside table '{$this->__tableName}'!");
        }

        $this->__primaryKey = $prmKey;
    }

    /**
     * get the primary key name and its value
     * 
     * @param string $prmKey
     * @return void
     * @throws \Exception
     */
    final public function getPrimaryKey(){
        // throw error when the column was not exist
        if( $this->__primaryKey !== null && property_exists($this,$this->__primaryKey."_") ){
            $prmk = $this->__primaryKey;
            return array(
                "name"=>$this->__primaryKey,
                "value"=>$this->$prmk 
            );
        }

        return null;
    }

    /**
     * get the name of table
     * 
     * @return string
     */
    final public function tableName(){
        return $this->__tableName;
    }

    /**
     * retrieve the name of primary key column title
     * 
     * @return string
     */
    final public function primaryKey(){
        return $this->__primaryKey;
    }

    /**
     * check if the table has a modified column or not
     * 
     * @return bool
     */
    final public function isModified(){
        return !(!$this->__isModified);
    }

    /**
     * set the modified state to true
     * 
     * @return void
     */
    final protected function modify(){
        $this->__isModified = true;
    }

    /**
     * set data to columns event if it is auto increment
     * 
     * @param string $name
     * @param mixed $value
     * @return void
     * @throws \Exception
     */
    final public function forceSet($name,$value){

        if( !property_exists($this,$name."_") ){
            throw new \Exception("column '$name' is not exist . inside table '{$this->__tableName}'!");
        }

        $keys = array_keys($this->checkAndReturnWhere());
        if( array_search($name,$keys) !== false ){
            $this->isNew = true;
        }

        $this->{$name."_"}->forceSet($value);

    }

    /**
     * the magic function that listen on setting property on table when the column going to add or edit
     * 
     * @param string $name
     * @param mixed $value
     * @return void
     * @throws \Exception
     */
    final public function __set($name, $value)
    {
        $this->__isModified = !(!$this->__isModified);


        if( method_exists($this,"onInitSet") ){
            if( call_user_func(array($this,"onInitSet"),$name,$value) ){
                return;
            }
        }

        // check if the property existence
        if( !property_exists($this,$name."_") ){
            Console::warn("column '$name' is not exist . inside table '{$this->__tableName}'!");
            //throw new \Exception("column '$name' is not exist . inside table '{$this->__tableName}'!");
            return;
        }

        // if the value of column is not empty, so it is not initialized yet
        $hasEvaled = false;
        if( $this->{$name."_"} !== null ){
            $this->__isModified = true;
            // check if the function "onSet" is created in child class to call.
            // if the return value of the function equals to "true" it means not run the rest of code in here
            if( method_exists($this,"onBeforeSet") ){
                $hasEvaled = $this->onBeforeSet($name,$value);
            }
            
            if(!$hasEvaled){
                // the column property has function to set value 
                // but if the property was not a column , 
                // means that it is a foreign column or unknown
                if( $this->{$name."_"} instanceof Column ){
                    $this->{$name."_"}->set($value);
                }
                else{
                    $fk = $this->getFK($name);
                    if( $fk !== false ){
                        $this->{$fk}->set($value);
                    }
                    else{
                        throw new \Exception("unknown column '$name' assignment . inside table '{$this->__tableName}'!");
                    }
                }
            }
        }
        else{
            // the column value is not initialized
            // so first check it is foreign key or not
            // to set the column for the foreign key
            $fk = $this->getFK($name);

            if( $fk !== false ){
                $this->{$fk} = new Column($name,$value,$this->__isModified,$this->getColumnDetail($name));
            }
            

            if( !$hasEvaled ){
                $this->{$name."_"} = new Column($name,$value,$this->__isModified,$this->getColumnDetail($name));
            }

            // then checks the "onSet" function as above
            if( method_exists($this,"onAfterSet") ){
                $this->onAfterSet($name,$value);
            }

            // if current property is the primary key and the content of that is not a column ,
            // so it is not possible
            if( $this->__primaryKey == $name && !($this->{$name."_"} instanceof Column) ){
                throw new \Exception("Primary Key '$name' could not be a foreign key . inside table '{$this->__tableName}'!");
            }

        }
    }

    /**
     * set value to column with raw data and not parse it
     * @param string $name
     * @param string $value
     */
    final public function rawSet($name,$val){
        if( property_exists($this,$name."_") ){
            $this->{$name."_"}->set($val,true);
        }
        return $this;
    }

    /**
     * set value to column with raw data and not parse it
     * @param string $name
     * @param string $value
     */
    final public function getRaw($name,$val){
        if( property_exists($this,$name."_") ){
            return $this->{$name."_"}->getRaw();
        }
        return null;
    }

    /**
     * handle the magic gunction "get" on getting value of any property
     * 
     * @param string $name
     * @return mixed
     * @throws \Exception
     */
    final public function __get($name)
    {

        if( method_exists($this,"onInitGet") ){
            $evaled = call_user_func(array($this,"onInitGet"),$name);
            if( $evaled !== null ){
                return $evaled;
            }
        }

        if( !property_exists($this,$name."_") ){
            throw new \Exception("column '$name' is not exist . inside table '{$this->__tableName}'!");
        }

        $evaluated = null;

        // evaluate the "onBeforeGet" event if exist
        // null means don't mind the event
        if( method_exists($this,"onGet") ){
            $evaluated = $this->onGet($name);
        }
        

        if($evaluated === null){
            if( $this->{$name."_"} !== null ){
                if( $this->{$name."_"} instanceof Column ){
                    $evaluated = $this->{$name."_"}->get();
                }
                else{
                    $evaluated = $this->{$name."_"};
                }
            }
        }


        if( method_exists($this,"onAfterGet") ){
            $evaluated = $this->onAfterGet($name,$evaluated);
        }
        

        return $evaluated;
    }

    final public function hasColumn($name){
        if( property_exists($this,$name."_") ){
            return true;
        }
        return false;
    }

    final public function __proccessColumns($usePrimKey=false){
        $insertKey = array();
        $insertValues = array();

        $udateDate = array();

        foreach( $this->__columnsDetails as $key=>$details ){
            $save = false;
            $fk = $this->getFK($key);
            if($fk !== false && $this->$fk->isModified() ){
                $__value = $this->$fk->getToUpdate();
                if( $__value !== Base::NoChange ){
                    $udateDate[$key] = $__value;
                    $save = true;
                }
            }
            else if($this->{$key."_"}->isModified() && ($this->__primaryKey !== $key || $usePrimKey)){
                $__value = $this->{$key."_"}->getToUpdate();
                if( $__value !== Base::NoChange ){
                    $udateDate[$key] = $__value;
                    $save = true;
                }
            }

            if( (!$details["auto"]||$usePrimKey) && $save && isset($udateDate[$key])){
                $insertKey[] = $key;
                $insertValues[] = $udateDate[$key];
            }
        }
        return [
            "insertKey"=>$insertKey,
            "insertValues"=>$insertValues,
            "udateDate"=>$udateDate
        ];
    }

    /**
     * save row (insert or update)
     * @return int
     * @throws \System\Database\DatabaseException
     */
    final public function save(){
        
        if( !$this->isModified() ){
            return 0;
        }

        //$insertKey = array();
        //$insertValues = array();

        //$udateDate = array();

        extract($this->__proccessColumns());
        

        if( $this->isNew ){
            $this->db->insert(
                $this->__tableName,
                $insertKey,
                $insertValues
            );

            $where = array();
            $where[$this->__primaryKey] = $this->db->id();
            $row = $this->db->select(
                $this->__tableName,
                $where
            )->fetch();

            if( is_array($row) ){
                $tempDetails = &$this->__columnsDetails;
                foreach( $row as $key=>$val ){
                    if( isset($tempDetails[$key]) ){
                        if( !$tempDetails[$key]["auto"] ){
                            $this->$key = $val;
                        }
                        else{
                            $this->forceSet($key,$val);
                        }
                    }
                }
            }
        }
        else{
            $this->db->update(
                $this->__tableName,
                $udateDate,
                $this->checkAndReturnWhere()
            );
        }

        $this->isNew = false;
        $this->__isModified = false;
        return $this->db->rowCount();
    }


    /**
     * check if model is exist with current filled data, if exist , replace it with older
     * @return bool
     */
    final public function exist(){
        if( !$this->isModified() ){
            return 0;
        }

        extract($this->__proccessColumns(true));
        
        $newAr = [];
        $c = count($insertKey);
        for($i=0;$i<$c;$i++){
            $newAr[$insertKey[$i]] = $insertValues[$i];
            $newAr[] = "AND";
        }
        array_pop($newAr);
        
        $this->db->select(
            $this->__tableName,
            $newAr
        );

        $data = $this->db->fetch();

        if( 
            $data!==null && $data !== false 
        ){
            $this->isNew = false;
            if( is_array($data) ){
                $tempDetails = &$this->__columnsDetails;
                foreach( $data as $key=>$val ){
                    if( isset($tempDetails[$key]) ){
                        $this->{$key."_"}->initSet($val);
                    }
                }
            }
            
            return true;
        }
        return false;
    }

    /**
     * insert if not exist
     * @return int
     */
    final public function saveIgnore(){
        
        if( !$this->isModified() ){
            return 0;
        }

        
        extract($this->__proccessColumns());

        if( $this->isNew ){
            $this->db->insertIgnore(
                $this->__tableName,
                $insertKey,
                $insertValues
            );

            $where = array();
            $where[$this->__primaryKey] = $this->db->id();
            
            $this->forceSet($this->__primaryKey,$this->db->id());

            $row = $this->db->select(
                $this->__tableName,
                $where
            )->fetch();

            if( is_array($row) ){
                $tempDetails = &$this->__columnsDetails;
                foreach( $row as $key=>$val ){
                    if( isset($tempDetails[$key]) ){
                        if( !$tempDetails[$key]["auto"] ){
                            $this->$key = $val;
                        }
                        else{
                            $this->forceSet($key,$val);
                        }
                    }
                }
            }
        }
        else{
            $this->db->update(
                $this->__tableName,
                $udateDate,
                $this->checkAndReturnWhere()
            );
        }

        $this->isNew = false;
        $this->__isModified = false;
        return $this->db->rowCount();
    }

    final public function delete(){
        if( $this->isNew ){
            throw new \Exception("you can not delete not inserted row!");
        }

        $this->db->delete(
            $this->__tableName,
            $this->checkAndReturnWhere()
        );

        return $this->db->rowCount();
    }

    /**
     * check the where data to be an assoc array
     * @return array
     * @throws \Exception
     */
    final private function checkAndReturnWhere(){
        $where = $this->where();
        // if (array() === $where){
        //     throw new \Exception("the where statement cant be empty! | table: ".$this->__tableName);
        // }
        if( array_keys($where) !== range(0, count($where) - 1) ){
            return $where;
        }

        throw new \Exception("the where statement must be an assoc array | table: ".$this->__tableName);
    }

    /**
     * generate the range of data to be effected
     * default is on primary ke. each table , if has no primary key must override it
     * @return array
     * @throws \Exception
     */
    private function where(){
        $primKey = $this->__primaryKey;
        if( $primKey === null ){
            throw new \Exception("the table ".($this->__tableName)." has no primary key!");
        }

        $res = array();
        $__value = $this->{$primKey."_"}->getToUpdate();
        if( $__value !== Base::NoChange ){
            $res[$primKey] = $__value;
        }

        return $res;
    }

}
