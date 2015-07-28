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

    const CONSOLE_LINE_LENGTH = 90;

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

        if (isset($config['scripts'])) {
            // run any 'install' scripts
            $eventDispatcher = new EventDispatcher($package, $this, $this->composer, $this->io);
            $eventDispatcher->dispatchScript('install', true);
        }

        if (isset($config['copy'])) {
            $fileMover = new FileMover($package, $this->io, $this->composer, $this);
            $fileMover->copyFiles($config['copy']);
        }

        $migrationScripts = new MigrationScripts($package, $this->io, $this->composer, $this);
        $migrationScripts->copy();

        if (isset($config['permissions'])) {
            $this->configurePermissions($config['permissions']);
        }

        $this->processGitIgnore();

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

    public function getRootPath()
    {
        return getcwd();
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
        $gitIgnore = new GitIgnore($this->getRootPath() . '/.gitignore');
        $count = $gitIgnore->addFiles($this->pathsToIgnore);
        if ($count) {
            $this->io->write(sprintf('    %d %s added to .gitignore', $count, $count == 1 ? 'path' : 'paths'));
        }
        return $count;
    }

    protected function configurePermissions($permissions)
    {
        $permissionsHelper = new PermissionsHelper($this->getRootPath() . '/.gitignore');
        $count = $permissionsHelper->addFiles($permissions);
        if ($count) {
            $this->io->write(sprintf('    %d permissions %s added', $count, $count == 1 ? 'rule' : 'rules'));
        }
        return $count;
    }

}
