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
    const FILESYSTEM_MIGRATIONS_FOLDER = 'upgrades/migrations/filesystem';

    const CONSOLE_LINE_LENGTH = 90;

    protected $pathsToIgnore = array();
    protected $config = array();
    protected $buildXml;

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
        try {
            $this->reset();

            $config = $this->getConfig($package);

            $this->buildXml = new BuildXml($this->getRootPath() . '/build.xml');

            if (isset($config['scripts'])) {
                // run any 'install' scripts
                $eventDispatcher = new EventDispatcher($package, $this, $this->composer, $this->io);
                $eventDispatcher->dispatchScript('install');
            }

            if (isset($config['copy'])) {
                $fileMover = new FileMover($package, $this->io, $this);
                $fileMover->copyFiles($config['copy']);
            }

            $migrationScripts = new MigrationScripts($package, $this->io, $this->composer, $this);
            $migrationScripts->copy();

            $versionFiles = new VersionFiles($package, $this->io, $this);
            $versionFiles->copy(self::MIGRATIONS_FOLDER);
            $versionFiles->copy(self::FILESYSTEM_MIGRATIONS_FOLDER);

            if (isset($config['permissions'])) {
                $this->configurePermissions($config['permissions']);
            }

            $this->processGitIgnore();

            $this->processBuildXml($config);

        } catch (\Exception $e) {
            $this->io->writeError(sprintf(
                '    Error installing package, in %s line %d',
                $e->getFile(),
                $e->getLine()
            ));
        }
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
     * Reset ready for a new package
     *
     * The same installer instance is used for each packages to be installed, so
     * we need to reset before each to ensure separation.
     * @return [type] [description]
     */
    protected function reset()
    {
        $this->config = array();
        $this->pathsToIgnore = array();
    }

    public function getConfig(PackageInterface $package, $key = null, $fallback = null)
    {
        if (!$this->config) {
            $extra = $package->getExtra();
            if (!isset($extra[self::EXTRA_KEY])) {
                $this->config = array();
            }
            $this->config = $extra[self::EXTRA_KEY];
        }
        if ($key === null) {
            return $this->config;
        } elseif (isset($this->config[$key])) {
            return $this->config[$key];
        } else {
            return $fallback;
        }
    }

    public function addBuildXmlInclude($path)
    {
        $this->buildXml->addInclude($path);
    }

    public function addBuildXmlExclude($path)
    {
        $this->buildXml->addExclude($path);
    }

    protected function processBuildXml($config)
    {
        // add any includes/excludes specified in the composer.json
        if (isset($config['package-include'])) {
            foreach ($config['package-include'] as $include) {
                $this->addBuildXmlInclude($include);
            }
        }
        if (isset($config['package-exclude'])) {
            foreach ($config['package-exclude'] as $exclude) {
                $this->addBuildXmlExclude($exclude);
            }
        }
        $this->buildXml->write();
    }

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
        $permissionsHelper = new PermissionsHelper($this->getRootPath() . '/config/permissions/custom');
        $count = $permissionsHelper->addFiles($permissions);
        if ($count) {
            $this->io->write(sprintf('    %d permissions %s added or modified', $count, $count == 1 ? 'rule' : 'rules'));
        }
        return $count;
    }
}
