<?php
namespace WP_CLI\Unlikely\Import;


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
use BadMethodCallException;
use InvalidArgumentException;
use UnexpectedValueException;
use DateTime;
use DateTimeZone;
use XmlWriter;
use SplFileObject;
use DOMDocument;
use ArrayIterator;

class BuildWXR
{
    public const ERR_CALLBACK = 'ERROR: unable to process callback: missing configuration?';
    public const ERR_CALLBACK_CLASS = 'ERROR: unable to process callback: missing configuration for %s class?';
    public const ERR_CALLBACK_METHOD = 'ERROR: unable to process callback method: missing configuration for %s class?';
    public const ERR_CALLBACK_INVALID = 'ERROR: callback must implement Unlikely\Import\BuildWXRInterface';
    public const ERR_ITEM_KEY = 'ERROR: problem with "item" configuration.  All values must be arrays. "configuration" treated separately.';
    public $config = [];
    public $export = [];            // import template config
    public $item   = [];            // config to build "item" node
    public $wxr    = NULL;            // WXR document (DOMDocument)
    public $extract = NULL;         // instance of Extract class
    public $writer = NULL;          // XmlWriter instance
    public $template = NULL;        // DOMDocument representing the import template
    public $callbackManager = NULL; // stores additional callbacks (for future expansion)
    /**
     * Initializes delimiters and creates transform callback array
     *
     * @param array $config : ['export' => ['rss' => [attribs], 'channel' => [WXR nodes]], 'item' => [config for building "item" node]]
     * @param Extract $extract : new Extract instance
     */
    public function __construct(array $config, Extract $extract = NULL)
    {
        // bail out if unable to open $fn
        $this->err = [];
        $this->config  = $config;
        $this->export  = $config['export'] ?? [];
        $this->item    = $config['item']   ?? [];
        $this->writer  = new XmlWriter();
        $this->callbackManager = new ArrayIterator();
        if (!empty($extract)) $this->setExtract($extract);
    }
    /**
     * Assembles article into template
     *
     *
     * @param array|null $item : override configuration for building "item" node
     * @return DOMDocument : article rendered as DOMDocument instance into template
     */
    public function assembleWXR(?array $item = NULL)
    {
        if (empty($this->template))
            $this->template = $this->buildTemplate();
        $this->wxr = clone $this->template;
        $article = $this->addArticle($item);
        //$this->wxr->appendChild($article);
        return $this->wxr;
    }
    /**
     * Sets new Extract instance
     *
     * @param Extract $extract : new Extract instance
     * @return void
     */
    public function setExtract(Extract $extract)
    {
        $this->extract = $extract;
        $this->addCallback($extract);
    }
    /**
     * Retrieves class instance from callbackManager
     *
     * @param string $name : name of class to retrieve
     * @return object|NULL $obj
     */
    public function getCallback(string $name)
    {
        return ($this->callbackManager->offsetExists($name))
                ? $this->callbackManager->offsetGet($name)
                : NULL;
    }
    /**
     * Adds class instance to callbackManager
     *
     */
    public function addCallback(object $obj)
    {
        if (!$obj instanceof BuildWXRInterface)
            throw new InvalidArgumentException(self::ERR_CALLBACK_INVALID);
        $this->callbackManager->offsetSet(get_class($obj), $obj);
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
            } elseif (is_string($key)) {
                $this->writer->startElement($key);
                $this->writer->text($value);
                $this->writer->endElement();
            } else {
                throw new UnexpectedValueException(static::ERR_NODE_KEY . ':' . var_export($key, TRUE));
            }
        }
    }
    /**
     * Builds import XML template
     *
     * Leaves <item></item> node blank
     * Puts in the form of a SimpleXMLElement instance
     *
     * @return DOMDocument : $this->template
     */
    public function buildTemplate() : DOMDocument
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
        $this->template = new DOMDocument();
        $this->template->loadXML($this->writer->outputMemory());
        return $this->template;
    }
    /**
     * Adds XML nested nodes pertaining to WordPress article
     *
     * @param array|null $item : override configuration for building "item" node
     * @return string $xml : XML document representing article
     */
    public function addArticle(?array $item = NULL)
    {
        $this->writer->openMemory();
        $this->writer->startDocument('1.0', 'UTF-8');
        $this->writer->startElement('item');
        $item = $item ?? $this->item;
        foreach ($item as $key => $value) {
            $key = str_replace(':', '__', $key);
            $this->writer->startElement($key);
            if ($key === 'category') {
                $this->doAddCategory($value);
            } elseif (is_array($value)) {
                if (isset($value['CDATA'])) {
                    if (is_array($value['CDATA']) && isset($value['CDATA']['callback'])) {
                        $this->addCdata($this->doCallback($value['CDATA']['callback']));
                    } else {
                        $this->writer->text($this->addCdata($value['CDATA']));
                    }
                } elseif (isset($value['callback'])) {
                    $this->writer->text($this->doCallback($value['callback']));
                } else {
                    $this->writer->text(current($value));
                }
            } else {
                $this->writer->text($value);
            }
            $this->writer->endElement();
        }
        $this->writer->endElement();
        $xml = $this->writer->outputMemory();
        $xml = str_replace('__', ':', $xml);
        return $xml;
    }
    /**
     * Processes "category" node
     *
     * @param array $node : node representing category
     */
    public function doAddCategory(array $node) : void
    {
        $attributes = $node['attributes'] ?? [];
        foreach ($attributes as $attrib => $value) {
            $this->writer->startAttribute($attrib);
            if (is_string($value)) {
                $this->writer->text($value);
            } elseif (is_array($value)) {
                if (isset($value['callback'])) {
                    $this->writer->text($this->doCallback($value['callback']));
                }
            }
            $this->writer->endAttribute();
        }
        $this->addCdata($this->doCallback($node['CDATA']['callback']));
    }
    /**
     * Runs callbacks
     *
     * @param array $params  : ['class' => class name of callback, 'method' => method name, 'args' => optional arguments]
     *                         or ['callable' => callable $callback, 'args' => optional arguments]
     * @param string $method : method name of callback
     * @param mixed  $args   : arguments to provide to callback
     * @return mixed $result : result of callback | NULL if callback not found
     */
    public function doCallback(array $params)
    {
        $result = NULL;
        if (isset($params['callable'])) {
            $args = $params['args'] ?? [];
            $result = call_user_func($params['callable'], $args);
        } elseif (isset($params['class'])) {
            $result = $this->useCallbackManager($params);
        } else {
            error_log(__METHOD__ . ':' . static::ERR_CALLBACK);
            $result = NULL;
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
        $result = NULL;
        $class  = $params['class']  ?? 'Unknown';
        $method = $params['method'] ??  'Unknown';
        $args   = $params['args']   ?? NULL;
        // scan to see if $class already exists
        $obj = $this->getCallback($class);
        if (!empty($obj) && is_object($obj)) {
            if (!method_exists($obj, $method))
                throw new BadMethodCallException(sprintf(static::ERR_CALLBACK_METHOD, $class));
            $obj->setBuildWXRInstance($this);
            return $obj->$method($args) ?? NULL;
        }
        // if we get to this point, the callback class is not registered
        try {
            // pull config for this callback class
            $config = $this->config[$class] ?? [];
            if (empty($config))
                throw new Exception(sprintf(static::ERR_CALLBACK_CLASS, $class));
            // if we have config create the instance, store it and use it
            $callback = new $class(...$config);
            $this->addCallback($callback);
            // call method and pass $args and instance of this class
            $result = $callback->$method($args, $this);
        } catch (Throwable $t) {
            error_log(__METHOD__ . ':' . $t->getMessage());
        }
        return $result;
    }
}
