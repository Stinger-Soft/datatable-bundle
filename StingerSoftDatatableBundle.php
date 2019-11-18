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

namespace StingerSoft\DatatableBundle;

use StingerSoft\DatatableBundle\Column\ColumnTypeInterface;
use StingerSoft\DatatableBundle\DependencyInjection\Compiler\TableCompilerPass;
use StingerSoft\DatatableBundle\Filter\FilterTypeInterface;
use StingerSoft\DatatableBundle\Table\TableTypeInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class StingerSoftDatatableBundle extends Bundle {

	public const TABLE_TYPE_SERVICE_TAG = 'stinger_soft_datatable.table';
	public const COLUMN_TYPE_SERVICE_TAG = 'stinger_soft_datatable.column';
	public const FILTER_TYPE_SERVICE_TAG = 'stinger_soft_datatable.filter';
	public const TABLE_EXTENSION_SERVICE_ID = 'stinger_soft_datatable.extension';
	
	public function build(ContainerBuilder $container) {
		$container->registerForAutoconfiguration(TableTypeInterface::class)->addTag(self::TABLE_TYPE_SERVICE_TAG);
		$container->registerForAutoconfiguration(ColumnTypeInterface::class)->addTag(self::COLUMN_TYPE_SERVICE_TAG);
		$container->registerForAutoconfiguration(FilterTypeInterface::class)->addTag(self::FILTER_TYPE_SERVICE_TAG);
		$container->addCompilerPass(new TableCompilerPass(self::TABLE_EXTENSION_SERVICE_ID, self::TABLE_TYPE_SERVICE_TAG, self::COLUMN_TYPE_SERVICE_TAG, self::FILTER_TYPE_SERVICE_TAG));
	}
}