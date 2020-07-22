<?php


use System\Controller\Route;
use System\Controller\Language;


Route::filter("system.lang",function($val){
    if( Language::exist($val) ){
        if( Language::name() !== $val ){
            Language::changeTo($val,true);
        }
    }
    return $val;
});

Route::filter("system.form.email",function($value){
    if( filter_var($value, FILTER_VALIDATE_EMAIL) ){
        return $value;
    }
    return null;
});


Route::filter("system.free.max255",function($value){
    if( !empty($value) ){
        return substr($value,0,255);
    }
    return null;
});