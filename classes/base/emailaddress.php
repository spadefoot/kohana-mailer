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
 * This class represents an email address.
 *
 * @package Messaging
 * @category Data Types
 * @version 2012-01-09
 *
 * @see http://msdn.microsoft.com/en-us/library/system.net.mail.mailaddress.aspx
 */
class Base_EmailAddress extends Kohana_Object {

    /**
     * This variable stores the email address.
     *
     * @access protected
     * @var string
     */
    protected $email;

    /**
     * This variable stores the name associated with the email address.
     *
     * @access protected
     * @var string
     */
    protected $name;

	/**
     * This constructor creates an instance of this class.
     *
     * @access public
     * @param string $email                     the email address
     * @param string $name                      the name
     */
    public function __construct($email, $name = '') {
        $email = trim($email);
        if ( ! preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i', $email)) {
            throw new Kohana_InvalidArgument_Exception('Message: Pattern mismatch. Reason: String is not an email address.', array(':email' => $email));
        }
        $this->email = $email;
        $this->name = trim(preg_replace('/\R/', '', $name));
    }

    /**
     * This function provides read-only access to certain properties.
     *
     * @access public
     * @param string $key                       the name of the property
     * @return mixed                            the value of the property
     * @throws Kohana_InvalidProperty_Exception indicates that the specified property is
     *                                          either inaccessible or undefined
     */
    public function __get($key) {
        switch ($key) {
            case 'email':
                return $this->email;
            case 'name':
                return $this->name;
            default:
				throw new Kohana_InvalidProperty_Exception('Message: Unable to get the specified property. Reason: Property :key is either inaccessible or undefined.', array(':key' => $key));
        }
    }

	/**
	 * This function returns the components formatted as a string.
	 *
	 * @access public
	 * @return string                           the components formatted as a string
	 */
    public function as_string() {
        if ( ! empty($this->name)) {
            return '"' . addslashes($this->name) . '" <' . $this->email . '>';
        }
        return $this->email;
    }

	/**
	 * This function returns the components in an associated array.
	 *
	 * @access public
	 * @return array                            the components stored in an array
	 */
    public function as_array() {
        $array = array(
            'email' => $this->email,
            'name' => $this->name
        );
        return $array;
    }

}
?>