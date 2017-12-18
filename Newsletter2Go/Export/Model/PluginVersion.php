<?php

namespace Newsletter2Go\Export;

class PluginVersion
{
    /**
     * Default version, in case composer.json not found.
     *
     * @var string
     */
    private $defaultVersion = '4.1.00';

    /**
     * @return string
     */
    public function getStandardVersion()
    {
        static $version;

        if (!$version) {
            $composerPath = __DIR__ . '/../../composer.json';
            $realPath = realpath($composerPath);
            $version = $this->defaultVersion;

            if (file_exists($realPath)) {
                $config = json_decode(file_get_contents($realPath), true);
                $version = $config['version'];
            }
        }

        return $version;
    }

    /**
     * @return string
     */
    public function getShortVersion()
    {
        return str_replace('.', '', $this->getStandardVersion());
    }
}