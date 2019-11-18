<?php

/*
 * This file is part of the StingerSoft Datatable Bundle.
 *
 * (c) Oliver Kotte <oliver.kotte@stinger-soft.net>
 * (c) Florian Meyer <florian.meyer@stinger-soft.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace StingerSoft\DatatableBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TableCompilerPass implements CompilerPassInterface {

	protected $tableExtensionService;
	protected $tableTypeTag;
	protected $columnTypeTag;
	protected $filterTypeTag;

	public function __construct(string $tableExtensionService = 'stinger_soft_datatable.extension', string $tableTypeTag = 'stinger_soft_datatable.table', string $columnTypeTag = 'stinger_soft_datatable.column', string $filterTypeTag = 'stinger_soft_datatable.filter') {
		$this->tableExtensionService = $tableExtensionService;
		$this->tableTypeTag = $tableTypeTag;
		$this->columnTypeTag = $columnTypeTag;
		$this->filterTypeTag = $filterTypeTag;
	}

	public function process(ContainerBuilder $container) {

		if(!$container->hasDefinition($this->tableExtensionService)) {
			return;
		}

		$definition = $container->getDefinition($this->tableExtensionService);

		$servicesMap = array();
		$this->processTypes($container, $this->tableTypeTag, $servicesMap);
		$this->processTypes($container, $this->columnTypeTag, $servicesMap);
		$this->processTypes($container, $this->filterTypeTag, $servicesMap);

		$definition->addArgument(ServiceLocatorTagPass::register($container, $servicesMap));

	}

	private function processTypes(ContainerBuilder $container, string $tagType, array &$servicesMap) {
		// Builds an array with fully-qualified type class names as keys and service IDs as values
		foreach($container->findTaggedServiceIds($tagType, true) as $serviceId => $tag) {
			// Add form type service to the service locator
			$serviceDefinition = $container->getDefinition($serviceId);
			$servicesMap[$serviceDefinition->getClass()] = new Reference($serviceId);
		}
		return $servicesMap;
	}
}