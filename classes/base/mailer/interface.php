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
 * This interface specifies the functions that a mailer driver class must implement.
 *
 * @package Messaging
 * @category Mailer
 * @version 2012-01-09
 */
interface Base_Mailer_Interface {

	/**
	 * This function provides a way to pass specific options to the mail service.
	 *
	 * @access public
	 * @param array $options            any special options for the mail service
	 */
	public function set_options(Array $options);

    /**
     * This function adds a recipient to the list of recipients that will receive
     * the email.
     *
     * @access public
     * @param EmailAddress $address     the email address and name
     * @return boolean                  whether the recipient was added
     */
    public function add_recipient(EmailAddress $address);

    /**
     * This function will carbon copy the specified email account.
     *
     * @access public
     * @param EmailAddress $address     the email address and name
     * @return boolean                  whether the recipient was added
     */
    public function add_cc(EmailAddress $address);

    /**
     * This function will blind carbon copy the specified email account.
     *
     * @access public
     * @param EmailAddress $address     the email address and name
     * @return boolean                  whether the recipient was added
     */
    public function add_bcc(EmailAddress $address);

    /**
     * This function sets the sender of the email message.
     *
     * @access public
     * @param EmailAddress $address     the email address and name
     * @return boolean                  whether the sender was set
     */
    public function set_sender(EmailAddress $address);

    /**
     * This function sets the reply to email address.
     *
     * @access public
     * @param EmailAddress $address     the email address and name
     * @return boolean                  whether the reply-to was set
     */
	public function set_reply_to(EmailAddress $address);

    /**
     * This function sets the subject line for the email message.
     *
     * @access public
     * @param string $subject           the subject line
     */
    public function set_subject($subject);

    /**
     * This function sets the content type for the email.
     *
     * @access public
     * @param string $mime              the content type (either "multipart/mixed", "text/html",
     *                                  or "text/plain")
     */
    public function set_content_type($mime);

    /**
     * This function sets the message that will be sent.
     *
     * @access public
     * @param string $message           the message that will be sent
     */
    public function set_message($message);

    /**
     * This function sets the alternative message that will be sent.
     *
     * @access public
     * @param string $message           the message that will be sent
     */
    public function set_alt_message($message);

    /**
     * This function adds an attachment to the email message.
     *
     * @access public
     * @param Attachment $attachment    the attachment to be added
     * @param boolean                   whether the attachment is attached to the email message
     */
    public function add_attachment(Attachment $attachment);

    /**
     * This function sets an embedded image to the email message that will use the specified
     * content ID.
     *
     * @param string $cid               the ID used for accessing the image in the message
     * @param string $file              the file name to the image
     * @param string $alias             the file name given to the image
     * @return boolean                  whether the image was embedded
     */
    public function set_embedded_image($cid, $file, $alias = '');

    /**
     * This function attempts to send the email message to the recipient(s).
     *
     * @access public
     * @return boolean                  returns TRUE if all of the recipient(s) are successfully
     *                                  sent the email message; otherwise, FALSE
     */
    public function send();

    /**
     * This function returns the last error reported.
     *
     * @access public
     * @return array                    the last error reported
     */
	public function get_error();

    /**
     * This function will log the basic header information when an email is sent.
     *
     * @access public
     * @param boolean $log              whether to log the email being sent
     */
    public function log($log);

}
?>