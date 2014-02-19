<?php 
namespace Dsc\Lib;

class String
{
    /**
     * Determines if any of an array of needles is in a haystack
     * 
     * @param unknown $needles
     * @param unknown $haystack
     * @return boolean
     */
    public static function inStrings($needles, $haystack)
    {
        foreach ($needles as $needle) 
        {
            if (strpos($haystack, $needle) !== false) 
            {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     *	Split comma-, semicolon-, or pipe-separated string
     *
     *	@return array
     *	@param $str string
     **/
    public static function split($str) 
    {
        return array_map('trim', preg_split('/[,;|]/',$str,0,PREG_SPLIT_NO_EMPTY));
    }
}