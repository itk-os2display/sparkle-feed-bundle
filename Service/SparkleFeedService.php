<?php

namespace Os2Display\SparkleFeedBundle\Service;

use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Os2Display\CoreBundle\Events\CronEvent;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class SparkleFeedService
 * @package Os2Display\SparkleFeedBundle\Service
 */
class SparkleFeedService
{
    // See: https://api.getsparkle.io/help

    private $clientId;
    private $clientSecret;
    private $cache;

    /**
     * SparkleFeedService constructor.
     * @param $clientId
     * @param $clientSecret
     */
    public function __construct(CacheProvider $cache, $clientId, $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->cache = $cache;
    }

    /**
     * @param \Os2Display\CoreBundle\Events\CronEvent $cronEvent
     */
    public function onCron(CronEvent $cronEvent)
    {
    }

    /**
     * Get a feed by id.
     *
     * @param $id
     * @return bool|mixed
     */
    public function getFeed($id)
    {
        $token = $this->getToken();

        if (!$token) {
            return false;
        }

        try {
            $client = new Client();
            $res = $client->request(
                'GET',
                'https://api.getsparkle.io/v0.1/feed/'.$id,
                [
                    'timeout' => 2,
                    'headers' => [
                        'Authorization' => sprintf('Bearer %s', $token),
                    ],
                ]
            );

            $contents = $res->getBody()->getContents();

            return json_decode($contents);
        } catch (GuzzleException $exception) {
            return false;
        }
    }

    /**
     * Get list of available feeds.
     *
     * @return bool|mixed
     */
    public function getFeeds() {
        $token = $this->getToken();

        if (!$token) {
            return false;
        }

        try {
            $client = new Client();
            $res = $client->request(
                'GET',
                'https://api.getsparkle.io/v0.1/feed',
                [
                    'timeout' => 2,
                    'headers' => [
                        'Authorization' => sprintf('Bearer %s', $token),
                    ],
                ]
            );

            $contents = $res->getBody()->getContents();

            return json_decode($contents);
        } catch (GuzzleException $exception) {
            return false;
        }
    }

    /**
     * Get an access token to Sparkle.io API.
     *
     * @return array|mixed
     */
    private function getToken()
    {
        // Return cached token, if it exists.
        if ($this->cache->contains('access_token')) {
            return $this->cache->fetch('access_token');
        }

        try {
            $client = new Client();
            $res = $client->request(
                'POST',
                'https://api.getsparkle.io/oauth/token',
                [
                    'timeout' => 2,
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'form_params' => [
                        'grant_type' => urlencode('client_credentials'),
                        'client_id' => urlencode($this->clientId),
                        'client_secret' => urlencode($this->clientSecret),
                    ],
                ]
            );

            $tokenResponse = $res->getBody()->getContents();
            $tokenDecoded = json_decode($tokenResponse);

            $this->cache->save('access_token', $tokenDecoded->access_token, $tokenDecoded->expires_in - 1000);

            return $tokenDecoded->access_token;
        } catch (GuzzleException $exception) {
            return false;
        }
    }
}
