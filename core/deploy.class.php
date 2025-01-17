<?php

use FTP\Connection;

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

    private $connection;

    public function __construct(string $type, string $env = 'production')
    {
        $this -> state['type'] = $type;
        $this -> state['env'] = $env;
    }

    public function __destruct()
    {
        if(is_object($this -> connection) && $this -> connection instanceof Connection)
            ftp_close($this -> connection);
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
        if(!$this -> connectFTP())
            return [];

        //$data = ftp_rawlist($this -> connection, '.', true);
        $files = ftp_nlist($this -> connection, '-al ./');
        $this -> state['initial_deploy'] = (count($files) === 2 && $files[0] === '.' && $files[1] === '..');

        if(!in_array('deploy', $files))
        {
            ftp_mkdir($this -> connection, './deploy');
            ftp_mkdir($this -> connection, './deploy/backups');
        }

        //$files = ftp_nlist($this -> connection, '-al ./');
        $root = Registry::get('IncludePath').'.env';
        //$root = Registry::get('IncludePath').'index.php';
        $root = Registry::get('IncludePath');
        $this -> uploadFileOrDirectory($root);

        //Debug::pre($files);

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

    public function connectFTP(): bool
    {
        try{
            $host = $this -> state['settings']['connection']['host'];
            $port = $this -> state['settings']['connection']['port'];
            $login = $this -> state['settings']['connection']['login'];
            $password = $this -> state['settings']['connection']['password'];

            if(false === $connection = ftp_connect($host, $port, 10))
                throw new Exception('Unable to connect to host: '.$host);
            else if(true !== ftp_login($connection,  $login,  $password))
                throw new Exception('Unable to login into host '.$host.' with login: '.$login);
        }
        catch(Exception $error)
        {
            $this -> state['errors'][] = $error -> getMessage();
        }

        if(is_object($connection) && $connection instanceof Connection)
        {
            ftp_pasv($connection, true);
            $this -> connection = $connection;

            return true;
        }

        return false;
    }

    public function uploadFileOrDirectory($path)
    {
        $base = basename($path);
        $is_root = realpath(Registry::get('IncludePath')) === realpath($path);

        if($base === '.git' || $base === '.svn')
            return;

        //Debug::pre(realpath(Registry::get('IncludePath')));
        //Debug::pre($is_root);

        if(is_file($path))
        {
            $remote = Service::removeFileRoot($path);
            ftp_put($this -> connection, './'.$remote, $path, FTP_BINARY);
        }
        else if(is_dir($path))
        {
            $files = scandir($path);
            $remote_data = ftp_nlist($this -> connection, './');
            //Debug::pre($files);

            foreach($files as $file)
            {
                if($is_root && is_dir($path.$file) && $file !== 'core')
                    continue;

                if($file !== '.' && $file !== '..')
                    if(is_dir($path.$file))
                    {
                        $remote = Service::removeFileRoot($path.$file);
                        Debug::pre($remote);

                        if(!in_array($file, $remote_data))
                            ftp_mkdir($this -> connection, './'.$remote);

                        foreach(scandir($path.$file) as $one)
                        {
                            //Debug::pre($path.$file.'/'.$one.'/');
                            $this -> uploadFileOrDirectory($path.$file.'/'.$one);
                        }
                        
                    }
                    else if(is_file($path.$file))
                    {
                        $this -> uploadFileOrDirectory($path.$file);
                    }
                
                // $remote = Service::removeFileRoot($path.$file);
                // Debug::pre($remote);
                //Debug::pre($path.$file);
                //$this -> uploadFileOrDirectory($path.$file);
            }
                // if($file !== '.' && $file !== '..')
                //     if(is_dir($path.$file))
                //     {

                //     }
                //     else if(is_file($path.$file))
                //     {

                //     }

            // {
            //     if(is_file($file))
            //         ftp_put($this -> connection, $file, $path.$file, FTP_BINARY);
            // }
        }

        //$files = scandir($path);
        //Debug::pre($files);
    }

    /* SFTP connection */

    private function connectSFTP()
    {

    }
}