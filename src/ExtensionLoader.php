<?php
namespace AUS\GrumphpBomTask;

use GrumPHP\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ExtensionLoader implements ExtensionInterface
{
    public function load(ContainerBuilder $container)
    {
        return $container->register('task.aus_bom_fixer', BomFixerTask::class)
            ->addArgument($container->get('config'))
            ->addArgument($container->get('process_builder'))
            ->addArgument($container->get('formatter.raw_process'))
            ->addTag('grumphp.task', ['config' => 'aus_bom_fixer']);
    }
}
