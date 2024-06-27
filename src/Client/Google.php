<?php
namespace Lukasbableck\ContaoInstantIndexingBundle\Client;

use Contao\CoreBundle\Monolog\ContaoContext;
use Google\Client;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class Google {
	public function __construct(private LoggerInterface $contaoGeneralLogger) {
	}

	public function publish(string $url, string $authConfig, bool $delete = false): void {
		$client = new Client();
		$phpModules = get_loaded_extensions();
		if (!\in_array('openssl', $phpModules)) {
			$guzzleClient = new \GuzzleHttp\Client(['curl' => [
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
		$response = $httpClient->request('POST', $endpoint, ['body' => $body]);

		if (200 != $response->getStatusCode()) {
			$this->contaoGeneralLogger->log(Logger::ERROR, 'Error while submitting URL: '.$response->getStatusCode(), ['contao' => new ContaoContext(__METHOD__, ContaoContext::GENERAL)]);

			return;
		}
		$this->contaoGeneralLogger->log(Logger::INFO, 'URL submitted to Google: '.$url, ['contao' => new ContaoContext(__METHOD__, ContaoContext::GENERAL)]);
	}

	public function getMetadata(string $url, string $authConfig): array {
		$client = new Client();
		$phpModules = get_loaded_extensions();
		if (!\in_array('openssl', $phpModules)) {
			$guzzleClient = new \GuzzleHttp\Client(['curl' => [
				\CURLOPT_SSL_VERIFYPEER => false,
				\CURLOPT_SSL_VERIFYHOST => false,
			]]);

			$client->setHttpClient($guzzleClient);
		}

		$client->setAuthConfig(json_decode($authConfig, true));
		$client->addScope('https://www.googleapis.com/auth/indexing');

		$httpClient = $client->authorize();
		$endpoint = 'https://indexing.googleapis.com/v3/urlNotifications/metadata?url='.$url;
		$response = $httpClient->request('GET', $endpoint);

		if (200 != $response->getStatusCode()) {
			$this->contaoGeneralLogger->log(Logger::ERROR, 'Error while getting metadata: '.$response->getStatusCode(), ['contao' => new ContaoContext(__METHOD__, ContaoContext::GENERAL)]);
		}

		return json_decode($response->getBody(), true);
	}
}
