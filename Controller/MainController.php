<?php

namespace Os2Display\SparkleFeedBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class MainController
 * @package Os2Display\SparkleFeedBundle\Controller
 */
class MainController extends Controller
{
    /**
     * Test function.
     * @param \Os2Display\SparkleFeedBundle\Service\SparkleFeedService $feedService
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function testAction()
    {
        $feeds = $this->container->get('os2display.sparkle_feed.service')
            ->getFeeds();

        foreach ($feeds as $feed) {
            $feed->contents = $this->container->get('os2display.sparkle_feed.service')
                ->getFeed($feed->id);
        }

        return new JsonResponse(
            ['feeds' => $feeds]
        );
    }

    /**
     * Get a list of feeds available.
     */
    public function feedsAction()
    {
        $feeds = $this->container->get('os2display.sparkle_feed.service')
            ->getFeeds();

        return new JsonResponse(
            ['feeds' => $feeds]
        );
    }
}
