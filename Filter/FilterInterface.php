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

namespace StingerSoft\DatatableBundle\Filter;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

/**
 * The interface all filters must implement, providing methods to access column and filter options and types,
 * to create views when rendering a filter and methods that actually manipulate a query builder in order to filter for
 * selected / entered values.
 */
interface FilterInterface {

	/**
	 * Creates a new view to be used for rendering column filters.
	 *
	 * @param FilterView|null $parent an already pre-populated parent view to extend, if any
	 * @return FilterView the new filter view.
	 */
	public function createView(FilterView $parent = null);

	/**
	 * Get the filter type used for this filter instance
	 *
	 * @return FilterTypeInterface the filter type used for this filter instance
	 */
	public function getFilterType();

	/**
	 * Get the options defined for the filter type.
	 *
	 * @return array the options defined for the filter type.
	 */
	public function getFilterOptions();

	/**
	 * Set the options defined for the filter type.
	 *
	 * @param array $filterOptions the options defined for the filter type.
	 * @return FilterInterface .
	 */
	public function setFilterOptions(array $filterOptions);

	/**
	 * Get the callable to update the query builder of the column the filter is bound to in order to add any required
	 * where clauses explicitly for that column to be used for filtering that column.
	 *
	 * By default the query_path or path property will be used to perform a like query for a column specific filter term.
	 *
	 * @return callable|null the callable to update the query builder of the column the filter is bound to in order to add any required
	 * where clauses explicitly for that column to be used for filtering that column.
	 */
	public function getFilterDelegate();

	/**
	 * Get the options defined for the original column this filter belongs to
	 *
	 * @return array the options defined for the original column this filter belongs to
	 */
	public function getColumnOptions();

	/**
	 * Set the options defined for the original column this filter belongs to
	 *
	 * @param array $columnOptions the options defined for the original column this filter belongs to
	 * @return FilterInterface
	 */
	public function setColumnOptions(array $columnOptions);
	
	/**
	 * Get the option by key defined for the original column this filter belongs to
	 *
	 * @param string $key The array key to fetch
	 * @return array the options defined for the original column this filter belongs to
	 */
	public function getColumnOption($key);
	
	/**
	 * Set the option by key defined for the original column this filter belongs to
	 *
	 * @param string $key The array key to set
	 * @param mixed $value the options defined for the original column this filter belongs to
	 * @return FilterInterface
	 */
	public function setColumnOption($key, $value);

	/**
	 * Apply any filtering on the given QueryBuilder using the given value to filter by.
	 *
	 * @param QueryBuilder    $queryBuilder         the query builder to create filter expressions for.
	 * @param string|string[] $filterValue          the value to be used for filtering, in case a filter has to handle a
	 *                                              range, an array with start / end or min / max values is provided.
	 * @param boolean         $filterRegex          <code>true</code> in case the filter is considered as a regular
	 *                                              expression,
	 *                                              <code>false</code> otherwise.
	 * @param string          $parameterBindingName the initial name of the parameter to be used for binding the filter
	 *                                              value to any query builder expression, the binding name is suffixed
	 *                                              with a counter value. The value can and should be used to bind
	 *                                              parameters on the query builder, like this:
	 *                                              <code>$queryBuilder->setParameter($parameterBindingName, $filterValue)</code>
	 * @param string          $queryPath            the path determining the field to filter on. If you for instance performed
	 *                                              a <code>$queryBuilder->leftJoin('user.address', 'address')</code> and
	 *                                              the column to be filtered shall display the addresses city, the query path
	 *                                              would be something like <code>address.city</code>. Use <code>$rootAlias</code>
	 *                                              in order to be able to query on <code>user.address.city</code> (if required).
	 * @param array           $filterTypeOptions    an array containing all resolved and configured options of the filter type.
	 *                                              These options may contain additional information useful for filtering, such as
	 *                                              performing case insensitive filtering, matching information (exact matches only,
	 *                                              substring matches, etc.)
	 * @return Expr|null an expression (and set parameters!) to be added to the filter query or <code>null</code> in
	 *                                              case no filtering will be applied for the given values. If this method
	 *                                              returns any expression, its parameters MUST be bound in here!.
	 *                                              Any expression returned will be added to an <code>andWhere</code> clause
	 *                                              to the underlying query builder.
	 */
	public function applyFilter(QueryBuilder $queryBuilder, $filterValue, $filterRegex, $parameterBindingName, $queryPath, $filterTypeOptions, $rootAlias);

}