<?php
declare(strict_types=1);

namespace Comet;

// FIXME Absolutely Expreimental! Please do not use it in production
// TODO Use Comet\Response object with simplified acces like Python Requests objects

class Client {
	
	// TODO Allow using cURL as HTTP transport lib
	public function __construct()
	{
	}

	// TODO Use file_get_contents
	static public function get($url, $data = null)
	{		
		if ($data) {
			$url .= '?' . http_build_query($data);
		}

		// TODO Errors?
		return file_get_contents($url);
	}

	static public function post($url, $data)
	{
		if (is_array($data)) {
			$data = json_encode($data);
		}

		$opts = [
            'http' => [
                'method' => "POST",
                'header' => 
                    "Content-type: application/json\r\n" .
                    "Accept: application/json\r\n" .
                    "Connection: close\r\n" .
                    "Content-length: " . strlen($data) . "\r\n",
                'content'=> $data,
                'protocol_version' => 1.1,
       		],
     		'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ];

   		$ctx = stream_context_create($opts);
   		$result = file_get_contents($url, false, $ctx);

   		// TODO Errors?
   		return $result;
	}
}