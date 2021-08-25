<?php
namespace Unlikely\Import;


/*
 * Unlikely\Import\BuildWXR
 *
 * Produces WXR (WordPress eXtended Rss) file
 *
 * @author doug@unlikelysource.com
 * @date 2021-08-21
 * Copyright 2021 unlikelysource.com
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 */

use Exception;
use DateTime;
use DateTimeZone;
use XmlWriter;
use SplFileObject;
use SplObjectStorage;
use DOMDocument;

class BuildWXR
{
    public const ERR_CALLBACK = 'ERROR: missing configuration for %s callback class';
    public $config = [];
    public $export = [];
    public $extract = NULL;         // instance of Extract class
    public $writer = NULL;          // XmlWriter instance
    public $template = NULL;        // DOMDocument representing the import template
    public $article = NULL;         // DOMDocument representing the WP article
    public $callbackManager = NULL; // stores additional callbacks (for future expansion)
    /**
     * Initializes delimiters and creates transform callback array
     *
     * @param MercuryFree\Import\Extract $extract : class with methods able to fulfill WXR requirements
     * @param array $config : ['export' => ['rss' => [attribs], 'channel' => [WXR nodes]]]
     */
    public function __construct(Extract $extract, array $config)
    {
        // bail out if unable to open $fn
        $this->err = [];
        $this->config  = $config;
        $this->export = $config['export'] ?? [];
        $this->extract = $extract;
        $this->writer  = new XmlWriter();
        $this->callbackManager = new SplObjectStorage();
    }
    /**
     * Adds CDATA text to a node
     *
     * @param string $text : CDATA text to be added
     */
    public function addCdata(string $text) : void
    {
        $this->writer->startCdata();
        $this->writer->text($text);
        $this->writer->endCdata();
    }
    /**
     * Adds XML nested nodes
     *
     * @param array $node = [key => value]
     * @param mixed $value : value to  be added to node; if is_array($value), node is not closed
     */
    public function addNode(array $node) : void
    {
        $this->writer->openMemory();
        $this->writer->startDocument('1.0', 'UTF-8');
        $this->doAddNode($node);
    }
    /**
     * Recursively adds nodes to open XMLWriter instance
     *
     * @param array $node = [key => value]
     * @param mixed $value : value to  be added to node; if is_array($value), node is not closed
     */
    public function doAddNode(array $node) : void
    {
        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $this->writer->startElement($key);
                if (isset($value['CDATA'])) {
                    $this->writer->text($this->addCdata($value['CDATA']));
                } else {
                    // recurse to next level
                    $this->doAddNode($value);
                }
                $this->writer->endElement();
            } else {
                $this->writer->startElement($key);
                $this->writer->text($value);
                $this->writer->endElement();
            }
        }
    }
    /**
     * Builds import XML template
     *
     * Leaves <item></item> node blank
     * Puts in the form of a SimpleXMLElement instance
     *
     */
    public function buildTemplate() : void
    {
        $this->writer->openMemory();
        $this->writer->startDocument('1.0', 'UTF-8');
        $this->writer->startElement('rss');
        $this->writer->startAttribute('version');
        $this->writer->text($this->export['rss']['version']);
        $this->writer->endAttribute();
        foreach ($this->export['rss']['xmlns'] as $attrib => $value) {
            $this->writer->startAttributeNs('xmlns', $attrib, NULL);
            $this->writer->text($value);
            $this->writer->endAttribute();
        }
        $this->writer->startElement('channel');
        $this->doAddNode($this->export['channel']);
        $this->writer->endElement();    // ends "channel"
        $this->writer->endElement();    // ends "rss"
        $this->template = new DOMDocument($this->writer->outputMemory());
    }
    /**
     * Adds XML nested nodes pertaining to WordPress article
     *
     * @param array $node = [key => value]
     * @param mixed $value : value to  be added to node; if is_array($value), node is not closed
     */
    public function addArticle(array $node)
    {
        $this->writer->openMemory();
        $this->writer->startDocument('1.0', 'UTF-8');
        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $this->writer->startElement($key);
                if (isset($value['CDATA'])) {
                    if (is_array($value['CDATA']) && isset($value['CDATA']['callback'])) {
                        $this->addCdata($this->doCallback($value['CDATA']['callback']));
                    } else {
                        $this->writer->text($this->addCdata($value['CDATA']));
                    }
                } elseif (isset($value['callback'])) {
                    $this->writer->text($this->doCallback($value['callback']));
                } else {
                    // recurse to next level
                    $this->addNode($value);
                }
                $this->writer->endElement();
            } else {
                $this->writer->startElement($key);
                $this->writer->text($value);
                $this->writer->endElement();
            }
        }
        $this->article = new DOMDocument($this->writer->outputMemory());
    }
    /**
     * Runs callbacks
     *
     * @param array $params  : ['class' => class name of callback, 'method' => method name, 'args' => optional arguments]
     * @param string $method : method name of callback
     * @param mixed  $args   : arguments to provide to callback
     * @return mixed $result : result of callback
     */
    public function doCallback(array $params)
    {
        $result = NULL;
        switch ($class) {
            case Extract::class :
                $method = $params['method'] ??  'Unknown';
                $args   = $params['args']   ?? NULL;
                $result = $this->extract->$method($args);
                break;
            default :
                $result = $this->useCallbackManager($params);
        }
        return $result;
    }
    /**
     * Provides for additional callbacks via $callbackManager
     *
     * All callbacks must accept an instance of this class as the first argument
     * in order to gain access to the original HTML file being imported
     *
     * @param string $method : name of the unique method name references by the CallbackManager
     * @param array $params  : array of params passed to the callback
     * @return mixed $unknown : return value from callback
     */
    public function useCallbackManager(array $params)
    {
        $class  = $params['class']  ?? 'Unknown';
        $method = $params['method'] ??  'Unknown';
        $args   = $params['args']   ?? NULL;
        // scan to see if $class already exists
        $this->callbackManager->rewind();
        foreach ($this->callbackManager as $obj) {
            if ($obj instanceof $class) {
                return $obj->$method($this, $args);
            }
        }
        // if we get to this point, the callback class is not registered
        $result = NULL;
        try {
            // pull config for this callback class
            $config = $this->config[$class] ?? [];
            if (empty($config))
                throw new Exception(sprintf(static::ERR_CALLBACK, $class));
            // if we have config create the instance, store it and use it
            $callback = new $class($config);
            $this->callbackManager->attach($callback, $class);
            $result = $callback->$method($this, $params);
        } catch (Throwable $t) {
            error_log(__METHOD__ . ':' . $t->getMessage());
        }
        return $result;
    }
}
