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
        $encoded_content = json_encode($manifest_data);
        file_put_contents($global_manifest_path, $encoded_content);
        @file_put_contents("{$global_manifest_path}.gz", gzcompress($encoded_content, 9));
        $this->writeLn("Global manifest file generated!");
    }
}