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
namespace StingerSoft\DatatableBundle\Table;

use Symfony\Component\OptionsResolver\OptionsResolver;
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;

/**
 * Basic implementation of a table
 */
abstract class AbstractTableType implements TableTypeInterface {

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\DatatableBundle\Table\TableTypeInterface::buildTable()
	 */
	public function buildTable(TableBuilderInterface $builder, array $tableOptions) {
	}

	public function buildView(TableView $view, TableInterface $table, array $tableOptions, array $columns) {
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\DatatableBundle\Table\TableTypeInterface::getId()
	 */
	public function getId(array $tableOptions) {
		return 'datatable_' . uniqid();
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\DatatableBundle\Table\TableTypeInterface::configureOptions()
	 */
	public function configureOptions(OptionsResolver $resolver) {
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\DatatableBundle\Table\TableTypeInterface::getParent()
	 */
	public function getParent() {
		return TableType::class;
	}
}