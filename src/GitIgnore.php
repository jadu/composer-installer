<?php

namespace Jadu\Composer;

class GitIgnore {

    protected $ignoreFilePath;

    /**
     * @param string $ignoreFilePath /path/to/.gitignore
     */
    public function __construct($ignoreFilePath)
    {
        $this->ignoreFilePath = $ignoreFilePath;
    }

    /**
     * Adds each of the file paths in the given array to the .gitignore,
     * if they are not already present there
     *
     * File paths should be relative to the .gitignore file
     * @param array[]string $files
     */
    public function addFiles($files)
    {
        $addedCount = 0;
        $lines = $this->readLines();
        if ($lines === false) {
            throw new RuntimeException("Error loading .gitignore file", 1);
        }
        foreach ($files as $file) {
            foreach ($lines as $line) {
                if ($line == $file) {
                    continue 2;
                }
            }
            $lines[] = $file;
            $addedCount++;
        }

        $this->write($lines);

        return $addedCount;
    }

    protected function readLines()
    {
        if (file_exists($this->ignoreFilePath)) {
            $lines = file($this->ignoreFilePath, \FILE_IGNORE_NEW_LINES);
        }
        else {
            $lines = array();
        }
        return $lines;
    }

    protected function write($lines)
    {
        $file = fopen($this->ignoreFilePath, 'w');
        foreach ($lines as $line) {
            fputs($file, $line . \PHP_EOL);
        }
        if ($line !== \PHP_EOL) {
            // add trailing newline if the last line wasn't one
            fputs($file, \PHP_EOL);
        }
        fclose($file);
    }

}
