<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Copyright 2011-2012 Spadefoot
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * This class represents a binary data source.
 *
 * @package Messaging
 * @category Data Types
 * @version 2012-01-09
 *
 * @see http://developer.apple.com/library/mac/#documentation/Cocoa/Reference/Foundation/Classes/NSData_Class/Reference/Reference.html
 * @see http://download.oracle.com/javase/6/docs/api/javax/activation/DataSource.html
 * @see http://download.oracle.com/javase/6/docs/api/javax/activation/FileDataSource.html
 * @see http://download.oracle.com/javase/6/docs/api/javax/activation/URLDataSource.html
 * @see http://msdn.microsoft.com/en-us/library/system.net.mail.attachment.aspx
 */
abstract class Base_DataSource extends Kohana_Object {

    /**
     * This constant stores the data source type for a file.
     *
     * @access public
     * @var string
     */
    const DATA_CONTENTS = 'data';

    /**
     * This constant stores the data source type for a file.
     *
     * @access public
     * @var string
     */
    const FILE_CONTENTS = 'file';

    /**
     * This constant stores the data source type for a file.
     *
     * @access public
     * @var string
     */
    const STRING_CONTENTS = 'string';

    /**
     * This constant stores the data source type for a file.
     *
     * @access public
     * @var string
     */
    const URL_CONTENTS = 'url';

    /**
     * This variable stores the original data source type.
     *
     * @access protected
     * @var string
     */
    protected $type = NULL;

    /**
     * This variable stores the contents of the attachment.
     *
     * @access protected
     * @var string
     */
    protected $contents = NULL;

    /**
     * This variable stores the mime type of the attachment.
     *
     * @access protected
     * @var string
     */
    protected $mime = NULL;

    /**
     * This variable stores the encoding type for the attachment.
     *
     * @access protected
     * @var string
     */
    protected $encoding = NULL;

    /**
     * This constructor instantiates the class with the contents of the specified
     * data source.
     *
     * @access public
     * @param enum $type                        the data source type (e.g. data, file, string, url)
     * @param mixed $source                     the data source
     */
    public function __construct($type, $source) {
        $type = strtolower($type);
        if ($type == self::DATA_CONTENTS) {
            if (!($source instanceof Base_Attachment)) {
                throw new Kohana_InvalidArgument_Exception('Class is of wrong type', array(':source' => $source));
            }
            $this->type = $source->type;
            $this->contents = $source->contents;
        }
        else {
            if (!is_string($source)) {
                throw new Kohana_InvalidArgument_Exception('Source is of wrong type', array(':source' => $source));
            }
            if ($type == self::FILE_CONTENTS) {
                if (!file_exists($source)) {
                    throw new Kohana_FileNotFound_Exception('Unable to load source :source', array(':source' => $source));
                }
                $this->contents = file_get_contents($source);
            }
            else if ($type == self::STRING_CONTENTS) {
                $this->contents = $source;
            }
            else if ($type == self::URL_CONTENTS) {
                if (!self::does_url_exists($source)) {
                    throw new Kohana_FileNotFound_Exception('Unable to load source :source', array(':source' => $source));
                }
                $this->contents = file_get_contents($source);
            }
            else {
                throw new Kohana_InvalidArgument_Exception('Unrecognized data source type :type', array(':type' => $type));
            }
            $this->type = $type;
        }
        switch ($this->type) {
            case self::FILE_CONTENTS:
            case self::URL_CONTENTS:
                $this->mime = $this->get_mime_type($source);
            break;
            default:
                $this->mime = 'application/octet-stream';
            break;
        }
        $this->encoding = 'base64';
    }

    /**
     * This function provides read-only access to certain properties.
     *
     * @access public
     * @param string $key                           the name of the property
     * @return mixed                                the value of the property
     * @throws Kohana_InvalidProperty_Exception     indicates that property is undefined or inaccessible
     */
    public function __get($key) {
        switch ($key) {
            case 'contents':
                return $this->contents;
            case 'data':
                return base64_encode($this->contents);
            case 'encoding':
                return $this->encoding;
            case 'mime':
                return $this->mime;
            case 'type':
                return $this->type;
            default:
                throw new Kohana_InvalidProperty_Exception('Unknown property :key', array(':key' => $key));
        }
    }

    /**
     * This function sets the value for the specified key.
     *
     * @access public
     * @param string $key                           the name of the property
     * @return mixed                                the value of the property
     * @throws Kohana_InvalidProperty_Exception     indicates that property is undefined or inaccessible
     */
    public function __set($key, $value) {
        switch ($key) {
            case 'mime':
                $this->mime = (is_string($value)) ? $value : 'application/octet-stream';
            break;
            default:
                throw new Kohana_InvalidProperty_Exception('Unknown property :key', array(':key' => $key));
        }
    }

    ///////////////////////////////////////////////////HELPERS//////////////////////////////////////////////////////////

    /**
     * This function that gets the extension of a file. Simply using PATHINFO_EXTENSION will yield incorrect results
     * if the path contains a query string with dots in the parameter names (for eg. &x.1=2&y.1=5), so this function
     * eliminates the query string first and subsequently runs PATHINFO_EXTENSION on the clean path/url.
     *
     * @access protected
     * @param string $uri                           the URI
     * @return string                               the URI's file extension
     *
     * @see http://www.php.net/manual/en/function.pathinfo.php
     */
    protected function get_extension($uri) {
        $query_at = strpos($uri, '?');
        if ($query_at !== FALSE) {
            $uri = substr($uri, 0, $query_at);
        }
        $extension = pathinfo($uri, PATHINFO_EXTENSION);
        return $extension;
    }

    /**
     * This function attempts to auto-detect the appropriate mime type using the URI's file extension.
     *
     * @access protected
     * @param string $uri                           the URI
     * @return string                               the mime type
     *
     * @see http://www.w3schools.com/media/media_mimeref.asp
     * @see http://www.php.net/manual/en/ref.fileinfo.php
     * @see http://php.net/manual/en/function.mime-content-type.php
     */
    protected function get_mime_type($uri) {
        if (($mimes = Kohana::$config->load('mimes.' . $this->get_extension($uri))) === NULL) {
			return 'application/octet-stream';
		}
		return $mimes[0];
    }

    /**
     * This function checks whether the specified URL is active.
     *
     * @access protected
     * @static
     * @param string $url                           the URL to be tested
     * @return boolean                              whether the specified URL is active
     */
    protected static function does_url_exists($url) {
        $headers = @get_headers($url);
        return is_array($headers) ? preg_match('/^HTTP\\/\\d+\\.\\d+\\s+2\\d\\d\\s+.*$/', $headers[0]) : FALSE;
    }

}
?>