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

use StingerSoft\DatatableBundle\Column\Column;
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The TableTypeInterface is the interface to be implemented by all custom table types, providing
 * methods that allow building of tables by adding columns, configuration of table options, providing a query builder etc.
 */
interface TableTypeInterface {

	/**
	 * Builds the table
	 *
	 * This method is called for each type in the hierarchy starting from the
	 * top most type.
	 *
	 * @param TableBuilderInterface $builder
	 * @param array                 $tableOptions
	 */
	public function buildTable(TableBuilderInterface $builder, array $tableOptions);

	/**
	 * Builds the table view used for rendering of the table.
	 *
	 * This method is called for each type in the hierarchy starting from the
	 * top most type. It allows adding more variables to the given view which may be used during rendering of the table.
	 *
	 * @param TableView      $view         the table view to add any additional information to
	 * @param TableInterface $table        the table instance the view belongs to
	 * @param array          $tableOptions the options of the table, previously configured via the #configureOptions method
	 * @param Column[]       $columns      the columns of the table
	 * @return void
	 */
	public function buildView(TableView $view, TableInterface $table, array $tableOptions, array $columns);

	/**
	 * Returns the HTML id to identify this table.
	 *
	 * @param array $tableOptions the resolved options of the table type, maybe containing an id prefix or suffix
	 * @return string the HTML id of this table
	 */
	public function getId(array $tableOptions);

	/**
	 * Builds the table settings.
	 *
	 * This method is called for each type in the hierarchy starting from the
	 * top most type.
	 *
	 * @param OptionsResolver $resolver
	 */
	public function configureOptions(OptionsResolver $resolver);

	/**
	 * Returns the name of the parent type.
	 *
	 * @return string|null The name of the parent type if any, null otherwise
	 */
	public function getParent();
}