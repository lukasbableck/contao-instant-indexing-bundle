<?php
namespace LukasBableck\ContaoInstantIndexingBundle\Client;

use Google\Client;

class Google {
	public function index(string $url, string $authConfig): void {
		$client = new Client();
		$phpModules = get_loaded_extensions();
		if (!\in_array('openssl', $phpModules)) {
			$guzzleClient = new GuzzleHttp\Client(['curl' => [
				\CURLOPT_SSL_VERIFYPEER => false,
				\CURLOPT_SSL_VERIFYHOST => false,
			],
			]);

			$client->setHttpClient($guzzleClient);
		}

		$client->setAuthConfig($authConfig);
		$client->addScope('https://www.googleapis.com/auth/indexing');

		$httpClient = $client->authorize();
		$endpoint = 'https://indexing.googleapis.com/v3/urlNotifications:publish';
		$body = '{"url": "'.$url.'",  "type": "URL_UPDATED"}';
		$response = $httpClient->post($endpoint, ['body' => $body]);

		if ($response->getStatusCode() != 200) {
			throw new Exception('Error while indexing URL', $response->getStatusCode());
		}
	}
}
