<?php
/**
 * 
 */
class Deploy
{
    private $state = [
        'type' => '',
        'env' => '',
        'initial_deploy' => false,
        'processed' => [],
        'errors' => [],
        'settings' => []
    ];

    public function __construct(string $type, string $env = 'production')
    {
        $this -> state['type'] = $type;
        $this -> state['env'] = $env;
    }

    public function analyzeLocalHost(): bool
    {
        $path = Registry::get('IncludePath').'config/deploy.php';

        if(!is_file($path))
        {
            $this -> state['errors'][] = 'Deployment local configuration file not found: '.$path;
            return false;
        }

        $settings = include_once($path);
        
        if(!array_key_exists($this -> state['env'], $settings))
        {
            $this -> state['errors'][] = 'Configuration settings list not found in config/deploy.php file: '.$this -> state['env'];
            return false;
        }
        
        $this -> state['settings'] = $settings[$this -> state['env']];

        foreach($this -> state['settings']['connection'] as $key => $value)
            if(!$value)
                $this -> state['errors'][] = 'Configuration settings is not set: '.$key;
        
        if(count($this -> state['errors']))
            return false;

        return true;
    }

    public function analyzeRemoteHost(): bool
    {
        $path = Registry::get('IncludePath');
        
        if(!is_writable($path))
            $this -> state['errors'][] = 'Application root directory is not writable: '.$path;

        $files = scandir($path);
        $this -> state['initial_deploy'] = (count($files) === 2 && $files[0] === '.' && $files[1] === '..');
        
        $folders = ['config', 'core', 'views', 'media', 'models', 'plugins', 'customs'];

        foreach($folders as $folder)
            if(is_dir($path.$folder) && !is_writable($path.$folder))
                $this -> state['errors'][] = 'Application directory is not writable: '.$path.$folder;

        return true;
    }

    /* Main API commands */

    /**
     * 
     */
    public function upload(): array
    {
        return [];
    }

    public function backup(): array
    {
        //Installation::copyDirectory();

        return [];
    }

    public function rollback(): array
    {
        return [];
    }

    public function migrate(): array
    {
        return [];
    }

    /* FTP connection */

    private function connectFTP()
    {

    }

    /* SFTP connection */


}