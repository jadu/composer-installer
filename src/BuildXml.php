<?php

namespace Jadu\Composer;

class BuildXml {

    protected $buildXmlPath;
    protected $xml;

    /**
     * @param string $buildXmlPath /path/to/build.xml
     */
    public function __construct($buildXmlPath)
    {
        $this->buildXmlPath = $buildXmlPath;
        $this->read();
    }

    /**
     * Adds each of the file paths in the given array to the .gitignore,
     * if they are not already present there
     *
     * File paths should be relative to the .gitignore file
     * @param array[]string $files
     */
    public function addFiles()
    {
        $addedCount = 0;
        $lines = $this->read();

        return $addedCount;
    }

    protected function read()
    {
        $this->xml = simplexml_load_file($this->buildXmlPath);
        print_r($this->xml);
    }

    protected function write()
    {
        return file_put_contents($this->buildXmlPath, $this->xml->asXML());
    }

}
