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
 * This class handles communications to and from the What Counts' REST server
 * using the specified API.
 *
 * @package Messaging
 * @category Mailer
 * @version 2012-01-09
 *
 * TODO Replace all 'ereg' and 'eregi' with preg_match; otherwise, this will cause the code
 * to not work properly.
 */
class WhatCountsDriver {

    /**
     * @access private
     * @var string
     */
	private $url = 'http://api.whatcounts.com/bin/api_web';
	
	/**
	 * @access protected
	 * @var string
	 */
	protected $username = null; // realm
	
	/**
	 * @access protected
	 * @var string
	 */
	protected $password = null;
	
	/**
	 * @access private
	 * @var integer
	 */
	private $timeout = 15;

    /**
     * Initializes the driver.
     *
     * @access public
     */
	public function __construct($username, $password) {
		$this->username = $username;
		$this->password = $password;
	}
	
	/**
	 * Subscribe a user.
	 *
	 * @access public
	 * @param integer $list
	 * @param mixed $data
	 * @param integer $format
	 * @param integer $forceSubscribe
	 * @return array
	 * Format of data array
	 *   $data[fields]
	 *     $data['email']
	 *     $data['first']
	 *     $data['last']
	 *     $data['custom']
	 * Format of return array
	 *   ['isSuccess'] = Boolean, true if successful
	 *   ['total'] = Integer - total of successful subscribes
	 *   ['reason'] = String - The original message for failures
	 * 
	 * Example of return data
	 *   Success - SUCCESS: Total Records Processed 1, Total Subscriptions 1, Records Added 0, Records Updated 1, Records Ignored (Optout Error) 0, Records Failed Other Error 0
	 *   Failure - FAILURE: Total Records Processed 1, Total Subscriptions 0, Records Added 0, Records Updated 0, Records Ignored (Optout Error) 0, Records Failed Other Error 0
	 */
	public function subscribe($list, $data, $format = 1, $forceSubscribe = 0) {
		if (is_array($data)) {
			$data = $this->formatData($data);
		}
		
		$postArgs = '';
		$postArgs .= $this->postArgsHeader('sub');
		$postArgs .= '&list_id=' . $list;
		$postArgs .= '&format=' . $format;
		$postArgs .= '&force_sub=' . $forceSubscribe;
		$postArgs .= '&data=' . $data;
		
		$resp = $this->sendRequest($postArgs);
		if ($resp) {
			if (strstr($resp, "SUCCESS:")) {
				$ret['isSuccess'] = true;
				preg_match("/Total Records Processed ([0-9]+)/i", $resp, $regs);
				$ret['total'] = $regs[1];
			} else {
				$ret['isSuccess'] = false;
				
				// Commenting this line out because the failure message doesn't really tell us anything
				// preg_match("/Total Records Processed ([0-9]+)/i", $resp, $regs);
				
				$ret['reason'] = 'Please check the data that you are passing is valid.';
			}
		} else {
			$ret['isSuccess'] = false;
			$ret['reason'] = 'Connection error';
		}
		
		return $ret;
	}
	
	/**
	 * Unsubscribe a user.
	 *
	 * @access public
	 * @param integer $list
	 * @param string $data
	 * @param integer $optOut
	 * @return Array
	 * Format of return array
	 *   ['isSuccess'] = Boolean, true if successful
	 *    ['total'] = Integer - total of successful subscribes
	 *   ['reason'] = String - The original message for failures
	 */
	public function unsubscribe($list, $data, $optOut = 0) {
		$postArgs = '';
		$postArgs .= $this->postArgsHeader('unsub');
		$postArgs .= '&list_id=' . $list;
		$postArgs .= '&optout=' . $optOut;
		$postArgs .= '&data=' . $data;
		
		$resp = $this->sendRequest($postArgs);
		
		if ($resp) {
			if (strstr($resp, "SUCCESS:")) {
				$ret['isSuccess'] = TRUE;
				preg_match("/([0-9]+) record/i", $resp, $regs);
				$ret['total'] = $regs[1];
			} else {
				$ret['isSuccess'] = FALSE;
				//preg_match("/^(success|failure): (.+) record/i", $resp, $regs);
				$ret['reason'] = $resp; //$regs[2];
			}
		} else {
			$ret['isSuccess'] = FALSE;
			$ret['reason'] = 'Connection error';
		}
		
		return $ret;
	}
	
	/**
	 * Delete a user.
	 *
	 * @access public
	 * @param string $data
	 * @return array
	 * Format of return array
	 *   ['isSuccess'] = Boolean, true if successful
	 *   ['total'] = Integer - total of successful subscribes
	 *   ['reason'] = String - The original message for failures
	 */
	public function delete($data) {
		$postArgs = '';
		$postArgs .= $this->postArgsHeader('delete');
		$postArgs .= '&data=' . $data;
		
		$resp = $this->sendRequest($postArgs);
		
		if ($resp) {
			if (strstr($resp, "SUCCESS:")) {
				$ret['isSuccess'] = true;
				eregi("([0-9]+) record", $resp, $regs);
				$ret['total'] = $regs[1];
			} else {
				$ret['isSuccess'] = false;
				eregi("^(success|failure): (.+) record", $resp, $regs);
				$ret['reason'] = $regs[2];
			}
		} else {
			$ret['isSuccess'] = false;
			$ret['reason'] = 'Connection error';
		}
		
		return $ret;
	}
	
	/**
	 * Send an email to anyone.  If the email address doesn't exist in the list or isn't subscribed,
	 * a subscriber record will be created in the database with no subscription.
	 *
	 * @access public
	 * @param integer $list
	 * @param string $to
	 * @param integer $format
	 * @param string $errorsTo
	 * @param integer $templateID
	 * @param string $body
	 * @param string $plainTextBody
	 * @param string $htmlBody
	 * @param string $from
	 * @param string $subject
	 * @param string $data
	 * @return boolean
	 */
	public function send($list, $to, $format = 1, $errorsTo = '', $templateID = NULL, $body = '', $plainTextBody = '', $htmlBody = '', $from = '', $subject = '', $data = '', $reply_to = '') {
		$postArgs = '';
		$postArgs .= $this->postArgsHeader('send');
		$postArgs .= '&list_id=' . $list;
		$postArgs .= '&to=' . $to;
		if (!empty($reply_to)) {
			$postArgs .= '&reply_to=' . $reply_to;
		}
		$postArgs .= '&format=' . $format;
		$postArgs .= '&errors_to=' . $errorsTo;
		if ($templateID) {	
			$postArgs .= '&template_id=' . $templateID;
		}
		$postArgs .= '&body=' . urlencode($body);
		$postArgs .= '&plain_text_body=' . $plainTextBody;
		$postArgs .= '&html_body=' . $htmlBody;
		$postArgs .= '&from=' . urlencode($from);
		$postArgs .= '&subject=' . $subject;
		$postArgs .= '&data=' . $data;

		$resp = $this->sendRequest($postArgs);

		$ret = ($resp && preg_match('/^SUCCESS/i', $resp));

		return $ret;
	}
	
	/**
	 * Changes a user's email address.
	 *
	 * @access public
	 * @param string $email
	 * @param string $newEmail
	 * @return boolean
	 */
	public function change($email, $newEmail) {
		$postArgs = '';
		$postArgs .= $this->postArgsHeader('change');
		$postArgs .= '&email=' . $email;
		$postArgs .= '&email_new=' . $newEmail;
		
		$resp = $this->sendRequest($postArgs);
		
		$ret = ($resp && preg_match('/^SUCCESS/i', $resp));
		
		return $ret;
	}
	
	/**
	 * Searches for one or more subscribers by list ID.
	 *
	 * @access public
	 * @param integer $list
	 * @param integer $email
	 * @param integer $first
	 * @param integer $last
	 * @param integer $outputFormat
	 * @param integer $limit
	 * @param integer $exactMatch
	 * @param integer $header
	 * @return array
	 */
	public function findInList($list, $email, $first = '', $last = '', $outputFormat = '', $limit = 250, $exactMatch = 1, $header = 0) {
		$postArgs = '';
		$postArgs .= $this->postArgsHeader('findinlist');
		$postArgs .= '&list_id=' . $list;
		$postArgs .= '&email=' . $email;
		$postArgs .= '&first=' . $first;
		$postArgs .= '&last=' . $last;
		$postArgs .= '&limit=' . $limit;
		$postArgs .= '&exact=' . $exactMatch;
		$postArgs .= '&output_format=' . $outputFormat;
		$postArgs .= '&header=' . $header;
		
		$resp = $this->sendRequest($postArgs);
		
		if (!$resp) {
			return false;
		}
		
		$arr = $this->formatResponse($resp, $outputFormat);
		
		return $arr;
	}
	
	/**
	 * Search for a single subscriber by ID.
	 *
	 * @access public
	 * @param integer $subscriberId
	 * @param string $outputFormat
	 * @param integer $header
	 * @return array
	 */
	public function subscriberDetails($subscriberId, $outputFormat = '', $header = 0) {
		$postArgs = '';
		$postArgs .= $this->postArgsHeader('detail');
		$postArgs .= '&subscriber_id=' . $subscriberId;
		$postArgs .= '&output_format=' . $outputFormat;
		$postArgs .= '&header=' . $header;
		
		$resp = $this->sendRequest($postArgs);
		
		if (!$resp) {
			return false;
		}
		
		$arr = $this->formatResponse($resp, $outputFormat);
		
		return $arr;
	}

    /**
     * Fetches the list_id of the specified named list.
     *
     * @access public
     * @param string $name                      the name of list
     * @return array
     */	
	public function getListByName($name) {
		if (empty($name)) {
			return false;
		}
		
		$strRegEx = '/' . $name . '/i';

		$arrLists = $this->getLists();

		if (!$arrLists) {
			return false;
		}
		
		for ($i = 0; $i < count($arrLists); $i++) {
			if (isset($arrLists[$i]['listName']) && preg_match($strRegEx, $arrLists[$i]['listName'])) {
				return $arrLists[$i];
			}
		}
		
		return FALSE;
	}

    /**
     * Fetches an array of lists.
     *
     * @access public
     * @param string $outputFormat              the format of which the response
     *                                          message will be sent back as
     * @param integer $header
     * @return array
     */
	public function getLists($outputFormat = 'csv', $header = 0) {
		$postArgs = '';
		$postArgs .= $this->postArgsHeader('show_lists');
		$postArgs .= '&output_format=' . $outputFormat;
		$postArgs .= '&header=' . $header;
		
		$resp = $this->sendRequest($postArgs);
		
		if (!$resp) {
			return false;
		}
		
		$arrListKeys = array("listID", "listName", "listDescription");
		$arrLists = array();
		$arrResp = explode("\n", $resp);
		for ($i = 0; $i < count($arrResp); $i++) {
			$arrValues = explode(",", $arrResp[$i]);
			// Trim all the quotes off all the values
			for ($j = 0; $j < count($arrValues); $j++) {
				$arrValues[$j] = trim($arrValues[$j]);
				$arrValues[$j] = trim($arrValues[$j], '"');
				$arrLists[$i][$arrListKeys[$j]] = $arrValues[$j];
			}
		}
		
		return $arrLists;
	}

	public function getTemplateByName($name) {
		if (empty($name)) {
			return false;
		}

		$strRegEx = '/' . $name . '/i';

		$arrTemps = $this->getTemplates();

		if (!$arrTemps) {
			return false;
		}

		for ($i = 0; $i < count($arrTemps); $i++) {
			if (isset($arrTemps[$i]['templateName']) && preg_match($strRegEx, $arrTemps[$i]['templateName'])) {
				return $arrTemps[$i];
			}
		}

		return FALSE;
	}
	
	public function getTemplates($outputFormat = 'csv', $header = 0) {
		$postArgs = '';
		$postArgs .= $this->postArgsHeader('show_templates');
		$postArgs .= '&output_format=' . $outputFormat;
		$postArgs .= '&header=' . $header;
		
		$resp = $this->sendRequest($postArgs);
		
		if (!$resp) {
			return false;
		}
		
		$arrTemplateKeys = array("templateID", "templateName");
		$arrTemplate = array();
		$arrResp = explode("\n", $resp);
		for ($i = 0; $i < count($arrResp); $i++) {
			$arrValues = explode(",", $arrResp[$i]);
			// Trim all the quotes off all the values
			for ($j = 0; $j < count($arrValues); $j++) {
				$arrValues[$j] = trim($arrValues[$j]);
				$arrValues[$j] = trim($arrValues[$j], '"');
				$arrTemplate[$i][$arrTemplateKeys[$j]] = $arrValues[$j];
			}
		}
		return $arrTemplate;
	}
	
	private function sendRequest($postArgs) {
		// Get the curl session object
		$session = curl_init($this->url);
		
		// Set the POST options.
		curl_setopt($session, CURLOPT_POST, true);
		curl_setopt($session, CURLOPT_POSTFIELDS, $postArgs);
		curl_setopt($session, CURLOPT_HEADER, false);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session, CURLOPT_TIMEOUT, $this->timeout);
		
		// Do the POST and then close the session
		$response = curl_exec($session);
		if (curl_errno($session)) {
			if (curl_errno($session) == 18) { // Ignoring 18 because its still successfull
				return "SUCCESS";
			}
			return false;
		}
		else {
			curl_close($session);
		}
		
		return $response;
	}
	
	/**
	 * Format an array into the data string. Takes an indexed associative array and creates
	 * the special string format designated by the WhatCounts Web API.
	 *
	 * @param array $arrData
	 * @return string
	 */
	public function formatData($arrData) {
		$arrFields = array_keys($arrData[0]);
		$strFields = implode(",", $arrFields);
		
		$strValues = '^';
		$seperator = '';
		for ($i = 0; $i < count($arrData); $i++) {
			$arrValues = array_values($arrData[$i]);
			$strValues .= $seperator . implode(',', $arrValues);
			$seperator = '^';
		}
		
		return $strFields . $strValues;
	}
	
	private function postArgsHeader($command) {
		$postArgs = '';
		$postArgs .= 'cmd=' . $command;
		$postArgs .= '&realm=' . $this->username;
		$postArgs .= '&pwd=' . $this->password;
		
		return $postArgs;
	}
	
	/**
	 * Takes a response and returns an array.
	 *
	 * @access private
	 * @param string $str
	 * @param string $type
	 * @return array
	 */
	private function formatResponse($str, $type = '') {
		$arrRet = array();
		if (!in_array($type, array('plain', 'xml'))) {
			$lines = explode("\n", $str);
			foreach($lines as $line) {
				if (!empty($line)) {
					$sep = " ";
					if ($type == 'csv') {
						$sep = ',';
					}
					elseif ($type == 'csv_tab') {
						$sep = "\t";
					}
					elseif ($type == 'csv_pipe') {
						$sep = '|';
					}
					$arrRet[] = explode($sep, $line);
				}
			}
		}
		else {
			$arrRet = array($str);
		}
		
		return $arrRet;
	}

}
?>