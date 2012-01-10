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
 * This class sends emails via the specified mail service.
 *
 * @package Messaging
 * @category Mailer
 * @version 2012-01-09
 */
class Base_Mailer extends Kohana_Object implements Base_Mailer_Interface {

    /**
     * This variable stores an array of drivers.
     *
     * @access protected
     * @var array
     */
	protected $driver = array();

    /**
     * This constructor initializes the driver for the specified mail service.
     *
     * @access public
     * @return Mailer                               an instance of this class
     */
	public function __construct() {
        // Initializes an array to temporarily store all mailer configurations.
        $params = array();
        // Loads all mailer configurations into array
        if (func_num_args() < 1) {
            $group = 'mailer.default';
			if (($config = Kohana::$config->load($group)) === NULL) {
				throw new Kohana_InvalidProperty_Exception('Message: Cannot load configuration. Reason: Configuration group :group is undefined.', array(':group' => $group));
			}
			$params[] = $config;
        }
        else {
            $configs = func_get_args();
            foreach ($configs as $config) {
                if (is_string($config)) {
        			$group = 'mailer.' . $config;
        			if (($config = Kohana::$config->load($group)) === NULL) {
						throw new Kohana_InvalidProperty_Exception('Message: Cannot load configuration. Reason: Configuration group :group is undefined.', array(':group' => $group));
        			}
        			$params[] = $config;
        		}
        		else {
        		    if (!is_array($config) || empty($config)) {
        		        throw new Kohana_InvalidProperty_Exception('Message: Cannot load configuration. Reason: Invalid configuration array.', array(':config', Debug::vars($config)));
        		    }
        		    $params[] = $config;
        		}
            }
        }
        // Instantiates the appropriate drivers
        for ($i = 0; $i < count($params); $i++) {
            // Wraps the driver's credentials
            if (isset($params[$i]['credentials'])) {
                $params[$i]['credentials'] = new Credentials($params[$i]['credentials']['username'], $params[$i]['credentials']['password']);
            }
            // Wraps the driver's default email address for the sender
            if (isset($params[$i]['sender'])) {
                if (!isset($params[$i]['sender']['name'])) {
                    $params[$i]['sender']['name'] = '';
                }
                $params[$i]['sender'] = new EmailAddress($params[$i]['sender']['email'], $params[$i]['sender']['name']);
            }
    		// Creates the driver class name
    		$driver = 'Mailer_' . $params[$i]['driver'];
    		// Initializes the driver
    		$this->driver[$i] = new $driver($params[$i]);
    		// Validates the driver
    		if (!($this->driver[$i] instanceof Base_Mailer_Interface)) {
    			throw new Kohana_ClassCast_Exception('Message: Cannot cast to interface.  Reason: Class :class does not implement interface :interface.', array(':class' => $driver, ':interface' => 'Base_Mailer_Interface'));
            }
        }
	}

	/**
	 * This function provides a way to pass specific options to the mail service.
	 *
	 * @access public
	 * @param array $options                        any special options for the mail service
	 */
	public function set_options(Array $options) {
        foreach ($this->driver as $driver) {
            $driver->set_options($options);
        }
	}

    /**
     * This function adds the specified mailing list from the configuration file.
     *
     * @access public
     * @param scaler $list                          either the group id or a configuration array
     * @return boolean                              whether the recipients were added
     */
    public function add_mailing_list($list) {
    	if (is_string($list)) {
    	    $group = 'mailer-lists.' . $list;
    		if (($list = Kohana::$config->load($group)) === NULL) {
    			throw new Kohana_InvalidProperty_Exception('Message: Cannot load configuration. Reason: Configuration group :group is undefined.', array(':group' => $group));
    		}
        }
    	foreach ($list as $category => $recipients) {
    	    foreach ($recipients as $recipient) {
    	        $email = new EmailAddress($recipient['email'], ((isset($recipient['name'])) ? $recipient['name'] : ''));
                switch (strtolower($category)) {
                    case 'recipient':
                        $this->add_recipient($email);
                    break;
                    case 'cc':
                        $this->add_cc($email);
                    break;
                    case 'bcc':
                        $this->add_bcc($email);
                    break;
                }
            }
    	}
    	return TRUE;
    }

    /**
     * This function adds a recipient to the list of recipients that will receive
     * the email.
     *
     * @access public
     * @param EmailAddress $address                 the email address and name
     * @return boolean                              whether the recipient was added
     */
    public function add_recipient(EmailAddress $address) {
        $successful = TRUE;
        foreach ($this->driver as $driver) {
            $good = $driver->add_recipient($address);
            if (!$good) {
                $successful = FALSE;
            }
        }
        return $successful;
    }

    /**
     * This function will carbon copy the specified email account.
     *
     * @access public
     * @param EmailAddress $address                 the email address and name
     * @return boolean                              whether the recipient was added
     */
    public function add_cc(EmailAddress $address) {
        $successful = TRUE;
        foreach ($this->driver as $driver) {
            $good = $driver->add_cc($address);
            if (!$good) {
                $successful = FALSE;
            }
        }
        return $successful;
    }

    /**
     * This function will blind carbon copy the specified email account.
     *
     * @access public
     * @param EmailAddress $address                 the email address and name
     * @return boolean                              whether the recipient was added
     */
    public function add_bcc(EmailAddress $address) {
        $successful = TRUE;
        foreach ($this->driver as $driver) {
            $good = $driver->add_bcc($address);
            if (!$good) {
                $successful = FALSE;
            }
        }
        return $successful;
    }

    /**
     * This function sets the sender of the email message.
     *
     * @access public
     * @param EmailAddress $address                 the email address and name
     * @return boolean                              whether the sender was set
     */
    public function set_sender(EmailAddress $address) {
        $successful = TRUE;
        foreach ($this->driver as $driver) {
            $good = $driver->set_sender($address);
            if (!$good) {
                $successful = FALSE;
            }
        }
        return $successful;
    }

    /**
     * This function sets the reply-to email address.
     *
     * @access public
     * @param EmailAddress $address                 the email address and name
     * @return boolean                              whether the reply-to was set
     */
	public function set_reply_to(EmailAddress $address) {
        $successful = TRUE;
        foreach ($this->driver as $driver) {
            $good = $driver->set_reply_to($address);
            if (!$good) {
                $successful = FALSE;
            }
        }
        return $successful;
	}

    /**
     * This function sets the "subject" for the email message.
     *
     * @access public
     * @param string $subject                       the subject
     */
    public function set_subject($subject) {
        foreach ($this->driver as $driver) {
            $driver->set_subject($subject);
        }
    }

    /**
     * This function sets the content type for the email.
     *
     * @access public
     * @param string $mime                          the content type (either "multipart/mixed",
     *                                              "text/html", or "text/plain")
     */
    public function set_content_type($mime) {
        foreach ($this->driver as $driver) {
            $driver->set_content_type($mime);
        }
    }

    /**
     * This function sets the message that will be sent.
     *
     * @access public
     * @param string $message                       the message that will be sent
     */
    public function set_message($message) {
        foreach ($this->driver as $driver) {
            $driver->set_message($message);
        }
    }

    /**
     * This function sets the alternative message that will be sent.
     *
     * @access public
     * @param string $message                       the message that will be sent
     */
    public function set_alt_message($message) {
        foreach ($this->driver as $driver) {
            $driver->set_alt_message($message);
        }
    }

    /**
     * This function adds an attachment to the email message.
     *
     * @access public
     * @param Attachment $attachment                the attachment to be added
     * @param boolean                               whether the attachment is attached to the email message
     */
    public function add_attachment(Attachment $attachment) {
        $successful = TRUE;
        foreach ($this->driver as $driver) {
            $good = $driver->add_attachment($attachment);
            if (!$good) {
                $successful = FALSE;
            }
        }
        return $successful;
    }

    /**
     * This function sets an embedded image to the email message that will use the specified
     * content ID.
     *
     * @param string $cid                           the ID used for accessing the image in the message
     * @param string $file                          the file name to the image
     * @param string $alias                         the file name given to the image
     * @return boolean                              whether the image was embedded
     */
    public function set_embedded_image($cid, $file, $alias = '') {
        $successful = TRUE;
        foreach ($this->driver as $driver) {
            $good = $driver->set_embedded_image($cid, $file, $alias);
            if (!$good) {
                $successful = FALSE;
            }
        }
        return $successful;
    }

    /**
     * This function attempts to send the email message to the recipient(s).
     *
     * @access public
     * @return boolean                              returns TRUE if all of the recipient(s)
     *                                              are successfully sent the email message;
     *                                              otherwise, FALSE
     */
    public function send() {
        foreach ($this->driver as $driver) {
            $good = $driver->send();
            if ($good) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * This function returns the last error reported.
     *
     * @access public
     * @return array                                the last error reported
     */
	public function get_error() {
        foreach ($this->driver as $driver) {
            $error = $driver->get_error();
            if (is_array($error)) {
                return $error;
            }
        }
	    return NULL;
	}

    /**
     * This function will log the basic header information when an email is sent.
     *
     * @access public
     * @param boolean $log                          whether to log the email being sent
     */
    public function log($log) {
        foreach ($this->driver as $driver) {
            $driver->log($log);
        }
    }

    /**
     * This function sends a request to the specified email address for it to be verified.
     *
     * @access public
     * @param EmailAddress $address                 the email address to be verified
     * @return boolean                              whether the request was sent
     */
    public function request_email_verification(EmailAddress $address) {
        return $this->driver[0]->request_email_verification($address);
    }

}
?>