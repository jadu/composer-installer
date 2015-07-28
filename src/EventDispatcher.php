<?php

namespace Jadu\Composer;

use Composer\EventDispatcher\EventDispatcher as ComposerEventDispatcher;
use Composer\EventDispatcher\Event;
use Composer\Package\PackageInterface;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Util\ProcessExecutor;

class EventDispatcher extends ComposerEventDispatcher {

    public function __construct(PackageInterface $package, Composer $composer, IOInterface $io, ProcessExecutor $process = null)
    {
        $this->package = $package;
        parent::__construct($composer, $io, $process);
    }

    /**
     * Finds all listeners defined as scripts in this package
     *
     *
     * @param  Event $event Event object
     * @return array Listeners
     */
    protected function getScriptListeners(Event $event)
    {
        $extra = $this->package->getExtra();
        if (isset($extra[Installer::EXTRA_KEY]) && isset($extra[Installer::EXTRA_KEY]['scripts'])) {
            $scripts = $extra[Installer::EXTRA_KEY]['scripts'];
        }

        if (empty($scripts[$event->getName()])) {
            return array();
        }

        parent::getScriptListeners($event);

        return $scripts[$event->getName()];
    }

}