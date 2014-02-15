<?php 
namespace Dsc\Phalcon;

class System 
{
    use \Dsc\Phalcon\Traits\Singleton;
    
    protected $_di;
    
    public function __construct($di)
    {
        $this->_di = $di;
        
        $di->setShared('session', function() {
            $session = new \Phalcon\Session\Adapter\Files();
            $session->start();
            return $session;
        });
        
        $di->set('flash', function(){
            $flash = new \Phalcon\Flash\Session(array(
                            'error' => 'alert alert-error',
                            'success' => 'alert alert-success',
                            'notice' => 'alert alert-info',
            ));
            return $flash;
        });
        
        $di->setShared('system', function(){
            return \Dsc\Phalcon\System::instance();
        });
    }
}