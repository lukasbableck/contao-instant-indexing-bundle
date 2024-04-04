<?php
namespace LukasBableck\ContaoInstantIndexingBundle\Client;

use Google\Client;

class Google {
	public function publish(string $url, string $authConfig, bool $delete = false): void {
		$client = new Client();
		$phpModules = get_loaded_extensions();
		if (!\in_array('openssl', $phpModules)) {
			$guzzleClient = new GuzzleHttp\Client(['curl' => [
				\CURLOPT_SSL_VERIFYPEER => false,
				\CURLOPT_SSL_VERIFYHOST => false,
			]]);

			$client->setHttpClient($guzzleClient);
		}

		$client->setAuthConfig(json_decode($authConfig, true));
		$client->addScope('https://www.googleapis.com/auth/indexing');

		$httpClient = $client->authorize();
		$endpoint = 'https://indexing.googleapis.com/v3/urlNotifications:publish';
		$body = '{"url": "'.$url.'",  "type": "'.($delete ? 'URL_DELETED' : 'URL_UPDATED').'"}';
		$response = $httpClient->post($endpoint, ['body' => $body]);

		if (200 != $response->getStatusCode()) {
			throw new \Exception('Error while indexing URL: '.$response->getStatusCode(), $response->getBody());
		}
	}

	public function getMetadata(string $url, string $authConfig): array {
		$client = new Client();
		$phpModules = get_loaded_extensions();
		if (!\in_array('openssl', $phpModules)) {
			$guzzleClient = new GuzzleHttp\Client(['curl' => [
				\CURLOPT_SSL_VERIFYPEER => false,
				\CURLOPT_SSL_VERIFYHOST => false,
			]]);

			$client->setHttpClient($guzzleClient);
		}

		$client->setAuthConfig(json_decode($authConfig, true));
		$client->addScope('https://www.googleapis.com/auth/indexing');

		$httpClient = $client->authorize();
		$endpoint = 'https://indexing.googleapis.com/v3/urlNotifications/metadata?url='.$url;
		$response = $httpClient->get($endpoint);

		if (200 != $response->getStatusCode()) {
			throw new \Exception('Error while getting metadata: '.$response->getStatusCode(), $response->getBody());
		}

		return json_decode($response->getBody(), true);
	}
}
