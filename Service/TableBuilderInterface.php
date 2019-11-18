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

namespace StingerSoft\DatatableBundle\Service;

use StingerSoft\DatatableBundle\Column\Column;

/**
 * Class to build a table, ie add columns and configuration
 */
interface TableBuilderInterface extends \ArrayAccess, \Traversable, \Countable {

	/**
	 * Adds a column to the table
	 *
	 * @param Column|string $column
	 *            Property path to bind to this column or ColumnView instance
	 * @param string        $type
	 *            The type (i.e. class name) of this column
	 * @param array         $options
	 *            Options to pass the column type
	 * @return $this The table builder, allowing for chaining
	 */
	public function add($column, $type = null, array $options = array());

	/**
	 * Returns the column with the given path.
	 *
	 * @param string $path The path of the column
	 * @return Column
	 * @throws \OutOfBoundsException If the named column does not exist.
	 */
	public function get($path);

	/**
	 * Returns whether a column with the given path exists..
	 *
	 * @param string $path The path of the column
	 * @return boolean
	 */
	public function has($path);

	/**
	 * Removes a column from the table.
	 *
	 * @param string $path The path of the column to remove
	 * @return $this
	 */
	public function remove($path);

	/**
	 * Returns all columns in this table.
	 *
	 * @return Column[]
	 */
	public function all();

}