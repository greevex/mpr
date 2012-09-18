<?php
namespace mpr\server;

class helper
{
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
     * Build mpr package (.phar) by package manifest array
     *
     * @param array $package Package manifest array
     * @return bool Result
     */
    protected function createMprPackage($package)
    {
        try {
            $phar_path = $this->getConfig()['document_root'] . $package['name'] . "/";
            if(!file_exists($phar_path)) {
                @mkdir($phar_path, 0777, true);
            }
            $phar_file = "{$phar_path}{$package['name']}.phar";
            $lib_path = $this->getConfig()['libs_root'] . mb_strtolower($package['name']);

            if(file_exists($phar_file)) {
                $this->writeLn("Package already exists!");
                return true;
            }
            $phar = new \Phar($phar_file);
            $phar->buildFromDirectory($lib_path);
            $phar->setStub($phar->createDefaultStub($package['package']['init']));
            $phar->compressFiles(\Phar::GZ);
            return true;
        } catch(\Exception $e) {
            $this->writeLn("[ERROR] Package `{$package['name']}`: {$e->getMessage()}");
            if(file_exists($phar_path)) {
                rmdir($phar_path);
            }
            return false;
        }
    }

    /**
     * Load manifest by package folder path
     *
     * @param string $fullpath Path to package folder
     * @return bool Result
     */
    protected function loadManifest($fullpath)
    {
        $manifest_data = file_get_contents("{$fullpath}/manifest.mpr.json");
        if($manifest_data === null || $manifest_data === false) {
            $this->writeLn("[ERROR] manifest.mpr.json not found!");
            return false;
        }
        $manifest = json_decode($manifest_data, true);
        if($manifest == null) {
            $this->writeLn("[ERROR] unable to parse manifest.mpr.json!");
            return false;
        }
        return $this->validateManifest($manifest) ? $manifest : false;
    }

    /**
     * Check param to be valid
     *
     * @param array $package Package manifest array
     * @param string $param Param key
     * @param bool $needBeArray Is this param need be an instance of array
     * @return bool Result
     */
    protected function checkParam($package, $param, $needBeArray = false)
    {
        if(!isset($package[$param])) {
            $this->writeLn("`{$param}` parameter is not set");
            return false;
        }
        if($needBeArray && !is_array($package[$param])) {
            return false;
        }
        return true;
    }

    /**
     * Validate current manifest
     *
     * @param array $package Package manifest
     * @return bool Result
     */
    protected function validateManifest($package)
    {
        return (
            $this->checkParam($package, "name") &&
            $this->checkParam($package, "description") &&
            $this->checkParam($package, "package", true) &&
            $this->checkParam($package['package'], "path") &&
            $this->checkParam($package['package'], "init") &&
            $this->checkParam($package['package'], "version") &&
            $this->checkParam($package, "meta", true) &&
            $this->checkParam($package['meta'], "type") &&
            $this->checkParam($package['meta'], "tags") &&
            $this->checkParam($package, "depends", true)
        );
    }

    /**
     * Get package manifest by package name
     *
     * @param string $packageName Package name
     * @return bool|array Result
     */
    protected function getManifest($packageName)
    {
        $lib_path = $this->getConfig()['libs_root'] . mb_strtolower($packageName);

        $manifest = $this->loadManifest($lib_path);
        if($manifest === false) {
            return false;
        }
        return $manifest;
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
}