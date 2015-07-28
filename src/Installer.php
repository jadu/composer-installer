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

    /**
     * {@inheritDoc}
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        parent::install($repo, $package);

        // getInstallPath
        // getPackageBasePath

        print is_a($package,'Composer\\Package\\RootPackage') ? 'is root' : 'not root';
        print "\n\n";
        $extra = $package->getExtra();
        var_dump($extra);

    }

    /**
     * {@inheritDoc}
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        parent::update($repo, $initial, $target);
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        parent::uninstall($repo, $package);
    }


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
