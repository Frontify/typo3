<?php
/* (c) Copyright Frontify Ltd., all rights reserved. Created 2019-12-17 */

namespace Frontify\Typo3;

use Frontify\Typo3\Asset\FrontifyAsset;
use Frontify\Typo3\Utility\Frontify;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class ApiServiceProvider implements SingletonInterface {

    /** @var bool */
    protected $isEnabled;

    /** @var string|null */
    protected $frontifyBaseUrl;

    /** @var string|null */
    protected $token;

    /**
     * @var Client
     */
    protected $client;

    /**
     * ServiceProvider constructor.
     */
    public function __construct() {
        $configuration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('frontify_typo3');
        $this->frontifyBaseUrl = $configuration['url'] ?? null;
        $this->token = $configuration['token'] ?? null;
        $this->isEnabled = $this->frontifyBaseUrl && $this->token;
    }

    /**
     * @return string|null
     */
    public function getToken(): ?string {
        return $this->token;
    }

    /**
     * @return string|null
     */
    public function getFrontifyBaseUrl(): ?string {
        return $this->frontifyBaseUrl;
    }

    /**
     * Returns if frontify is setup and can be used
     * @return bool
     */
    public function isEnabled(): bool {
        return $this->isEnabled;
    }

    /**
     * Returns the configured HttpClient
     * @return Client
     * @throws \Exception
     */
    protected function getClient(): Client {
        if (!$this->isEnabled) {
            throw new \Exception('Please configure Frontify first.');
        }

        if (!$this->client) {
            $this->client = new Client([
                'base_uri' => rtrim($this->frontifyBaseUrl, '/'),
                'timeout' => 5.0,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token
                ]
            ]);
        }

        return $this->client;
    }

    /**
     * Extracts the json from the response and returns an array
     *
     * @param ResponseInterface $response
     *
     * @return array
     * @throws \Exception
     */
    protected function getJson(ResponseInterface $response): array {
        $responseString = (string) $response->getBody();
        $json = json_decode($responseString, true);

        if (!$json || $json['success'] !== true) {
            throw new \Exception('Invalid response');
        }

        return $json;
    }

    /**
     * Writes a file to a writable resource
     *
     * @param string $identifier
     * @param $resource
     */
    public function writeToResource(string $identifier, $resource) {
        list($id, $url) = Frontify::extractIdAndToken($identifier);
        $this->client->get(
            $url,
            [
                'sink' => $resource
            ]
        );
    }

    public function getAssetByIdentifier(string $identifier): ?FrontifyAsset {
        list($id, $url) = Frontify::extractIdAndToken($identifier);
        return $this->getAssetById($id);
    }

    /**
     * @param int $id
     *
     * @return array|null
     * @throws \Exception
     */
    public function getAssetById(int $id): ?FrontifyAsset {
        $response = $this->getClient()->get("/v1/screen/data/{$id}");
        $assetData = $this->getJson($response);

        return new FrontifyAsset(
            $assetData['id'],
            $assetData['title'],
            $assetData['filename'],
            $assetData['description'],
            $assetData['width'],
            $assetData['height'],
            $assetData['ext'],
            $assetData['mime_type'],
            $assetData['generic_url'],
            $assetData['preview_url'],
            $assetData['created'],
            $assetData['modified'],
            (int) $assetData['filesize'] * 1024,
            $assetData['metadata']
        );
    }

}