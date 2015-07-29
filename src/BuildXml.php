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
     * Adds each of the file paths in the given array to the files included in the package
     * if they are not already present there
     *
     * @param string $file
     */
    public function addInclude($file)
    {
        if (!$this->xml) return;
        $this->includes[] = $file;
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
        $this->excludes[] = $file;
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
            // load & parse default fileset from Meteor
            // attach node to $this->xml
        }
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

    protected function write()
    {
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

        return file_put_contents($this->buildXmlPath, $this->xml->asXML());
    }

}
