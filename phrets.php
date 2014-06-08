<?php

class phRETS {

/**
*  PHRETS - PHP library for RETS
*  version 1.0.1
*  http://troda.com/projects/phrets/
*  Copyright (C) 2007-2014 Troy Davisson
*  please submit problem or error reports to https://github.com/troydavisson/PHRETS/issues
*
*  All rights reserved.
*  Permission is hereby granted, free of charge, to use, copy or modify this software.  Use at your own risk.
*
*  This library is divided into 2 sections: high level and low level
*    High level: Helpful functions that take much of the burden out of processing RETS data
*    Low level: Framework for communicating with a RETS server.  High level functions sit on top of these
*
*/

	public $capability_url = array();
	private $ch;
	private $server_hostname;
	private $server_port;
	private $server_protocol;
	private $server_version;
	private $server_software;
	private $static_headers = array();
	private $server_information = array();
	private $cookie_file = "";
	private $debug_file = "rets_debug.txt";
	private $debug_mode;
	private $allowed_capabilities = array(
			"Action" => 1,
			"ChangePassword" => 1,
			"GetObject" => 1,
			"Login" => 1,
			"LoginComplete" => 1,
			"Logout" => 1,
			"Search" => 1,
			"GetMetadata" => 1,
			"ServerInformation" => 1,
			"Update" => 1,
			"PostObject" => 1,
			"GetPayloadList" => 1
			);
	private $last_request = array();
	private $auth_support_basic = false;
	private $auth_support_digest = false;
	private $last_response_headers = array();
	private $last_response_headers_raw = "";
	private $last_remembered_header = "";
	private $compression_enabled = false;
	private $ua_pwd = "";
	private $ua_auth = false;
	private $request_id = "";
	private $disable_follow_location = false;
	private $force_basic_authentication = false;
	private $use_interealty_ua_auth = false;
	private $int_result_pointer = 0;
	private $error_info = array();
	private $last_request_url;
	private $last_server_response;
	private $session_id;
	private $catch_last_response = false;
	private $disable_encoding_fix = false;
	private $offset_support = false;
	private $override_offset_protection = false;
	private $use_post_method = false;
	private $search_data = array();



	public function phRETS() { }


	public function GetLastServerResponse() {
		return $this->last_server_response;
	}


	public function FirewallTest() {
		$google = $this->FirewallTestConn("google.com", 80);
		$crt80 = $this->FirewallTestConn("demo.crt.realtors.org", 80);
		$crt6103 = $this->FirewallTestConn("demo.crt.realtors.org", 6103);
		$flexmls80 = $this->FirewallTestConn("retsgw.flexmls.com", 80);
		$flexmls6103 = $this->FirewallTestConn("retsgw.flexmls.com", 6103);

		if (!$google && !$crt80 && !$crt6103 && !$flexmls80 && !$flexmls6103) {
			echo "Firewall Result: All tests failed.  Possible causes:";
			echo "<ol>";
			echo "<li>Firewall is blocking your outbound connections</li>";
			echo "<li>You aren't connected to the internet</li>";
			echo "</ol>";
			return false;
		}

		if (!$crt6103 && !$flexmls6103) {
			echo "Firewall Result: All port 6103 tests failed.  ";
			echo "Likely cause: Firewall is blocking your outbound connections on port 6103.";
			return false;
		}

		if ($google && $crt6103 && $crt80 && $flexmls6103 && $flexmls80) {
			echo "Firewall Result: All tests passed.";
			return true;
		}

		if (($crt6103 && !$flexmls6103) || (!$crt6103 && $flexmls6103)) {
			echo "Firewall Result: At least one port 6103 test passed.  ";
			echo "Likely cause: One of the test servers might be down but connections on port 80 and port 6103 should work.";
			return true;
		}

		if (!$google || !$crt80 || !$flexmls80) {
			echo "Firewall Result: At least one port 80 test failed.  ";
			echo "Likely cause: One of the test servers might be down.";
			return true;
		}

		echo "Firewall Results: Unable to guess the issue.  See individual test results above.";
		return false;

	}


	private function FirewallTestConn($hostname, $port = 6103) {
		$fp = @fsockopen($hostname, $port, $errno, $errstr, 5);

		if (!$fp) {
			echo "Firewall Test: {$hostname}:{$port} FAILED<br>\n";
			return false;
		}
		else {
			@fclose($fp);
			echo "Firewall Test: {$hostname}:{$port} GOOD<br>\n";
			return true;
		}

	}


	public function GetObject($resource, $type, $id, $photo_number = '*', $location = 0) {
		$this->reset_error_info();
		$return_photos = array();

		if (empty($resource)) {
			die("Resource parameter is required for GetObject() request.");
		}
		if (empty($type)) {
			die("Type parameter is required for GetObject() request.");
		}
		if (empty($id)) {
			die("ID parameter is required for GetObject() request.");
		}
		if (empty($this->capability_url['GetObject'])) {
			die("GetObject() called but unable to find GetObject location.  Failed login?\n");
		}

		$send_id = "";
		$send_numb = "";

		// check if $photo_number needs fixing
		if (strpos($photo_number, ',') !== false) {
			// change the commas to colons for the request
			$photo_number = preg_replace('/\,/', ':', $photo_number);
		}

		if (strpos($photo_number, ':') !== false) {
			// photo number contains multiple objects
			// chopping and cleaning
			$requested_numbers = explode(":", $photo_number);
			if (is_array($requested_numbers)) {
				foreach ($requested_numbers as $numb) {
					$numb = trim($numb);
					if (!empty($numb) || $numb == "0") {
					$send_numb .= "{$numb}:";
					}
				}
			}
			$send_numb = preg_replace('/\:$/', '', $send_numb);
		}
		else {
			$send_numb = trim($photo_number);
		}

		if (strpos($id, ',') !== false) {
			// id contains multiple objects.
			// chopping and combining with photo_number
			$requested_ids = explode(",", $id);
			if (is_array($requested_ids)) {
				foreach ($requested_ids as $req_id) {
				$req_id = trim($req_id);
					if (!empty($req_id) && $req_id != "0") {
						$send_id .= "{$req_id}:{$send_numb},";
					}
				}
			}
			$send_id = preg_replace('/\,$/', '', $send_id);
		}
		else {
			$send_id = trim($id).':'.$send_numb;
		}

		// make request
		$result = $this->RETSRequest($this->capability_url['GetObject'],
						array(
								'Resource' => $resource,
								'Type' => $type,
								'ID' => $send_id,
								'Location' => $location
								)
						);

		if (!$result) {
			return false;
		}
		list($headers, $body) = $result;

		// fix case issue if exists
		if (isset($this->last_response_headers['Content-type']) && !isset($this->last_response_headers['Content-Type'])) {
			$this->last_response_headers['Content-Type'] = $this->last_response_headers['Content-type'];
		}

		if (!isset($this->last_response_headers['Content-Type'])) {
			$this->last_response_headers['Content-Type'] = "";
		}

		// check what type of response came back
		if (strpos($this->last_response_headers['Content-Type'], 'multipart') !== false) {

			// help bad responses be more multipart compliant
			$body = "\r\n{$body}\r\n";

			// multipart
			preg_match('/boundary\=\"(.*?)\"/', $this->last_response_headers['Content-Type'], $matches);
			if (isset($matches[1])) {
				$boundary = $matches[1];
			}
			else {
				preg_match('/boundary\=(.*?)(\s|$|\;)/', $this->last_response_headers['Content-Type'], $matches);
				$boundary = $matches[1];
			}
			// strip quotes off of the boundary
			$boundary = preg_replace('/^\"(.*?)\"$/', '\1', $boundary);

			// clean up the body to remove a reamble and epilogue
			$body = preg_replace('/^(.*?)\r\n--'.$boundary.'\r\n/', "\r\n--{$boundary}\r\n", $body);
			// make the last one look like the rest for easier parsing
			$body = preg_replace('/\r\n--'.$boundary.'--/', "\r\n--{$boundary}\r\n", $body);

			// cut up the message
			$multi_parts = array();
			$multi_parts = explode("\r\n--{$boundary}\r\n", $body);
			// take off anything that happens before the first boundary (the preamble)
			array_shift($multi_parts);
			// take off anything after the last boundary (the epilogue)
			array_pop($multi_parts);

			// go through each part of the multipart message
			foreach ($multi_parts as $part) {
				// default to processing headers
				$on_headers = true;
				$on_body = false;
				$first_body_found = false;
				$this_photo = array();

				// go through the multipart chunk line-by-line
				$body_parts = array();
				$body_parts = explode("\r\n", $part);
				$this_photo['Data'] = "";
				foreach ($body_parts as $line) {
					if (empty($line) && $on_headers == true) {
						// blank line.  switching to processing a body and moving on
						$on_headers = false;
						$on_body = true;
						continue;
					}
					if ($on_headers == true) {
						// non blank line and we're processing headers so save the header
						$header = null;
						$value = null;

						if (strpos($line, ':') !== false) {
							@list($header, $value) = explode(':', $line, 2);
						}

						$header = trim($header);
						$value = trim($value);
						if (!empty($header)) {
							if ($header == "Description") {
								// for servers where the implementors didn't read the next word in the RETS spec.
								// 'Description' is the BNF term. Content-Description is the correct header.
								// fixing for sanity
								$header = "Content-Description";
							}
							// fix case issue if exists
							if ($header == "Content-type") {
								$header = "Content-Type";
							}
							$this_photo[$header] = $value;
						}
					}
					if ($on_body == true) {
						if ($first_body_found == true) {
							// here again because a linebreak in the body section which was cut out in the explode
							// add the CRLF back
							$this_photo['Data'] .= "\r\n";
						}
						// non blank line and we're processing a body so save the line as part of Data
						$first_body_found = true;
						$this_photo['Data'] .= $line;
					}
				}
				// done with parsing out the multipart response
				// check for errors and finish up

				$this_photo['Success'] = true; // assuming for now

				if (strpos($this_photo['Content-Type'], 'xml') !== false) {
					// this multipart might include a RETS error
					$xml = $this->ParseXMLResponse($this_photo['Data']);

					if ($xml['ReplyCode'] == 0 || empty($this_photo['Data'])) {
						// success but no body
						$this_photo['Success'] = true;
					}
					else {
						// RETS error in this multipart section
						$this_photo['Success'] = false;
						$this_photo['ReplyCode'] = "{$xml['ReplyCode']}";
						$this_photo['ReplyText'] = "{$xml['ReplyText']}";
					}
				}

				// add information about this multipart to the returned array
				$return_photos[] = $this_photo;
			}
		}
		else {
			// all we know is that the response wasn't a multipart so it's either a single photo or error
			$this_photo = array();

			$this_photo['Success'] = true; // assuming for now
			if (isset($this->last_response_headers['Content-ID'])) {
				$this_photo['Content-ID'] = $this->last_response_headers['Content-ID'];
			}
			if (isset($this->last_response_headers['Object-ID'])) {
				$this_photo['Object-ID'] = $this->last_response_headers['Object-ID'];
			}
			if (isset($this->last_response_headers['Content-Type'])) {
				$this_photo['Content-Type'] = $this->last_response_headers['Content-Type'];
			}
			if (isset($this->last_response_headers['MIME-Version'])) {
				$this_photo['MIME-Version'] = $this->last_response_headers['MIME-Version'];
			}
			if (isset($this->last_response_headers['Location'])) {
				$this_photo['Location'] = $this->last_response_headers['Location'];
			}
			if (isset($this->last_response_headers['Preferred'])) {
				$this_photo['Preferred'] = $this->last_response_headers['Preferred'];
			}

			if (isset($this->last_response_headers['Description'])) {
				if (!empty($this->last_response_headers['Description'])) {
					// for servers where the implementors didn't read the next word in the RETS spec.
					// 'Description' is the BNF term. Content-Description is the correct header.
					// fixing for sanity
					$this_photo['Content-Description'] = $this->last_response_headers['Description'];
				}
			}
			if (isset($this->last_response_headers['Content-Description'])) {
				$this_photo['Content-Description'] = $this->last_response_headers['Content-Description'];
			}

			$this_photo['Length'] = strlen($body);
			$this_photo['Data'] = $body;

			if (isset($this->last_response_headers['Content-Type'])) {
				if (strpos($this->last_response_headers['Content-Type'], 'xml') !== false) {
					// RETS error maybe?
					$xml = $this->ParseXMLResponse($body);

					if ($xml['ReplyCode'] == 0 || empty($body)) {
						// false alarm.  we're good
						$this_photo['Success'] = true;
					}
					else {
						// yes, RETS error
						$this->last_request['ReplyCode'] = "{$xml['ReplyCode']}";
						$this->last_request['ReplyText'] = "{$xml['ReplyText']}";
						$this_photo['ReplyCode'] = "{$xml['ReplyCode']}";
						$this_photo['ReplyText'] = "{$xml['ReplyText']}";
						$this_photo['Success'] = false;
					}
				}
			}

			// add information about this photo to the returned array
			$return_photos[] = $this_photo;
		}

		// return everything
		return $return_photos;
	}


	public function IsMaxrowsReached($pointer_id = "") {
		if (empty($pointer_id)) {
			$pointer_id = $this->int_result_pointer;
		}
		return $this->search_data[$pointer_id]['maxrows_reached'];
	}


	public function TotalRecordsFound($pointer_id = "") {
		if (empty($pointer_id)) {
			$pointer_id = $this->int_result_pointer;
		}
		return $this->search_data[$pointer_id]['total_records_found'];
	}


	public function NumRows($pointer_id = "") {
		if (empty($pointer_id)) {
			$pointer_id = $this->int_result_pointer;
		}
		return $this->search_data[$pointer_id]['last_search_returned'];
	}


	public function SearchGetFields($pointer_id) {
		if (!empty($pointer_id)) {
			return $this->search_data[$pointer_id]['column_names'];
		}
		else {
			return false;
		}
	}


	public function FreeResult($pointer_id) {
		if (!empty($pointer_id)) {
			unset($this->search_data[$pointer_id]['data']);
			unset($this->search_data[$pointer_id]['delimiter_character']);
			unset($this->search_data[$pointer_id]['column_names']);
			return true;
		}
		else {
			return false;
		}
	}


	public function FetchRow($pointer_id) {

		$this_row = false;

		if (!empty($pointer_id)) {

			if (isset($this->search_data[$pointer_id]['data'])) {
				$field_data = current($this->search_data[$pointer_id]['data']);
				next($this->search_data[$pointer_id]['data']);
			}

			if (!empty($field_data)) {
				$this_row = array();

				// split up DATA row on delimiter found earlier
				$field_data = preg_replace("/^{$this->search_data[$pointer_id]['delimiter_character']}/", "", $field_data);
				$field_data = preg_replace("/{$this->search_data[$pointer_id]['delimiter_character']}\$/", "", $field_data);
				$field_data = explode($this->search_data[$pointer_id]['delimiter_character'], $field_data);

				foreach ($this->search_data[$pointer_id]['column_names'] as $key => $name) {
					// assign each value to it's name retrieved in the COLUMNS earlier
					$this_row[$name] = $field_data[$key];
				}
			}
			else {
				$this->FreeResult($pointer_id);
			}
		}

		return $this_row;

	}


	public function SearchQuery($resource, $class, $query = "", $optional_params = array()) {
		$this->reset_error_info();

		if (empty($resource)) {
			die("Resource parameter is required in SearchQuery() request.");
		}
		if (empty($class)) {
			die("Class parameter is required in SearchQuery() request.");
		}
		if (empty($this->capability_url['Search'])) {
			die("SearchQuery() called but unable to find Search location.  Failed login?\n");
		}

		$this->int_result_pointer++;
		$this->search_data[$this->int_result_pointer]['last_search_returned'] = 0;
		$this->search_data[$this->int_result_pointer]['total_records_found'] = 0;
		$this->search_data[$this->int_result_pointer]['column_names'] = "";
		$this->search_data[$this->int_result_pointer]['delimiter_character'] = "";
		$this->search_data[$this->int_result_pointer]['search_requests'] = 0;

		// setup request arguments
		$search_arguments = array();

		$search_arguments['SearchType'] = $resource;
		$search_arguments['Class'] = $class;

		// due to a lack of forward-thinking, reversing a previous decision
		// check if the query passed is missing the outer parenthesis
		// if so, add them
		if (empty($query)) {
			// do nothing.  http://retsdoc.onconfluence.com/display/rcpcenter/RCP+80+-+Optional+Query
		}
		elseif ($query == "*" || preg_match('/^\((.*)\)$/', $query)) {
			$search_arguments['Query'] = $query;
		}
		else {
			$search_arguments['Query'] = '('.$query.')';
		}


		if (isset($search_arguments['Query'])) {
			$search_arguments['QueryType'] = "DMQL2";
		}

		if (!empty($optional_params['QueryType'])) {
			$search_arguments['QueryType'] = $optional_params['QueryType'];
		}

		// setup additional, optional request arguments
        $search_arguments['Count'] = (!array_key_exists('Count', $optional_params)) ? 1 : $optional_params['Count'];
		$search_arguments['Format'] = empty($optional_params['Format']) ? "COMPACT-DECODED" : $optional_params['Format'];
		$search_arguments['Limit'] = empty($optional_params['Limit']) ? 99999999 : $optional_params['Limit'];

		if (isset($optional_params['Offset'])) {
			$search_arguments['Offset'] = $optional_params['Offset'];
		}
		elseif ($this->offset_support && empty($optional_params['Offset'])) {
			// start auto-offset looping with Offset at 1
			$search_arguments['Offset'] = 1;
		}
		else { }

		if (!empty($optional_params['Select'])) {
			$search_arguments['Select'] = $optional_params['Select'];
		}
		if (!empty($optional_params['RestrictedIndicator'])) {
			$search_arguments['RestrictedIndicator'] = $optional_params['RestrictedIndicator'];
		}

		$search_arguments['StandardNames'] = empty($optional_params['StandardNames']) ? 0 : $optional_params['StandardNames'];

		$continue_searching = true; // Keep searching if MAX ROWS is reached and offset_support is true
		while ($continue_searching) {

			$this->search_data[$this->int_result_pointer]['maxrows_reached'] = false;
			$this->search_data[$this->int_result_pointer]['search_requests']++;

			if ($this->search_data[$this->int_result_pointer]['search_requests'] == 300 && !$this->override_offset_protection) {
				// this call for SearchQuery() has resulted in X number of search requests
				// which is considered excessive.  stopping the process in order to prevent
				// abuse against the server.  almost ALWAYS happens when the user thinks Offset
				// is supported by the server when it's actually NOT supported
				$this->set_error_info("phrets", -1, "Last SearchQuery() has resulted in 300+ requests to the server.  Stopping to prevent abuse");
				return false;
			}

			// make request
			$result = $this->RETSRequest($this->capability_url['Search'], $search_arguments);
			if (!$result) {
				return false;
			}
			list($headers, $body) = $result;

			$body = $this->fix_encoding($body);

			$xml = $this->ParseXMLResponse($body);
			if (!$xml) {
				return false;
			}

			// log replycode and replytext for reference later
			$this->last_request['ReplyCode'] = "{$xml['ReplyCode']}";
			$this->last_request['ReplyText'] = "{$xml['ReplyText']}";

			if ($xml['ReplyCode'] != 0) {
				$this->set_error_info("rets", "{$xml['ReplyCode']}", "{$xml['ReplyText']}");
				return false;
			}

			if (isset($xml->DELIMITER)) {
				// delimiter found so we have at least a COLUMNS row to parse
				$delimiter_character = chr("{$xml->DELIMITER->attributes()->value}");
				$this->search_data[$this->int_result_pointer]['delimiter_character'] = $delimiter_character;
				$column_names = "{$xml->COLUMNS[0]}";
				$column_names = preg_replace("/^{$delimiter_character}/", "", $column_names);
				$column_names = preg_replace("/{$delimiter_character}\$/", "", $column_names);
				$this->search_data[$this->int_result_pointer]['column_names'] = explode($delimiter_character, $column_names);
			}

			if (isset($xml->DATA)) {
				foreach ($xml->DATA as $key) {
					$field_data = "{$key}";
					// split up DATA row on delimiter found earlier
					$this->search_data[$this->int_result_pointer]['data'][] = $field_data;
					$this->search_data[$this->int_result_pointer]['last_search_returned']++;
				}
			}

			if (isset($xml->MAXROWS)) {
				// MAXROWS tag found.  the RETS server withheld records.
				// if the server supports Offset, more requests can be sent to page through results
				// until this tag isn't found anymore.
				$this->search_data[$this->int_result_pointer]['maxrows_reached'] = true;
			}

			if (isset($xml->COUNT)) {
				// found the record count returned.  save it
				$this->search_data[$this->int_result_pointer]['total_records_found'] = "{$xml->COUNT->attributes()->Records}";
			}

			if (isset($xml)) {
				unset($xml);
			}

			if ($this->IsMaxrowsReached($this->int_result_pointer) && $this->offset_support) {
				$continue_searching = true;
				$search_arguments['Offset'] = $this->NumRows($this->int_result_pointer) + 1;
			}
			else {
				$continue_searching = false;
			}
		}

		return $this->int_result_pointer;
	}


	public function Search($resource, $class, $query = "", $optional_params = array()) {
		$data_table = array();

		$int_result_pointer = $this->SearchQuery($resource, $class, $query, $optional_params);

		while ($row = $this->FetchRow($int_result_pointer)) {
			$data_table[] = $row;
		}

		return $data_table;
	}


	public function GetAllLookupValues($resource) {
		$this->reset_error_info();

		if (empty($resource)) {
			die("Resource parameter is required in GetAllLookupValues() request.");
		}
		if (empty($this->capability_url['GetMetadata'])) {
			die("GetAllLookupValues() called but unable to find GetMetadata location.  Failed login?\n");
		}

		// make request
		$result = $this->RETSRequest($this->capability_url['GetMetadata'],
						array(
								'Type' => 'METADATA-LOOKUP_TYPE',
								'ID' => $resource.':*',
								'Format' => 'STANDARD-XML'
								)
						);

		if (!$result) {
			return false;
		}
		list($headers, $body) = $result;

		$xml = $this->ParseXMLResponse($body);
		if (!$xml) {
			return false;
		}

		if ($xml['ReplyCode'] != 0) {
			$this->set_error_info("rets", "{$xml['ReplyCode']}", "{$xml['ReplyText']}");
			return false;
		}

		$this_table = array();

		// parse XML into a nice array
		if ($xml->METADATA && $xml->METADATA->{'METADATA-LOOKUP_TYPE'}) {

			foreach ($xml->METADATA->{'METADATA-LOOKUP_TYPE'} as $key) {
				if (!empty($key->attributes()->Lookup)) {
					$this_lookup = array();

					$lookup_xml_array = array();
					if (!empty($key->LookupType)) {
						$lookup_xml_array = $key->LookupType;
					}
					else {
						$lookup_xml_array = $key->Lookup;
					}

					if (is_object($lookup_xml_array)) {
						foreach ($lookup_xml_array as $look) {
							$metadataentryid = isset($look->MetadataEntryID) ? "{$look->MetadataEntryID}" : "";
							$value = isset($look->Value) ? "{$look->Value}" : "";
							$shortvalue = isset($look->ShortValue) ? "{$look->ShortValue}" : "";
							$longvalue = isset($look->LongValue) ? "{$look->LongValue}" : "";

							$this_lookup[] = array(
									'MetadataEntryID' => $metadataentryid,
									'Value' => $value,
									'ShortValue' => $shortvalue,
									'LongValue' => $longvalue
									);
						}
					}

					$this_table[] = array('Lookup' => "{$key->attributes()->Lookup}", 'Values' => $this_lookup);
				}
			}
		}

		// return the big array
		return $this_table;
	}


	public function GetLookupValues($resource, $lookupname) {
		$this->reset_error_info();

		if (empty($resource)) {
			die("Resource parameter is required in GetLookupValues() request.");
		}
		if (empty($lookupname)) {
			die("Lookup Name parameter is required in GetLookupValues() request.");
		}
		if (empty($this->capability_url['GetMetadata'])) {
			die("GetLookupValues() called but unable to find GetMetadata location.  Failed login?\n");
		}

		// make request
		$result = $this->RETSRequest($this->capability_url['GetMetadata'],
						array(
								'Type' => 'METADATA-LOOKUP_TYPE',
								'ID' => $resource.':'.$lookupname,
								'Format' => 'STANDARD-XML'
								)
						);

		if (!$result) {
			return false;
		}
		list($headers, $body) = $result;

		$xml = $this->ParseXMLResponse($body);
		if (!$xml) {
			return false;
		}

		if ($xml['ReplyCode'] != 0) {
			$this->set_error_info("rets", "{$xml['ReplyCode']}", "{$xml['ReplyText']}");
			return false;
		}

		$this_table = array();

		// parse XML into a nice array
		if ($xml->METADATA && $xml->METADATA->{'METADATA-LOOKUP_TYPE'}) {

			$lookup_xml_array = array();
			if (!empty($xml->METADATA->{'METADATA-LOOKUP_TYPE'}->LookupType)) {
				$lookup_xml_array = $xml->METADATA->{'METADATA-LOOKUP_TYPE'}->LookupType;
			}
			else {
				$lookup_xml_array = $xml->METADATA->{'METADATA-LOOKUP_TYPE'}->Lookup;
			}

			if (is_object($lookup_xml_array)) {
				foreach ($lookup_xml_array as $key) {
					if (isset($key->Value)) {
						$metadataentryid = isset($key->MetadataEntryID) ? "{$key->MetadataEntryID}" : "";
						$value = isset($key->Value) ? "{$key->Value}" : "";
						$shortvalue = isset($key->ShortValue) ? "{$key->ShortValue}" : "";
						$longvalue = isset($key->LongValue) ? "{$key->LongValue}" : "";

						$this_table[] = array(
								'MetadataEntryID' => $metadataentryid,
								'Value' => $value,
								'ShortValue' => $shortvalue,
								'LongValue' => $longvalue
								);
					}
				}
			}
		}

		// return the big array
		return $this_table;
	}


	public function GetMetadataResources($id = 0) {
		$this->reset_error_info();

		if (empty($this->capability_url['GetMetadata'])) {
			die("GetMetadataResources() called but unable to find GetMetadata location.  Failed login?\n");
		}

		// make request
		$result = $this->RETSRequest($this->capability_url['GetMetadata'],
						array(
								'Type' => 'METADATA-RESOURCE',
								'ID' => $id,
								'Format' => 'STANDARD-XML'
								)
						);

		if (!$result) {
			return false;
		}
		list($headers, $body) = $result;

		$xml = $this->ParseXMLResponse($body);
		if (!$xml) {
			return false;
		}

		if ($xml['ReplyCode'] != 0) {
			$this->set_error_info("rets", "{$xml['ReplyCode']}", "{$xml['ReplyText']}");
			return false;
		}

		$this_resource = array();

		// parse XML into a nice array
		if ($xml->METADATA) {
			foreach ($xml->METADATA->{'METADATA-RESOURCE'}->Resource as $key => $value) {
				$this_resource["{$value->ResourceID}"] = array(
						'ResourceID' => "{$value->ResourceID}",
						'StandardName'=>"{$value->StandardName}",
						'VisibleName' => "{$value->VisibleName}",
						'Description' => "{$value->Description}",
						'KeyField' => "{$value->KeyField}",
						'ClassCount' => "{$value->ClassCount}",
						'ClassVersion' => "{$value->ClassVersion}",
						'ClassDate' => "{$value->ClassDate}",
						'ObjectVersion' => "{$value->ObjectVersion}",
						'ObjectDate' => "{$value->ObjectDate}",
						'SearchHelpVersion' => "{$value->SearchHelpVersion}",
						'SearchHelpDate' => "{$value->SearchHelpDate}",
						'EditMaskVersion' => "{$value->EditMaskVersion}",
						'EditMaskDate' => "{$value->EditMaskDate}",
						'LookupVersion' => "{$value->LookupVersion}",
						'LookupDate' => "{$value->LookupDate}",
						'UpdateHelpVersion' => "{$value->UpdateHelpVersion}",
						'UpdateHelpDate' => "{$value->UpdateHelpDate}",
						'ValidationExpressionVersion' => "{$value->ValidationExpressionVersion}",
						'ValidationExpressionDate' => "{$value->ValidationExpressionDate}",
						'ValidationLookupVersion' => "{$value->ValidationLookupVersion}",
						'ValidationLookupDate' => "{$value->ValidationLookupDate}",
						'ValidationExternalVersion' => "{$value->ValidationExternalVersion}",
						'ValidationExternalDate' => "{$value->ValidationExternalDate}"
						);
			}
		}

		// send back array
		return $this_resource;
	}


	public function GetMetadataInfo($id = 0) {
		if (empty($this->capability_url['GetMetadata'])) {
			die("GetMetadataInfo() called but unable to find GetMetadata location.  Failed login?\n");
		}
		return $this->GetMetadataResources($id);
	}


	public function GetMetadataTable($resource, $class) {
		$this->reset_error_info();

		$id = $resource.':'.$class;
		if (empty($resource)) {
			die("Resource parameter is required in GetMetadata() request.");
		}
		if (empty($class)) {
			die("Class parameter is required in GetMetadata() request.");
		}
		if (empty($this->capability_url['GetMetadata'])) {
			die("GetMetadataTable() called but unable to find GetMetadata location.  Failed login?\n");
		}

		// request specific metadata
		$result = $this->RETSRequest($this->capability_url['GetMetadata'],
						array(
								'Type' => 'METADATA-TABLE',
								'ID' => $id,
								'Format' => 'STANDARD-XML'
								)
						);

		if (!$result) {
			return false;
		}
		list($headers, $body) = $result;

		$xml = $this->ParseXMLResponse($body);
		if (!$xml) {
			return false;
		}

		// log replycode and replytext for reference later
		$this->last_request['ReplyCode'] = "{$xml['ReplyCode']}";
		$this->last_request['ReplyText'] = "{$xml['ReplyText']}";

		if ($xml['ReplyCode'] != 0) {
			$this->set_error_info("rets", "{$xml['ReplyCode']}", "{$xml['ReplyText']}");
			return false;
		}

		$this_table = array();

		// parse XML into a nice array
		if ($xml->METADATA) {
			foreach ($xml->METADATA->{'METADATA-TABLE'}->Field as $key) {
				$this_table[] = array(
						'SystemName' => "{$key->SystemName}",
						'StandardName' => "{$key->StandardName}",
						'LongName' => "{$key->LongName}",
						'DBName' => "{$key->DBName}",
						'ShortName' => "{$key->ShortName}",
						'MaximumLength' => "{$key->MaximumLength}",
						'DataType' => "{$key->DataType}",
						'Precision' => "{$key->Precision}",
						'Searchable' => "{$key->Searchable}",
						'Interpretation' => "{$key->Interpretation}",
						'Alignment' => "{$key->Alignment}",
						'UseSeparator' => "{$key->UseSeparator}",
						'EditMaskID' => "{$key->EditMaskID}",
						'LookupName' => "{$key->LookupName}",
						'MaxSelect' => "{$key->MaxSelect}",
						'Units' => "{$key->Units}",
						'Index' => "{$key->Index}",
						'Minimum' => "{$key->Minimum}",
						'Maximum' => "{$key->Maximum}",
						'Default' => "{$key->Default}",
						'Required' => "{$key->Required}",
						'SearchHelpID' => "{$key->SearchHelpID}",
						'Unique' => "{$key->Unique}",
						'MetadataEntryID' => "{$key->MetadataEntryID}",
						'ModTimeStamp' => "{$key->ModTimeStamp}",
						'ForeignKeyName' => "{$key->ForiengKeyName}",
						'ForeignField' => "{$key->ForeignField}",
						'InKeyIndex' => "{$key->InKeyIndex}"
						);
			}
		}

		// return the big array
		return $this_table;
	}


	public function GetMetadata($resource, $class) {
		if (empty($this->capability_url['GetMetadata'])) {
			die("GetMetadata() called but unable to find GetMetadata location.  Failed login?\n");
		}
		return $this->GetMetadataTable($resource, $class);
	}


	public function GetMetadataObjects($id) {
		$this->reset_error_info();

		if (empty($id)) {
			die("ID parameter is required in GetMetadataObjects() request.");
		}
		if (empty($this->capability_url['GetMetadata'])) {
			die("GetMetadataObjects() called but unable to find GetMetadata location.  Failed login?\n");
		}

		// request basic metadata information
		$result = $this->RETSRequest($this->capability_url['GetMetadata'],
						array(
								'Type' => 'METADATA-OBJECT',
								'ID' => $id,
								'Format' => 'STANDARD-XML'
								)
						);

		if (!$result) {
			return false;
		}
		list($headers, $body) = $result;

		$xml = $this->ParseXMLResponse($body);
		if (!$xml) {
			return false;
		}

		// log replycode and replytext for reference later
		$this->last_request['ReplyCode'] = "{$xml['ReplyCode']}";
		$this->last_request['ReplyText'] = "{$xml['ReplyText']}";

		if ($xml['ReplyCode'] != 0) {
			$this->set_error_info("rets", "{$xml['ReplyCode']}", "{$xml['ReplyText']}");
			return false;
		}

		$return_data = array();

		if (isset($xml->METADATA->{'METADATA-OBJECT'})) {
			// parse XML into a nice array
			foreach ($xml->METADATA->{'METADATA-OBJECT'} as $key => $value) {
				foreach ($value->Object as $key) {
					if (!empty($key->ObjectType)) {
						$return_data[] = array(
								'MetadataEntryID' => "{$key->MetadataEntryID}",
								'VisibleName' => "{$key->VisibleName}",
								'ObjectTimeStamp' => "{$key->ObjectTimeStamp}",
								'ObjectCount' => "{$key->ObjectCount}",
								'ObjectType' => "{$key->ObjectType}",
								'StandardName' => "{$key->StandardName}",
								'MimeType' => "{$key->MimeType}",
								'Description' => "{$key->Description}"
								);
					}
				}
			}
		}

		// send back array
		return $return_data;
	}


	public function GetMetadataClasses($id) {
		$this->reset_error_info();

		if (empty($id)) {
			die("ID parameter is required in GetMetadataClasses() request.");
		}
		if (empty($this->capability_url['GetMetadata'])) {
			die("GetMetadataClasses() called but unable to find GetMetadata location.  Failed login?\n");
		}

		// request basic metadata information
		$result = $this->RETSRequest($this->capability_url['GetMetadata'],
						array(
								'Type' => 'METADATA-CLASS',
								'ID' => $id,
								'Format' => 'STANDARD-XML'
								)
						);

		if (!$result) {
			return false;
		}
		list($headers, $body) = $result;

		$xml = $this->ParseXMLResponse($body);
		if (!$xml) {
			return false;
		}

		// log replycode and replytext for reference later
		$this->last_request['ReplyCode'] = "{$xml['ReplyCode']}";
		$this->last_request['ReplyText'] = "{$xml['ReplyText']}";

		if ($xml['ReplyCode'] != 0) {
			$this->set_error_info("rets", "{$xml['ReplyCode']}", "{$xml['ReplyText']}");
			return false;
		}

		$return_data = array();

		// parse XML into a nice array
		if ($xml->METADATA) {
			foreach ($xml->METADATA->{'METADATA-CLASS'} as $key => $value) {
				foreach ($value->Class as $key) {
					if (!empty($key->ClassName)) {
						$return_data[] = array(
								'ClassName' => "{$key->ClassName}",
								'VisibleName' => "{$key->VisibleName}",
								'StandardName' => "{$key->StandardName}",
								'Description' => "{$key->Description}",
								'TableVersion' => "{$key->TableVersion}",
								'TableDate' => "{$key->TableDate}",
								'UpdateVersion' => "{$key->UpdateVersion}",
								'UpdateDate' => "{$key->UpdateDate}",
								'ClassTimeStamp' => "{$key->ClassTimeStamp}",
								'DeletedFlagField' => "{$key->DeletedFlagField}",
								'DeletedFlagValue' => "{$key->DeletedFlagValue}",
								'HasKeyIndex' => "{$key->HasKeyIndex}"
								);
					}
				}
			}
		}

		// send back array
		return $return_data;
	}


	public function GetMetadataTypes($id = 0) {
		$this->reset_error_info();

		if (empty($this->capability_url['GetMetadata'])) {
			die("GetMetadataTypes() called but unable to find GetMetadata location.  Failed login?\n");
		}

		// request basic metadata information
		$result = $this->RETSRequest($this->capability_url['GetMetadata'],
						array(
								'Type' => 'METADATA-CLASS',
								'ID' => $id,
								'Format' => 'STANDARD-XML'
								)
						);

		if (!$result) {
			return false;
		}
		list($headers, $body) = $result;
		$xml = $this->ParseXMLResponse($body);
		if (!$xml) {
			return false;
		}

		// log replycode and replytext for reference later
		$this->last_request['ReplyCode'] = "{$xml['ReplyCode']}";
		$this->last_request['ReplyText'] = "{$xml['ReplyText']}";

		if ($xml['ReplyCode'] != 0) {
			$this->set_error_info("rets", "{$xml['ReplyCode']}", "{$xml['ReplyText']}");
			return false;
		}

		$return_data = array();

		// parse XML into a nice array
		if ($xml->METADATA) {
			foreach ($xml->METADATA->{'METADATA-CLASS'} as $key => $value) {
				$resource = $value['Resource'];
				$this_resource = array();
				foreach ($value->Class as $key) {
					if (!empty($key->ClassName)) {
						$this_resource[] = array(
								'ClassName' => "{$key->ClassName}",
								'VisibleName' => "{$key->VisibleName}",
								'StandardName' => "{$key->StandardName}",
								'Description' => "{$key->Description}",
								'TableVersion' => "{$key->TableVersion}",
								'TableDate' => "{$key->TableDate}",
								'UpdateVersion' => "{$key->UpdateVersion}",
								'UpdateDate' => "{$key->UpdateDate}"
								);
					}
				}

				// prepare 2-deep array
				$return_data[] = array('Resource' => "{$resource}", 'Data' => $this_resource);
			}
		}

		// send back array
		return $return_data;
	}


	public function GetServerSoftware() {
		return $this->server_software;
	}


	public function GetServerVersion() {
		return $this->server_version;
	}


	public function CheckAuthSupport($type = "") {
		if ($type == "basic") {
			return $this->auth_support_basic;
		}
		if ($type == "digest") {
			return $this->auth_support_digest;
		}
		$this->set_error_info("phrets", -1, "Unknown auth type requested.");
		return false;
	}


	public function GetAllTransactions() {
		// read through capability_urls read during the Login and return
		$transactions = array();
		if (is_array($this->capability_url)) {
			foreach ($this->capability_url as $key => $value) {
				$transactions[] = $key;
			}
		}
		return $transactions;
	}


	public function LastRequestURL() {
		return $this->last_request_url;
	}


	public function GetLoginURL() {
		// see if the saved Login URL has a hostname included.
		// if not, make it based on the URL given in the Connect() call
		$parse_results = parse_url($this->capability_url['Login'], PHP_URL_HOST);
		if (empty($parse_results)) {
			// login transaction gave a relative path for this action
			$request_url = $this->server_protocol.'://'.$this->server_hostname.':'.$this->server_port.''.$this->capability_url['Login'];
		}
		else {
			// login transaction gave an absolute path for this action
			$request_url = $this->capability_url['Login'];
		}
		if (empty($request_url)) {
			$this->set_error_info("phrets", -1, "Unable to find a login URL.  Did initial login fail?");
			return false;
		}
		return $request_url;
	}


	public function GetServerInformation() {
		$this->reset_error_info();

		if (empty($this->capability_url['GetMetadata'])) {
			die("GetServerInformation() called but unable to find GetMetadata location.  Failed login?\n");
		}

		// request server information
		$result = $this->RETSRequest($this->capability_url['GetMetadata'],
						array(
								'Type' => 'METADATA-SYSTEM',
								'ID' => 0,
								'Format' => 'STANDARD-XML'
								)
						);

		if (!$result) {
			return false;
		}
		list($headers, $body) = $result;

		$xml = $this->ParseXMLResponse($body);
		if (!$xml) {
			return false;
		}

		if ($xml['ReplyCode'] != 0) {
			$this->set_error_info("rets", "{$xml['ReplyCode']}", "{$xml['ReplyText']}");
			return false;
		}

		$system_id = "";
		$system_description = "";
		$system_comments = "";
		$system_version = "";
        $timezone_offset = "";

		if ($this->is_server_version("1_5_or_below")) {
			if (isset($xml->METADATA->{'METADATA-SYSTEM'}->System->SystemID)) {
				$system_id = "{$xml->METADATA->{'METADATA-SYSTEM'}->System->SystemID}";
			}
			if (isset($xml->METADATA->{'METADATA-SYSTEM'}->System->SystemDescription)) {
				$system_description = "{$xml->METADATA->{'METADATA-SYSTEM'}->System->SystemDescription}";
			}
		}
		else {
			if (isset($xml->METADATA->{'METADATA-SYSTEM'}->SYSTEM->attributes()->SystemID)) {
				$system_id = "{$xml->METADATA->{'METADATA-SYSTEM'}->SYSTEM->attributes()->SystemID}";
			}
			if (isset($xml->METADATA->{'METADATA-SYSTEM'}->SYSTEM->attributes()->SystemDescription)) {
				$system_description = "{$xml->METADATA->{'METADATA-SYSTEM'}->SYSTEM->attributes()->SystemDescription}";
			}
			if (isset($xml->METADATA->{'METADATA-SYSTEM'}->SYSTEM->attributes()->TimeZoneOffset)) {
				$timezone_offset = "{$xml->METADATA->{'METADATA-SYSTEM'}->SYSTEM->attributes()->TimeZoneOffset}";
			}
		}

		if (isset($xml->METADATA->{'METADATA-SYSTEM'}->SYSTEM->Comments)) {
			$system_comments = "{$xml->METADATA->{'METADATA-SYSTEM'}->SYSTEM->Comments}";
		}
		if (isset($xml->METADATA->{'METADATA-SYSTEM'}->attributes()->Version)) {
			$system_version = (string) $xml->METADATA->{'METADATA-SYSTEM'}->attributes()->Version;
		}

		return array(
				'SystemID' => $system_id,
				'SystemDescription' => $system_description,
				'TimeZoneOffset' => $timezone_offset,
				'Comments' => $system_comments,
				'Version' => $system_version
				);
	}


	public function Disconnect() {
		$this->reset_error_info();

		if (empty($this->capability_url['Logout'])) {
			die("Disconnect() called but unable to find Logout location.  Failed login?\n");
		}

		// make request
		$result = $this->RETSRequest($this->capability_url['Logout']);
		if (!$result) {
			return false;
		}
		list($headers,$body) = $result;

		// close cURL connection
		curl_close($this->ch);

		if ($this->debug_mode == true) {
			// close cURL debug log file handler
			fclose($this->debug_log);
		}

		if (file_exists($this->cookie_file)) {
			@unlink($this->cookie_file);
		}

		return true;

	}


	public function Connect($login_url, $username, $password, $ua_pwd = "") {
		$this->reset_error_info();

		if (empty($login_url)) {
			die("PHRETS: Login URL missing from Connect()");
		}
		if (empty($username)) {
			die("PHRETS: Username missing from Connect()");
		}
		if (empty($password)) {
			die("PHRETS: Password missing from Connect()");
		}
		if (empty($this->static_headers['RETS-Version'])) {
			$this->AddHeader("RETS-Version", "RETS/1.5");
		}
		if (empty($this->static_headers['User-Agent'])) {
			$this->AddHeader("User-Agent", "PHRETS/1.0");
		}
		if (empty($this->static_headers['Accept']) && $this->static_headers['RETS-Version'] == "RETS/1.5") {
			$this->AddHeader("Accept", "*/*");
		}

		// chop up Login URL to use for later requests
		$url_parts = parse_url($login_url);
		$this->server_hostname = $url_parts['host'];
		$this->server_port = (empty($url_parts['port'])) ? (($url_parts['scheme'] == 'https') ? 443 : 80) : $url_parts['port'];
		$this->server_protocol = $url_parts['scheme'];

		$this->capability_url['Login'] = $url_parts['path'];

		if (isset($url_parts['query']) && !empty($url_parts['query'])) {
			$this->capability_url['Login'] .= "?{$url_parts['query']}";
		}

		$this->username = $username;
		$this->password = $password;

		if (!empty($ua_pwd)) {
			// force use of RETS 1.7 User-Agent Authentication
			$this->ua_auth = true;
			$this->ua_pwd = $ua_pwd;
		}

		if (empty($this->cookie_file)) {
			$this->cookie_file = tempnam("", "phrets");
		}

		@touch($this->cookie_file);

		if (!is_writable($this->cookie_file)) {
			$this->set_error_info("phrets", -1, "Cookie file \"{$this->cookie_file}\" cannot be written to.  Must be an absolute path and must be writable");
			return false;
		}

		// start cURL magic
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, array(&$this, 'read_custom_curl_headers'));
		if ($this->debug_mode == true) {
			// open file handler to be used by cURL debug log
			$this->debug_log = @fopen($this->debug_file, 'a');

			if ($this->debug_log) {
				curl_setopt($this->ch, CURLOPT_VERBOSE, 1);
				curl_setopt($this->ch, CURLOPT_STDERR, $this->debug_log);
			}
			else {
				echo "Unable to save debug log to {$this->debug_file}\n";
			}
		}
		curl_setopt($this->ch, CURLOPT_HEADER, false);
		if ($this->force_basic_authentication == true) {
			curl_setopt($this->ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		}
		else {
			curl_setopt($this->ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST|CURLAUTH_BASIC);
		}
		if ($this->disable_follow_location != true) {
			curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
		}
		curl_setopt($this->ch, CURLOPT_USERPWD, $this->username.":".$this->password);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->ch, CURLOPT_COOKIEFILE, $this->cookie_file);
		curl_setopt($this->ch, CURLOPT_TIMEOUT, 0);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);

		// make request to Login transaction
		$result =  $this->RETSRequest($this->capability_url['Login']);
		if (!$result) {
			return false;
		}

		list($headers,$body) = $result;

		// parse body response
		$xml = $this->ParseXMLResponse($body);
		if (!$xml) {
			return false;
		}

		// log replycode and replytext for reference later
		$this->last_request['ReplyCode'] = "{$xml['ReplyCode']}";
		$this->last_request['ReplyText'] = "{$xml['ReplyText']}";

		// chop up login response
		// if multiple parts of the login response aren't found splitting on \r\n, redo using just \n
		$login_response = array();

		if ($this->server_version == "RETS/1.0") {
			if (isset($xml)) {
				$login_response = explode("\r\n", $xml);
				if (empty($login_response[3])) {
					$login_response = explode("\n", $xml);
				}
			}
		}
		else {
			if (isset($xml->{'RETS-RESPONSE'})) {
				$login_response = explode("\r\n", $xml->{'RETS-RESPONSE'});
				if (empty($login_response[3])) {
					$login_response = explode("\n", $xml->{'RETS-RESPONSE'});
				}
			}
		}

		// parse login response.  grab all capability URLs known and ones that begin with X-
		// otherwise, it's a piece of server information to save for reference
		foreach ($login_response as $line) {
			$name = null;
			$value = null;

			if (strpos($line, '=') !== false) {
			@list($name,$value) = explode("=", $line, 2);
			}

			$name = trim($name);
			$value = trim($value);
			if (!empty($name) && !empty($value)) {
				if (isset($this->allowed_capabilities[$name]) || preg_match('/^X\-/', $name) == true) {
					$this->capability_url[$name] = $value;
				}
				else {
					$this->server_information[$name] = $value;
				}
			}
		}

		// if 'Action' capability URL is provided, we MUST request it following the successful Login
		if (isset($this->capability_url['Action']) && !empty($this->capability_url['Action'])) {
			$previous_reply_code = $this->last_request['ReplyCode'];
			$previous_reply_text = $this->last_request['ReplyText'];

			$result = $this->RETSRequest($this->capability_url['Action']);
			if (!$result) {
				return false;
			}
			list($headers, $body) = $result;

			// there are no formatting restrictions on the response from an Action transaction, so don't try to parse
			// and just carry over the previous codes
			$this->last_request['ReplyCode'] = $previous_reply_code;
			$this->last_request['ReplyText'] = $previous_reply_text;
		}

		if ($this->compression_enabled == true) {
			curl_setopt($this->ch, CURLOPT_ENCODING, "gzip");
		}

		if ($this->last_request['ReplyCode'] == 0) {
			return true;
		}
		else {
			$this->set_error_info("rets", $this->last_request['ReplyCode'], $this->last_request['ReplyText']);
			return false;
		}

	}


	public function LastRequest() {
		// return replycode and replytext from last request
		return $this->last_request;
	}


	public function AddHeader($name, $value) {
		// add static header for cURL requests
		$this->static_headers[$name] = $value;
		return true;
	}


	public function DeleteHeader($name) {
		// delete static header from cURL requests
		unset($this->static_headers[$name]);
		return true;
	}


	public function ParseXMLResponse($data = "") {
		$this->reset_error_info();

		if (!empty($data)) {
			// parse XML function.  ability to replace SimpleXML with something later fairly easily
			if (defined('LIBXML_PARSEHUGE')) {
				$xml = @simplexml_load_string($data, 'SimpleXMLElement', LIBXML_PARSEHUGE);
			} else {
				$xml = @simplexml_load_string($data);
			}
			if (!is_object($xml)) {
				$this->set_error_info("xml", -1, "XML parsing error: {$data}");
				return false;
			}
			return $xml;
		}
		else {
			$this->set_error_info("xml", -1, "XML parsing error.  No data to parse");
			return false;
		}
	}


	public function RETSRequest($action, $parameters = "") {
		$this->reset_error_info();

		$this->last_request = array();
		$this->last_response_headers = array();
		$this->last_response_headers_raw = "";
		$this->last_remembered_header = "";

		// exposed raw RETS request function.  used internally and externally

		if (empty($action)) {
			die("RETSRequest called but Action passed has no value.  Failed login?\n");
		}

		$parse_results = parse_url($action, PHP_URL_HOST);
		if (empty($parse_results)) {
			// login transaction gave a relative path for this action
			$request_url = $this->server_protocol.'://'.$this->server_hostname.':'.$this->server_port.''.$action;
		}
		else {
			// login transaction gave an absolute path for this action
			$request_url = $action;
		}

		// build query string from arguments
		$request_arguments = "";
		if (is_array($parameters)) {
			$request_arguments = http_build_query($parameters, '', '&');
		}

		// update request method on each request
		if ($this->use_post_method) {
			// setup cURL for POST requests
			curl_setopt($this->ch, CURLOPT_POST, true);

			// assign the POST data for this request
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $request_arguments);
		} else {
			// setup cURL for GET requests
			curl_setopt($this->ch, CURLOPT_HTTPGET, true);

			// build entire URL if needed
			if (!empty($request_arguments)) {
				$request_url = $request_url .'?'. $request_arguments;
			}
		}

		// build headers to pass in cURL
		$request_headers = "";
		if (is_array($this->static_headers)) {
			foreach ($this->static_headers as $key => $value) {
				$request_headers .= "{$key}: {$value}\r\n";
			}
		}

		if ($this->ua_auth == true) {
			$session_id_to_calculate_with = "";

			// calculate RETS-UA-Authorization header
			$ua_a1 = md5($this->static_headers['User-Agent'] .':'. $this->ua_pwd);
			$session_id_to_calculate_with = ($this->use_interealty_ua_auth == true) ? "" : $this->session_id;
			$ua_dig_resp = md5(trim($ua_a1) .':'. trim($this->request_id) .':'. trim($session_id_to_calculate_with) .':'. trim($this->static_headers['RETS-Version']));
			$request_headers .= "RETS-UA-Authorization: Digest {$ua_dig_resp}\r\n";
		}

		$this->last_request_url = $request_url;
		curl_setopt($this->ch, CURLOPT_URL, $request_url);

		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array(trim($request_headers)));
		// do it
		$response_body = curl_exec($this->ch);
		$response_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);

		if ($this->debug_mode == true) {
			fwrite($this->debug_log, $response_body ."\n");
		}

		if ($this->catch_last_response == true) {
			$this->last_server_response = $this->last_response_headers_raw . $response_body;
		}

		if (isset($this->last_response_headers['WWW-Authenticate'])) {
			if (strpos($this->last_response_headers['WWW-Authenticate'], 'Basic') !== false) {
				$this->auth_support_basic = true;
			}
			if (strpos($this->last_response_headers['WWW-Authenticate'], 'Digest') !== false) {
				$this->auth_support_digest = true;
			}
		}

		if (isset($this->last_response_headers['RETS-Version'])) {
			$this->server_version = $this->last_response_headers['RETS-Version'];
		}

		if (isset($this->last_response_headers['Server'])) {
			$this->server_software = $this->last_response_headers['Server'];
		}

		if (isset($this->last_response_headers['Set-Cookie'])) {
			if (preg_match('/RETS-Session-ID\=(.*?)(\;|\s+|$)/', $this->last_response_headers['Set-Cookie'], $matches)) {
				$this->session_id = $matches[1];
			}
		}

		if ($response_code != 200) {
			$this->set_error_info("http", $response_code, $response_body);
			return false;
		}

		// return raw headers and body
		return array($this->last_response_headers_raw, $response_body);
	}


	private function read_custom_curl_headers($handle, $call_string) {
		$this->last_response_headers_raw .= $call_string;
		$header = null;
		$value = null;

		$trimmed_call_string = trim($call_string);

		if (strpos($call_string, ':') !== false) {
			@list($header, $value) = explode(':', $trimmed_call_string, 2);
		}

		$header = trim($header);
		$value = trim($value);

		if ( preg_match('/^HTTP\/1/', $trimmed_call_string) ) {
			$value = $trimmed_call_string;
			$header = "HTTP";
		}

		if (!empty($header)) {
			// new header
			$this->last_response_headers[$header] = $value;
			$last_remembered_header = $header;
		}
		elseif (!empty( $trimmed_call_string )) {
			// continuation of last header.  append to previous
			$this->last_response_headers[$this->last_remembered_header] .= $trimmed_call_string;
		}
		else { }

		return strlen($call_string);
	}


	public function Error() {
		if (isset($this->error_info['type']) && !empty($this->error_info['type'])) {
			return $this->error_info;
		}
		else {
			return false;
		}
	}


	private function set_error_info($type, $code, $text) {
		$this->error_info['type'] = $type;
		$this->error_info['code'] = $code;
		$this->error_info['text'] = $text;
		return true;
	}


	private function reset_error_info() {
		$this->error_info['type'] = "";
		$this->error_info['code'] = "";
		$this->error_info['text'] = "";
		return true;
	}


	private function is_server_version($check_version) {
		if ($check_version == "1_5_or_below") {
			if ($this->GetServerVersion() == "RETS/1.5" || $this->GetServerVersion() == "RETS/1.0") {
				return true;
			}
			else {
				return false;
			}
		}
		if ($check_version == "1_7_or_higher") {
			if ($this->GetServerVersion() == "RETS/1.7" || $this->GetServerVersion() == "RETS/1.7.1" || $this->GetServerVersion() == "RETS/1.7.2" || $this->GetServerVersion() == "RETS/1.8") {
				return true;
			}
			else {
				return false;
			}
		}
		return false;
	}


	private function fix_encoding($in_str) {
		if ($this->disable_encoding_fix == true || !function_exists("mb_detect_encoding")) {
			return $in_str;
		}

		$in_str = preg_replace('/\&\s/', '&amp; ', $in_str);
		$cur_encoding = mb_detect_encoding($in_str);
		if ($cur_encoding == "UTF-8" && mb_check_encoding($in_str, "UTF-8")) {
			return $in_str;
		}
		else {
			return utf8_encode($in_str);
		}
	}


	public function ServerDetail($detail) {
		if (isset($this->server_information[$detail])) {
			return $this->server_information[$detail];
		}
		else {
			return "";
		}
	}


	public function SetParam($name, $value) {
		switch ($name) {
			case "cookie_file":
				$this->cookie_file = $value;
				break;
			case "debug_file":
				$this->debug_file = $value;
				break;
			case "debug_mode":
				$this->debug_mode = $value;
				break;
			case "compression_enabled":
				$this->compression_enabled = $value;
				break;
			case "force_ua_authentication":
				$this->ua_auth = $value;
				break;
			case "disable_follow_location":
				$this->disable_follow_location = $value;
				break;
			case "force_basic_authentication":
				$this->force_basic_authentication = $value;
				break;
			case "use_interealty_ua_auth":
				$this->use_interealty_ua_auth = $value;
				break;
			case "catch_last_response":
				$this->catch_last_response = $value;
				break;
			case "disable_encoding_fix":
				$this->disable_encoding_fix = $value;
				break;
			case "offset_support":
				$this->offset_support = $value;
				break;
			case "override_offset_protection":
				$this->override_offset_protection = $value;
				break;
			case "use_post_method":
				$this->use_post_method = $value;
				break;
			default:
				return false;
		}

		return true;
	}


}

