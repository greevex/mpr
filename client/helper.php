<?php

namespace mpr\client;

class helper
{
    static $mpr_root_filename = ".mprroot";

    /**
     * Get config array
     *
     * @return array|null Null on error
     */
    public function getConfig()
    {
        static $config;
        if($config == null) {
            $config = json_decode(file_get_contents(__DIR__ . "/config.json"), 1);
            if(!is_array($config)) {
                $this->writeLn("Error loading config.json!");
                exit(1);
            }
        }
        return $config;
    }

    /**
     * Write string
     *
     * @param string $string
     */
    protected function write($string)
    {
        echo $string;
    }

    /**
     * Write string and EOL
     *
     * @param string $string
     */
    protected function writeLn($string)
    {
        $this->write("{$string}\n");
    }

    /**
     * Search package by name
     *
     * @param $packageName
     * @return array|null
     */
    protected function _searchOne($packageName)
    {
        $packageList = $this->_getPackageList();
        foreach($packageList as $package) {
            if($package['name'] == $packageName) {
                return $package;
            }
        }
        return null;
    }

    /**
     * Find root path for local mpr repository
     *
     * @return bool|string
     */
    protected function findMe()
    {
        static $path_to_mpr;
        if($path_to_mpr == null) {
            $path_to_mpr = realpath(".");
            $found = false;
            while(!$found && $path_to_mpr != '/') {
                $found = in_array(self::$mpr_root_filename, scandir($path_to_mpr));
                if(!$found) {
                    $path_to_mpr = realpath($path_to_mpr . "/..");
                }
            }
            if(!$found) {
                $this->writeLn("[ERROR] Repository not found! Please try to execute `mpr init` in repository root path!");
                return false;
            }
        }
        return $path_to_mpr;
    }

    /**
     * Get package path
     *
     * @param array $package Package manifest array
     * @param string $pathType Path type. Allowed values: filename,fileurl,destination_file,destination_folder
     * @return string Path
     */
    protected function getPackagePath($package, $pathType)
    {
        switch($pathType) {
            case "filename":
                return "{$package['name']}.phar";
            case "fileurl":
                return $this->getConfig()['host'] . "{$package['name']}/{$package['name']}.phar";
            case "destination_file":
                return "{$this->findMe()}{$package['package']['path']}{$package['name']}.phar";
            case "destination_folder":
                return "{$this->findMe()}{$package['package']['path']}";
        }
    }

    /**
     * Download data from repository with auth
     *
     * @param string $url Url to download from
     * @param string|null $destination Path to download to
     * @return bool Result
     */
    protected function _wget($url, $destination = null)
    {
        $this->write("Receiving {$url}...");
        try {
            $filename = basename($url);
            $auth = base64_encode($this->getConfig()['login'] . ':' . $this->getConfig()['password']);
            $header = ["Authorization: Basic {$auth}"];
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => $header
                ]
            ];
            $context = stream_context_create($opts);
            if($destination == null) {
                $result = file_get_contents($url, false, $context);
                if($result == false) {
                    $this->writeLn("Fail!");
                    return false;
                }
                $this->writeLn("OK!");
                return true;
            } else {
                $fh = @fopen($url, 'rb', false, $context);
                if($fh == false) {
                    $this->writeLn("");
                    $this->writeLn("[ERROR] Error opening url. (Package broken?)");
                    return false;
                } else {
                    $this->writeLn("OK!");
                }
                $bytesDownloaded = 0;
                $full_destination = "{$destination}{$filename}";
                if(!file_exists($destination)) {
                    mkdir($destination, 0777, true);
                }
                $dest = @fopen($full_destination, 'wb');
                if($dest == false) {
                    $this->writeLn("[ERROR] Error opening destination path. (Check permissions?)");
                    return false;
                }
                while(!feof($fh)) {
                    $buffer = fread($fh, 4096);
                    if($buffer === false) {
                        $this->writeLn("[ERROR] Error downloading content!");
                        return false;
                    }
                    $bytesDownloaded += strlen($buffer);
                    fwrite($dest, $buffer);
                    $this->write("\rDownloading content... [{$bytesDownloaded} bytes]");
                }
                $this->writeLn("");
                $this->writeLn("Content downloaded to {$full_destination}!");
                fclose($fh);
                fclose($dest);
                return true;
            }

        } catch(\Exception $e) {
            return false;
        }
    }

    /**
     * Check is package installed in local repository
     *
     * @param array $package Package manifest array
     * @return bool Result
     */
    protected function _installed($package)
    {
        $packageLocalPath = $this->getPackagePath($package, 'destination_file');
        return file_exists($packageLocalPath);
    }

    /**
     * Search packages by regular expression
     *
     * @param string $input Regular expr. (e.g. "tw?tter")
     * @return array|bool
     */
    protected function _search($input)
    {
        if(empty($input)) {
            return false;
        }
        $packageList = $this->_getPackageList();
        if(!is_array($packageList)) {
            $this->writeLn("[ERROR] Error resolving repository package list!");
            return false;
        }

        $matches = [];
        foreach($packageList as $package) {
            if(
                preg_match("/{$input}/ui", $package['name']) > 0 ||
                preg_match("/{$input}/ui", $package['meta']['type']) > 0 ||
                preg_match("/{$input}/ui", $package['meta']['tags']) > 0 ||
                preg_match("/{$input}/ui", implode(',', $package['depends'])) > 0
            ) {
                $matches[] = $package;
            }
        }

        return $matches;
    }

    /**
     * Get package list cache filepath
     *
     * @return string
     */
    protected function _getPackageCacheFileName()
    {
        return '/tmp/packagelist.mpr';
    }

    /**
     * Update package list from remote repository and return it
     *
     * @return bool|string Result
     */
    protected function _updatePackageListAndGetIt()
    {
        $cache_file = $this->_getPackageCacheFileName();
        $this->writeLn("Update package list...");
        $manifest_raw = self::getConfig()['host'] . self::getConfig()['manifest_filename'];
        $manifest_gz = self::getConfig()['host'] . self::getConfig()['manifest_filename'] . ".gz";
        $data = @$this->_wget($manifest_gz);
        if($data == false) {
            $data = @$this->_wget($manifest_raw);
        } else {
            $data = gzuncompress($data);
        }
        if($data !== false) {
            file_put_contents($cache_file, $data);
        }
        return $data;
    }

    /**
     * Return package list array (global manifest)
     *
     * @return array|bool
     */
    protected function _getPackageList()
    {
        static $packages;
        if($packages == null) {
            $cache_file = $this->_getPackageCacheFileName();
            if(file_exists($cache_file)) {
                $filetime = filemtime($cache_file);
                $next_cache_update = $filetime + 60;
                if($next_cache_update > time()) {
                    $this->writeLn("Loading package list from cache. " . ($next_cache_update - time()) . " seconds before next update.");
                    $data = file_get_contents($cache_file);
                } else {
                    $data = $this->_updatePackageListAndGetIt();
                }
            } else {
                $data = $this->_updatePackageListAndGetIt();
            }

            $packages = json_decode($data, 1);
            if(!is_array($packages)) {
                $this->writeLn("[ERROR] Error loading package list!");
                return false;
            }
        }
        return $packages;
    }
}