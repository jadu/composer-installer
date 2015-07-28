<?php

namespace Jadu\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;

class FileMover {

    public function __construct(PackageInterface $package, IOInterface $io, Composer $composer, Installer $installer)
    {
        $this->package = $package;
        $this->io = $io;
        $this->composer = $composer;
        $this->installer = $installer;
    }

    /**
     * Copy a set of files and/or folders
     * @param  array[string]string $files Array where the key is the source path and the value is the destination path
     * @return integer        Number of files/folders copied
     */
    public function copyFiles($files)
    {
        $copyCount = 0;
        foreach ($files as $source => $dest) {
            if ($this->copyFile($source, $dest, true, true)) {
                $copyCount++;
            }
        }

        $this->io->write(sprintf('    %d %s copied', $copyCount, $copyCount == 1 ? 'file' : 'files'));
        return $copyCount;
    }

    /**
     * Copy file from PACKAGE_BASE_PATH/$source -> INSTALL_PATH/$dest
     *
     * @param  string $source    File path relative to package base path
     * @param  string $dest      File path relative to root install folder
     * @param  bool $overwrite   Overwrite exist files/folder if true. Default true.
     * @param  bool $gitIgnore   If true, file/folder copied will be added to .gitignore if not already there
     */
    public function copyFile($relativeSource, $relativeDest, $overwrite, $gitIgnore)
    {
        $source = $this->installer->getPackageBasePath($this->package) . '/' . $relativeSource;
        $dest = $this->installer->getInstallPath($this->package) . '/' . $relativeDest;

        if (!file_exists($source)) {
            throw new \RuntimeException("File to copy doesn't exist: $source");
        }

        if (!$overwrite && file_exists($dest)) {
            $this->io->writeError("    File not copied as destination exists: $dest");
            return;
        }

        if (is_dir($source)) {
            // delete existing folder
            if (is_dir($dest)) {
                if (!self::unlinkFolder($dest)) {
                    $this->io->writeError("    Existing folder could not be removed: $dest");
                }
            }
            $success = self::copyFolder($source, $dest);
            if (!$success) {
                throw new RuntimeException("Failed to copy folder to $dest");
            }
        }
        else {
            $success = copy($source, $dest);
            if (!$success) {
                throw new RuntimeException("Failed to copy file to $dest");
            }
        }

        if ($gitIgnore) {
            $this->installer->addToGitIgnore($relativeDest);
        }

        return true;
    }

    /**
     * Recursively delete folder
     * @param  string $path Full path to folder
     * @return bool
     */
    public static function unlinkFolder($path) {
        $files = array_diff(
            scandir($path),
            array('.', '..')
        );
        foreach ($files as $file) {
            if (is_dir("$path/$file") && !is_link($path)) {
                self::unlinkFolder("$path/$file");
            }
            else {
                unlink("$path/$file");
            }
        }
        return rmdir($path);
    }

    /**
     * Recursively copy folder
     * @param  string $source Full path to folder to copy from
     * @param  string $dest   Full path to copy to
     * @return bool
     */
    public static function copyFolder($source, $dest) {
        $files = array_diff(
            scandir($source),
            array('.', '..')
        );
        mkdir($dest, 0755, true);
        foreach ($files as $file) {
            if (is_dir("$source/$file") && !is_link($source)) {
                if (!self::copyFolder("$source/$file", "$dest/$file")) {
                    return false;
                }
            }
            else {
                if (!copy("$source/$file", "$dest/file")) {
                    return false;
                }
            }
        }
        return true;
    }
}
