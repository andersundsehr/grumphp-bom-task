<?php
namespace AUS\GrumphpBomTask;

use GrumPHP\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class ExtensionLoader
 *
 * @author Matthias Vogel <m.vogel@andersundsehr.com>
 * @package AUS\GrumphpBomTask
 */
class ExtensionLoader implements ExtensionInterface
{
    /**
     * @param ContainerBuilder $container
     * @return \Symfony\Component\DependencyInjection\Definition
     */
    public function load(ContainerBuilder $container)
    {
        return $container->register('task.aus_bom_fixer', BomFixerTask::class)
            ->addArgument($container->get('config'))
            ->addArgument($container->get('process_builder'))
            ->addArgument($container->get('formatter.raw_process'))
            ->addTag('grumphp.task', ['config' => 'aus_bom_fixer']);
    }
}
