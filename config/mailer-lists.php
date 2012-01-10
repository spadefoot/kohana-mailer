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
 * Specifies the recipients for certain emails.
 *
 * @package Messaging
 * @category Mailer
 * @version 2012-01-09
 */
$config = array();

$config['mylist'] = array(
    'recipient' => array(
        array('email' => 'user1@spadefootcode.com', 'name' => 'User One'),
    ),
    'cc' => array(
        array('email' => 'user2@spadefootcode.com', 'name' => 'User Two'),
    ),
	'bcc' => array(
        array('email' => 'user3@spadefootcode.com', 'name' => 'User Three'),
	),
);

return $config;
?>