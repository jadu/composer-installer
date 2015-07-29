<?php

namespace Jadu\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Script\Event as BaseEvent;
use Composer\Package\PackageInterface;

/**
 * Extends the Script Event class to attach $installer and $package for scripts to access
 */
class ScriptEvent extends BaseEvent
{

    protected $installer;
    protected $package;

    public function __construct($name, Composer $composer, IOInterface $io, PackageInterface $package, Installer $installer, $devMode = false, array $args = array(), array $flags = array())
    {
        $this->installer = $installer;
        $this->package = $package;
        parent::__construct($name, $composer, $io, $devMode, $args, $flags);
    }

    public function getInstaller()
    {
        return $this->installer;
    }

    public function getPackage()
    {
        return $this->package;
    }

}
