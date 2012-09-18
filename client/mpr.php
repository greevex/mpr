<?php

namespace mpr\client;

class mpr
extends helper
{
    /**
     * Strict search for one package and install it
     *
     * @param string $packageName Package name
     */
    public function install($packageName)
    {
        if($this->_installed($packageName)) {
            $this->writeLn("Package {$packageName} already installed!");
            return;
        }
        $root_path = $this->findMe();
        $package = $this->_searchOne($packageName);
        if(!$package) {
            return;
        }
        if(count($package['depends'])) {
            $this->writeLn("=== Warning! ===");
            $this->writeLn("This package has dependencies: " . implode(', ', $package['depends']));
            $this->writeLn("Package would not work without installed dependencies.");
            $this->writeLn("If you don't want to install dependencies you can not install this package!");
            $readline = trim(readline("Do you want to install all dependencies? [y/n]: "));
            $install_dependencies = strtolower($readline) == 'y';
            var_dump($install_dependencies, $readline);
            $this->writeLn("Installing dependencies...");
            foreach($package['depends'] as $dependency) {
                $this->writeLn("Checking {$dependency}...");
                $this->install($dependency);
            }
        }
        $url = $this->getConfig()['host'] . "{$package['name']}/{$package['name']}.phar";
        $this->_wget($url, $root_path.$package['package']['path']);
        $this->writeLn("Installed!");
    }

    protected function _installed($packageName)
    {
        $package = $this->_searchOne($packageName);
        $packageLocalPath = $this->getPackagePath($package, 'destination_file');
        return file_exists($packageLocalPath);
    }

    public function remove($packageName)
    {
        if($this->_installed($packageName)) {
            $package = $this->_searchOne($packageName);
            $packageLocalPath = $this->getPackagePath($package, 'destination_file');
            $this->writeLn("Removing {$package['name']}...");
            exec("rm -rf {$packageLocalPath}");
            @unlink($packageLocalPath);
            $this->writeLn("Package was removed!");
            exit;
        }
        $this->writeLn("Package {$packageName} not installed! Nothing to remove!");
    }

    public function init()
    {
        $path = realpath(".");
        $this->writeLn("[mpr] Initializing mpr repository...");
        $fullpath = "{$path}/" . self::$mpr_root_filename;
        if(file_exists($fullpath)) {
            $this->writeLn("[mpr] Repository already initialized! Nothing to do :)");
            return;
        }
        touch($fullpath);
        $this->writeLn("[mpr] Repository was initialized! Now you can install packages!");
        return true;
    }

    public function search($pattern)
    {
        $packages = $this->_search($pattern);
        if($packages === false) {
            $this->writeLn("Please check your search string...");
            return;
        }
        $name_field = 30;
        $version_field = 10;
        $description_field = 60;
        if(is_array($packages)) {
            $count = count($packages);
            $format = "%1\${$name_field}s | %2\${$version_field}s | %3\${$description_field}s";
            $this->writeLn(sprintf($format, str_repeat('=', $name_field), str_repeat('=', $version_field), str_repeat('=', $description_field)));
            $this->writeLn(sprintf($format, "-NAME-", "-VERSION-", "-DESCRIPTION-"));
            $this->writeLn(sprintf($format, str_repeat('-', $name_field), str_repeat('-', $version_field), str_repeat('-', $description_field)));
            foreach($packages as $package) {
                $this->writeLn(sprintf($format, $package['name'], $package['package']['version'], $package['description']));
            }
            $this->writeLn(sprintf($format, str_repeat('=', 6 - strlen($count)) . " Total: {$count} " . str_repeat('=', ($name_field/2)), str_repeat('=', $version_field), str_repeat('=', $description_field)));
        }
        print "---\n";
    }

    public function update($packageName = null)
    {

    }

    public function reindex()
    {

    }

    public function help()
    {
        $this->writeLn("---");
        $this->writeLn("- \\m/ Package Repository Manager");
        $this->writeLn("- allowed method: search, install, update, remove");
        $this->writeLn("- usage: mpr search grunge && mpr install grunge");
        $this->writeLn("---");
    }
}