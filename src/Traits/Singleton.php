<?php 
namespace Dsc\Lib\Traits;

trait Singleton
{
    /**
     *	Return class instance
     *	@return object
     **/
    static function instance() 
    {
        if (!\Dsc\Lib\Registry::exists($class=get_called_class())) 
        {
            $ref=new \Reflectionclass($class);
            $args=func_get_args();
            \Dsc\Lib\Registry::set($class, $args?$ref->newinstanceargs($args):new $class);
        }
        
        return \Dsc\Lib\Registry::get($class);
    }    
}