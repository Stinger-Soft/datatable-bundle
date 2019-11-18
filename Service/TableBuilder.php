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
use StingerSoft\DatatableBundle\Column\ColumnInterface;
use StingerSoft\DatatableBundle\Column\ColumnTypeInterface;
use StingerSoft\DatatableBundle\Column\ColumnView;
use StingerSoft\DatatableBundle\Exception\OutOfBoundsException;
use StingerSoft\DatatableBundle\Extension\DependencyInjection\DependencyInjectionExtension;
use StingerSoft\DatatableBundle\Table\Table;

/**
 * Default implementation of the TableBuilderInterface
 */
class TableBuilder implements \IteratorAggregate, TableBuilderInterface {


	/**
	 * @var Column[] Array of all column settings
	 */
	protected $columns;

	/**
	 * @var Table the table this builder is used for
	 */
	protected $table;

	/**
	 * @var array the options for the table
	 */
	protected $tableOptions;

	/**
	 * @var DependencyInjectionExtension
	 */
	protected $dependencyInjectionExtension;

	public function __construct(Table $table, DependencyInjectionExtension $dependencyInjectionExtension, array $tableOptions = array()) {
		$this->table = $table;
		$this->tableOptions = $tableOptions;
		$this->dependencyInjectionExtension = $dependencyInjectionExtension;
		$this->columns = array();
	}

	/**
	 * {@inheritdoc}
	 * @see \StingerSoft\DatatableBundle\Service\TableBuilderInterface::add()
	 */
	public function add($column, $type = null, array $options = array()) {
		if(!$column instanceof ColumnInterface) {
			$typeInstance = null;
			try {
				$typeInstance = $this->getColumnTypeInstance($type);
			} catch(\ReflectionException $re) {
				throw new \InvalidArgumentException('If the column parameter is no instance of the interface '.ColumnInterface::class.' you must specify a valid classname for the type to be used! '.$type.' given', null, $re);
			}
			$column = new Column($column, $typeInstance, $this->dependencyInjectionExtension, $options, $this->tableOptions, $this->table->getQueryBuilder());
		}
		$this->columns[$column->getPath()] = $column;
		return $this;
	}

	/**
	 * Creates an instance of the given column type class.
	 *
	 * @param string $class
	 *            Classname of the column type
	 * @return ColumnTypeInterface
	 * @throws \InvalidArgumentException
	 */
	private function getColumnTypeInstance($class) {
		if($class === null) {
			throw new \InvalidArgumentException('Paramater class may not be null!');
		}
		return $this->dependencyInjectionExtension->resolveColumnType($class);
	}

	/**
	 * Returns whether a column with the given path exists (implements the \ArrayAccess interface).
	 *
	 * @param string $path The path of the column
	 * @return bool
	 */
	public function offsetExists($path) {
		return $this->has($path);
	}

	/**
	 * Returns the column with the given path (implements the \ArrayAccess interface).
	 *
	 * @param string $path The path of the column
	 * @return ColumnView The column
	 * @throws \OutOfBoundsException If the named column does not exist.
	 */
	public function offsetGet($path) {
		return $this->get($path);
	}

	/**
	 * Adds a column to the table (implements the \ArrayAccess interface).
	 *
	 * @param string     $path     Ignored. The path of the column is used
	 * @param ColumnView $settings The column to be added
	 * @see self::add()
	 */
	public function offsetSet($path, $settings) {
		$this->add($settings);
	}

	/**
	 * Removes the column with the given path from the table (implements the \ArrayAccess interface).
	 *
	 * @param string $path The path of the column to remove
	 */
	public function offsetUnset($path) {
		$this->remove($path);
	}

	/**
	 * Returns the iterator for the columns.
	 *
	 * @return \Traversable|Column[]
	 */
	public function getIterator() {
		return $this->columns;
	}

	/**
	 * Returns the number of columns (implements the \Countable interface).
	 *
	 * @return int The number of columns
	 */
	public function count() {
		return count($this->columns);
	}

	/**
	 * @inheritdoc
	 */
	public function get($path) {
		if(isset($this->columns[$path])) {
			return $this->columns[$path];
		}

		throw new OutOfBoundsException(sprintf('Column "%s" does not exist.', $path));
	}

	/**
	 * @inheritdoc
	 */
	public function has($path) {
		return isset($this->columns[$path]);
	}

	/**
	 * Removes a column from the table.
	 *
	 * @param string $path The path of the column to remove
	 * @return $this
	 */
	public function remove($path) {
		if(isset($this->columns[$path])) {
			unset($this->columns[$path]);
		}
		return $this;
	}

	/**
	 * Returns all columns in this table.
	 *
	 * @return Column[]
	 */
	public function all() {
		return $this->columns;
	}
}