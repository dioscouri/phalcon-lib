<?php 
namespace Dsc\Lib;

class System implements \Phalcon\Events\EventsAwareInterface
{
    use \Dsc\Lib\Traits\Singleton;
    
    protected $_registry;
    
    protected $_di;
    
    private static $hive = array();
    
    protected $_eventsManager;
    
    public function setEventsManager($eventsManager)
    {
        $this->_eventsManager = $eventsManager;
    }
    
    public function getEventsManager()
    {
        return $this->_eventsManager;
    }

    public function di()
    {
        return $this->_di;
    }
    
    public function __construct($di)
    {
        $this->_di = $di;
        
        $di->setShared('session', function() {
            $session = new \Phalcon\Session\Adapter\Files();
            $session->start();
            return $session;
        });
        
        $di->setShared('flash', function(){
            $flash = new \Phalcon\Flash\Session(array(
                            'error' => 'alert alert-danger',
                            'success' => 'alert alert-success',
                            'notice' => 'alert alert-info',
                            'warning' => 'alert alert-warning',
            ));
            return $flash;
        });
        
        $di->setShared('flashSession', function(){
            $flash = new \Phalcon\Flash\Session(array(
                            'error' => 'alert alert-danger',
                            'success' => 'alert alert-success',
                            'notice' => 'alert alert-info',
                            'warning' => 'alert alert-warning',
            ));
            return $flash;
        });
        
        $di->setShared('system', function(){
            return \Dsc\Lib\System::instance();
        });
        
        $this->setEventsManager( new \Phalcon\Events\Manager() );
        
        $di->getShared('filter')->add('default', new \Dsc\Lib\Filter\DefaultFilter );
    }
    
    /**
     *	Return TRUE if object exists in catalog
     *	@return bool
     *	@param $key string
     **/
    static function exists($key) {
        return isset(self::$hive[$key]);
    }
    
    /**
     *	Add object to catalog
     *	@return object
     *	@param $key string
     *	@param $obj object
     **/
    static function set($key,$obj) {
        return self::$hive[$key]=$obj;
    }
    
    /**
     *	Retrieve object from catalog
     *	@return object
     *	@param $key string
     **/
    static function get($key) {
        return self::$hive[$key];
    }
    
    /**
     *	Delete object from catalog
     *	@return NULL
     *	@param $key string
     **/
    static function clear($key) {
        self::$hive[$key]=NULL;
        unset(self::$hive[$key]);
    }
    
    /**
     * Return contents of hive
     */
    static function hive()
    {
        return self::$hive;
    }
    
    /**
     * Trigger an event using the system dispatcher
     *
     * @param unknown $eventName
     * @param unknown $arguments
     */
    public function trigger( $eventName, $arguments=array() )
    {
        $event = "dsc-system:".$eventName;
    
        return $this->getEventsManager()->fire($event, $this, $arguments);
    }

    /**
     * Adds a listener to Dsc events
     * @param unknown $object
     */
    public function addListener( $object )
    {
        $this->getEventsManager->attach('dsc-system', $object );
    }
    
    /**
     * 
     */
    public function getSessionRegistry()
    {
        if (empty($this->_registry)) {
            $registry = new \Phalcon\Session\Bag('dsc-system-registry');
            $registry->setDI( $this->di() );
            $this->_registry = $registry;
        }
        
        return $this->_registry;
    }
    
    /**
     * Gets a user state.
     *
     * @param   string  $key      The path of the state.
     * @param   mixed   $default  Optional default value, returned if the internal value is null.
     *
     * @return  mixed  The user state or null.
     */
    public function getUserState($key, $default = null)
    {
        $registry = $this->getSessionRegistry();
    
        if (!is_null($registry))
        {
            return $registry->get($key, $default);
        }
    
        return $default;
    }
    
    /**
     * Gets the value of a user state variable.
     *
     * @param   string  $key      The key of the user state variable.
     * @param   string  $request  The name of the variable passed in a request.
     * @param   string  $default  The default value for the variable if not found. Optional.
     *
     * @return  object  The request user state.
     */
    public function getUserStateFromRequest($key, $request, $default = null, $type = 'default')
    {
        $cur_state = $this->getUserState($key, $default);
        $new_state = $this->input()->get($request, $type);
    
        // Save the new value only if it was set in this request.
        if ($new_state !== null)
        {
            $this->setUserState($key, $new_state);
        }
        else
        {
            $new_state = $cur_state;
        }
    
        return $new_state;
    }
    
    /**
     * Sets the value of a user state variable.
     *
     * @param   string  $key    The path of the state.
     * @param   string  $value  The value of the variable.
     *
     * @return  mixed  The previous state, if one existed.
     */
    public function setUserState($key, $value)
    {
        $registry = $this->getSessionRegistry();
    
        if (!is_null($registry))
        {
            return $registry->set($key, $value);
        }
    
        return null;
    }
    
    public function input()
    {
        if (empty($this->input)) {
        	$this->input = new \Phalcon\Http\Request();
        }
        
        return $this->input;
    }
    
}