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

include_once(Kohana::find_file('vendor', 'MailChimp/MCAPI.class', $ext = 'php'));

/**
 * This class handles subscriptions to the Mail Chimp mail service.
 *
 * @package Messaging
 * @category Subscriber
 * @version 2012-01-09
 *
 * @see http://admin.mailchimp.com/account/api
 */
class Base_Subscriber_MailChimp extends Kohana_Object implements Base_Subscriber_Interface {

    /**
     * This variable stores an instance of the Mail Chimp driver class.
     *
     * @access protected
     * @var MCAPI
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
    protected $data  = NULL;

    /**
     * This variable stores the content type of the body in the email message.
     *
     * @access protected
     * @var string
     */
    protected $content_type = 'text';

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
		$this->driver = new MCAPI($config['api-key']);
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
		$this->mailing_list = $this->get_list_by_name($mailing_list);
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
    			$this->data['ORG'] = $data['organization'];
    		}
    		if (!empty($data['first_name'])) {
    			$this->data['FIRST_NAME'] = $data['first_name'];
    		}
    		if (!empty($data['last_name'])) {
    			$this->data['LAST_NAME'] = $data['last_name'];
    		}
    		if (!empty($data['address_1'])) {
    			$this->data['ADDRESS']['addr1'] = $data['address_1'];
    		}
    		if (!empty($data['address_2'])) {
    			$this->data['ADDRESS']['addr2'] = $data['address_2'];
    		}
    		if (!empty($data['city'])) {
    			$this->data['ADDRESS']['city'] = $data['city'];
    		}
    		if (!empty($data['state'])) {
    			$this->data['ADDRESS']['state'] = $data['state'];
    		}
    		if (!empty($data['postal_code'])) {
    			$this->data['ADDRESS']['zip'] = $data['postal_code'];
    		}
    		if (!empty($data['country'])) {
    			$this->data['ADDRESS']['country'] = $data['country'];
    		}
    		if (!empty($data['phone'])) {
    			$this->data['PHONE'] = $data['phone'];
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
            case 'text/html':
                $this->content_type = 'html';
            break;
            case 'text/plain':
            default:
                $this->content_type = 'text';
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
                throw new Exception('Failed to subscribe because no mailing list has been set.');
            }
            if (empty($this->subscriber)) {
                throw new Exception("Failed to subscribe because no subscriber has been set.");
            }
            
            $do_notify = ($this->do_notify && is_null($this->mailer));

            $result = $this->driver->listSubscribe($this->mailing_list, $this->subscriber, $this->data, $this->content_type, TRUE, FALSE, TRUE, $do_notify);
            
            if ($result === FALSE) {
			    throw new Exception($this->driver->errorMessage, $this->driver->errorCode);
			}
			
			if ($this->do_notify && !is_null($this->mailer)) {
			    $sent = $this->mailer->send();
			    if (!$sent) {
			        $error = $this->mailer->get_error();
			        throw new Exception($error['message'], $error['code']);
			    }
		    }
		} catch (Exception $ex) {
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
     * This function will unsubscribe the specified email address from the specified mailing list.
     *
     * @access public
     * @param boolean $delete                   whether to delete the subscription completely
     * @return boolean                          whether the subscriber was unsubscribed
     */
    public function unsubscribe($delete = FALSE) {
        try {
            if (empty($this->mailing_list)) {
                throw new Exception('Failed to unsubscribe because no mailing list has been set.');
            }
            if (empty($this->subscriber)) {
                throw new Exception("Failed to unsubscribe because no subscriber has been set.");
            }
            
            $do_notify = ($this->do_notify && is_null($this->mailer));
            
			$result = $this->driver->listUnsubscribe($this->mailing_list, $this->subscriber, $delete, $do_notify, $do_notify);
			if ($result === FALSE) {
			    throw new Exception($this->driver->errorMessage, $this->driver->errorCode);
			}
			
			if ($this->do_notify && !is_null($this->mailer)) {
			    $sent = $this->mailer->send();
			    if (!$sent) {
			        $error = $this->mailer->get_error();
			        throw new Exception($error['message'], $error['code']);
			    }
		    }
        } catch (Exception $ex) {
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
     * @return array                            the last error reported
     */
	public function get_error() {
        return $this->error;
	}

    ///////////////////////////////////////////////////////////////HELPERS//////////////////////////////////////////////////////////////

    /**
     * This function is used to fetch the list's ID using its name.
     *
     * @access protected
     * @param string $name                      the list's name
     * @return string                           the list's ID
     */
	protected function get_list_by_name($name) {
		$lists = $this->driver->lists(array('list_name' => $name));
		if (!empty($lists['data'])) {
			return $lists['data'][0]['id']; // Just grab the first one since we are only expecting one result
		}
		return NULL;
	}

}
?>