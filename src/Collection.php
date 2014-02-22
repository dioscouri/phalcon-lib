<?php 
namespace Dsc\Lib;

class Collection extends \Phalcon\Mvc\Collection
{
    protected static $model_config = array(
        'cache_enabled' => true,
        'track_states' => true,
        'context' => null,
        'default_sort' => array( '_id' => 1 )
    );
    
    protected static $query_params = array(
        'fields' => array(),
        'conditions' => array(),
        'sort' => array(),
        'limit' => null,
        'skip' => null
    );
    
    protected static $model_state = null;
    

    /**
     * These fields are never stored in the database.
     * Override this in your models if necessary
     *
     * @return array
     */
    public function getReservedAttributes()
    {
        return array(
            'model_config',
            'model_state',
            'query_params'
        );
    }
    
    /**
     * Setup the model
     */
    public function onConstruct()
    {
        $this->emptyState();
    }
    
    /**
     * Manually set a query param without using setState()
     * 
     */
    public function setParam( $param, $value )
    {
        if (array_key_exists($param, $this->query_params)) 
        {
            $this->query_params[$param] = $value;
        }
        
        return $this;
    }
    
    /**
     * Set a condition in the query
     *
     */
    public function setCondition( $key, $value )
    {
        $this->query_params['conditions'][$key] = $value;
    
        return $this;
    }
    
    /**
     * Get a parameter from the query
     * 
     * @param unknown $param
     * @return NULL
     */
    public static function getParam( $param )
    {
        if (array_key_exists($param, static::$query_params))
        {
            return static::$query_params[$param];
        }
    
        return null;
    }
    
    public function context()
    {
        if (empty($this->model_config['context'])) {
            $this->model_config['context'] = strtolower(get_class($this));
        }
        
        return $this->model_config['context'];
    }
    
    public function inputFilter()
    {
        return $this->getDI()->get('filter');
    }
    
    public function getState( $property=null, $default=null, $return_type='default' )
    {
        $return = ($property === null) ? $this->model_state : $this->model_state->get($property, $default);
    
        return $this->inputFilter()->sanitize( $return, $return_type );
    }
    
    /**
     * Method to set model state variables
     *
     * @param   string  $property  The name of the property.
     * @param   mixed   $value     The value of the property to set or null.
     *
     * @return  mixed  The previous value of the property or null if not set.
     */
    public function setState($property, $value = null)
    {
        if ($property instanceof \Dsc\Lib\Object) {
            $this->model_state = $property;
        } else {
            $this->model_state->set($property, $value);
        }
    
        return $this;
    }
    
    public function emptyState()
    {
        $blank = new \Dsc\Lib\Object;
        $this->setState( $blank );
    
        return $this;
    }
    
    /**
     * Method to auto-populate the model state.
     *
     */
    public function populateState()
    {
        if ($filters = $this->getUserStateFromRequest($this->context() . '.filter', 'filter', array(), 'array'))
        {
            foreach ($filters as $name => $value)
            {
                $this->setState('filter.' . $name, $value);
            }
        }
    
        if ($list = $this->getUserStateFromRequest($this->context() . '.list', 'list', array(), 'array'))
        {
            foreach ($list as $name => $value)
            {
                $this->setState('list.' . $name, $value);
            }
        }
    
        $offset = \Dsc\Lib\Paginator::findCurrentPage();
        $this->setState('list.offset', ($offset-1 >= 0) ? $offset-1 : 0);
    
        if (is_null($this->getState('list.sort')))
        {
            $this->setState('list.sort', $this->model_config['default_sort']);
        }
    
        return $this;
    }
    
    /**
     * Gets the value of a user state variable and sets it in the session
     *
     * This is the same as the method in \Dsc\System except that this also can optionally
     * force you back to the first page when a filter has changed
     *
     * @param   string   $key        The key of the user state variable.
     * @param   string   $request    The name of the variable passed in a request.
     * @param   string   $default    The default value for the variable if not found. Optional.
     * @param   string   $type       Filter for the variable. Optional.
     * @param   boolean  $resetPage  If true, the offset in request is set to zero
     *
     * @return  The request user state.
     */
    public function getUserStateFromRequest($key, $request, $default = null, $type = 'default', $resetPage = true)
    {
        $system = \Dsc\Lib\System::instance();
        $input = $system->input();
        
        $old_state = $system->getUserState($key);
        $cur_state = (!is_null($old_state)) ? $old_state : $default;
        $new_state = $input->get($request, $type);
    
        if (($cur_state != $new_state) && ($resetPage))
        {
            $input->set('list.offset', 0);
        }
    
        // Save the new value only if it is set in this request.
        if ($new_state !== null)
        {
            $system->setUserState($key, $new_state);
        }
        else
        {
            $new_state = $cur_state;
        }
    
        return $new_state;
    }
    
    /**
     * An alias for find()
     * that uses the model's state
     * and implements caching (if enabled)
     */
    public static function getItems($refresh=false)
    {
        // TODO Store the state        
    	// TODO Implement caching
    	return static::fetchItems();
    }
    
    protected static function fetchItems()
    {
        $return = static::find($this->query_params);
        
        return $return;
    }

    /**
     * An alias for findOne
     * that uses the model's state
     * and implements caching (if enabled)
     */
    public static function getItem()
    {
        // TODO Store the state
        // TODO Implement caching
        return static::fetchItem();
    }
    
    protected static function fetchItem()
    {
        $return = static::findFirst($this->query_params);
    
        return $return;
    }
    
    /**
     * An alias for find()
     * that uses the model's state
     * implements caching (if enabled)
     * and returns a pagination object
     * 
     * @return \Dsc\Lib\Paginator
     */
    public static function paginate($refresh=false)
    {
        $pos = static::getState('list.offset', 0, 'int');
        $size = static::getState('list.limit', 10, 'int');
        $total = static::count( static::getParam( 'conditions' ) );
        $count = ceil($total/$size);
        $pos = max(0,min($pos,$count-1));
        $config = array(
            'items'=>static::getItems($refresh),
            'total_items'=>$total,
            'items_per_page'=>$size,
            'total_pages'=>$count,
            'pos'=>$pos<$count?$pos:0
        );
        
        $result = new \Dsc\Lib\Paginator( $config );
        
        return $result;        
    }
    
    public function getFields()
    {
        if (empty($this->query_params['fields'])) {
            $this->fetchFields();
        }
        
        return $this->query_params['fields'];
    }
    
    protected function fetchFields()
    {
        $this->query_params['fields'] = array();
    
        $select_fields = $this->getState('select.fields');
        if (!empty($select_fields) && is_array($select_fields))
        {
            $this->query_params['fields'] = $select_fields;
        }
    
        return $this;
    }
    
    public static function getConditions()
    {
        if (empty(static::$query_params['conditions'])) {
            static::fetchConditions();
        }
        
        return static::$query_params['conditions'];
    }
    
    protected static function fetchConditions()
    {
        static::$query_params['conditions'] = array();
    
        return $this;
    }

    /**
     * Assigns an array of data to this object
     *
     * @param array $data
     * @return \Dsc\Lib\Collection
     */
    public function assign(array $data)
    {
        foreach ($data as $key=>$value)
        {
            $this->__set($key, $value);
        }
         
        return $this;
    }

    /**
     * Allows setting values using dot.notation
     * 
     * @param unknown $key
     * @param unknown $value
     */
    public function __set($key, $value)
    {
        if (strpos($key, '.') !== false) 
        {
            $keys = explode('.', $key);
            
            $parent = $keys[0];
            if (!isset($this->$parent)) {
                $this->$parent = array();
            }
            
            array_shift($keys);
            \Dsc\Lib\ArrayHelper::set( $this->$parent, implode('.', $keys), $value );
             
        } 
            else 
        {
            $this->$key = $value;
        }
    }
    
    /**
     * Allows getting values using dot.notation
     * 
     * @param unknown $key
     */
    public function __get($key) 
    {
        if ($key == 'id') {
            $key = '_id';
        }
        
        if (strpos($key, '.') !== false)
        {
            $keys = explode('.', $key);
            $parent = $keys[0];
            array_shift($keys);
            return \Dsc\Lib\ArrayHelper::get( $this->$parent, implode('.', $keys) );
        }
        else
        {
            return $this->readAttribute($key);
        }
    }
    
    /*
     * TODO Implement these 
     * 
    public function __unset($key) 
    {
    	
    }
    
    public function __isset($key)
    {
         
    }
    */
}
?>