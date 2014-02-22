<?php 
namespace Dsc\Lib;

class Theme extends \Phalcon\Mvc\View\Simple
{
    protected $dsc_theme = array(
    	'themes' => array(              // themes are style sets for the entire application
            'current' => null,
            'paths' => array()        	
        ),
        'variants' => array(            // a different version of the same theme
            'current' => 'index.php'
        ),                    
        'views' => array(               // display related to a controller action, or just a block of html
            'current' => null,
            'paths' => array()
        ),
        'buffers' => array()
    );
    
    public function registerEngine(array $engine)
    {
    	if (empty($this->_engines)) {
    	    $default = array(
                ".phtml" => 'Phalcon\Mvc\View\Engine\Php'
    	    );
    	    $array = $engine + $default;
    	} else {
    	    $array = $engine + $this->_engines; 
    	}
    	
        $this->registerEngines( $array );
    }
    
    /**
     * Register the path for a theme
     * 
     * @param unknown $path
     * @param string $name
     */
    public function registerThemePath( $path, $name ) 
    {
        // TODO str_replace(\\ with /)
        // TODO ensure that the path has a trailing slash
        // TODO ensure that the path exists
        // TODO ensure that the path has an index.php in it
        
        \Dsc\Lib\ArrayHelper::set($this->dsc_theme, 'themes.paths.' . $name, $path);

        return $this;
    }
    
    /**
     * Register a view path
     *
     * @param unknown $path
     * @param string $key
     */
    public function registerViewPath( $path, $key )
    {
        // str_replace(\\ with /)
        $path = str_replace("\\", "/", $path);
        // TODO ensure that the path has a trailing slash
        // TODO ensure the path exists
        
        \Dsc\Lib\ArrayHelper::set($this->dsc_theme, 'views.paths.' . $key, $path);
        
        return $this;
    }
    
    /**
     * Renders a theme, template, and view, defaulting to the currently set theme if none is specified
     */
    public function renderTheme( $view, array $params=null, $theme_name=null )
    {
        // TODO Render any mini-modules (blocks, or whatever we're calling them)
        /*
        if (class_exists( '\\Modules\\Factory' ))
        {
            // Render the requested modules
            foreach ( $this->template_tags as $full_string => $args )
            {
                if (in_array( strtolower( $args['type'] ), array(
                                'modules' 
                ) ) && ! empty( $args['name'] ))
                {
                    // get the requested module position content
                    $content = \Modules\Factory::render( $args['name'], \Base::instance()->get( 'PARAMS.0' ) );
                    $this->setContents( $content, $args['type'], $args['name'] );
                }
            }
        }
        */
                
        // then render the view, the most precise of the parts
        // do it before the messages
        $view_string = $this->renderView( $view, $params );
        
        // render the system messages, right before the theme
        ob_start();
        $this->getDI()->getShared('flashSession')->output();
        $messages = ob_get_contents();
        ob_end_clean();
        $this->setBuffer( $messages, 'system.messages' );
        
        // and replace the tags in the view with their appropriate buffers
        $view_tags = $this->getTags( $view_string );
        $view_string = $this->replaceTagsWithBuffers( $view_string, $view_tags );
        $this->setBuffer( $view_string, 'view' );
        
        // Finally render the theme, replacing any of its tags with the appropriate buffers
        // TODO Before loading the variant file, ensure it exists.  If not, load index.php or throw a 500 error
        $theme = $this->loadFile( $this->getThemePath( $this->getCurrentTheme() ) . $this->getCurrentVariant() );
        $theme_tags = $this->getTags( $theme );
        $string = $this->replaceTagsWithBuffers( $theme, $theme_tags );
        
        return $string;
    }
    
    public function renderView( $view, array $params=null ) 
    {
        $string = null;
        
        $view = str_replace("\\", "/", $view);
        $pieces = \Dsc\Lib\String::split(str_replace(array("::", ":"), "|", $view));
        
        // Overrides!
        // an overrides folder exists in this theme, let's check for the presence of an override for the requested view file
        $dir = \Dsc\Lib\Filesystem\Path::clean( $this->getThemePath( $this->getCurrentTheme() ) . "Overrides/" );
        if ($dir = \Dsc\Lib\Filesystem\Path::real($dir)) 
        {
            if (count($pieces) > 1)
            {
                // we're looking for a specific view (e.g. Blog/Site/View::posts/category)
                $view_string = $pieces[0];
                $requested_file = $pieces[1];
                $requested_folder = (dirname($pieces[1]) == ".") ? null : dirname($pieces[1]);
                $requested_filename = basename($pieces[1]);
            } 
                else 
            {
            	// (e.g. posts/category.php) that has been requested, so look for it in the overrides dir
                $view_string = null;
                $requested_file = $pieces[0];
                $requested_folder = (dirname($pieces[0]) == ".") ? null : dirname($pieces[0]);
                $requested_filename = basename($pieces[0]);                
            }
            
            $path = \Dsc\Lib\Filesystem\Path::clean( $dir . "/" . $view_string . "/" . $requested_folder . "/" );
            if ($path = \Dsc\Lib\Filesystem\Path::real($path))
            {
                $path_pattern = $path . $requested_filename . ".*";
                if ($matches = glob( $path_pattern ))
                {
                    $this->setViewsDir( \Dsc\Lib\Filesystem\Path::clean( \Dsc\Lib\Filesystem\Path::real($dir . "/" . $view_string) . "/" ) );
                    $string = parent::render( $requested_file, $params );
                }
            }
        }
        
        // if the overrides section above has set $string, then return it, otherwise continue
        if (!is_null($string)) {
            return $string;
        }
        
        if (count($pieces) > 1) {
            // we're looking for a specific view (e.g. Blog/Site/View::posts/category)            
            // $view is a specific app's view/template.php, so try to find & render it
            $view_string = $pieces[0];
            $requested_file = $pieces[1];
            
            $view_dir = $this->getViewPath( $view_string );
            $this->setViewsDir( $view_dir );
            /*
            \FB::log('specific view, no overrides');            
            \FB::log($view_string);
            \FB::log($requested_file);
            \FB::log($view_dir);
            \FB::log(get_object_vars($this));
            */
            
            //echo \Dsc\Lib\Debug::dump( get_object_vars($this) );
            
            $string = parent::render( $requested_file, $params );            
        }        
        
        if (is_null($string)) {
            $string = parent::render( $view, $params );
        }

        return $string;
    }
    
    /**
     * Sets the theme to be used for the current rendering, but only if it has been registered.
     * if a path is provided, it will be registered.
     * 
     * @param unknown $theme
     */
    public function setTheme( $theme, $path=null )
    {
        if ($path) 
        {
        	$this->registerThemePath($path, $theme);
        }
        
        if (\Dsc\Lib\ArrayHelper::exists($this->dsc_theme, 'themes.paths.' . $theme)) {
            \Dsc\Lib\ArrayHelper::set($this->dsc_theme, 'themes.current', $theme);
        }
        
        return $this;
    }
    
    public function setVariant( $name ) 
    {
        $filename = $name;
        $ext = substr($filename, -4);
        if ($ext != '.php') {
            $filename .= '.php';
        }
        
        // TODO ensure that the variant filename exists in the theme folder?        
        \Dsc\Lib\ArrayHelper::set($this->dsc_theme, 'variants.current', $filename);
        
        return $this;
    }
    
    /**
     * Gets the current set theme
     */
    public function getCurrentTheme()
    {
        return \Dsc\Lib\ArrayHelper::get($this->dsc_theme, 'themes.current');
    }

    /**
     * Gets the current set variant
     */
    public function getCurrentVariant()
    {
        return \Dsc\Lib\ArrayHelper::get($this->dsc_theme, 'variants.current');
    }
    
    /**
     * Gets the current set theme
     */
    public function getCurrentView()
    {
        return \Dsc\Lib\ArrayHelper::get($this->dsc_theme, 'views.current');
    }
    
    /**
     * Gets a theme's path by theme name
     */
    public function getThemePath( $name )
    {
        return \Dsc\Lib\ArrayHelper::get($this->dsc_theme, 'themes.paths.'.$name);
    }
    
    /**
     * Gets a view's path by name
     */
    public function getViewPath( $name )
    {
        return \Dsc\Lib\ArrayHelper::get($this->dsc_theme, 'views.paths.'.$name);
    }
    
    /**
     * Gets all registered themes
     * 
     * @return array
     */
    public function getThemes()
    {
        $return = (array) \Dsc\Lib\ArrayHelper::get($this->dsc_theme, 'themes.paths');
        
        return $return;
    }

    /**
     * Return any tmpl tags found in the string
     * 
     * @return \Dsc\Lib\Theme
     */
    public function getTags( $file )
    {
        $matches = array();
        $tags = array();
        
        if (preg_match_all( '#<tmpl\ type="([^"]+)" (.*)\/>#iU', $file, $matches ))
        {
            $count = count( $matches[0] );
            for($i = 0; $i < $count; $i ++)
            {
                $type = $matches[1][$i];
                $attribs = empty( $matches[2][$i] ) ? array() : $this->parseAttributes( $matches[2][$i] );
                $name = isset( $attribs['name'] ) ? $attribs['name'] : null;
                
                $tags[$matches[0][$i]] = array(
                                'type' => $type,
                                'name' => $name,
                                'attribs' => $attribs 
                );
            }
        }
        
        return $tags;
    }

    /**
     * Method to extract key/value pairs out of a string with XML style attributes
     *
     * @param string $string
     *            String containing XML style attributes
     * @return array Key/Value pairs for the attributes
     */
    public static function parseAttributes( $string )
    {
        $attr = array();
        $retarray = array();
        
        preg_match_all( '/([\w:-]+)[\s]?=[\s]?"([^"]*)"/i', $string, $attr );
        
        if (is_array( $attr ))
        {
            $numPairs = count( $attr[1] );
            for($i = 0; $i < $numPairs; $i ++)
            {
                $retarray[$attr[1][$i]] = $attr[2][$i];
            }
        }
        
        return $retarray;
    }
    
    public function replaceTagsWithBuffers( $file, array $tags )
    {
        $replace = array();
        $with = array();
    
        foreach ($tags as $full_string => $args)
        {
            $replace[] = $full_string;
            $with[] = $this->getBuffer($args['type'], $args['name']);
        }
    
        return str_replace($replace, $with, $file);
    }
    

    public function loadFile( $path )
    {
        //extract(\Dsc\Lib\System::hive());
        
        ob_start();
        require $path;
        $file_contents = ob_get_contents();
        ob_end_clean();
        
        return $file_contents;
    }
    
    public function setBuffer( $contents, $type, $name=null )
    {
        if (empty($name)) {
            $name = 0; 
        }
        
        \Dsc\Lib\ArrayHelper::set($this->dsc_theme, 'buffers.' . $type . "." . $name, $contents);
        
        return $this;
    }
    
    public function getBuffer( $type, $name=null )
    {
        if (empty($name)) {
            $name = 0; 
        }
    
        return \Dsc\Lib\ArrayHelper::get($this->dsc_theme, 'buffers.' . $type . "." . $name );
    }
}