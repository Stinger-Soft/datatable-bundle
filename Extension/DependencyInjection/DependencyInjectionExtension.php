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

namespace StingerSoft\DatatableBundle\Extension\DependencyInjection;

use Psr\Container\ContainerInterface;
use StingerSoft\DatatableBundle\Column\ColumnTypeInterface;
use StingerSoft\DatatableBundle\Filter\FilterTypeInterface;
use StingerSoft\DatatableBundle\Helper\InstanceHelperTrait;
use StingerSoft\DatatableBundle\Table\TableTypeInterface;

class DependencyInjectionExtension {

	use InstanceHelperTrait;

	protected $typeContainer;

	protected $parameters = [];

	/**
	 * @param ContainerInterface $typeContainer
	 */
	public function __construct(ContainerInterface $typeContainer) {
		$this->typeContainer = $typeContainer;
	}

	public function resolveTableType(string $type): TableTypeInterface {
		return $this->resolveType($type, TableTypeInterface::class);
	}

	protected function resolveType(string $type, string $typeInterfaceClassName) {
		if($this->typeContainer->has($type)) {
			return $this->typeContainer->get($type);
		}
		return $this->createTypeInstance($type, $typeInterfaceClassName);
	}

	public function resolveColumnType(string $type): ColumnTypeInterface {
		return $this->resolveType($type, ColumnTypeInterface::class);
	}

	public function resolveFilterType(string $type): FilterTypeInterface {
		return $this->resolveType($type, FilterTypeInterface::class);
	}

	public function setParameter(string $key, $value) {
		$this->parameters[$key] = $value;
	}

	public function getParameter(string $key, $default = null) {
		return $this->parameters[$key] ?? $default;
	}

}