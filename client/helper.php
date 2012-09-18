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

    protected function write($string)
    {
        echo $string;
    }

    protected function writeLn($string)
    {
        $this->write("{$string}\n");
    }

    protected function _searchOne($packageName)
    {
        $packageList = $this->_getPackageList();
        $this->writeLn("Searching package {$packageName}...");
        foreach($packageList as $package) {
            if($package['name'] == $packageName) {
                return $package;
            }
        }
        $this->writeLn("Package not found!");
    }

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
                exit(1);
            }
        }
        return $path_to_mpr;
    }

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
                return $result;
            } else {
                $fh = @fopen($url, 'rb', false, $context);
                if($fh == false) {
                    $this->writeLn("");
                    return $this->writeLn("[ERROR] Error opening url. (Package broken?)");
                } else {
                    $this->writeLn("OK!");
                }
                $this->writeLn("Connection opened!");
                $bytesDownloaded = 0;
                $full_destination = "{$destination}{$filename}";
                if(!file_exists($destination)) {
                    mkdir($destination, 0777, true);
                }
                $dest = @fopen($full_destination, 'wb');
                if($dest == false) {
                    return $this->writeLn("[ERROR] Error opening destination path. (Check permissions?)");
                }
                while(!feof($fh)) {
                    $buffer = fread($fh, 4096);
                    if($buffer === false) {
                        return $this->writeLn("[ERROR] Error downloading content!");
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

    protected function _search($input)
    {
        if(empty($input)) {
            return false;
        }
        $packageList = $this->_getPackageList();
        if(!is_array($packageList)) {
            return $this->writeLn("Error resolving repository package list!");
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

    protected function _getPackageList()
    {
        $manifest_raw = self::getConfig()['host'] . self::getConfig()['manifest_filename'];
        $manifest_gz = self::getConfig()['host'] . self::getConfig()['manifest_filename'] . ".gz";
        $data = @$this->_wget($manifest_gz);
        if($data == false) {
            $data = @$this->_wget($manifest_raw);
        } else {
            $data = gzuncompress($data);
        }

        $packages = json_decode($data, 1);
        if(!is_array($packages)) {
            return $this->writeLn("[ERROR] Error loading package list from {$url}");
        }
        return $packages;
    }
}