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
 * This class send emails via the Mail Chimp mail service.
 *
 * @package Messaging
 * @category Mailer
 * @version 2012-01-09
 *
 * @see http://admin.mailchimp.com/account/api
 * @see http://apidocs.mailchimp.com/sts/1.0/
 * @see http://apidocs.mailchimp.com/sts/1.0/sendemail.func.php
 */
class Base_Mailer_MailChimp extends Kohana_Object implements Base_Mailer_Interface {

    /**
     * This variable stores an instance of the MailChimp driver class.
     *
     * @access protected
     * @var MailChimpDriver
     */
    protected $mailer = NULL;    

    /**
     * This variable stores the URL to the mail service.
     *
     * @access protected
     * @var string
     */
    protected $url = NULL;

	/**
     * This variable stores the API key.
     *
     * @access protected
     * @var string
     */
    protected $api_key = NULL;

    /**
     * This variable stores the tags assigned to the email.
     *
     * @access protected
     * @var array
     */
    protected $tags = array();

    /**
     * This variable stores what should be tracked via the mail service.
     *
     * @access protected
     * @var array
     */
    protected $track = array(
        'clicks' => FALSE,
        'opens' => FALSE
    );

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
     * @var EmailAddress
     */
    protected $sender = NULL;

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
        $this->url = $config['url'];
		$this->api_key = $config['api-key'];
        if (isset($config['sender'])) {
    	    $this->set_sender($config['sender']);
        }
        if (isset($config['options'])) {
            $this->set_options($config['options']);
        }
	}

	/**
	 * This function provides a way to pass specific options to the mail service.
	 *
	 * @access public
	 * @param array $options                any special options for the mail service
	 */
	public function set_options(Array $options) {
		if (isset($options['tags'])) {
		    $this->tags = array_merge($options['tags'], $this->tags);
	    }
	    if (isset($options['track']['clicks'])) {
            $this->track['clicks'] = $options['track']['clicks'];
	    }
	    if (isset($options['track']['opens'])) {
	        $this->track['opens'] = $options['track']['opens'];
	    }
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
        $this->recipients[] = $address;
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
        $this->recipients[] = $address;
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
        $this->recipients[] = $address;
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
        $this->sender = $address;
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
	    return FALSE;
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
        $this->content_type = strtolower($mime);
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
    		if (is_null($this->sender)) {
    			throw new Exception('Failed to send email because no sender has been set.');
    		}

    		if (empty($this->recipients)) {
    			throw new Exception('Failed to send email because no recipient has been set.');
    		}

            if (empty($this->message)) {
    			throw new Exception('Failed to send email because no message has been set.');
    		}

            if ($this->content_type == 'multipart/mixed') {
                $alt_message = (!empty($this->alt_message)) ? $this->alt_message : strip_tags($this->message);
            }
            else {
                $alt_message = '';
            }

            for ($i = 0; $i < count($this->recipients); $i++) {
                $params = array(
                    'apikey' => $this->api_key,
                    'message' => array(
                        'html' => $this->message,
                        'text' => $alt_message,
                        'subject' => $this->subject,
                        'from_email' => $this->sender->email,
                        'from_name' => $this->sender->name,
                        'to_email' => array($this->recipients[$i]->email),
                        'to_name' => array($this->recipients[$i]->name)
                    ),
                    'track_opens' => $this->track['opens'],
                    'track_clicks' => $this->track['clicks'],
                    'tags' => $this->tags
                );

                $curl = curl_init();
        		curl_setopt($curl, CURLOPT_URL, $this->url);
        		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    		    curl_setopt($curl, CURLOPT_POST, TRUE);
        		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
                $response = curl_exec($curl);

                $json = json_decode($response);

                $error = curl_error($curl);

        		if (!empty($error)) {
        			throw new Exception("Failed to send email for the following reason: {$error}");
        		}

        		$status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);

                if (!$this->is_successful($status_code)) {
                    $message = preg_replace('/' . $this->api_key . '/', 'XXXXXXXXXXXXXXXXXXXXXXXXXX', $json->message);
        			throw new Exception("Failed to send email. Mail service returned HTTP status code {$status_code}. {$message}");
        		}

                if (!preg_match('/^(sent|queued)$/', $json->status)) {
                    $message = preg_replace('/' . $this->api_key . '/', 'XXXXXXXXXXXXXXXXXXXXXXXXXX', $json->message);
                    throw new Exception("Failed to send email. Mail service returned AWS code {$json->aws_code} with message: {$message}");
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

    ///////////////////////////////////////////////////////////////HELPERS//////////////////////////////////////////////////////////////

	/**
	 * This function tests for whether the response status code is in the 200's (i.e. between 200-299).
	 *
	 * @access protected
	 * @param integer $value                    the response's status code
	 * @return boolean                          whether the specified status code is in the 200's
	 */
	protected function is_successful($status_code) {
		return intval($status_code / 100) == 2;
	}

}
?>