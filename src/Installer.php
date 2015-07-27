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

}
