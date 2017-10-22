<?php

date_default_timezone_set ('UTC'); // important - do not remove and leave as first line

// --- a simple logging class

class Log
{
    private static $seperator = ' | ';  // column seperator
    private static $maxSize   = 255;    // max column size
    private static $lineSize  =  80;    // size of a seperator line
    private static $header    = true;   // want log headers
    private static $tracer    = false;  // want trace output

    public static function header ($header) { self::$header = $header; }
    public static function tracer ($tracer) { self::$tracer = $tracer; }    
    
    public static function trace  (/* varg list*/) { if (self::$tracer) self::out ('---', func_get_args ()); }
    public static function info   (/* varg list*/) { self::out ('   ', func_get_args ()); }    
    public static function warn   (/* varg list*/) { self::out ('!!!', func_get_args ()); }
    public static function error  (/* varg list*/) { self::out ('***', func_get_args ()); }
    public static function line   ()               { echo str_repeat ('-', self::$lineSize)."\n"; }

    private static function tidy ($text)
    {
        $text = substr ($text, 0, self::$maxSize);  // truncate too long text
        $text = str_replace ("\n", " [newline] ", $text);  // tidy up unprintables
        $text = str_replace ("\r", " [return] " , $text);
        $text = str_replace ("\t", " [tab] "    , $text);

        return $text;
    }

    private static function out ($class, $args)
    {
        $msg  = array_shift ($args);
        $text = implode (self::$seperator, array_map ('self::tidy', $args));
        $now  = date ('YmdHis');
        $head = self::$header ? "$now - $class - " : '';
        
        echo "$head $msg".($text ? " - $text" : '')."\n";
    }
}

?>