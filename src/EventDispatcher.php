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
        foreach (explode("\n", $input) as $line) {
            if (mb_strlen($line) > Installer::CONSOLE_LINE_LENGTH) {
                $line = implode("\n    ", str_split($line, $lineLength));
            }
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

        parent::getScriptListeners($event);

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
