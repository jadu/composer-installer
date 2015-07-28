<?php

namespace Jadu\Composer;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Repository\InstalledRepositoryInterface;

/**
 * Installer for modules, widgets, etc for Jadu CMS
 */
class Installer extends LibraryInstaller
{

    // key within the composer.json extra array
    const EXTRA_KEY = 'jadu-install';

    const MIGRATIONS_FOLDER = 'upgrades/migrations';

    protected $pathsToIgnore = array();

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return in_array(strtolower($packageType), array(
            'jadu-module',
            'jadu-widget',
            'jadu-supplement',
        ));
    }

    protected function doStuff($repo, $package)
    {
        $extra = $package->getExtra();
        // $rootExtra = $this->composer->getPackage()->getExtra();

        if (!isset($extra[self::EXTRA_KEY])) {
            return;
        }

        $config = $extra[self::EXTRA_KEY];

        if (isset($config['copy'])) {
            $fileMover = new FileMover($this->io, $this->composer, $this);
            $fileMover->copyFiles($config['copy']);
        }

        if (isset($config['scripts'])) {
            // run any 'install' scripts
            $eventDispatcher = new EventDispatcher($package, $this->composer, $this->io);
            $eventDispatcher->dispatchScript('install', true);
        }

        $migrationScripts = new MigrationScripts($this, $this->io);
        $migrationScripts->copy();

        die('dying so we don\'t complete the install');
    }

    /**
     * {@inheritDoc}
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        parent::install($repo, $package);
        $this->doStuff($repo, $package);
    }

    /**
     * {@inheritDoc}
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        parent::update($repo, $initial, $target);
        $this->doStuff($repo, $target);
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        parent::uninstall($repo, $package);
    }

    /**************************************************************************/

    public function getInstallPath(PackageInterface $package)
    {
        $targetDir = $package->getTargetDir();

        return $this->getPackageBasePath($package) . ($targetDir ? '/'.$targetDir : '');
    }

    public function getPackageBasePath(PackageInterface $package)
    {
        $this->initializeVendorDir();

        return ($this->vendorDir ? $this->vendorDir.'/' : '') . $package->getPrettyName();
    }

    /**************************************************************************/

    /**
     * Record a path as to be added to .gitignore — will be batch added by processGitIgnore
     * @param string $path Path relative to install dir
     */
    public function addToGitIgnore($path)
    {
        $this->pathsToIgnore[] = $path;
    }

    protected function processGitIgnore()
    {
        $gitIgnore = new GitIgore($this->getInstallPath() . '/.gitignore');
        $count = $gitIgnore->addFiles($this->pathsToIgnore);
        if ($count) {
            $this->io->write(sprintf('    %d %s added to .gitignore', $count, $count == 1 ? 'path' : 'paths'));
        }
        return $count;
    }

}
