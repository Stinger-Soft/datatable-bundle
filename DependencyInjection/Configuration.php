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
namespace StingerSoft\DatatableBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface {

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getConfigTreeBuilder() {
		$treeBuilder = new TreeBuilder('stinger_soft_datatable');
		if(Kernel::VERSION_ID < 40200) {
			$root = $treeBuilder->root('stinger_soft_datatable');
		} else {
			$root = $treeBuilder->getRootNode();
		}
		// @formatter:off
		$root->children()
			->arrayNode('search')
				->addDefaultsIfNotSet()
				->children()
					->integerNode('delay')
						->defaultValue(500)
						->validate()
							->ifTrue(function($value){return $value < 0;})
							->thenInvalid('Please provide a positive value as the delay in milliseconds or 0 for no delay')
						->end()
					->end()
				->end()
			->end()
		->end();
		// @formatter:on
		return $treeBuilder;
	}
}
