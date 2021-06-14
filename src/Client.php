<?php
declare(strict_types=1);

namespace Comet;

/**
 * Class Client
 * Absolutely Expreimental! Please do not use it in production
 * @package Comet
 */
class Client {

    /**
     * Client constructor
     */
	public function __construct()
	{
	}

    /**
     * @param $url
     * @param null $data
     * @return false|string
     */
	static public function get($url, $data = null)
	{		
		if ($data) {
			$url .= '?' . http_build_query($data);
		}

		return file_get_contents($url);
	}

    /**
     * @param $url
     * @param $data
     * @return false|string
     */
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
                'protocol_version' => '1.1',
       		],
     		'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ];

   		$ctx = stream_context_create($opts);
   		$result = file_get_contents($url, false, $ctx);

   		return $result;
	}
}