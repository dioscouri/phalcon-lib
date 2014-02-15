<?php 
namespace Dsc\Phalcon;

class ArrayHelper
{
	/**
	 * Get an item from an array using dot notation
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public static function get($array, $key, $default=null)
	{
		if (is_null($key)) 
		{
		    return $array;
		}
		
		if (is_object($array)) {
		    $array = self::fromObject($array);
		}

		if (isset($array[$key])) 
		{
		    return $array[$key];
		}

		foreach (explode('.', $key) as $segment)
		{
		    if (is_object($array)) {
		        $array = self::fromObject($array);
		    }
		    		    
			if ( !is_array($array) || !array_key_exists($segment, $array))
			{
				return $default;
			}

			$array = $array[$segment];
		}

		return $array;
	}
	
	/**
	 * Set an array item to a given value using dot notation
	 *
	 * If no key is given to the method, the entire array will be replaced.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return array
	 */
	public static function set(&$array, $key, $value=null)
	{
	    if (is_null($key)) {
	        return $array = $value;
	    }
	
	    $keys = explode('.', $key);
	
	    while (count($keys) > 1)
	    {
	        $key = array_shift($keys);
	
	        if ( !isset($array[$key]) || !is_array($array[$key]))
	        {
	            $array[$key] = array();
	        }
	
	        $array =& $array[$key];
	    }
	
	    $array[array_shift($keys)] = $value;
	
	    return $array;
	}
	
	/**
	 * Get a subset of the items from the given array
	 *
	 * @param  array  $array
	 * @param  array  $keys
	 * @return array
	 */
	public static function only($array, $keys)
	{
	    return array_intersect_key($array, array_flip((array) $keys));
	}
	
	/**
	 * Remove an array item from a given array using dot notation
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @return void
	 */
	public static function clear(&$array, $key)
	{
	    $keys = explode('.', $key);
	    $count = count($keys);
	    
	    while ($count > 1)
	    {
	        $key = array_shift($keys);
	
	        if ( !isset($array[$key]) || !is_array($array[$key]))
	        {
	            return;
	        }
	
	        $array =& $array[$key];
	    }
	
	    unset($array[array_shift($keys)]);
	}
	
	/**
	 * Get an item from an array using dot notation
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public static function exists($array, $key)
	{
	    if (isset($array[$key]))
	    {
	        return true;
	    }
	
	    foreach (explode('.', $key) as $segment)
	    {
	        if ( !is_array($array) || !array_key_exists($segment, $array))
	        {
	            return false;
	        }
	
	        $array = $array[$segment];
	    }
	
	    return true;
	}

	/**
	 * Filter an array using the provided Closure callback function
	 *
	 * @param  array  $array
	 * @param  \Closure  $callback
	 * @param boolean $useCallbackReturn
	 * @return array
	 */
	public static function where($array, \Closure $callback, $useCallbackReturn=true)
	{
	    $filtered = array();
	
	    foreach ($array as $key => $value)
	    {
	        if ($return = call_user_func($callback, $key, $value)) 
	        {
	            if ($useCallbackReturn === false) {
	                $filtered[] = $value;
	            } else {
	                $filtered[] = $return;
	            }	            
	        }
	    }
	    	
	    return $filtered;
	}

	/**
	 * Fetch a flattened array of a nested array element using dot notation
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @return array
	 */
	public static function fetch($array, $key)
	{
	    foreach (explode('.', $key) as $segment)
	    {
	        $results = array();
	
	        foreach ($array as $value)
	        {
	            $value = (array) $value;
	
	            $results[] = $value[$segment];
	        }
	
	        $array = array_values($results);
	    }
	
	    return array_values($results);
	}
	
	/**
	 * Flatten a multi-dimensional associative array with dots to indicate depth
	 *
	 * @param  array   $array
	 * @param  string  $prepend
	 * @return array
	 */
	public static function dot($array, $prepend = '')
	{
	    $results = array();
	
	    foreach ($array as $key => $value)
	    {
	        if (is_array($value))
	        {
	            $results = array_merge($results, self::dot($value, $prepend.$key.'.'));
	        }
	        else
	        {
	            $results[$prepend.$key] = $value;
	        }
	    }
	
	    return $results;
	}
	
	/**
	 * Flatten a multi-dimensional array into a single level
	 *
	 * @param  array  $array
	 * @return array
	 */
	public static function flatten($array, $recursive=false)
	{
	    $return = array();
	
	    if ($recursive) {
	        array_walk_recursive($array, function($x) use (&$return) { $return[] = $x; });
	    } else {
	        array_walk($array, function($x) use (&$return) { $return[] = $x; });
	    }

	    return $return;
	}
	
	/**
	 * Fetch all values from an array that are at a particular depth in the source array
	 *
	 * @param  array  $array
	 * @return array
	 */
	public static function level($array, $level=1)
	{
	    $return = array();
	    
	    for ($n=0; $n<$level; $n++) 
	    {
	        array_walk($array, function($x) use (&$return) { $return = array_merge( array(), array_values($x)); });
	    	$array = $return;
	    }
	
	    return $array;
	}
	
	/**
	 * Utility function to map an object to an array
	 *
	 * @param   object   $p_obj    The source object
	 * @param   boolean  $recurse  True to recurse through multi-level objects
	 * @param   string   $regex    An optional regular expression to match on field names
	 *
	 * @return  array    The array mapped from the given object
	 */
	public static function fromObject($p_obj, $recurse = true, $regex = null)
	{
		if (is_object($p_obj))
		{
			return self::arrayFromObject($p_obj, $recurse, $regex);
		}
		else
		{
			return null;
		}
	}

	/**
	 * Utility function to map an object or array to an array
	 *
	 * @param   mixed    $item     The source object or array
	 * @param   boolean  $recurse  True to recurse through multi-level objects
	 * @param   string   $regex    An optional regular expression to match on field names
	 *
	 * @return  array  The array mapped from the given object
	 */
	private static function arrayFromObject($item, $recurse, $regex)
	{
		if (is_object($item))
		{
			$result = array();

			foreach (get_object_vars($item) as $k => $v)
			{
				if (!$regex || preg_match($regex, $k))
				{
					if ($recurse)
					{
						$result[$k] = self::arrayFromObject($v, $recurse, $regex);
					}
					else
					{
						$result[$k] = $v;
					}
				}
			}
		}
		elseif (is_array($item))
		{
			$result = array();

			foreach ($item as $k => $v)
			{
				$result[$k] = self::arrayFromObject($v, $recurse, $regex);
			}
		}
		else
		{
			$result = $item;
		}

		return $result;
	}
}