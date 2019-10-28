<?php

namespace Os2Display\SparkleFeedBundle\DependencyInjection;

use Os2Display\CoreBundle\DependencyInjection\Os2DisplayBaseExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class Os2DisplaySparkleFeedExtension extends Os2DisplayBaseExtension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->dir = __DIR__;

        parent::load($configs, $container);

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $def = $container->getDefinition('os2display.sparkle_feed.service');
        $def->replaceArgument(0, $config['cron_interval']);
        $def->replaceArgument(1, $config['api_url']);
        $def->replaceArgument(2, $config['client_id']);
        $def->replaceArgument(3, $config['client_secret']);
    }
}
