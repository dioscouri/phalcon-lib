<?php 
namespace Dsc\Lib;

class Paginator implements \Phalcon\Paginator\AdapterInterface
{
    public $items;
    public $current;
    public $before;
    public $next;
    public $last;
    public $total_pages;
    public $total_items;
    public $items_per_page; 
    
    
    /**
     * Adapter constructor
     *
     * @param array $config
     */
    public function __construct($config)
    {
        if (empty($config['total_items'])) {
            throw new \Exception('Paginator requires total_items to be set');
        }
        
        if (empty($config['items_per_page'])) {
            throw new \Exception('Paginator requires items_per_page to be set');
        }

        foreach ($config as $key=>$value) 
        {
        	$this->$key = $value;
        }
    }
    
    /**
     * Set the current page number
     *
     * @param int $page
    */
    public function setCurrentPage($page) 
    {
        $this->current = $page;
        
    	return $this;
    }
    
    /**
     * Returns a slice of the resultset to show in the pagination
     *
     * @return stdClass
    */
    public function getPaginate() 
    {
    	return $this;
    }
        
    /**
     * Extract the current page number from the request
     * 
     * @param string $key
     * @return int|mixed
     */
    public static function findCurrentPage($key='page') 
    {
        $page = \Dsc\Lib\System::instance()->input()->get($key, 'int');
        $page = ($page < 1) ? 1 : $page;
        
        return $page;
    }
    
    public function serve() 
    {
        echo "TODO render Pagination";
    }	
}
?>