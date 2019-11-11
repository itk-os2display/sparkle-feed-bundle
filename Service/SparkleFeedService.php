<?php

namespace Os2Display\SparkleFeedBundle\Service;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Os2Display\CoreBundle\Entity\Slide;
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
    private $entityManager;
    private $cronInterval;
    private $apiUrl;

    /**
     * SparkleFeedService constructor.
     * @param int $cronInterval
     * @param string $apiUrl
     * @param $clientId
     * @param $clientSecret
     * @param \Doctrine\Common\Cache\CacheProvider $cache
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     */
    public function __construct(int $cronInterval, string $apiUrl, $clientId, $clientSecret, CacheProvider $cache, EntityManagerInterface $entityManager)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->cache = $cache;
        $this->entityManager = $entityManager;
        $this->cronInterval = $cronInterval;
        $this->apiUrl = $apiUrl;
    }

    /**
     * @param \Os2Display\CoreBundle\Events\CronEvent $cronEvent
     */
    public function onCron(CronEvent $cronEvent)
    {
        $lastCron = $this->cache->fetch('last_cron');
        $timestamp = \time();

        if (false === $lastCron || $timestamp > $lastCron + $this->cronInterval) {
            $this->updateSlides();
            $this->cache->save('last_cron', $timestamp);
        }
    }

    /**
     * Update external data for sparkle slides.
     */
    private function updateSlides()
    {
        $cache = [];

        $slides = $this->entityManager
            ->getRepository('Os2DisplayCoreBundle:Slide')
            ->findBySlideType('sparkle');

        /* @var Slide $slide */
        foreach ($slides as $slide) {
            $options = $slide->getOptions();
            $selectedFeedId = $options['selectedFeed'] ?? null;

            if (isset($cache[$selectedFeedId])) {
                $slide->setExternalData($cache[$selectedFeedId]);
                continue;
            }

            $this->updateSlide($slide, $selectedFeedId);

            $cache[$selectedFeedId] = $slide->getExternalData();
        }

        $this->entityManager->flush();
    }

    /**
     * Update slide with data from sparkle.io feed.
     *
     * @param \Os2Display\CoreBundle\Entity\Slide $slide
     * @param $selectedFeedId
     */
    public function updateSlide(Slide $slide, $selectedFeedId)
    {
        $options = $slide->getOptions();

        $feed = $this->getFeed($selectedFeedId);

        // Set first element from feed for display in administration.
        if (count($feed) > 0) {
            $options['firstElement'] = $this->getFeedItemObject($feed[0]);
        }

        $slide->setOptions($options);

        $feedItems = [];

        foreach ($feed as $item) {
            $feedItems[] = $this->getFeedItemObject($item);
        }

        $slide->setExternalData($feedItems);
    }

    /**
     * Convert sparkle.io feed object to local representation.
     *
     * @param $item
     * @return object
     */
    private function getFeedItemObject($item)
    {
        return (object) [
            'text' => $item->text,
            'textMarkup' => $this->wrapTags($item->text),
            'mediaUrl' => $item->mediaUrl,
            'videoUrl' => $item->videoUrl,
            'username' => $item->username,
            'createdTime' => $item->createdTime,
        ];
    }

    public function wrapTags(string $input)
    {
        $text = trim($input);

        // Strip unicode zero-width-space.
        $text = str_replace("\xE2\x80\x8B", "", $text);

        // Collects trailing tags one by one.
        $trailingTags = [];
        $pattern = "/\s*#(?<tag>[^\s#]+)\n?$/u";
        while (preg_match($pattern, $text, $matches)) {
            // We're getting tags in reverse order.
            array_unshift($trailingTags, $matches['tag']);
            $text = preg_replace($pattern, '', $text);
        }

        // Wrap sections in p tags.
        $text = preg_replace("/(.+)\n?/u", '<p>\1</p>', $text);

        // Wrap inline tags.
        $pattern = '/(#(?<tag>[^\s#]+))/';
        $text = '<div class="text">'.preg_replace($pattern,
                '<span class="tag">\1</span>', $text).'</div>';
        // Append tags.
        $text .= PHP_EOL.'<div class="tags">'.implode(' ',
                array_map(function ($tag) {
                    return '<span class="tag">#'.$tag.'</span>';
                }, $trailingTags)).'</div>';

        return $text;
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
                $this->apiUrl.'v0.1/feed/'.$id,
                [
                    'timeout' => 2,
                    'headers' => [
                        'Authorization' => sprintf('Bearer %s', $token),
                    ],
                ]
            );

            $contents = $res->getBody()->getContents();
            $arr = json_decode($contents);

            $res = [];

            foreach ($arr->items as $item) {
                $res[] = $this->getFeedItemObject($item);
            }

            return $res;
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
                $this->apiUrl.'v0.1/feed',
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
                $this->apiUrl.'oauth/token',
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
