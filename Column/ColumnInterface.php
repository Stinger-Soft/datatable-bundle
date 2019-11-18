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

namespace StingerSoft\DatatableBundle\Column;

use Doctrine\ORM\QueryBuilder;
use StingerSoft\DatatableBundle\Filter\Filter;
use StingerSoft\DatatableBundle\Filter\FilterInterface;
use StingerSoft\DatatableBundle\Transformer\DataTransformerInterface;

/**
 * The interface all columns must implement, providing methods to access the path / property to retrieve a value from,
 * to create views when rendering a column and methods that actually allow to retrieve the data.
 */
interface ColumnInterface {

	/**
	 * Get the path to access the property on the bound object
	 *
	 * @return string the path to access the property on the bound object
	 */
	public function getPath();

	/**
	 * Get the path to access the property on the bound object
	 *
	 * @param string $path the path to access the property on the bound object
	 * @return ColumnInterface
	 */
	public function setPath($path);

	/**
	 * Get the data source of the column.
	 *
	 * @return QueryBuilder|array the data source of the column.
	 */
	public function getDataSource();

	/**
	 * Get the query builder used by a filter for the column (if any).
	 *
	 * @return QueryBuilder the query builder used by a filter for the column (if any).
	 */
	public function getQueryBuilder();

	/**
	 * Get the path to be used by a query builder for sorting and ordering etc.
	 * This may differ from the <code>ColumnSettings::getPath()</code> especially for joined paths.
	 *
	 * @return string the path to be used by a query builder for sorting and ordering etc.
	 */
	public function getQueryPath();

	/**
	 * Set the path to be used by a query builder for sorting and ordering etc.
	 *
	 * @param string $queryPath the path to be used by a query builder for sorting and ordering etc.
	 * @return ColumnInterface
	 */
	public function setQueryPath($queryPath);

	/**
	 * Get the path to be used by a query builder when generating all selectable values to be used in a filter.
	 *
	 * This may differ from the query path, as the query path may be used for ordering by a title, but in a filter,
	 * ids have to be selected. In case a filter query path is given it will be used for retrieving filterable values,
	 * otherwise the query path will be used instead.
	 *
	 * @return string|null the query path to be used for generating selectable filterable values or null in order
	 *                     to use the default query path used for ordering and searching etc.
	 */
	public function getFilterQueryPath();

	/**
	 * Get whether the column can be ordered or not.
	 *
	 * @return boolean <code>true</code> if the column can be ordered, <code>false</code> otherwise.
	 */
	public function isOrderable();

	/**
	 * Get whether the column can be searched by a global search term or not.
	 *
	 * @return boolean <code>true</code> if the column can be searched by a global search term, <code>false</code> otherwise.
	 */
	public function isSearchable();

	/**
	 * Get whether the column can be filtered individually or not.
	 *
	 * @return boolean <code>true</code> if the column can be filtered individually, <code>false</code> otherwise.
	 */
	public function isFilterable();

	/**
	 * Get the filter that is associated to the column, if any.
	 *
	 * @return FilterInterface|null the filter that is associated to the column, if any.
	 */
	public function getFilter();

	/**
	 * Set the filter that is associated to the column.
	 *
	 * @param FilterInterface $filter the filter that is associated to the column.
	 * @return ColumnInterface
	 */
	public function setFilter($filter);

	/**
	 * Creates a new view to be used for rendering the column in the the context of the table.
	 *
	 * @param ColumnView|null $parent an already pre-populated parent view to extend, if any
	 * @return ColumnView the new column view.
	 */
	public function createView(ColumnView $parent = null);

	/**
	 * Creates the data to be used for the content of a cell belonging to the column.
	 *
	 * @param object $item      the object to retrieve the data from using the columns path or any value delegates
	 * @param string $rootAlias the root alias of the table / query builder required for actually accessing the correct
	 *                          object property / field from the query builder
	 * @return mixed the value or data of the item according to the columns path and options etc. including
	 *                          any data transformers registered on the column.
	 */
	public function createData($item, $rootAlias);

	/**
	 * Gets the callable to fetch the value of the bound object.
	 * By default a property accessor will be used to fetch the value based on the configured path
	 *
	 * @return callable Callable to fetch the value of the bound object.
	 */
	public function getValueDelegate();

	/**
	 * Sets the callable to fetch the value of the bound object.
	 * By default a property accessor will be used to fetch the value based on the configured path
	 *
	 * @param callable $valueDelegate
	 *            Callable to fetch the value of the bound object.
	 * @return ColumnInterface
	 */
	public function setValueDelegate($valueDelegate);

	/**
	 * Get the callable to update the query builder of the bound object in order to add any required
	 * where clauses explicitly required for a global search.
	 * By default the query_path or path property will be used to perform a like query for a global search term.
	 *
	 * @return callable the callable to update the query builder of the bound object in order to add any required
	 * where clauses explicitly required for a global search.
	 */
	public function getServerSideSearchDelegate();

	/**
	 * Set the callable to update the query builder of the bound object in order to add any required
	 * where clauses explicitly required for a global search.
	 * By default the query_path or path property will be used to perform a like query for a global search term.
	 *
	 * @param callable $searchDelegate the callable to update the query builder of the bound object in order to add any required
	 *                                 where clauses explicitly required for a global search.
	 * @return ColumnInterface
	 */
	public function setServerSideSearchDelegate($searchDelegate);

	/**
	 * Get the callable to update the query builder of the bound object in order to add any required
	 * where clauses explicitly for that column to be used for filtering that column.
	 * By default the query_path or path property will be used to perform a like query for a column specific filter term.
	 * Please Note: this delegate will only be used if no specific filter type is set for the column and the filter type
	 * does not define an own filter delegate (specified via the 'filter_server_delegate' option for the filter type)
	 *
	 * @return callable the callable to update the query builder of the bound object in order to add any required
	 * where clauses explicitly for that column to be used for filtering that column.
	 * @see Filter::getFilterDelegate()
	 */
	public function getServerSideFilterDelegate();

	/**
	 * Set the callable to update the query builder of the bound object in order to add any required
	 * where clauses explicitly for that column to be used for filtering that column.
	 * By default the query_path or path property will be used to perform a like query for a column specific filter term.
	 *
	 * @param callable $filterDelegate the callable to update the query builder of the bound object in order to add any required
	 *                                 where clauses explicitly for that column to be used for filtering that column.
	 * @return ColumnInterface
	 */
	public function setServerSideFilterDelegate($filterDelegate);

	/**
	 * Get the callable to return an array of queryPath => direction mappings, allowing to order by joined fields.
	 * By default the query_path or path will be used to perform order by.
	 *
	 * @return callable|null
	 */
	public function getServerSideOrderDelegate();

	/**
	 * Set the callable to return an array of queryPath => direction mappings, allowing to order by joined fields.
	 * By default the query_path or path will be used to perform order by.
	 *
	 * @param callable $orderDelegate the callable to return an array of queryPath => direction mappings, allowing to
	 *                                order by joined fields.
	 * @return ColumnInterface
	 */
	public function setServerSideOrderDelegate($orderDelegate);

	/**
	 * Adds the given data transformer to the column.
	 * Any data transformers are executed whenever the value of the object / cell the column is mapped to shall be
	 * retrieved in order to be rendered. Data transformers are applied in the order they were added. The order may be
	 * influenced by forcing a data transformer to be appended rather than to be prepended, which is the default behaviour.
	 *
	 * @param DataTransformerInterface $dataTransformer the data transformer to be added
	 * @param bool                     $forceAppend     true, in case the data transformer shall be added to the end of the
	 *                                                  list of transformers (i.e. will be inserted after the already existing ones) or
	 *                                                  false (default), in case the data transformer shall be prepended to the list of
	 *                                                  transformers (i.e. it will be inserted before the already existing ones).
	 * @return ColumnInterface
	 */
	public function addDataTransformer(DataTransformerInterface $dataTransformer, $forceAppend = false);

	/**
	 * Get all attached data transformers for the column.
	 *
	 * @return DataTransformerInterface[] all attached data transformers for the column.
	 */
	public function getDataTransformers();

	/**
	 * Clears the data transformers.
	 *
	 * @return ColumnInterface
	 */
	public function resetDataTransformers();

	/**
	 * Get the column type used for this column instance
	 *
	 * @return ColumnTypeInterface the column type used for this column instance
	 */
	public function getColumnType();

	/**
	 * Get the options defined for the filter type.
	 *
	 * @return array the options defined for the filter type.
	 */
	public function getColumnOptions();

	/**
	 * Set the options defined for the column type.
	 *
	 * @param array $columnOptions the options defined for the column type.
	 * @return ColumnInterface.
	 */
	public function setColumnOptions(array $columnOptions);

	/**
	 * Get the options defined for the filter type.
	 *
	 * @param string $option the name of the option to be set
	 * @return mixed the value for the given option
	 */
	public function getColumnOption($option);

	/**
	 * Set one option defined for the column type to the given value.
	 *
	 * @param string $option the name of the option to be set
	 * @param mixed  $value  the value for the option to be set
	 * @return ColumnInterface.
	 */
	public function setColumnOption($option, $value);

	/**
	 * Get the options defined for the original table this column belongs to
	 *
	 * @return array the options defined for the original table this column belongs to
	 */
	public function getTableOptions();

	/**
	 * Set the options defined for the original table this column belongs to
	 *
	 * @param array $tableOptions the options defined for the original table this column belongs to
	 * @return ColumnInterface
	 */
	public function setTableOptions(array $tableOptions);

	/**
	 * Get the hash code of the column.
	 *
	 * @return int the hash code of the column.
	 */
	public function getHashCode();
}