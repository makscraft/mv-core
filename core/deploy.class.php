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

        if(!isset($this -> state['settings']['skip']) || !is_array($this -> state['settings']['skip']))
            $this -> state['settings']['skip'] = [];

        foreach($this -> state['settings']['skip'] as $key => $one)
        {
            $one = $this -> state['settings']['skip'][$key] = preg_replace('/^\//', '', $one);
            $this -> state['settings']['skip'][$key] = preg_replace('/\/$/', '', $one);
        }

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
    public function prepare()
    {
        if(!$this -> connectFTP())
            return null;
        
        $files = ftp_nlist($this -> connection, '-al ./');
        $this -> state['initial_deploy'] = (count($files) === 2 && $files[0] === '.' && $files[1] === '..');

        if(!in_array('deploy', $files))
        {
            ftp_mkdir($this -> connection, './deploy');
            ftp_mkdir($this -> connection, './deploy/backups');
        }

        $deployer = Registry::get('IncludePath').'/deployer.php';
        ftp_put($this -> connection, './deployer.php', $deployer, FTP_BINARY);

        $url = $this -> state['settings']['connection']['domain'].'/deployer.php?action=health';
        $json = json_decode(file_get_contents($url), true);
        
        //Debug::pre($json);

        if(!is_array($json) || !$json['success'])
        {
            return null;
        }

        //Debug::pre(file_get_contents($url));
        //$response = file()

        return $this;
    }

    public function upload(): array
    {
        // if(!$this -> connectFTP())
        //     return [];

        //$data = ftp_rawlist($this -> connection, '.', true);
        
        



        //$files = ftp_nlist($this -> connection, '-al ./');
        $root = Registry::get('IncludePath').'.env';
        //$root = Registry::get('IncludePath').'index.php';
        $root = Registry::get('IncludePath');
        $this -> uploadFileOrDirectory($root, '.');

        //Debug::pre($files);

        return [];
    }

    public function backup(): array
    {
        if(!$this -> state['settings']['backup'])
            return [];

        if(!$this -> connectFTP())
            return [];

        $files = ftp_nlist($this -> connection, '-al ./');
        Debug::pre($files);

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

    public function uploadFileOrDirectory($source_path, $remote_path)
    {
        if(is_dir($source_path))
            $source_path = preg_replace('/\/$/', '', $source_path);

        $is_root = realpath(Registry::get('IncludePath')) === realpath($source_path);        
        $files = scandir($source_path);
        $skip = $this -> state['settings']['skip'];

        foreach($files as $file)
        {
            if($is_root && is_dir($source_path.'/'.$file) && $file !== 'core')
                continue;

            if($file === '.' || $file === '..' || $file === '.git' || $file === '.svn')
                continue;

            $check = Service::removeFileRoot($source_path.'/'.$file);

            if(in_array($check, $skip))
                continue;

            if(is_file($source_path.'/'.$file))
                ftp_put($this -> connection, $remote_path.'/'.$file, $source_path.'/'.$file, FTP_BINARY);
            else if(is_dir($source_path.'/'.$file))
            {
                if(ftp_nlist($this -> connection, $remote_path.'/'.$file) === false)
                    ftp_mkdir($this -> connection, $remote_path.'/'.$file);

                $this -> uploadFileOrDirectory($source_path.'/'.$file, $remote_path.'/'.$file);
            }
        }
    }

    /* SFTP connection */

    private function connectSFTP()
    {

    }
}