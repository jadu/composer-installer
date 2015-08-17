<?php

namespace Jadu\Composer;

use Composer\Package\PackageInterface;
use Composer\IO\IOInterface;
use Composer\Composer;

class MigrationScripts
{

    protected $package;
    protected $io;
    protected $composer;
    protected $installer;

    public function __construct(PackageInterface $package, IOInterface $io, Composer $composer, Installer $installer)
    {
        $this->package = $package;
        $this->io = $io;
        $this->composer = $composer;
        $this->installer = $installer;
    }

    /**
     * Gets the hash of a file contents, by first normalising the Version class name
     * to avoid that affecting the hash
     * @param  string $file Path to Version* file
     * @return hash       Hash
     */
    protected function getHash($file)
    {
        $contents = file_get_contents($file);
        // normalise the class name to avoid mismatches
        $contents = preg_replace(
            '/(\bclass\s+Version.+?)(?=$|[ \t]+(extends|\{))/',
            'class VersionNormalised',
            $contents
        );
        return sha1($contents);
    }

    /**
     * Copy any migration scripts from the package into the root folder's upgrades/migrations/ folder
     *
     * Will only copy a migration script if a script with the same sha1sum doesn't exist
     * @return integer  The number of migration scripts copied, FALSE on error
     */
    public function copy()
    {
        $packageMigrationsFolder = $this->installer->getPackageBasePath($this->package) . '/' . Installer::MIGRATIONS_FOLDER;
        if (!is_dir($packageMigrationsFolder)) {
            return 0;
        }

        $rootMigrationsFolder = $this->installer->getRootPath() . '/' . Installer::MIGRATIONS_FOLDER;
        if (!is_dir($rootMigrationsFolder)) {
            $this->io->writeError("    Error: Migrations folder doesn't exist in $rootMigrationsFolder");
            return false;
        }

        $newMigrationFiles = array();
        foreach (glob($packageMigrationsFolder . '/Version*.php') as $file) {
            $newMigrationFiles[$file] = $this->getHash($file);
        }
        $existingMigrationFiles = array();
        foreach (glob($rootMigrationsFolder . '/Version*.php') as $file) {
            $existingMigrationFiles[$file] = $this->getHash($file);
        }

        $count = 0;

        foreach ($newMigrationFiles as $newFile => $newHash) {
            if (empty($newHash)) {
                $this->io->writeError("    Failed to calculate SHA1 of $newFile, skipping");
                continue;
            }
            foreach ($existingMigrationFiles as $existingFile => $existingHash) {
                // skip files that already exist (determined by matching SHA1)
                if (!empty($existingHash) && $newHash === $existingHash) {
                    continue 2;
                }
            }
            $timestamp = null;
            do {
                $timestamp = ($timestamp === null) ? date('YmdHis') : $timestamp+1;
                $newFilename = $rootMigrationsFolder . '/Version' . $timestamp . '.php';
            } while (file_exists($newFilename));

            $newFileContents = file_get_contents($newFile);
            $newFileContents = preg_replace('/\\bclass\\s+Version\\S+\\s/i', "class Version$timestamp ", $newFileContents);

            file_put_contents($newFilename, $newFileContents);

            $count++;
        }

        return $count;
    }
}
