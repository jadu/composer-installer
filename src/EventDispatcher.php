<?php

namespace Jadu\Composer;

use Composer\EventDispatcher\EventDispatcher as ComposerEventDispatcher;
use Composer\EventDispatcher\Event;
use Composer\Package\PackageInterface;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Util\ProcessExecutor;

class EventDispatcher extends ComposerEventDispatcher {

    protected $installer;

    public function __construct(PackageInterface $package, Installer $installer, Composer $composer, IOInterface $io, ProcessExecutor $process = null)
    {
        $this->package = $package;
        $this->installer = $installer;
        parent::__construct($composer, $io, $process);
    }

    /**
     * Dispatch a script event.
     *
     * Overrides Composer's EventDispatcher::dispatchScript to use an overriden ScriptEvent which contains
     * the package and installer instances.
     *
     * @param  string $eventName      The constant in ScriptEvents
     * @param  bool   $devMode
     * @param  array  $additionalArgs Arguments passed by the user
     * @param  array  $flags          Optional flags to pass data not as argument
     * @return int    return code of the executed script if any, for php scripts a false return
     *                               value is changed to 1, anything else to 0
     */
    public function dispatchScript($eventName, $devMode = false, $additionalArgs = array(), $flags = array())
    {
        return $this->doDispatch(new ScriptEvent($eventName, $this->composer, $this->io, $this->package, $this->installer, $devMode, $additionalArgs, $flags));
    }

    protected function doDispatch(Event $event)
    {
        $listeners = $this->getListeners($event);

        $this->io->write('    Running scripts for ' . $this->package->getPrettyName() . '…' . \PHP_EOL . '    ');

        ob_start(array($this, 'ob_process'), 2);
        ob_implicit_flush(true);
        $return = parent::doDispatch($event);
        ob_end_flush();

        return $return;
    }

    /**
     * Indent output
     * @param  string $output
     * @return string
     */
    public function ob_process($input)
    {
        $output = array();
        $lines = explode("\n", $input);
        foreach ($lines as $line) {
            $output[] = '    ' . $line;
        }
        return implode("\n", $output);
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

        if ($this->loader) {
            $this->loader->unregister();
        }

        $generator = $this->composer->getAutoloadGenerator();
        $packages = $this->composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();
        $packageMap = $generator->buildPackageMap($this->composer->getInstallationManager(), $this->package, $packages);
        $map = $generator->parseAutoloads($packageMap, $this->package);
        $this->loader = $generator->createLoader($map);
        $this->loader->register();

        $eventScripts = $scripts[$event->getName()];
        $packageBasePath = $this->installer->getPackageBasePath($this->package);

        foreach ($eventScripts as &$script) {
            // for any shell scripts, first cd into package dir
            if (is_string($script) && !$this->isPhpScript($script)) {
                $script = 'cd ' . escapeshellarg($packageBasePath) . ' && ' . $script;
            }
        }

        return $eventScripts;
    }

}
