<?php
//====================================================================================================+
// File name   : berliCRM_webserviceexamples.php
// Begin       : 2014-01-02
// Last Update : 2018-02-26
// Author      : Alexander Krawczyk - info@crm-now.de - http://www.crm-now.de
// Version     : 2.0.0
// License     : GNU LGPL (http://www.gnu.org/copyleft/lesser.html)
//-----------------------------------------------------------------------------------------------------
//  Copyright (C) 2004-2018  crm-now GmbH
//
// 	This program is free software: you can redistribute it and/or modify
// 	it under the terms of the GNU Lesser General Public License as published by
// 	the Free Software Foundation, either version 2.1 of the License, or
// 	(at your option) any later version.
//
// 	This program is distributed in the hope that it will be useful,
// 	but WITHOUT ANY WARRANTY; without even the implied warranty of
// 	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// 	GNU Lesser General Public License for more details.
//
// 	You should have received a copy of the GNU Lesser General Public License
// 	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 	See LICENSE.TXT file for more information.
//-----------------------------------------------------------------------------------------------------
//
// Description : This is a PHP class for the berliCRM API
//
// Main features:
//  * uses CURL for GET and POST operations;
// 	* uses one method for all types of API operations;
// 	* supports File uploads and downloads;
// 	* supports UTF-8 Unicode;
//-----------------------------------------------------------------------------------------------------
// Contributors:
//
//====================================================================================================+
class WS_Curl_Class {
	private $endpointUrl;
	private $userName;
	private $userKey;
	public $token;
	public $errorMsg = '';

	private $defaults = array(
			CURLOPT_HEADER => 0,
			CURLOPT_HTTPHEADER => array('Expect:'),
			// CURLOPT_FRESH_CONNECT => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_SSL_VERIFYHOST => 0
		);

	//constructor saves the values
	function __construct($url, $name, $key) {
		$this->endpointUrl=$url;
		$this->userName=$name;
		$this->userKey=$key;
		$this->token=$key;
	}

	private function getChallenge() {
		$curl_handler = curl_init();
		$params = array("operation" => "getchallenge", "username" => $this->userName);
		$options = array(CURLOPT_URL => $this->endpointUrl."?".http_build_query($params));
		curl_setopt_array($curl_handler, ($this->defaults + $options));

		$result = curl_exec($curl_handler);
		if (!$result) {
			$this->errorMsg = curl_error($curl_handler);
			return false;
		}
		$jsonResponse = json_decode($result, true);

		if($jsonResponse["success"]==false) {
			$this->errorMsg = "getChallenge failed: ".$jsonResponse["error"]["message"]."<br>";
			return false;
		}

		$challengeToken = $jsonResponse["result"]["token"];

		return $challengeToken;
	}

	function login() {
		$curl_handler = curl_init();
		$token = $this->getChallenge();
		//create md5 string containing user access key from my preference menu
		//and the challenge token obtained from get challenge result
		$generatedKey = md5($token.$this->userKey);

		$params = array("operation" => "login", "username" => $this->userName, "accessKey" => $generatedKey);
		$options = array(CURLOPT_URL => $this->endpointUrl, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($params));
		curl_setopt_array($curl_handler, ($this->defaults + $options));
		$result = curl_exec($curl_handler);
		if (!$result) {
			$this->errorMsg = curl_error($curl_handler);
			return false;
		}
		$jsonResponse = json_decode($result, true);
		if($jsonResponse["success"]==false) {
			$this->errorMsg = "Login failed: ".$jsonResponse["error"]["message"]."<br>";
			return false;
		}

		$sessionId = $jsonResponse["result"]["sessionName"];
		//save session id
		$this->token=$sessionId;

		return true;
	}

	private function handleReturn($result, $name, $curl_handler) {
		if (!$result) {
			$this->errorMsg = curl_error($curl_handler);
			return false;
		}
		$jsonResponse = json_decode($result, true);

		if (!$jsonResponse) {
			$this->errorMsg = "$name failed: ".$result."<br>";
			return false;
		}
		if($jsonResponse["success"]==false) {
			$this->errorMsg = "$name failed: ".$jsonResponse["error"]["message"]."<br>";
			return false;
		}
		return $jsonResponse["result"];
	}

	public function operation($name, $params, $type = "GET", $filepath = '') {
		$params = array_merge(array("operation" => $name, "sessionName" => $this->token), $params);
		if (strtolower($type) == "post") {
			$options = array(CURLOPT_URL => $this->endpointUrl, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($params));
		}
		else {
			$options = array(CURLOPT_URL => $this->endpointUrl."?".http_build_query($params));
		}

		if ($filepath != '' && strtolower($type) == "post") {
			$element = $params['element'];
			if (!empty($element)) {
				$element = json_decode($element, true);
			}
			if (isset($element['filename'])) {
				$filename = $element['filename'];
			}
			else {
				$filename = pathinfo($filepath, PATHINFO_BASENAME);
			}
			$size = filesize($filepath);
			$add_options = array(CURLOPT_HTTPHEADER => array("Content-Type: multipart/form-data"), CURLOPT_INFILESIZE => $size);
			if (function_exists("mime_content_type")) {
				$type = mime_content_type($filepath);
			}
			elseif (isset($element['filetype'])) {
				$type = $element['filetype'];
			}
			else {
				$type = '';
			}
			if (!function_exists('curl_file_create')) {
				$add_params = array("filename" => "@$filepath;type=$type;filename=$filename");
			}
			else {
				$cfile = curl_file_create($filepath, $type, $filename);
				$add_params = array('filename' => $cfile);
			}

			$options += $add_options;
			$options[CURLOPT_POSTFIELDS] = $params + $add_params;
		}


		$curl_handler = curl_init();
		curl_setopt_array($curl_handler, ($this->defaults + $options));

		$result = curl_exec($curl_handler);

		return $this->handleReturn($result, $name, $curl_handler);
	}
}
?>
