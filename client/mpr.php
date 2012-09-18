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
    public function install($packageName, $force = false)
    {
        $installed = [];
        $root_path = $this->findMe();
        $this->writeLn("Searching package {$packageName}...");
        $package = $this->_searchOne($packageName);
        if(!$package) {
            $this->writeLn("[ERROR] Package {$packageName} not found!");
            return false;
        }
        $this->writeLn("Checking local packages...");
        if($this->_installed($package)) {
            $this->writeLn("[WARNING] Package {$package['name']} already installed!");
            return false;
        }
        $this->writeLn("Installing package...");
        if(!$force && count($package['depends'])) {
            $this->writeLn("=== Warning! ===");
            $this->writeLn("Package would not work without installed dependencies.");
            $this->writeLn("If you don't want to install dependencies you can not install this package!");
            $this->writeLn("Dependencies: " . implode(', ', $package['depends']));
            $readline = trim(readline("Do you want to install all dependencies? [y/n]: "));
            $install_dependencies = strtolower($readline) == 'y';
            if(!$install_dependencies) {
                $this->writeLn("[WARNING] Unable to install package without dependencies!");
                return false;
            }
            $this->writeLn("Installing dependencies...");
            foreach($package['depends'] as $dependency) {
                $this->writeLn("Checking {$dependency}...");
                if($this->install($dependency, true)) {
                    $installed[] = $dependency;
                }
            }
        }
        $url = $this->getConfig()['host'] . "{$package['name']}/{$package['name']}.phar";
        $this->_wget($url, $root_path . $package['package']['path']);
        $installed[] = $package['name'];
        if(!$force) {
            $this->writeLn("Installed packages: " . implode(', ', $installed));
        } else {
            $this->writeLn("Package installed!");
        }
        return true;
    }

    public function update()
    {
        $this->_updatePackageListAndGetIt();
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
        if(scandir($path) != ['.', '..']) {
            $this->writeLn("[ERROR] Current directory not empty!");
            return false;
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

    public function help()
    {
        $this->writeLn("---");
        $this->writeLn("- \\m/ Package Repository Manager");
        $this->writeLn("- allowed method: search, install, update, remove");
        $this->writeLn("- usage: mpr search grunge && mpr install grunge");
        $this->writeLn("---");
    }
}