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
 * This class send emails via the Postmark mail service.
 *
 * @package Messaging
 * @category Mailer
 * @version 2012-01-09
 *
 * @see http://developer.postmarkapp.com/developer-build.html
 */
class Base_Mailer_Postmark extends Kohana_Object implements Base_Mailer_Interface {

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
     * This variable stores the number of recipients added.
     *
     * @access protected
     * @var integer
     */
    protected $recipient = NULL;

    /**
     * This variable stores a list of email addresses (and names) to be carbon copied.
     *
     * @access protected
     * @var array
     */
    protected $cc = array();

    /**
     * This variable stores a list of email addresses (and names) to be carbon copied.
     *
     * @access protected
     * @var array
     */
    protected $bcc = array();

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
     * This variable stores the subject of the email.
     *
     * @access protected
     * @var string
     */
    protected $subject = '(no subject)';

    /**
     * This variable stores the tag assigned to the email.
     *
     * @access protected
     * @var string
     */
    protected $tag = '';

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
     * This variable stores a list of all attachments added.
     *
     * @access protected
     * @var array
     */
    protected $attachments = array();

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
        if (isset($config['reply-to'])) {
    	    $this->set_reply_to($config['reply-to']);
        }
        else {
            $this->reply_to = $this->sender;
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
        if (is_null($this->recipient)) {
            $this->recipient = $address->as_string();
            return TRUE;
        }
        return $this->add_cc($address);
    }

    /**
     * This function will carbon copy the specified email account.
     *
     * @access public
     * @param EmailAddress $address         the email address and name
     * @return boolean                      whether the recipient was added
     */
    public function add_cc(EmailAddress $address) {
        $this->cc[] = $address->as_string();
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
        $this->bcc[] = $address->as_string();
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
        $this->attachments[] = array(
            'Name' => $attachment->name,
            'ContentType' => $attachment->mime,
            'Content' => $attachment->data
        );
        return TRUE;
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
    		if (empty($this->sender)) {
    			throw new Exception('Failed to send email because no sender has been set.');
    		}

    		if (empty($this->recipient)) {
    			throw new Exception('Failed to send email because no recipient has been set.');
    		}

    		if ((1 + count($this->cc) + count($this->bcc)) > 20) { // The 1 is for the recipient.
    			throw new Exception("Failed to send email because too many email recipients have been set.");
    		}

            if (empty($this->message)) {
    			throw new Exception('Failed to send email because no message has been set.');
    		}

    		$params = $this->prepare_data();

    		$headers = array(
    			'Accept: application/json',
    			'Content-Type: application/json',
    			'X-Postmark-Server-Token: ' . $this->api_key
    		);

    		$curl = curl_init();
    		curl_setopt($curl, CURLOPT_URL, $this->url);
    		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
    		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
    		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    		$response = curl_exec($curl);

            $error = curl_error($curl);

    		if (!empty($error)) {
    			throw new Exception("Failed to send email for the following reason: {$error}");
    		}

    		$status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

    		if (!$this->is_successful($status_code)) {
    			$message = json_decode($response)->Message;
    			throw new Exception("Failed to send email. Mail service returned HTTP status code {$status_code} with message: {$message}");
    		}
        }
        catch (Exception $ex) {
            $this->error = array(
                'message' => $ex->getMessage(),
                'code' => $ex->getCode()
            );
            return FALSE;
        }
        $this->error = NULL;
        return TRUE;
    }

    /**
     * This function returns the last error reported.
     *
     * @access public
     * @return array                        the last error reported
     */
    public function get_error() {
        return $this->error;
    }

    /**
     * This function will log the basic header information when an email is sent.
     *
     * @access public
     * @param boolean $log                  whether to log the email being sent
     */
    public function log($log) {
        //$this->log = $log;
    }

    ///////////////////////////////////////////////////////////////HELPERS//////////////////////////////////////////////////////////////

	/**
	 * This function prepares the data for sending it to the Web service.
	 *
	 * @access protected
	 * @return array                        the data array
	 */
	protected function prepare_data() {
		$data = array();

		$data['From'] = $this->sender;
		
		if (!is_null($this->reply_to)) {
			$data['ReplyTo'] = $this->reply_to;
		}

		$data['To'] = $this->recipient;

		if (!empty($this->cc)) {
			$data['Cc'] = implode(',', $this->cc);
		}

		if (!empty($this->bcc)) {
			$data['Bcc'] = implode(',', $this->bcc);
		}

		$data['Subject'] = $this->subject;

		if (!empty($this->tags)) {
			$data['Tag'] = $this->tags[0];
		}

        switch ($this->content_type) {
            case 'multipart/mixed':
                $data['HtmlBody'] = $this->message;
                $data['TextBody'] = (!empty($this->alt_message)) ? $this->alt_message : strip_tags($this->message);
            break;
            case 'text/html':
                $data['HtmlBody'] = $this->message;
            break;
            case 'text/plain':
                $data['TextBody'] = $this->message;
            break;
            default:
                throw new Exception('Failed to send email because mime type is unknown.');
            break;
        }

        if (!empty($this->attachments)) {
            $data['Attachments'] = $this->attachments;
        }

		return $data;
	}

	/**
	 * This function tests for whether the response status code is in the 200's (i.e. between 200-299).
	 *
	 * @access protected
	 * @param integer $value                the response's status code
	 * @return boolean                      whether the specified status code is in the 200's
	 */
	protected function is_successful($status_code) {
		return intval($status_code / 100) == 2;
	}

}
?>