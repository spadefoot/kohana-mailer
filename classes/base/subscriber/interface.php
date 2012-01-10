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
 * This interface specifies the functions that a subscriber driver class must implement.
 *
 * @package Messaging
 * @category Subscriber
 * @version 2012-01-09
 */
interface Base_Subscriber_Interface {

    /**
     * This function will set the mailing list.
     *
     * @access public
     * @param string $mailing_list              the key used to identify the mailing list
     */
    public function set_mailing_list($mailing_list);

    /**
     * This function sets the subscriber's email and related data.
     *
     * @access public
     * @param string $email                     the subscriber's email address
     * @param array $data                       the data to be sent (e.g. first_name, last_name, address_1, address_2, city, state, postal_code, country, phone)
     */
    public function set_subscriber($email, $data = NULL);

    /**
     * This function sets the content type for the email.
     *
     * @access public
     * @param string $mime                      the content type (either "multipart/mixed", "text/html",
     *                                          or "text/plain")
     */
    public function set_content_type($mime);

    /**
     * This function will cause a notification email to be sent upon success.
     *
     * @access public
     * @param boolean send                      whether a notification email should be sent
     * @param Mailer $mailer                    the mail service to be used
     */
    public function do_notify($send, $mailer = NULL);

    /**
     * This function will attempt to subscribe the recipient(s) to the specified mailing list.
     *
     * @access public
     * @param boolean $force                    whether the email address should be forcibly added to the
     *                                          specified mailing list
     * @return boolean                          whether the email address was successfully added to the
     *                                          specified mailing list
     */
	public function subscribe($force = FALSE);

    /**
     * This function will unsubscribe the specified email address from the specified mailing list.
     *
     * @access public
     * @param boolean $delete                   whether to delete the subscription completely
     * @return boolean                          whether the subscriber was unsubscribed
     */
    public function unsubscribe($delete = FALSE);

    /**
     * This function returns the last error reported.
     *
     * @access public
     * @return array                            the last error reported
     */
	public function get_error();

}
?>