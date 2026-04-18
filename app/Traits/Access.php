<?php


namespace app\Traits;


trait Access {

    public static function getprefix($prefix)
        {
            //$pattern = "/[^D]*/";
            $pattern = "/[0-9]+$/";
            $matches = null;
            $text = $prefix;
            preg_match($pattern,$prefix,$matches);
            if(!empty($matches))
                $text =  substr($prefix,0,(strlen($prefix) - (strlen($matches[0]))));

            return $text;
        }
}
