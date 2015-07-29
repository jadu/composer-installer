<?php

namespace Jadu\Composer;

use Composer\Package\PackageInterface;
use Composer\IO\IOInterface;
use Composer\Composer;

/**
 * Copy any *_VERSION files from the root of the package into the root
 *
 * These will then be revealed in the Control Centre
 */
class VersionFiles {

    protected $package;
    protected $io;
    protected $installer;

    public function __construct(PackageInterface $package, IOInterface $io, Installer $installer)
    {
        $this->package = $package;
        $this->io = $io;
        $this->installer = $installer;
    }

    /**
     * Copy any *_VERSION files from the root of the package into the root
     * Won't overwrite existing files
     *
     * @return integer  The number of version files copied, FALSE on error
     */
    public function copy()
    {
        $versionFiles = glob($this->installer->getPackageBasePath($this->package) . '/*_VERSION');

        if (empty($versionFiles)) {
            return 0;
        }

        $fileMover = new FileMover($this->package, $this->io, $this->installer);
        $count = 0;

        foreach ($versionFiles as $filePath) {
            $fileName = basename($filePath);

            // copy without overwriting
            if ($fileMover->copyFile($fileName, $fileName, false, true)) {
                $count++;
            }
        }

        return $count;
    }
}
