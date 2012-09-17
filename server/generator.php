<?php
namespace mpr\server;

class generator
extends helper
{
    /**
     * ReIndex repository
     */
    public function reindex()
    {
        if(!file_exists($this->getConfig()['document_root'])) {
            $this->writeLn("[ERROR] Document root doesn't exists in [{$this->getConfig()['document_root']}]! Please set up it first!");
            return false;
        }
        $this->writeLn("Reading libs path...");
        $files = scandir($this->getConfig()['libs_root']);
        if(!is_array($files)) {
            $this->writeLn("[ERROR] Error parsing files from document root. (Check permissions?)");
            return false;
        }
        $manifest_data = [];
        $this->writeLn("Found " . count($files) . " files...");
        foreach($files as $packageName) {
            if(strpos($packageName, '.') === 0) {
                continue;
            }
            $this->writeLn("Processing package {$packageName}...");
            $package_manifest = $this->getManifest($packageName);
            if($package_manifest == false) {
                $this->writeLn("[ERROR] Error reading manifest for package {$packageName}");
                continue;
            }
            $this->writeLn("Creating phar package {$packageName}...");
            $result = $this->createMprPackage($package_manifest);
            if($result) {
                $manifest_data[$packageName] = $package_manifest;
                $this->writeLn("Package {$packageName} was created!");
            } else {
                $this->writeLn("[ERROR] Error creating mpr package {$packageName}!");
            }
        }
        $global_manifest_path = $this->getConfig()['document_root'].$this->getConfig()['manifest_filename'];
        file_put_contents($global_manifest_path, json_encode($manifest_data));
        $this->writeLn("Global manifest file generated!");
    }

    protected function createMprPackage($package)
    {
        try {
            $phar_path = $this->getConfig()['document_root'] . "{$package['name']}/";
            if(!file_exists($phar_path)) {
                @mkdir($phar_path, 0777, true);
            }
            $phar_file = "{$phar_path}{$package['name']}.phar";
            $lib_path = $this->getConfig()['libs_root'] . $package['name'];

            if(file_exists($phar_file)) {
                \Phar::unlinkArchive($phar_file);
            }
            $phar = new \Phar($phar_file);
            $phar->buildFromDirectory($lib_path);
            $phar->setStub($phar->createDefaultStub($package['package']['init']));
            $phar->compressFiles(\Phar::GZ);
            return true;
        } catch(\Exception $e) {
            $this->writeLn("[ERROR] Package `{$package['name']}`: {$e->getMessage()}");
            return false;
        }
    }

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


    protected function validateManifest($package)
    {
        $this->checkParam($package, "name");
        $this->checkParam($package, "description");
        $this->checkParam($package, "package", true);
        $this->checkParam($package['package'], "path");
        $this->checkParam($package['package'], "init");
        $this->checkParam($package['package'], "version");
        $this->checkParam($package, "meta", true);
        $this->checkParam($package['meta'], "type");
        $this->checkParam($package['meta'], "tags");
        $this->checkParam($package, "depends", true);
        return true;
    }

    public function getManifest($packageName)
    {
        $lib_path = $this->getConfig()['libs_root'] . "{$packageName}";

        $manifest = $this->loadManifest($lib_path);
        if($manifest === false) {
            return false;
        }
        return $manifest;
    }
}