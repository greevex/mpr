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

    protected function write($string)
    {
        echo $string;
    }

    protected function writeLn($string)
    {
        $this->write("{$string}\n");
    }
}