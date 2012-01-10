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

include_once(Kohana::find_file('vendor', 'WhatCounts/WhatCountsDriver', $ext = 'php'));

/**
 * This class send emails via the What Counts mail service.
 *
 * @package Messaging
 * @category Mailer
 * @version 2012-01-09
 */
class Base_Mailer_WhatCounts extends Kohana_Object implements Base_Mailer_Interface {

    /**
     * This variable stores an instance of the What Counts driver class.
     *
     * @access protected
     * @var WhatCountsDriver
     */
    protected $mailer = NULL;
    
    /**
     * This variable stores a list of all recipients to received the email
     * message.
     *
     * @access protected
     * @var array
     */
    protected $recipients = array();

    /**
     * This variable stores the email address of the sender.
     *
     * @access protected
     * @var string
     */
    protected $sender = '';
    
    /**
     * This variable stores the email address of the reply to.
     *
     * @access protected
     * @var string
     */
    protected $reply_to = '';

    /**
     * This variable stores the name of the mail list.
     *
     * @access protected
     * @var string
     */
    protected $mailing_list = NULL;
    
    /**
     * This variable stores the subject of the email.
     *
     * @access protected
     * @var string
     */
    protected $subject = '(no subject)';

    /**
     * This variable stores the content type of the body in the email message.
     *
     * @access protected
     * @var string
     */
    protected $content_type = 'text/plain';
    
    /**
     * This variable stores the message.
     *
     * @access protected
     * @var string
     */
    protected $message = '';
    
    /**
     * This variable stores the alternative message.
     *
     * @access protected
     * @var string
     */
    protected $alt_message = '';

    /**
     * This variable stores the last error message reported.
     *
     * @access protected
     * @var array
     */
    protected $error = NULL;

    /**
     * This constructor initializes the driver for this mail service.
     *
     * @access public
     * @param array $config                 the configuration array
     * @return Mailer_Interface             an instance of the driver class
     */
	public function __construct($config) {
	    $this->mailer = new WhatCountsDriver($config['credentials']->username, $config['credentials']->password);
	    if (isset($config['sender'])) {
    	    $this->set_sender($config['sender']);
        }
        if (isset($config['reply-to'])) {
    	    $this->set_reply_to($config['reply-to']);
        }
        else {
            $this->reply_to = $this->sender;
        }
	    $this->mailing_list = $this->mailer->getListByName($config['mailing_list']);
	}

	/**
	 * This function provides a way to pass specific options to the mail service.
	 *
	 * @access public
	 * @param array $options                any special options for the mail service
	 */
	public function set_options(Array $options) {
        // does nothing
	}

    /**
     * This function adds a recipient to the list of recipients that will receive
     * the email.
     *
     * @access public
     * @param EmailAddress $address         the email address and name
     * @return boolean                      whether the recipient was added
     */
    public function add_recipient(EmailAddress $address) {
        $this->recipients[] = $address->email;
        return TRUE;
    }

    /**
     * This function will carbon copy the specified email account.
     *
     * @access public
     * @param EmailAddress $address         the email address and name
     * @return boolean                      whether the recipient was added
     */
    public function add_cc(EmailAddress $address) {
        $this->recipients[] = $address->email;
        return TRUE;
    }

    /**
     * This function will blind carbon copy the specified email account.
     *
     * @access public
     * @param EmailAddress $address         the email address and name
     * @return boolean                      whether the recipient was added
     */
    public function add_bcc(EmailAddress $address) {
        $this->recipients[] = $address->email;
        return TRUE;
    }

    /**
     * This function sets the sender of the email message.
     *
     * @access public
     * @param EmailAddress $address         the email address and name
     * @return boolean                      whether the sender was set
     */
    public function set_sender(EmailAddress $address) {
        $this->sender = $address->as_string();
        return TRUE;
    }

    /**
     * This function sets the reply-to email address.
     *
     * @access public
     * @param EmailAddress $address         the email address and name
     * @return boolean                      whether the reply-to was set
     */
	public function set_reply_to(EmailAddress $address) {
        $this->reply_to = $address->as_string();
        return TRUE;
	}

    /**
     * This function sets the subject line for the email message.
     *
     * @access public
     * @param string $subject               the subject line
     */
    public function set_subject($subject) {
        $this->subject = '(no subject)';
        if (is_string($subject)) {
            $subject = trim(preg_replace('/\R/', '', $subject)); // aims to prevent an Email Header Injection attacks
            if (!empty($subject)) {
                $this->subject = $subject;
            }
        }
    }

    /**
     * This function sets the content type for the email.
     *
     * @access public
     * @param string $mime                  the content type (either "multipart/mixed", "text/html",
     *                                      or "text/plain")
     */
    public function set_content_type($mime) {
	    /**
         * "multipart/mixed" = 99
         * "text/html" = 2
         * "text/plain" = 1
         */
	    $this->content_type = $mime;
    }

    /**
     * This function sets the message that will be sent.
     *
     * @access public
     * @param string $message               the message that will be sent
     */
    public function set_message($message) {
        $this->message = (!is_null($message) && is_string($message)) ? $message : '';
    }

    /**
     * This function sets the alternative message that will be sent.
     *
     * @access public
     * @param string $message               the message that will be sent
     */
    public function set_alt_message($message) {
        $this->alt_message = (!is_null($message) && is_string($message)) ? $message : '';
    }

    /**
     * This function adds an attachment to the email message.
     *
     * @access public
     * @param Attachment $attachment        the attachment to be added
     * @param boolean                       whether the attachment is attached to the email message
     */
    public function add_attachment(Attachment $attachment) {
        $this->error = array(
            'message' => 'Failed to add attachment because mail service does not support attachments',
            'code' => 0
        );
        return FALSE;
    }

    /**
     * This function sets an embedded image to the email message that will use the specified
     * content ID.
     *
     * @param string $cid                   the ID used for accessing the image in the message
     * @param string $file                  the file name to the image
     * @param string $alias                 the file name given to the image
     * @return boolean                      whether the image was embedded
     */
    public function set_embedded_image($cid, $file, $alias = '') {
        $this->error = array(
            'message' => 'Failed to embed image because mail service does not support this feature.',
            'code' => 0
        );
        return FALSE;
    }

    /**
     * This function attempts to send the email message to the recipient(s).
     *
     * @access public
     * @return boolean                      returns TRUE if all of the recipient(s) are successfully
     *                                      sent the email message; otherwise, FALSE
     */
    public function send() {
        try {
            if (!isset($this->mailing_list['listID'])) {
                throw new Exception('Failed to send email because no mailing list has been set.');
            }
            if (empty($this->sender)) {
    			throw new Exception('Failed to send email because no sender has been set.');
    		}
            if (empty($this->recipients)) {
    			throw new Exception('Failed to send email because no recipient has been set.');
    		}
            if (empty($this->message)) {
    			throw new Exception('Failed to send email because no message has been set.');
    		}
            $message = '';
            $html_message = '';
            $text_message = '';
            $format = NULL;
            switch ($this->content_type) {
    			case 'multipart/mixed':
    		        $html_message = urlencode($this->message);
                    $text_message = (!empty($this->alt_message)) ? $this->alt_message : strip_tags($this->message);
                    $text_message = urlencode($text_message);
        		    $format = 99;
    			break;
    			case 'text/html':
    				$html_message = urlencode($this->message);
    				$format = 2;
    			break;
    			default:
    			    $message = $this->message;
    			    $format = 1;
    			break;
    		}
            $template_id = '';
            $data = '';
    		foreach ($this->recipients as $recipient) {
    		    $sent = $this->mailer->send($this->mailing_list['listID'], $recipient, $format, $this->reply_to, $template_id, $message, $text_message, $html_message, $this->sender, $this->subject, $data);
    		    if (!$sent) {
    		        // TODO Modify vendor/WhatCounts/WhatCountsDriver.php to get the error message from the response variable
    		        throw new Exception("Failed to send message to: {$recipient}");
    	        }
    		}
        }
        catch (Exception $ex) {
            $this->error = array(
                'message' => $ex->getMessage(),
                'code' => $ex->getCode()
            );
            return FALSE;   
        }
        return TRUE;
    }

    /**
     * This function returns the last error reported.
     *
     * @access public
     * @return array                            the last error reported
     */
	public function get_error() {
	    return $this->error;
	}

    /**
     * This function will log the basic header information when an email is sent.
     *
     * @access public
     * @param boolean $log                      whether to log the email being sent
     */
    public function log($log) {
        //$this->log = $log;
    }

}
?>