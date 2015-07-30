<?php

namespace Jadu\Composer;

use SimpleXmlElement;

class BuildXml {

    // this path is correct as of Meteor 1.0.4 — it may need updating if Meteor is changed
    const DEFAULT_FILESET_PATH = 'vendor/jadu/meteor/res/build/ant/filesets.xml';

    protected $buildXmlPath;
    protected $xml;

    protected $includes;
    protected $excludes;

    protected $modified = false;

    /**
     * @param string $buildXmlPath /path/to/build.xml
     */
    public function __construct($buildXmlPath)
    {
        $this->buildXmlPath = $buildXmlPath;
        $this->read();
    }

    /**
     * Adds each of the file paths in the given array to the files included in the package
     * if they are not already present there
     *
     * @param string $file
     */
    public function addInclude($file)
    {
        if (!$this->xml) return;
        if (!in_array($file, $this->includes)) {
            $this->includes[] = $file;
            $this->modified = true;
        }
    }

    /**
     * Adds each of the file paths in the given array to the files excluded from the package
     * if they are not already present there
     *
     * @param string $file
     */
    public function addExclude($file)
    {
        if (!$this->xml) return;
        if (!in_array($file, $this->excludes)) {
            $this->excludes[] = $file;
            $this->modified = true;
        }
    }

    protected function read()
    {
        if (!is_file($this->buildXmlPath)) {
            $this->xml = null;
            return;
        }

        $this->xml = simplexml_load_file($this->buildXmlPath);
        $this->includes = array();
        $this->excludes = array();

        if (isset($this->xml->fileset)) {
            $this->parseFileset($this->xml->fileset);
        }
        else {
            $this->readDefaultFileSet();
        }
    }

    protected function readDefaultFileSet()
    {
        $defaultFilesetXmlFile = dirname($this->buildXmlPath) . '/' . self::DEFAULT_FILESET_PATH;
        if (file_exists($defaultFilesetXmlFile)) {
            $defaultFilesetXml = simplexml_load_file($defaultFilesetXmlFile);
            if (isset($defaultFilesetXml->fileset)) {
                $this->parseFileset($defaultFilesetXml->fileset);
                return true;
            }
        }
        return false;
    }

    protected function parseFileset($filesetNode)
    {
        if (isset($filesetNode->include)) {
            foreach ($filesetNode->include as $includeNode) {
                $this->includes[] = (string)$includeNode['name'];
            }
        }
        if (isset($filesetNode->exclude)) {
            foreach ($filesetNode->exclude as $excludeNode) {
                $this->excludes[] = (string)$excludeNode['name'];
            }
        }
    }

    public function write()
    {
        if (!$this->modified) return;

        $filesetNode = new SimpleXmlElement('<fileset id="fileset.files" dir="${basedir}"></fileset>');
        foreach ($this->includes as $includePath) {
            $includeNode = new SimpleXmlElement('<include />');
            $includeNode->addAttribute('name', $includePath);
            $filesetNode->addChild($includeNode);
        }
        foreach ($this->excludes as $excludePath) {
            $excludeNode = new SimpleXmlElement('<exclude />');
            $excludeNode->addAttribute('name', $excludePath);
            $filesetNode->addChild($excludeNode);
        }
        $this->xml['fileset'] = $filesetNode;

        foreach ($this->xml->property as $property) {
            if ($property['name'] == 'package-fileset') {
                $property['value'] = 'fileset.files';
            }
        }

        foreach ($this->xml->target as $target) {
            foreach ($target->property as $property) {
                if ($property['name'] == 'package-fileset') {
                    $property['value'] = 'fileset.files';
                }
            }
        }

        return file_put_contents($this->buildXmlPath, $this->xml->asXML());
    }

}
