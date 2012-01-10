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

include_once(dirname(__FILE__) . '/phpMailer_v2.2.1_/class.phpmailer.php');
//include_once(dirname(__FILE__) . '/PHPMailer_v5.1/class.phpmailer.php');

/**
 * This class sends emails via the PHPMailer mail service.
 *
 * @package Messaging
 * @category Mailer
 * @version 2012-01-09
 *
 * @see http://phpmailer.worxware.com/
 */
class PHPMailerDriver {

    /**
    * This variable stores an instance of the PHP Mailer driver class.
    *
    * @access protected
    * @var PHPMailerDriver
    */
    protected $mailer;

    /**
    * Initializes the driver for this mail service.
    *
    * @access public
    * @param string $host               the host address
    * @param string $port               the port number
    * @param Credentials $credentials   the credentials
    * @return PHPMailerDriver            an instance of this class
    */
	public function __construct($host, $port, Credentials $credentials) {
	    $this->mailer = new PHPMailer();
	    $this->mailer->IsSMTP();						    // tells the class to use SMTP
		$this->mailer->SMTPSecure = 'ssl';				    // sets the prefix to the server
		$this->mailer->SMTPAuth	= TRUE;					    // enable SMTP authentication
		$this->mailer->Host = $host;
		$this->mailer->Port = $port;
		$this->mailer->Username	= $credentials->username;
		$this->mailer->Password	= $credentials->password;
		$this->mailer->Subject = '(no subject)';
		$this->mailer->IsHTML(FALSE);
	}

    /**
    * This function adds a recipient to the list of recipients that will receive
    * the email.
    *
    * @access public
    * @param EmailAddress $address      the email address and name
    * @return boolean                   whether the recipient was added
    */
    public function add_recipient(EmailAddress $address) {
        $this->mailer->AddAddress($address->email, $address->name);
        return TRUE;
    }

    /**
    * This function will carbon copy the specified email account.
    *
    * @access public
    * @param EmailAddress $address      the email address and name
    * @return boolean                   whether the recipient was added
    */
    public function add_cc(EmailAddress $address) {
        $this->mailer->AddCC($address->email, $address->name);
        return TRUE;
    }

    /**
    * This function will blind carbon copy the specified email account.
    *
    * @access public
    * @param EmailAddress $address      the email address and name
    * @return boolean                   whether the recipient was added
    */
    public function add_bcc(EmailAddress $address) {
        $this->mailer->AddBCC($address->email, $address->name);
        return TRUE;
    }

    /**
    * This function sets the sender of the email message.
    *
    * @access public
    * @param EmailAddress $address      the email address and name
    * @return boolean                   whether the sender was set
    */
    public function set_sender(EmailAddress $address) {
        // phpMailer_v2.2.1
        $this->mailer->From = $address->email;
        $this->mailer->FromName = $address->name;
        // PHPMailer_v5.1
        // $this->mailer->SetFrom($address->email, $address->name);
        return TRUE;
    }

    /**
    * This function sets the reply-to email address.
    *
    * @access public
    * @param EmailAddress $address      the email address and name
    * @return boolean                   whether the reply-to was set
    */
	public function set_reply_to(EmailAddress $address) {
        $this->mailer->ReplyTo = array(); // Note: the field's visiability was changed to public in class.phpmailer.php
        $this->mailer->AddReplyTo($address->email, $address->name);
        return TRUE;
	}

    /**
    * This function sets the subject line for the email message.
    *
    * @access public
    * @param string $subject        the subject line
    */
    public function set_subject($subject) {
        $this->mailer->Subject = '(no subject)';
        if (is_string($subject)) {
            $subject = trim(preg_replace('/\R/', '', $subject)); // aims to prevent an Email Header Injection attacks
            if (!empty($subject)) {
                $this->mailer->Subject = $subject;
            }
        }
    }

    /**
    * This function sets the content type for the email.
    *
    * @access public
    * @param string $mime       the content type (either "multipart/mixed", "text/html",
    *                           or "text/plain")
    */
    public function set_content_type($mime) { 
		$this->mailer->IsHTML(preg_match('/^(multipart\/mixed)|(text\/html)$/', $mime));
    }

    /**
    * This function sets the message that will be sent.
    *
    * @access public
    * @param string $message        the message that will be sent
    */
    public function set_message($message) {
        $this->mailer->Body = (!is_null($message) && is_string($message)) ? $message : '';
    }

    /**
    * This function sets the alternative message that will be sent.
    *
    * @access public
    * @param string $message        the message that will be sent
    */
    public function set_alt_message($message) {
        $this->mailer->AltBody = (!is_null($message) && is_string($message)) ? $message : '';
    }

    /**
    * This function adds an attachment to the email message.
    *
    * @access public
    * @param Attachment $attachment     the attachment to be added
    * @param boolean                    whether the attachment is attached to the email message
    */
    public function add_attachment(Attachment $attachment) {
        $this->mailer->AddStringAttachment($attachment->contents, $attachment->name, $attachment->encoding, $attachment->mime);
        return TRUE;
    }

    /**
    * This function sets an embedded image to the email message that will use the specified
    * content ID.
    *
    * @param string $cid            the ID used for accessing the image in the message
    * @param string $file           the file name to the image
    * @param string $alias          the file name given to the image
    * @return boolean               whether the image was embedded
    */
    public function set_embedded_image($cid, $file, $alias = '') {
        $result = $this->mailer->AddEmbeddedImage($file, $cid, $alias);
        return $result;
    }

    /**
    * This function attempts to send the email message to the recipient(s).
    *
    * @access public
    * @return boolean               returns TRUE if all of the recipient(s) are successfully
    *                               sent the email message; otherwise, FALSE
    */
    public function send() {
		$sent = $this->mailer->Send();
		if ($sent) {
		    $this->mailer->ErrorInfo = '';
		}
		return $sent; 
    }

    /**
    * This function returns the last error reported.
    *
    * @access public
    * @return array                             the last error reported
    */
	public function get_error() {
		if (!empty($this->mailer->ErrorInfo)) {
    		$error = array(
    		  'message' => $this->mailer->ErrorInfo,
    		  'code' => 0
    		);
		    return $error;
	    }
	    return NULL;
	}

    /**
    * This function will log the basic header information when an email is sent.
    *
    * @access public
    * @param boolean $log                       whether to log the email being sent
    */
    public function log($log) {
        //$this->log = $log;
    }

}
?>