<?php 
namespace Dsc\Phalcon\Traits;

trait Singleton
{
    /**
     *	Return class instance
     *	@return object
     **/
    static function instance() 
    {
        if (!\Dsc\Phalcon\Registry::exists($class=get_called_class())) 
        {
            $ref=new \Reflectionclass($class);
            $args=func_get_args();
            \Dsc\Phalcon\Registry::set($class, $args?$ref->newinstanceargs($args):new $class);
        }
        
        return \Dsc\Phalcon\Registry::get($class);
    }    
}