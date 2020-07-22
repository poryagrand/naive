<?php

namespace System\Controller;

use System\Communicate\Cookie;
use System\Communicate\Debug\Console;

class LanguageHandleException extends \Exception
{
}

class Language
{

    protected static $__lang = "en";
    protected static $__folder = Route::LANG_PATH;
    protected static $__content = array();
    protected static $__font = "";
    protected static $__name = "";
    protected static $__icon = "";
    protected static $__rtl = false;

    /**
     * initialize language contents and attributes
     * @return void
     */
    public static function init()
    {

        if (!Cookie::has("site_language")) {
            Cookie::get("site_language")->value = "en";
            Cookie::save();
        }

        self::$__lang = Cookie::get("site_language")->value;

        if (self::exist(self::$__lang)) {
            $json = file_get_contents(self::$__folder . self::$__lang . ".json");

            if ($json === false) {
                throw new LanguageHandleException("language file is not accessable");
            }

            $json = @json_decode($json, true);

            if ($json === null || !isset($json["name"]) || !isset($json["font"]) || !isset($json["direction"]) || !isset($json["keywords"])) {
                throw new LanguageHandleException("language structure is not correct  (" . (self::$__lang . ".json") . ")");
            }

            self::$__content = $json["keywords"];
            self::$__font = $json["font"];
            self::$__name = $json["name"];
            self::$__icon = $json["icon"];
            self::$__rtl = $json["direction"] == "rtl" ? true : false;
        } else {
            $langs = Route::find("/.+\.json/", self::$__folder);

            if (count($langs) > 0) {
                $langs = basename($langs[0], ".json");
            } else {
                throw new LanguageHandleException("no language file found!");
            }

            Cookie::get("site_language")->value = $langs;
            Cookie::save();
            self::$__lang = $langs;
            self::init();
        }
    }

    public static function open($file)
    {
        $json = file_get_contents(self::$__folder . $file . ".json");

        $json = @json_decode($json, true);

        if ($json === null) {
            return [];
        }
        return $json;
    }

    /**
     * change language of system for next request or for now
     * @param string $lang
     * @param bool $reInit
     * @return void
     */
    public static function changeTo($lang, $reInit = false)
    {
        if (self::exist($lang)) {
            Cookie::get("site_language")->value = $lang;
            Cookie::save();
            if ($reInit) {
                self::init();
            }
        }
    }

    public static function exist($lang)
    {
        try {
            if (@file_exists(self::$__folder . $lang . ".json")) {
                return true;
            }
        } catch (LanguageHandleException $e) {
        }
        return false;
    }

    /**
     * check if language is rtl or not
     * @return bool
     */
    public static function isRtl()
    {
        return self::$__rtl;
    }

    /**
     * get the language icon url
     * @return string
     */
    public static function icon()
    {
        return (self::$__icon);
    }

    /**
     * return the default fornt name of language
     * @return string
     */
    public static function font()
    {
        return self::$__font;
    }

    /**
     * return the name of language
     * @return string
     */
    public static function name()
    {
        return self::$__name;
    }

    /**
     * return the slug of language
     * @return string
     */
    public static function slug()
    {
        return self::$__lang;
    }

    /**
     * check if a keyword is exist or not and return its value
     * @param string $name
     * @param array $param
     * @return string|null
     */
    public static function get($name, $param = array())
    {
        if (!is_string($name)) {
            return null;
        }

        if (!is_array($param)) {
            $param = array();
        }

        $tmp = self::$__content;
        $str = null;
        if (isset($tmp[$name])) {
            $str = $tmp[$name];
        } else {
            $name = explode(".", $name);
            $tn = array_shift($name);
            while ($tn && isset($tmp[$tn])) {
                $tmp = $tmp[$tn];
                $tn = array_shift($name);
            }
            if (is_string($tmp)) {
                $str = $tmp;
            }
        }

        if ($str !== null) {
            foreach ($param as $key => $val) {
                try {
                    $str = str_replace("{{{$key}}}", (string) $val, $str);
                } catch (LanguageHandleException $e) {
                }
            }
            return $str;
        }
        return null;
    }

    /**
     * return general information of all exists languages
     * @return array
     */
    public static function all()
    {
        $langs = Route::find("/.+\.json/", self::$__folder);
        $output = [];
        foreach ($langs as $url) {
            try {
                $content = file_get_contents($url);
                $content = @json_decode($content, true);

                if ($content !== null) {
                    $output[] = [
                        "name" => $content["name"],
                        "icon" => $content["icon"],
                        "slug" => \basename($url, ".json")
                    ];
                }
            } catch (\Exception $e) {
            }
        }
        return $output;
    }
}
