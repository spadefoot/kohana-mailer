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
 * This class handles subscriptions to the What Counts mail service.
 *
 * @package Messaging
 * @category Subscriber
 * @version 2012-01-09
 */
class Base_Subscriber_WhatCounts extends Kohana_Object implements Base_Subscriber_Interface {

    /**
     * This variable stores an instance of the What Counts driver class.
     *
     * @access protected
     * @var WhatCountsDriver
     */
    protected $driver = NULL;

    /**
     * This variable stores the name of the mailing list.
     *
     * @access protected
     * @var string
     */
    protected $mailing_list = NULL;

    /**
     * This variable stores the subscriber's email address.
     *
     * @access protected
     * @var string
     */
    protected $subscriber = NULL;

    /**
     * This variable stores the subscriber's data.
     *
     * @access protected
     * @var array
     */
    protected $data  = array();

    /**
     * This variable stores the content type of the body in the email message.
     *
     * @access protected
     * @var integer
     */
    protected $content_type = 1; // 'text/plain'

    /**
     * This variable stores whether an email notification will be sent.
     *
     * @access protected
     * @var boolean
     */
    protected $do_notify = FALSE;

    /**
     * This variable stores an instance of the MailChimp driver class.
     *
     * @access protected
     * @var Mailer
     */
    protected $mailer = NULL;

    /**
     * This variable stores the last error message reported.
     *
     * @access protected
     * @var string
     */
    protected $error = NULL;

    /**
     * Initializes the driver for this mail service.
     *
     * @access public
     * @param array $config                     the configuration array
     */
	public function __construct($config) {
	    $credentials = $config['credentials'];
		$this->driver = new WhatCountsDriver($credentials->username, $credentials->password);
		if (isset($config['mailing_list'])) {
		    $this->set_mailing_list($config['mailing_list']);
		}
	}

    /**
     * This function will set the mailing list.
     *
     * @access public
     * @param string $mailing_list              the key used to identify the mailing list
     */
	public function set_mailing_list($mailing_list) {
		$this->mailing_list = $this->driver->getListByName($mailing_list);
	}

    /**
     * This function sets the subscriber's email and related data.
     *
     * @access public
     * @param string $email                     the subscriber's email address
     * @param array $data                       the data to be sent (e.g. organization, first_name, last_name, address_1, address_2, city, state, postal_code, country, phone)
     */
    public function set_subscriber($email, $data = NULL) {
        $this->subscriber = $email;
        if (is_array($data)) {
            $this->data = array();
            if (!empty($data['organization'])) {
    		//	$this->data['organization'] = $data['organization'];
    		}
    		if (!empty($data['first_name'])) {
    			$this->data['first'] = $data['first_name'];
    		}
    		if (!empty($data['last_name'])) {
    			$this->data['last'] = $data['last_name'];
    		}
    		if (!empty($data['address_1'])) {
    			$this->data['address'] = $data['address_1'];
    		}
    		if (!empty($data['address_2'])) {
    			$this->data['address2'] = $data['address_2'];
    		}
    		if (!empty($data['city'])) {
    			$this->data['city'] = $data['city'];
    		}
    		if (!empty($data['state'])) {
    			$this->data['state'] = $data['state'];
    		}
    		if (!empty($data['postal_code'])) {
    			$this->data['zip'] = $data['postal_code'];
    		}
    		if (!empty($data['country'])) {
    			$this->data['country'] = $data['country'];
    		}
    		if (!empty($data['phone'])) {
    			$this->data['phone'] = $data['phone'];
    		}
	    }
	    else {
	        $this->data = NULL;
	    }
    }

    /**
     * This function sets the content type for the email.
     *
     * @access public
     * @param string $mime                      the content type (either "multipart/mixed", "text/html",
     *                                          or "text/plain")
     */
    public function set_content_type($mime) {
	    switch (strtolower($mime)) {
			case 'multipart/mixed':
			    $this->content_type = 99;
			break;
			case 'text/html':
			    $this->content_type = 2;
			break;
			default:
			    $this->content_type = 1;
			break;
		}
    }

    /**
     * This function will cause a notification email to be sent upon success.
     *
     * @access public
     * @param boolean send                      whether a notification email should be sent
     * @param Mailer $mailer                    the mail service to be used
     */
    public function do_notify($send, $mailer = NULL) {
        $this->do_notify = (is_bool($send)) ? $send : FALSE;
        if (is_a($mailer, 'Mailer')) {
            $this->mailer = $mailer;
        }
        else {
            $this->mailer = NULL;
        }
    }

    /**
     * This function will attempt to subscribe the recipient(s) to the specified mailing list.
     *
     * @access public
     * @param boolean $force                    whether the email address should be forcibly added to the
     *                                          specified mailing list
     * @return boolean                          whether the email address was successfully added to the
     *                                          specified mailing list
     */
	public function subscribe($force = FALSE) {
        try {
            if (empty($this->mailing_list)) {
                throw new Exception('Failed to unsubscribe because no mailing list has been set.');
            }
            if (empty($this->subscriber)) {
                throw new Exception("Failed to unsubscribe because no subscriber has been set.");
            }
            $data = array();
            $data[0] = $this->data;
            $data[0]['email'] = rawurlencode($this->subscriber);
		    $force = (!$force) ? 0 : 1;		
    		$response = $this->subscriber->subscribe($this->mailing_list['listID'], $data, $this->content_type, $force);
    		if (!(isset($response['isSuccess']) && $response['isSuccess'])) {
    			throw new Exception("Failed to subscribe because {$response['reason']}");
    		}
    		if ($this->do_notify) {
			    $mailer = $this->mailer;
			    if (is_null($mailer)) {
			        $mailer = new Mailer('WHAT_COUNTS');
			        $mailer->set_recipient($this->subscriber);
			        $mailer->set_subject('Welcome! Your email has been subscribed.');
			        $mailer->set_message('We have successfully subscribed this email to our mailing list.');
		        }
			    $sent = $mailer->send();
			    if (!$sent) {
			        $error = $mailer->get_error();
			        throw new Exception($error['message'], $error['code']);
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
        $this->error = NULL;
		return TRUE;
	}

    /**
     * This function will unsubscribe the specified email address from the associated
     * mailing list.
     *
     * @access public
     * @param string $email                     the email address to be unsubscribed
     * @return boolean
     */
    public function unsubscribe($email) {
        try {
            if (empty($this->mailing_list)) {
                throw new Exception('Failed to unsubscribe because no mailing list has been set.');
            }
            if (empty($this->subscriber)) {
                throw new Exception("Failed to unsubscribe because no subscriber has been set.");
            }
            $data = 'email^' . rawurlencode($this->subscriber);
            $response = $this->subscriber->unsubscribe($this->mailing_list['listID'], $data, 0);
            if (!(isset($response['isSuccess']) && $response['isSuccess'])) {
    			throw new Exception("Failed to unsubscribe because {$response['reason']}");
    		}
    		if ($this->do_notify) {
			    $mailer = $this->mailer;
			    if (is_null($mailer)) {
			        $mailer = new Mailer('WHAT_COUNTS');
			        $mailer->set_recipient($this->subscriber);
			        $mailer->set_subject('Goodbye! Your email was unsubscribed.');
			        $mailer->set_message('We have successfully unsubscribed this email from our mailing list.');
		        }
			    $sent = $mailer->send();
			    if (!$sent) {
			        $error = $mailer->get_error();
			        throw new Exception($error['message'], $error['code']);
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
        $this->error = NULL;
		return TRUE;
    }

    /**
     * This function returns the last error reported.
     *
     * @access public
     * @return array                                the last error reported
     */
	public function get_error() {
        return $this->error;
	}

}
?>