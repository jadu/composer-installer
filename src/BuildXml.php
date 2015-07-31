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
        if (!$this->inArray($file, $this->includes)) {
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
        if (!$this->inArray($file, $this->excludes)) {
            $this->excludes[] = $file;
            $this->modified = true;
        }
    }

    /**
     * Determines if the $file is within the $array,
     * either exactly or as part of a folder wildcard entry
     * @param  array[]string $array
     * @param  string $file
     * @return bool
     */
    public function inArray($file, $array)
    {
        if (in_array($file, $array)) {
            return true;
        }
        // if there is an entry "/foo/bar/**" already in the array,
        // consider the $file /foo/bar/baz to be in the array
        foreach ($array as $existing) {
            if (preg_match('%^(.+/)\*\*$%', $existing, $m)) {
                if (strpos($file, $m[1]) === 0) {
                    return true;
                }
            }
        }
        return false;
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

        if (isset($this->xml->fileset) && $this->xml->fileset->children()) {
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
        if (isset($filesetNode->include) && $filesetNode->include->children()) {
            foreach ($filesetNode->include as $includeNode) {
                $this->includes[] = (string)$includeNode['name'];
            }
        }
        if (isset($filesetNode->exclude) && $filesetNode->exclude->children()) {
            foreach ($filesetNode->exclude as $excludeNode) {
                $this->excludes[] = (string)$excludeNode['name'];
            }
        }
    }

    public function write()
    {
        if (!$this->modified) return;

        unset($this->xml->fileset->include);
        unset($this->xml->fileset->exclude);

        $this->xml->fileset['id'] = 'fileset.files';
        $this->xml->fileset['dir'] = '${basedir}';

        sort($this->includes);
        sort($this->excludes);

        foreach ($this->includes as $includePath) {
            $includeNode = $this->xml->fileset->addChild('include');
            $includeNode['name'] = $includePath;
        }
        foreach ($this->excludes as $excludePath) {
            $excludeNode = $this->xml->fileset->addChild('exclude');
            $excludeNode['name'] = $excludePath;
        }

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

        // use DOM to format XML
        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($this->xml->asXML());

        return file_put_contents($this->buildXmlPath, $dom->saveXML());
    }

}
