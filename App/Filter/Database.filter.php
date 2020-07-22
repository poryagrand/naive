<?php

use App\Toast;
use System\Controller\Language;
use System\Database\Model\Base;
use System\Security\Crypt;

Base::filter("db.bigint",function($value,$update,$get,$set,$isModified,$isInit){
    if( !preg_match("/^[0-9]+$/",$value) ){
        if($get){
            return "";
        }
        return Base::NoChange;
    }
    return $value;
});


Base::filter("auth.username",function($value,$update,$get,$set,$isModified,$isInit){
    if( preg_match("/[ \s\t\n\v\r\#\/\@]/",$value) ){
        Toast::error(Language::get("word.error"),Language::get("db.usernameCheck"));
        if($get){
            return "";
        }
        return Base::NoChange;
    }
    return $value;
});



Base::filter("auth.password",function($value,$update,$get,$set,$isModified,$isInit){

    if( !is_string($value) ){
        return Base::NoChange;
    }

    if( $set && !$isInit ){
        if(!is_string($value)){
            return Base::NoChange;
        }
        elseif (strlen($value) < 8) {
            return Base::NoChange;
        }
        elseif(!preg_match("/[0-9]+/",$value)) {
            return Base::NoChange;
        }
        elseif(!preg_match("/[A-Z]+/",$value)) {
            return Base::NoChange;
        }
        elseif(!preg_match("/[a-z]+/",$value)) {
            return Base::NoChange;
        }
        return Crypt::password($value);
    }
    
    return $value;
});