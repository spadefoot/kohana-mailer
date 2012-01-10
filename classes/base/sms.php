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
 * This class sends a text message to specified recipient(s).
 *
 * @package Messaging
 * @category SMS
 * @version 2012-01-09
 */
class Base_SMS extends Kohana_Object {

    const ALLTEL            = '@message.alltel.com';
    const ATT               = '@txt.att.net';
    const BOOST             = '@myboostmobile.com';
    const CINGULAR          = '@mobile.mycingular.com';
    const NEXTEL            = '@messaging.nextel.com';
    const SPRINT            = '@messaging.sprintpcs.com';
    const T_MOBILE          = '@tmomail.net';
    const VERIZON           = '@vtext.com';
    const VIRGIN_MOBILE     = '@vmobl.com';

	/**
	 * This variable stores the recipient's text messaging emails.
	 *
	 * @access protected
	 * @var array
	 */
    protected $recipients = array();

	/**
	 * This variable stores the sender's text messaging email.
	 *
	 * @access protected
	 * @var string
	 */
    protected $sender = '';

    /**
     * This variable stores the subject line for the text message.
     *
     * @access protected
     * @var string
     */
    protected $subject = '';

    /**
	 * This variable stores the message that will be sent to recipient(s).
	 *
	 * @access protected
	 * @var string
	 */
    protected $message = '';

    /**
     * This variable stores the last error message reported.
     *
     * @access protected
     * @var array
     */
    protected $error = NULL;

    /**
     * Initializes the SMS service.
     *
     * @access public
     */
	public function __construct() { }

    /**
     * This function adds a recipient to the list of recipients that will receive
     * the text message.
     *
     * @access public
     * @param string $number        the recipient's phone number
     * @param string $carrier       the carrier that provides service to the recipient
     */
    public function add_recipient($number, $carrier) {
        $this->recipients[] = preg_replace('/(\D*)/', '', $number) . $carrier;
    }

    /**
     * This function sets the sender of the text message.
     *
     * @access public
     * @param string $number        the sender's phone number
     * @param string $carrier       the carrier that provides service to the sender
     */
    public function set_sender($number, $carrier) {
        $this->sender = preg_replace('/(\D*)/', '', $number) . $carrier;
    }

    /**
     * This function sets the subject line for the text message.
     *
     * @access public
     * @param string $subject       the subject line
     */
    public function set_subject($subject) {
        $this->subject = $subject;
    }

    /**
     * This function sets the message that will be sent.
     *
     * @access public
     * @param string $message       the message that will be sent
     */
    public function set_message($message) {
        $this->message = $message;
    }

    /**
     * This function attempts to send the text message to the recipient(s).
     *
     * @access public
     * @return boolean              returns TRUE if all of the recipient(s) are successfully
     *                              sent the text message; otherwise, FALSE
     */
    public function send() {
        foreach ($this->recipients as $recipient) {
		    $sent = mail($recipient, $this->subject, $this->message, "From: {$this->sender}");
		    if (!$sent) {
		        $this->error = array(
		            'message' => "Failed to send message to {$recipient}",
		            'code' => 0
		        );
		        return FALSE;
	        }
		}
        return TRUE;
    }

    /**
     * This function returns the last error reported.
     *
     * @access public
     * @return array                the last error reported
     */
	public function get_error() {
	    return $this->error;
	}

	/**
	 * Returns a singleton instance of SMS.
	 *
	 * @param mixed                 configuration name and/or array
	 * @return SMS
	 */
	public static function instance() {
	    static $singleton = NULL;
		if (is_null($singleton)) {
			$singleton = new SMS();
		}
		return $singleton;
	}

}
?>