<?php

// --- a simple mysql database class

class Database
{
    private static $host   = '127.0.0.1';
    private static $schema = 'wooc';
    private static $uid    = 'web';
    private static $pwd    = 'web';

    public static function executeQuery ($sql, $args = [])
    {
        $data = [];
        $con  = new mysqli (self::$host, self::$uid, self::$pwd, self::$schema);

        foreach ($args as $col=>$arg)
        {
            $esc = $con->real_escape_string ($arg);
            $sql = str_ireplace ("':$col'", "'$esc'", $sql);  // must replace quoted instanes first
            $sql = str_ireplace ( ":$col" ,   $arg  , $sql);
            $sql = str_ireplace ( "'null'" , 'null' , $sql);  // nullable columns will have got quoted
        }

        // echo "$sql;\n";  // trace output

        $con->multi_query ($sql);

        $cursor = $con->store_result ();

        if ($cursor instanceof mysqli_result)
        {
            while ($row = $cursor->fetch_object ())
            {
                $data [] = $row;
            }

            $cursor->free ();
        }

        $con->close ();
        return $data;
    }
}

?>
