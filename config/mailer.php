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
 * Specifies the driver configurations for the Mailer class.
 *
 * @package Messaging
 * @category Mailer
 * @version 2012-01-09
 */
$config = array();

$config['AMAZON'] = array(
    'driver'            => 'Amazon',
    'api-key' 	        => 'XXXXXXXXXXXXXXXXXXXX',
	'secret'	        => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
    'sender'            => array(
        'email'         => 'webmaster@spadefootcode.com',
        'name'          => 'Webmaster'
    )
);

$config['GMAIL'] = array(
    'driver'            => 'Gmail',
	'uri'               => array(
	   'host'           => 'smtp.gmail.com',
	   'port'           => '465'
	),
    'credentials'       => array(
        'username'      => 'webmaster@spadefootcode.com',
        'password'      => 'XXXXXXXXXX'
    )
);

$config['MAIL_CHIMP'] = array(
    'driver'            => 'MailChimp',
    'url'               => 'http://us1.sts.mailchimp.com/1.0/SendEmail',
    'credentials'       => array(
        'username'      => 'USERNAME',
        'password'      => 'XXXXXXXXXX'
    ),
    'api-key'           => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX-us1',
    'sender'            => array(
        'email'         => 'webmaster@spadefootcode.com',
        'name'          => 'Webmaster'
    )
);

$config['ONE_AND_ONE'] = array(
    'driver'            => 'OneAndOne',
	'uri'               => array(
	   'host'           => 'smtp.1and1.com',
	   'port'           => '587'
	),
    'credentials'       => array(
        'username'      => 'webmaster@spadefootcode.com',
        'password'      => 'XXXXXXXXXX'
    )
);

$config['POSTMARK'] = array(
    'driver'            => 'Postmark',
    'url'               => 'http://api.postmarkapp.com/email',
    'api-key'           => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
    'sender'            => array(
        'email'         => 'webmaster@spadefootcode.com',
        'name'          => 'Webmaster'
    )
);

$config['SEND_MAIL'] = array(
    'driver'            => 'SendMail'
);

$config['WHAT_COUNTS'] = array(
    'driver'            => 'WhatCounts',
    'credentials'       => array(
        'username'      => 'USERNAME',
        'password'      => 'XXXXXXXXXX'
    ),
	'mailing_list'      => 'Default',
    'sender'            => array(
        'email'         => 'webmaster@spadefootcode.com',
        'name'          => 'Webmaster'
    )
);

$config['default'] = $config['AMAZON'];

return $config;
?>