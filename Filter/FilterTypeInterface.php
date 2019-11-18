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
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The FilterTypeInterface is the interface to be implemented by all custom filter types, providing
 * methods that allow configuration of options, building and definition of a view for a filter used for rendering
 * the filter, for applying a filter and manipulating a query builder etc.
 */
interface FilterTypeInterface {

	/**
	 * @var string String constant for filters that are manually highlighted by the filter type
	 */
	const HIGHLIGHT_MODE_MANUAL = 'manual';

	/**
	 * @var string String constant for filters that are automatically highlighted by the table
	 */
	const HIGHLIGHT_MODE_AUTO = 'auto';

	/**
	 * @var string String constant for filters which allow to filter by using text input.
	 */
	const FILTER_TYPE_TEXT = 'text';
	/**
	 * @var string String constant for filters which allow to filter by using select input and allow only one value to be selected.
	 */
	const FILTER_TYPE_SELECT = 'select';
	/**
	 * @var string String constant for filters which allow to filter by using select input and only multiple values to be selected.
	 */
	const FILTER_TYPE_MULTI_SELECT = 'multi_select';
	/**
	 * @var string String constant for filters which allow to filter by using text input and provide auto completion / suggestion
	 * of items.
	 */
	const FILTER_TYPE_AUTO_COMPLETE = 'auto_complete';
	/**
	 * @var string String constant for filters which allow to filter by using date values.
	 */
	const FILTER_TYPE_DATE = 'date';
	/**
	 * @var string String constant for filters which allow to filter by using a numeric range (min & max values).
	 */
	const FILTER_TYPE_RANGE_NUMBER = 'range_number';
	/**
	 * @var string String constant for filters which allow to filter by using a numeric range (min & max values) with a draggable slider.
	 */
	const FILTER_TYPE_RANGE_NUMBER_SLIDER = 'range_number_slider';
	/**
	 * @var string String constant for filters which allow to filter by using date ranges (start & end values).
	 */
	const FILTER_TYPE_RANGE_DATE = 'range_date';
	/**
	 * @var string String constant for filters which allow to filter by using select input and allow only one value to be selected,
	 * applying actual filtering by a custom javascript function.
	 */
	const FILTER_TYPE_CUSTOM_FUNCTION = 'custom_func';
	/**
	 * @var string String constant for filters which allow to filter by using select input and allow multiple values to be selected,
	 * applying actual filtering by a custom javascript function.
	 */
	const FILTER_TYPE_MULTI_SELECT_CUSTOM_FUNCTION = 'multi_select_custom_func';

	/**
	 * @var string String constant for specifying a columns content as being of type text.
	 */
	const COLUMN_DATA_TYPE_TEXT = 'text';
	/**
	 * @var string String constant for specifying a columns content as being of type html.
	 */
	const COLUMN_DATA_TYPE_HTML = 'html';
	/**
	 * @var string String constant for specifying a columns content as being of type rendered html (i.e. html returned
	 * from a render function).
	 */
	const COLUMN_DATA_TYPE_RENDERED_HTML = 'rendered_html';

	/**
	 * @var string String constant for specifying that column data of type HTML shall be parsed as text (i.e. omitting
	 * tags, just parsing the text by using jQuerys .text() method).
	 */
	const HTML_DATA_TYPE_TEXT = 'text';
	/**
	 * @var string String constant for specifying that column data of type HTML shall be parsed as form input (parsing
	 * the content by using jQuerys .val() method).
	 */
	const HTML_DATA_TYPE_VALUE = 'value';
	/**
	 * @var string String constant for specifying that column data of type HTML shall use the id of the content only
	 * (parsing the content by using .id property).
	 */
	const HTML_DATA_TYPE_ID = 'id';
	/**
	 * @var string String constant for specifying that column data of type HTML shall be parsed using a custom selector
	 * and then the text (parsing the content by using jQuerys .find(selector).text() method).
	 */
	const HTML_DATA_TYPE_SELECTOR = 'selector';

	/**
	 * @var string String constant for name of the html5 data attribute which may contain data used for ordering a column
	 */
	const HTML5_DATA_ORDER = 'data-order';
	/**
	 * @var string String constant for name of the html5 data attribute which may contain data used for ordering a column
	 */
	const HTML5_DATA_SORT = 'data-sort';
	/**
	 * @var string String constant for name of the html5 data attribute which may contain data used for filter a column
	 */
	const HTML5_DATA_SEARCH = 'data-search';
	/**
	 * @var string String constant for name of the html5 data attribute which may contain data used for filter a column
	 */
	const HTML5_DATA_FILTER = 'data-filter';

	/**
	 * @var string String constant for specifying that a column shall be sorted plain alphabetically
	 */
	const SORT_AS_ALPHA = 'alpha';
	/**
	 * @var string String constant for specifying that a column shall be sorted numerically
	 */
	const SORT_AS_NUM = 'num';
	/**
	 * @var string String constant for specifying that a column shall be sorted alpha-numerically
	 */
	const SORT_AS_ALPHA_NUM = 'alphaNum';
	/**
	 * @var string String constant for specifying that a column shall be sorted not at all
	 */
	const SORT_AS_NONE = 'none';
	/**
	 * @var string String constant for specifying that a column shall be sorted according to a custom javascript function
	 */
	const SORT_AS_CUSTOM = 'custom';

	/**
	 * @var string String constant for specifying an ascending sort order
	 */
	const SORT_ORDER_ASC = 'asc';
	/**
	 * @var string String constant for specifying a descending sort order
	 */
	const SORT_ORDER_DESC = 'desc';

	/**
	 * @var string String constant for specifying that filtering should be applied using a contains or in-string mechanism
	 */
	const FILTER_MATCH_MODE_CONTAINS = 'contains';
	/**
	 * @var string String constant for specifying that filtering should be applied using an equals comparison
	 */
	const FILTER_MATCH_MODE_EXACT = 'exact';
	/**
	 * @var string String constant for specifying that filtering should be applied using a starts-with mechanism
	 */
	const FILTER_MATCH_MODE_STARTS_WITH = 'startsWith';
	/**
	 * @var string String constant for specifying that filtering should be applied using a regular expression
	 */
	const FILTER_MATCH_MODE_REGEX = 'regex';

	/**
	 * Builds the filter options using the given options resolver.
	 *
	 * This method is called for each type in the hierarchy starting from the
	 * top most type. It allows to define additional options for this type of filter.
	 *
	 * @param OptionsResolver $resolver      the options resolver used for checking validity of the filter options,
	 *                                       defining default values etc.
	 * @param array           $columnOptions the configured and resolved options of the column type the filter belongs to.
	 * @param array           $tableOptions  the configured and resolved options of the table type the filter belongs to.
	 */
	public function configureOptions(OptionsResolver $resolver, array $columnOptions = array(), array $tableOptions = array());

	/**
	 * Builds the filter view used for rendering of the filter.
	 *
	 * This method is called for each type in the hierarchy starting from the
	 * top most type. It allows adding more variables to the given view which may be used during rendering of the filter.
	 *
	 * @param FilterView         $view       the filter view to add any additional information to
	 * @param FilterInterface    $filter     the filter instance the view belongs to
	 * @param array              $options    the options of the column, previously configured by the #configureOptions method
	 * @param QueryBuilder|array $dataSource the data source of the underlying table and column
	 * @param string             $queryPath  the query path under which the column is accessible in the query builder,
	 *                                       allowing for actual filtering by adding comparison expression on the query path
	 * @param string             $rootAlias  the root alias of the type contained in the table whose column is to be filtered
	 * @return void
	 */
	public function buildView(FilterView $view, FilterInterface $filter, array $options, $dataSource, $queryPath, $rootAlias);

	/**
	 * Returns the name of the parent type.
	 *
	 * @return string|null The name of the parent type if any, null otherwise
	 */
	public function getParent();

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
	 * @return Expr|Expr\Comparison|null an expression (and set parameters!) to be added to the filter query or <code>null</code> in
	 *                                              case no filtering will be applied for the given values. If this method
	 *                                              returns any expression, its parameters MUST be bound in here!.
	 *                                              Any expression returned will be added to an <code>andWhere</code> clause
	 *                                              to the underlying query builder.
	 */
	public function applyFilter(QueryBuilder $queryBuilder, $filterValue, $filterRegex, $parameterBindingName, $queryPath, $filterTypeOptions, $rootAlias);

}