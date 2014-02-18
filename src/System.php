<?php 
namespace Dsc\Phalcon;

class System 
{
    use \Dsc\Phalcon\Traits\Singleton;
    
    protected $_di;
    
    public function __construct($di)
    {
        $this->_di = $di;
        
        $di->set('session', function() {
            $session = new \Phalcon\Session\Adapter\Files();
            $session->start();
            return $session;
        }, true);
        
        $di->set('flash', function(){
            $flash = new \Phalcon\Flash\Session(array(
                            'error' => 'alert alert-error',
                            'success' => 'alert alert-success',
                            'notice' => 'alert alert-info',
            ));
            return $flash;
        }, true);
        
        $di->set('flashSession', function(){
            $flash = new \Phalcon\Flash\Session(array(
                            'error' => 'alert alert-error',
                            'success' => 'alert alert-success',
                            'notice' => 'alert alert-info',
            ));
            return $flash;
        }, true);
        
        $di->setShared('system', function(){
            return \Dsc\Phalcon\System::instance();
        });
    }
}