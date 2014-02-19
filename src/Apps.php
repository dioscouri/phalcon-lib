<?php 
namespace Dsc\Lib;

class Apps
{
    /**
     * 
     * @param unknown_type $app
     * @return void
     */
    public static function bootstrap($application, $additional_paths=array())
    {
        // bootstrap all apps
        // loop through each child folder (only 1st level) of the /apps folder
        // if a bootstrap.php file exists, require it once
        if (!defined('PATH_ROOT')) {
            throw new \Exception('The "PATH_ROOT" constant must be defined for your application.');
        }
        
        \Dsc\Lib\System::instance( $application->getDI() );
        
        // do the original apps first
        $path = \Dsc\Lib\Filesystem\Path::clean( PATH_ROOT . 'vendor/dioscouri/' );
        if ($folders = \Dsc\Lib\Filesystem\Folder::folders( $path ))
        {
            foreach ($folders as $folder)
            {
                if (file_exists( $path . $folder . '/bootstrap.php' )) {
                    require_once $path . $folder . '/bootstrap.php';
                }
            }
        }
        
        // then do the custom apps
        $path = \Dsc\Lib\Filesystem\Path::clean( PATH_ROOT . 'apps/' );
        if ($folders = \Dsc\Lib\Filesystem\Folder::folders( $path ))
        {
            foreach ($folders as $folder)
            {
                if (file_exists( $path . $folder . '/bootstrap.php' )) {
                    require_once $path . $folder . '/bootstrap.php';
                }
            }
        }
        
        // then do any additional paths
        foreach ($additional_paths as $additional_path)
        {
            $additional_path = \Dsc\Lib\Filesystem\Path::clean( $additional_path . DIRECTORY_SEPARATOR );
                        
            if ($folders = \Dsc\Lib\Filesystem\Folder::folders( $additional_path ))
            {
                foreach ($folders as $folder)
                {
                    if (file_exists( $additional_path . $folder . '/bootstrap.php' )) {
                        require_once $additional_path . $folder . '/bootstrap.php';
                    }
                }
            }
        }
        
    }
}