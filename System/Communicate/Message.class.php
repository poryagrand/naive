<?php
namespace System\Communicate;

use System\Security\Crypt;
use System\Communicate\Debug\Console;
use System\Controller\Route;

class MessageErrorHandller extends \Exception{}

/**
 * @brief   send every type of message to web browser window. 
 *          if message not been shown in a request because of something like redirection , it will show afterward.
 */
class Message{

    /**
     * @brief tell system to flush/not flush data at end of each routing page
     */
    protected static $AutoFlush = true;

    /**
     * the template of message data in js format
     */
    public static $template = "";

    /**
     * @brief storage stack of notifications
     */
    protected static $Storage = array();

    protected static $sotrageName = "MessageData";

    /**
     * @brief add new message to stack by specifying the type
     * 
     * @return void
     * 
     * 
     * @param array $params  list of data want to replace on template
     */
    public static function add($params=[]){
        self::init();
        
        if( !is_array($params) ){
            throw new MessageErrorHandller("the argument is not in correct format");
        }

        static::$Storage[] = array(
            "params"=>$params,
            "class"=>static::class
        );
    
        BasicSession::set(static::$sotrageName,static::$Storage);
    }

    /**
     * initial message session from before data
     */
    public static function init(){
        if( count(static::$Storage) <= 0 ){
            $tmp = BasicSession::get(static::$sotrageName);
            static::$Storage = ( ( isset( $tmp ) && !empty($tmp) ) ? $tmp : array() );
        }
    }

    /**
     * check if auto flush is on or not
     * @return bool
     */
    public static function isAutoFlush(){
        return self::$AutoFlush;
    }

    /**
     * flush data automaticly after routing
     */
    public static function autoFlush(){
        self::$AutoFlush = true;
    }

    /**
     * disable flush data automaticly after routing
     */
    public static function manualFlush(){
        self::$AutoFlush = false;
    }
    
    /**
     * @brief write out the message code on screen
     * @return void
     */
    public static function flush(){
        if( Route::hasError() ){
            return;
        }
        self::init();
        if( count(static::$Storage) > 0  ){
            $all = "";
            foreach(static::$Storage as &$val){
                $tClass = $val["class"];
                $temp = $tClass::$template;

                foreach( $val["params"] as $key=>&$value ){
                    $temp  = str_replace("{{".$key."}}",$value,$temp);
                }
                
                $all .= $temp;
            }
            
            //file_put_contents(__ROOT__."/debug.log", "Message: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}".PHP_EOL , FILE_APPEND | LOCK_EX);
            BasicSession::remove(static::$sotrageName);
            BasicSession::set(static::$sotrageName,[]);
        
            echo '<script>'.$all.'</script>';
        }
    }
}