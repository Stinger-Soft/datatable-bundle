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

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Knp\Component\Pager\PaginatorInterface;
use StingerSoft\DatatableBundle\Column\Column;
use StingerSoft\DatatableBundle\Column\SelectColumnType;
use StingerSoft\DatatableBundle\DependencyInjection\StingerSoftDatatableExtension;
use StingerSoft\DatatableBundle\Exception\InvalidArgumentTypeException;
use StingerSoft\DatatableBundle\Exception\NotYetHandledException;
use StingerSoft\DatatableBundle\Extension\DependencyInjection\DependencyInjectionExtension;
use StingerSoft\DatatableBundle\Filter\FilterTypeInterface;
use StingerSoft\DatatableBundle\Helper\TemplatingTrait;
use StingerSoft\DatatableBundle\Orderer\TableOrderer;
use StingerSoft\DatatableBundle\Service\TableBuilder;
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Templating\EngineInterface;
use Twig\Environment;

/**
 * The table instance type that allows rendering tables with columns, handling requests that influence the table in terms
 * of searching, filtering, ordering, pagination etc.
 */
class Table implements TableInterface {

	use TemplatingTrait;

	/**
	 *
	 * @var TableBuilderInterface the table builder containing all the column types
	 */
	protected $builder;

	/**
	 *
	 * @var TableTypeInterface the type of table to be used
	 */
	protected $tableType;

	/**
	 *
	 * @var \Knp\Component\Pager\Pagination\AbstractPagination the paginator, allowing for pagination of the query builder
	 */
	protected $paginator;

	/**
	 *
	 * @var QueryBuilder the query builder used for retrieving table entries, perform searching, ordering etc.
	 */
	protected $queryBuilder;

	/**
	 *
	 * @var array an array containing all options to be passed to the table type
	 */
	protected $options;

	/**
	 *
	 * @var string the alias for the root entity of the query builder used by the table
	 */
	protected $rootAlias;

	/**
	 *
	 * @var Column[] an array containing all column instances
	 */
	protected $columns;

	/**
	 *
	 * @var TableOrderer the orderer that allows positioning columns
	 */
	protected $orderer;

	/**
	 *
	 * @var bool|int the number of total results in the table, false in case the value has not yet been determined
	 */
	protected $totalResults = false;

	/**
	 *
	 * @var integer draw counter. This is used by DataTables to ensure that the Ajax returns from server-side processing
	 *      requests are drawn in sequence by DataTables (Ajax requests are asynchronous and thus can return out of sequence).
	 */
	protected $requestDrawId;

	/**
	 *
	 * @var array an array defining all columns in the table, containing keys for data, name, searchable,
	 *      orderable and search
	 */
	protected $requestColumns;

	/**
	 *
	 * @var integer Paging first record indicator. This is the start point in the current data set (0 index based - i.e.
	 *      0 is the first record).
	 */
	protected $requestOffset;

	/**
	 *
	 * @var integer Number of records that the table can display in the current draw.
	 */
	protected $requestCount;

	/**
	 *
	 * @var array an array defining how many columns are being ordered upon - i.e. if the array length is 1, then a
	 *      single column sort is being performed, otherwise a multi-column sort is being performed.
	 */
	protected $requestOrder;

	/**
	 *
	 * @var array Global search value and indicator if the global filter should be treated as a regular expression
	 */
	protected $requestSearch;

	/**
	 *
	 * @var bool indicating whether a request has been handled already
	 */
	protected $handledRequest = false;

	/**
	 *
	 * @var bool indicating whether the table has a selection column
	 */
	protected $selectionColumn;

	/** @var  QueryBuilder|array */
	protected $dataSource;

	/** @var DependencyInjectionExtension */
	protected $dependencyInjectionExtension;

	/**
	 * Constructs a new table.
	 *
	 * @param string $tableTypeClass
	 *            FQCN of the table type to be used
	 * @param QueryBuilder|array $dataSource
	 *            data source the table will use for retrieving entries,
	 *            applying filters, searches and ordering (if a query builder is given)
	 * @param ContainerInterface $container
	 *            Symfony Service Registry used for retrieving other relevant services,
	 *            such as templating, router etc.
	 * @param array $options
	 *            an array of options to be passed to the table type
	 */
	public function __construct($tableTypeClass, $dataSource, DependencyInjectionExtension $dependencyInjectionExtension, PaginatorInterface $paginator, ?EngineInterface $templating, ?Environment $twig, array $options = array()) {
		$this->templating = $templating;
		$this->twig = $twig;
		$this->paginator = $paginator;
		$this->dependencyInjectionExtension = $dependencyInjectionExtension;
		$this->dataSource = $dataSource;
		if($dataSource instanceof QueryBuilder) {
			$this->queryBuilder = clone $dataSource;
		}
		$this->options = $options;

		/** @var TableTypeInterface $tableType */
		$tableType = $this->getTableTypeInstance($tableTypeClass);
		$this->options = $this->setupOptionsResolver($tableType, $options);

		$this->tableType = $tableType;
		$this->orderer = new TableOrderer();
		$this->rootAlias = '';
		if($this->queryBuilder) {
			$rootAliases = $this->queryBuilder instanceof QueryBuilder ? $this->queryBuilder->getRootAliases() : array();
			$this->rootAlias = current($rootAliases);
		}
		$this->builder = new TableBuilder($this, $dependencyInjectionExtension, $this->options);
		$this->buildTable($tableType, $this->builder);

		$this->columns = $this->builder->all();
	}

	/**
	 * Creates a new instance of the given table type
	 *
	 * @param string $class
	 *            Class name of the table type to create an instance of
	 * @return TableTypeInterface an instance of the table type
	 * @throws InvalidArgumentTypeException in case the given class does not implement the TableTypeInterface interface
	 */
	protected function getTableTypeInstance($class) {
		return $this->dependencyInjectionExtension->resolveTableType($class);
	}

	/**
	 * Sets up the options resolver for the given table type and the initial options.
	 * Setting up means that the column type options and all applicable parent options will be validated and
	 * resolved according to the hierarchy grandparent, then parent, then instance etc.
	 *
	 * @param TableTypeInterface $tableType
	 *            the type to resolve the options for, also used for determining any parents
	 *            whose options are to be resolved as well
	 * @param array $options
	 *            the initial options to also be resolved (if any).
	 * @return array the resolved options for the given table type.
	 */
	protected function setupOptionsResolver(TableTypeInterface $tableType, array $options) {
		$resolver = new OptionsResolver();
		$this->resolveOptions($tableType, $resolver);
		$options = $resolver->resolve($options);
		if(array_key_exists('search_delay', $options) && $options['search_delay'] === null) {
			$options['search_delay'] = $this->dependencyInjectionExtension->getParameter(StingerSoftDatatableExtension::PARAMETER_SEARCH_DELAY);
		}
		return $options;
	}

	/**
	 * Merges the configurations of each type in the hierarchy starting from the top most type.
	 *
	 * @param TableTypeInterface $tableType
	 *            the table type to resolve the options from
	 * @param OptionsResolver $resolver
	 *            the resolver used for checking option values and defaults etc.
	 */
	private function resolveOptions(TableTypeInterface $tableType, OptionsResolver $resolver) {
		if($tableType->getParent()) {
			$parentType = $this->getTableTypeInstance($tableType->getParent());
			$this->resolveOptions($parentType, $resolver);
		}
		$tableType->configureOptions($resolver);
	}

	/**
	 * Merges the table columns of each type in the hierarchy starting from the top most type.
	 *
	 * @param TableTypeInterface $tableType
	 *            the table type to build the columns from
	 * @param TableBuilder $builder
	 *            the table builder
	 */
	protected function buildTable(TableTypeInterface $tableType, TableBuilder $builder) {
		if($tableType->getParent()) {
			$parentType = $this->getTableTypeInstance($tableType->getParent());
			$this->buildTable($parentType, $builder);
		}
		$tableType->buildTable($this->builder, $this->options);
	}

	/**
	 * Add filter to columns based on path and array values, set as pre_filtered_values
	 * per column.
	 *
	 * @param array $filter
	 *            Expects an array of column.path => values[]
	 * @return Table This table
	 */
	public function addFilter($filter) {
		foreach($this->columns as $column) {
			if($column->isFilterable() && array_key_exists($column->getPath(), $filter)) {
				$filterOptions = $column->getFilter()->getFilterOptions();
				$filterOptions['pre_filtered_value'] = $filter[$column->getPath()];
				$column->getFilter()->setFilterOptions($filterOptions);
			}
		}
		return $this;
	}

	/**
	 * Get the columns of the table.
	 *
	 * @return Column[] the columns of the table.
	 */
	public function getColumns() {
		return $this->columns;
	}

	/**
	 * Get the query builder this table operates on.
	 *
	 * @return QueryBuilder the query builder this table operates on.
	 */
	public function getQueryBuilder() {
		return $this->queryBuilder;
	}

	/**
	 * Handles the request sent by the datatable library.
	 *
	 * <p>The request ideally contains several parameters taken into account:
	 * 1. draw : Draw counter. This is used by DataTables to ensure that the Ajax returns from server-side processing
	 * requests are drawn in sequence by DataTables (Ajax requests are asynchronous and thus can return out of sequence).
	 * This is used as part of the draw return parameter
	 * 2. start : Paging first record indicator. This is the start point in the current data set (0 index based - i.e.
	 * 0 is the first record).
	 * 3. length : Number of records that the table can display in the current draw. It is expected that the number
	 * of records returned will be equal to this number, unless the server has fewer records to return. Note that this
	 * can be -1 to indicate that all records should be returned (although that negates any benefits of server-side
	 * processing!)
	 * 4. columns : an array defining all columns in the table.
	 * 5. order : is an array defining how many columns are being ordered upon - i.e. if the array length is 1, then
	 * a single column sort is being performed, otherwise a multi-column sort is being performed.
	 * 6. search : an array containing a global search value, to be applied to all searchable columns and an indicator
	 * if the global filter should be treated as a regular expression for advanced searching
	 *
	 * @param Request $request
	 *            the HTTP request to be handled, containing the above mentioned parameters
	 * @see https://datatables.net/manual/server-side#Sent-parameters Server side processing for details on the request body
	 */
	public function handleRequest(Request $request) {
		$parameters = $request->isMethod('GET') ? $request->query->all() : $request->request->all();
		$this->requestDrawId = $this->getValueForArrayKeyOrDefault($parameters, 'draw', 0);
		$this->requestColumns = $this->getValueForArrayKeyOrDefault($parameters, 'columns', array());
		$this->requestOffset = $this->getValueForArrayKeyOrDefault($parameters, 'start', 0);
		$this->requestCount = $this->getValueForArrayKeyOrDefault($parameters, 'length', 10);
		$this->requestOrder = $this->getValueForArrayKeyOrDefault($parameters, 'order', array());
		$this->requestSearch = $this->getValueForArrayKeyOrDefault($parameters, 'search', array(
			'value' => '',
			'regex' => false
		));
		$this->handledRequest = true;
	}

	/**
	 * Get the value from an array if the given key exists in the array, otherwise return the given default value.
	 *
	 * @param array $array
	 *            the array to lookup a value for the given key
	 * @param string $key
	 *            the key to be contained in the given array in order to retrieve its value
	 * @param mixed $default
	 *            the default value to be returned in case the given key is not existent in the given array
	 * @return mixed either the value from the array for the given key or the given default value
	 */
	protected function getValueForArrayKeyOrDefault($array, $key, $default) {
		return array_key_exists($key, $array) ? $array[$key] : $default;
	}

	/**
	 * Creates an HTTP response containing the data matching the original datatable request, handled by the 'handleRequest'
	 * method.
	 *
	 * @return \Symfony\Component\HttpFoundation\Response a response whose body is filled with the json data structured
	 *         according to the requirements of the datatable library
	 * @throws \StingerSoft\DatatableBundle\Exception\NotYetHandledException in case no request has been handled yet
	 *
	 * @see \StingerSoft\DatatableBundle\Table\Table::createJsonData() for details on the content of the response
	 * @see \StingerSoft\DatatableBundle\Table\Table::handleRequest() for handling a request before calling this method
	 */
	public function createJsonDataResponse() {
		return new Response($this->createJsonData());
	}

	/**
	 * @inheritdoc
	 */
	public function createJsonData($serverSide = null) {
		/**
		 * @noinspection CallableParameterUseCaseInTypeContextInspection
		 */
		$serverSide = $serverSide === null ? $this->options['serverSide'] === true : $serverSide;
		if($serverSide && !$this->handledRequest) {
			throw new NotYetHandledException('When using server side you must handle the request before creating a response!');
		}
		$totalResults = $this->getTotalResults();
		$parameters = array(
			'data'            => array(),
			'serverSide'      => $serverSide,
			'recordsTotal'    => $totalResults,
			'recordsFiltered' => $totalResults,
			'drawId'          => (int)$this->requestDrawId
		);

		$items = $this->getItems($serverSide);
		if($serverSide) {
			$parameters['recordsFiltered'] = $items->getTotalItemCount();
		}

		$columns = $this->getDataColumns($serverSide);
		foreach($items as $item) {
			if($this->options['data'] instanceof \Traversable || is_array($this->options['data'])) {
				$item = (object)$item;
			}
			$parameters['data'][] = $this->generateItemData($item, $columns);
		}

		return $this->renderView('StingerSoftDatatableBundle:Table:data.json.twig', $parameters);
	}

	/**
	 * Returns the amount of total results of the query before applying any filter by performing a count query using
	 * the root alias of the underlying query builder.
	 *
	 * @return integer the amount of total results of the query before applying any filter.
	 */
	public function getTotalResults() {
		if($this->totalResults === false) {
			if($this->options['total_results_query_builder'] instanceof QueryBuilder) {
				/** @var QueryBuilder $countQb */
				$countQb = $this->options['total_results_query_builder'];
				$this->totalResults = (int)$countQb->getQuery()->getSingleScalarResult();
			} else {
				if($this->queryBuilder) {
					$countQb = clone $this->queryBuilder;
					$countQb->resetDQLPart('orderBy');
					if(!empty($countQb->getDQLPart('groupBy'))) {
						$paginator = new Paginator($countQb);
						$this->totalResults = $paginator->count();
					} else {
						$this->totalResults = (int)$countQb->select('COUNT(' . $this->rootAlias . ')')->getQuery()->getSingleScalarResult();
					}
				}
				if(is_array($this->dataSource)) {
					$this->totalResults = count($this->dataSource);
				}
			}
		}
		return $this->totalResults;
	}

	/**
	 *
	 * @param boolean $serverSide
	 * @return \Knp\Component\Pager\Pagination\AbstractPagination|\Knp\Component\Pager\Pagination\SlidingPagination|mixed[]
	 */
	protected function getItems($serverSide) {
		$paginationOptions = $this->getPaginationOptions();
		if($serverSide) {
			$totalCount = 1;
			if($this->requestCount < 0 || $this->options['paging'] !== true) {
				$totalCount = $this->getTotalResults();
			}

			$this->applyOrderBy($this->requestOrder);
			$this->applySearch($this->requestSearch['value']);
			$this->applyFilter($this->getColumnsWithFilterValueOnly($this->requestColumns));
			if($this->requestCount >= 0 && $this->options['paging'] === true) {
				return $this->paginator->paginate($this->queryBuilder, $this->requestOffset / $this->requestCount + 1, $this->requestCount, $paginationOptions);
			}
			return $this->paginator->paginate($this->queryBuilder, 1, $totalCount, $paginationOptions);
		}

		if($this->options['data'] !== null) {
			if(is_int($this->options['data'])) {
				// we have a scalar number of items to be returned, so we return them
				return $this->paginator->paginate($this->queryBuilder, 1, $this->options['data'], $paginationOptions);
			}

			if($this->options['data'] === true && $this->options['paging'] === true) {
				// we shall follow the table page length
				$count = $this->options['pageLength'] ?: 10;
				return $this->paginator->paginate($this->queryBuilder, 1, $count, $paginationOptions);
			}

			if($this->options['data'] === false) {
				// we shall not follow the table page length, so we return ALL items
				return $this->queryBuilder->getQuery()->getResult();
			}

			if($this->options['data'] instanceof \Traversable || is_array($this->options['data'])) {
				return $this->options['data'];
			}
		}
		return array();
	}

	protected function getPaginationOptions() {
		if(isset($this->options['paginationOptions'])) {
			return $this->options['paginationOptions'];
		}
		return array();
	}

	/**
	 * Adds orderBy statements to the query builder according to the given columns to be ordered.
	 *
	 * <p>The columns to be taken into account are only those, who are marked as being orderable.
	 * For every orderable column, it is checked if it provides a delegate or callback to be used when ordering.
	 *
	 * <p>If so, the delegate will be invoked and the parameters required will be passed in order to modify the query
	 * builder of the table accordingly. As such, the delegate should return an array containing key => value pairs for
	 * every orderBy statement to be added to the query builder, where each key is used as the selector or query path
	 * for the query builder and the value must be the order direction ('asc' or 'desc').
	 *
	 * <p>In case no specific order delegate is defined for an orderable column, the columns query path and the order
	 * direction given in the first parameter will be used.
	 *
	 * <p>Finally, all paths and directions are added to the orderBy part of the underlying query builder consecutively.
	 *
	 * <p>The given parameter for the columns to be ordered must follow a well-defined structure. Every entry in the
	 * given array must have two keys and corresponding values:
	 * 1. key 'column' with an array as its value containing a 'name' key whose value refers to the name or id of the
	 * column to be sorted.
	 * 2. key 'dir' whose value indicates the direction of ordering, 'asc' for ascending, 'desc' for descending ordering
	 *
	 * <p>Example:
	 * <code>
	 * array(
	 * 0 => array(
	 * 'column' => array(
	 * 'name' => 'column1'
	 * ),
	 * 'dir' => 'asc'
	 * ),
	 * 1 => array(
	 * 'column' => array(
	 * 'name' => 'column2'
	 * ),
	 * 'dir' => 'desc'
	 * ),
	 * ...
	 * )
	 * </code>
	 *
	 * @param array $orderByEntries
	 *            an array containing the columns to be ordered, with details on how the ordering shall
	 *            be applied.
	 *
	 * @see Table::getOrderableColumnIds() for retrieval of all columns which are actually orderable
	 * @see Column::isOrderable() for determining whether a column is orderable
	 * @see Column::getServerSideOrderDelegate() for determining whether a column has defined a specialised function to be used
	 *      for ordering
	 */
	protected function applyOrderBy($orderByEntries) {
		$orderByEntries = array_filter($orderByEntries, function($entry) {
			// we only want to have entry containing a column AND a direction
			return isset($entry['column']) && isset($entry['dir']) && $entry['dir'] !== '';
		});
		if(count($orderByEntries) > 0) {
			$orderQuery = array();
			$orderableColumnIds = $this->getOrderableColumnIds();
			foreach($orderByEntries as $orderBy) {
				$columnIndex = $orderBy['column'];
				$direction = $orderBy['dir'];
				$columnId = isset($this->requestColumns[$columnIndex]) ? $this->requestColumns[$columnIndex]['name'] : null;
				if($columnId === null) {
					continue;
				}
				$queryPath = $this->getColumn($columnId)->getQueryPath();
				if(false === strpos($queryPath, '.')) {
					$queryPath = $this->rootAlias . '.' . $queryPath;
				}
				if(in_array($columnId, $orderableColumnIds, false)) {
					$column = $this->getColumn($columnId);
					$delegate = $column->getServerSideOrderDelegate();
					if($delegate && is_callable($delegate)) {
						/** @var array $delegatedOrderByEntries */
						$delegatedOrderByEntries = $delegate($direction, $this->queryBuilder, $column, $queryPath, $this->rootAlias);
						if($delegatedOrderByEntries && count($delegatedOrderByEntries) > 0) {
							foreach($delegatedOrderByEntries as $path => $direction) {
								$orderQuery[$path] = $direction;
							}
						}
					} else {
						$orderQuery[$queryPath] = $direction;
					}
				}
			}
			if(count($orderQuery) > 0) {
				foreach($orderQuery as $path => $direction) {
					$this->queryBuilder->addOrderBy($path, $direction);
				}
			}
		} else {
			// default order !!
			$path = $this->options['default_order_property'];
			if($path !== null) {
				$direction = $this->options['default_order_direction'];
				if(false === strpos($path, '.')) {
					$path = $this->rootAlias . '.' . $path;
				}
				$this->queryBuilder->addOrderBy($path, $direction);
			}
		}
	}

	/**
	 * Get the names / ids of columns which are orderable.
	 *
	 * A column is orderable, if column->isOrderable() returns true
	 *
	 * @return string[] the names / ids of columns which are orderable.
	 */
	protected function getOrderableColumnIds() {
		$result = array();
		foreach($this->requestColumns as $columnId => $columnDetails) {
			$column = $this->getColumn($columnDetails['name']);
			if($column->isOrderable()) {
				$result[] = $columnDetails['name'];
			}
		}
		return $result;
	}

	/**
	 * Gets the column instance for the given columns name / id.
	 *
	 * @param string $columnId
	 *            The name / id of the column.
	 * @return Column the column instance for the given columns name / id.
	 */
	protected function getColumn($columnId) {
		return $this->columns[$columnId];
	}

	/**
	 * Applies a table-wide search filter to the underlying query builder.
	 *
	 * The columns to be taken into account are only those, who are marked as being searchable.
	 * For every searchable column, it is checked if it provides a delegate or callback to be used when searching.
	 *
	 * If so, the delegate will be invoked and the parameters required will be passed in order to modify the query
	 * builder of the table accordingly. As such, the delegate should create any additionally required comparision
	 * expressions for the column to be filtered and return those expression in order for them to be added to the final
	 * query.
	 *
	 * In case no specific search delegate is defined for a searchable column, a simple like query with the search
	 * terms wrapped in % wildcards (at the beginning and at the end, i.e. LIKE %searchTerm%) is added for that
	 * particular column.
	 *
	 * Finally, all comparision expressions, regardless of their type (like, eq or whatever) are added to the WHERE
	 * part of the underlying query builder by combining them with the OR disjunction.
	 *
	 * @param string $search
	 *            the term(s) to search for.
	 *
	 * @see Column::isSearchable() for determining whether a column is searchable
	 * @see Column::getServerSideSearchDelegate() for determining whether a column has a special implementation to be used when
	 *      applying a table-wide search filter
	 */
	protected function applySearch($search) {
		if($search) {
			$searchQuery = array();
			$searchableColumns = $this->getSearchableColumnIds();
			$bindingCounter = 0;
			foreach($searchableColumns as $columnId) {
				$column = $this->getColumn($columnId);
				$searchParameterBinding = ':search_' . $bindingCounter;
				$queryPath = $column->getQueryPath();
				if(false === strpos($queryPath, '.')) {
					$queryPath = $this->rootAlias . '.' . $queryPath;
				}
				$delegate = $column->getServerSideSearchDelegate();
				if($delegate && is_callable($delegate)) {
					$searchExpression = $delegate($this->queryBuilder, $searchParameterBinding, $search, $column, $queryPath);
					if($searchExpression !== null) {
						if(is_array($searchExpression)) {
							foreach($searchExpression as $expression) {
								$searchQuery[] = $expression;
							}
						} else {
							$searchQuery[] = $searchExpression;
						}
						$bindingCounter++;
					}
				} else {
					$searchQuery[] = $this->queryBuilder->expr()->like($queryPath, $searchParameterBinding);
					$this->queryBuilder->setParameter($searchParameterBinding, '%' . $search . '%');
					$bindingCounter++;
				}
			}
			if(count($searchQuery) > 0) {
				$this->queryBuilder->andWhere($this->queryBuilder->expr()->orX()->addMultiple($searchQuery));
			}
		}
	}

	/**
	 * Get the names / ids of columns which are searchable.
	 *
	 * A column is searchable, if column->isSearchable() returns true.
	 *
	 * @return string[] the names / ids of columns which are searchable.
	 */
	protected function getSearchableColumnIds() {
		$result = array();
		foreach($this->requestColumns as $columnId => $columnDetails) {
			$column = $this->getColumn($columnDetails['name']);
			if($column->isSearchable()) {
				$result[] = $columnDetails['name'];
			}
		}
		return $result;
	}

	/**
	 * Applies a column specific search on the tables query builder, filtering out all non-matching elements.
	 *
	 * <p>The columns to be taken into account are only those, who are marked as being filterable and those who actually
	 * provide a filter type and filter options as well as filter instance.
	 * For every filterable column, it is checked if it provides a delegate or callback to be used when filtering.
	 *
	 * <p>If so, the delegate will be invoked and the parameters required will be passed in order to modify the query
	 * builder of the table accordingly. As such, the delegate should create any additionally required comparision
	 * expressions for the column to be filtered and return those expression in order for them to be added to the final
	 * query.
	 *
	 * <p>In case no specific filter delegate is defined for a filterable column, the filter type defined for the column
	 * is used for applying the filter. The actual implementation of the filtering is then specific to the filter type
	 * and may result in addition of LIKE or equality expressions.
	 *
	 * <p>Finally, all comparision expressions, regardless of their type (like, eq or whatever) are added to the WHERE
	 * part of the underlying query builder by combining them with the AND disjunction.
	 *
	 * @param array $columns
	 *            an array containing the columns that are to be filtered, where every entry in the array is
	 *            identified by the columns name / id (array key) and the value is supposed to be another array
	 *            with two keys: 'value' containing the value to be searched for and 'regex' containing a
	 *            boolean value, indicating whether the filter is a regular expression (in case of true value)
	 *            or not (in case of false value).
	 *
	 * @see Table::getColumnsWithFilterValueOnly() to get any filterable columns in the correct structure to be passed as
	 *      the first argument
	 * @see Column::isFilterable() for determining whether a column is filterable
	 * @see Column::getFilter() for determining whether a column has a filter defined
	 * @see Column::getServerSideFilterDelegate() for determining whether a column has defined a specialised function to be used
	 *      for filtering
	 * @see FilterTypeInterface::applyFilter() and the overwritten implemenations, specific to the filter type are used
	 *      for applying a filter in case no filter delegate is defined for the column to be filtered.
	 */
	protected function applyFilter($columns) {
		if(is_array($columns) && count($columns) > 0) {
			$filterQuery = array();
			$filterableColumnIds = $this->getFilterableColumnIds();
			$bindingCounter = 0;
			foreach($columns as $columnId => $filterValue) {
				if(in_array($columnId, $filterableColumnIds, true)) {
					$filterParameterBinding = ':filter_' . $bindingCounter;
					$column = $this->getColumn($columnId);
					$queryPath = $column->getFilterQueryPath();
					if(false === strpos($queryPath, '.')) {
						$queryPath = $this->rootAlias . '.' . $queryPath;
					}
					$returnValue = null;
					$filterObject = $column->getFilter();
					if($filterObject !== null) {
						$returnValue = $filterObject->applyFilter($this->queryBuilder, $filterValue['value'], $filterValue['regex'], $filterParameterBinding, $queryPath, $filterObject->getFilterOptions(), $this->rootAlias);
					}
					if($returnValue !== null) {
						$filterQuery[] = $returnValue;
						$bindingCounter++;
					}
				}
			}
			if(count($filterQuery) > 0) {
				$this->queryBuilder->andWhere($this->queryBuilder->expr()->andX()->addMultiple($filterQuery));
			}
		}
	}

	/**
	 * Get the names / ids of columns which are filterable.
	 *
	 * A column is filterable, if column->isFilterable() returns true and column->getFilter() is not null.
	 *
	 * @return string[] the names / ids of columns which are filterable.
	 */
	protected function getFilterableColumnIds() {
		$result = array();
		foreach($this->requestColumns as $columnId => $columnDetails) {
			$column = $this->getColumn($columnDetails['name']);
			if($column->isFilterable() && $column->getFilter() !== null) {
				$result[] = $columnDetails['name'];
			}
		}
		return $result;
	}

	/**
	 * Get an array containing all columns to actually be filtered.
	 *
	 * The given array usually contains all columns defined on the datatable and for every entry in the array, a column
	 * specific sub-array is provided, providing a potential filter value or an empty string as well as an indicator
	 * whether the filter is to be treated as a regular expression. Additionally, special column filters, such as range
	 * filters may provide an 'encrypted' filter value, using special delimiters for supporting multiple values inside
	 * a string value.
	 *
	 * For every of this columns, it is checked whether the filter value is non-empty and whether the filter value
	 * needs to be normalized before it can be used.
	 *
	 * The structure of the resulting array is as follows: for every column that is filterable (i.e. contains a non-empty
	 * filter value), a new sub-array is added to the returned array. The sub-entry is added under a key with the columns name,
	 * where the sub-array itself contains two keys: 'value' containing the filter term(s) (may be an array in case
	 * a range was filtered) and 'regex' containing a boolean value whether the filter is using a regular expression or not.
	 *
	 * Example:
	 * <code>
	 * 'myColumn1' => array(
	 * 'value' => 'myTerm',
	 * 'regex' => false
	 * ),
	 * 'myColumn2' => array(
	 * 'value' => array(
	 * 12,
	 * 182
	 * ),
	 * 'regex' => false
	 * ), ...
	 * </code>
	 *
	 * @param array $columns
	 *            an array of columns to be checked if they need to be filtered.
	 * @return array an array of columns that should be filtered, because they have a non-empty filter value
	 */
	protected function getColumnsWithFilterValueOnly($columns) {
		$filterableColumns = array();
		$rangeDelimiter = '-yadcf_delim-';
		if(is_array($columns) && count($columns) > 0) {
			foreach($columns as $column) {
				$searchValue = isset($column['search']) && isset($column['search']['value']) ? $column['search']['value'] : null;
				$searchRegex = isset($column['search']) && isset($column['search']['regex']) && filter_var($column['search']['regex'], FILTER_VALIDATE_BOOLEAN);
				if($searchValue !== null) {
					if(is_array($searchValue)) {
						foreach($searchValue as $value) {
							$filterableColumns = $this->getFilterableColumns($value, $searchRegex, $rangeDelimiter, $column['name'], $filterableColumns);
						}
					} else {
						$filterableColumns = $this->getFilterableColumns($searchValue, $searchRegex, $rangeDelimiter, $column['name'], $filterableColumns);
					}
				}
			}
		}
		return $filterableColumns;
	}

	/**
	 *
	 * @param string $searchValue
	 * @param string $searchRegex
	 * @param string $rangeDelimiter
	 * @param string $columnName
	 * @param array $filterableColumns Defaults to an empty array
	 * @return array[]
	 */
	protected function getFilterableColumns($searchValue, $searchRegex, $rangeDelimiter, $columnName, $filterableColumns = array()) {
		if(strlen(trim($searchValue)) > 0) {
			if(stripos($searchValue, $rangeDelimiter) !== false) {
				// take care of range filters!
				$searchValue = explode($rangeDelimiter, $searchValue);
			}
			$filterableColumns[$columnName] = array(
				'value' => $searchValue,
				'regex' => $searchRegex
			);
		}
		return $filterableColumns;
	}

	protected function getDataColumns($serverSide) {
		$columns = $this->columns;
		if($serverSide) {
			foreach(array_keys($this->requestColumns) as $columnIndex) {
				$columnId = $this->requestColumns[$columnIndex]['name'];
				$columns[$columnId] = $this->getColumn($columnId);
			}
		}
		return $columns;
	}

	/**
	 *
	 * @param
	 *            $item
	 * @param Column[] $columns
	 * @return array
	 */
	protected function generateItemData($item, array $columns) {
		$itemArray = array();
		foreach($columns as $column) {
			$this->setNestedArrayValue($itemArray, $column->getPath(), $column->createData($item, $this->rootAlias));
		}
		if($this->options['rowId']) {
			$itemArray['DT_RowId'] = $this->invokeCallableOrDefault($this->options['rowId'], $item);
		}
		if($this->options['rowClass']) {
			$itemArray['DT_RowClass'] = $this->invokeCallableOrDefault($this->options['rowClass'], $item, ' ');
		}
		if($this->options['rowData']) {
			$itemArray['DT_RowData'] = $this->invokeCallableOrDefault($this->options['rowData'], $item);
		}
		if($this->options['rowAttr']) {
			$itemArray['DT_RowAttr'] = $this->invokeCallableOrDefault($this->options['rowAttr'], $item);
		}
		if($this->options['rows_selectable'] === true) {
			$selectableIdValue = $this->invokeCallableOrDefault($this->options['row_selection_id'], $item);
			if(!isset($itemArray['DT_RowAttr'])) {
				$itemArray['DT_RowAttr'] = array();
			}
			$itemArray['DT_RowAttr']['data-selectable-id'] = $selectableIdValue;
		}

		return $itemArray;
	}

	/**
	 * Sets a value in a nested array based on path
	 * See http://stackoverflow.com/a/9628276/419887
	 *
	 * @param array $array
	 *            The array to modify
	 * @param string $path
	 *            The path in the array
	 * @param mixed $value
	 *            The value to set
	 * @param string $delimiter
	 *            The separator for the path
	 * @return mixed The previous value
	 */
	protected function setNestedArrayValue(array &$array, $path, $value, $delimiter = '.') {
		$pathParts = explode($delimiter, $path);

		$current = &$array;
		foreach($pathParts as $key) {
			$current = &$current[$key];
		}

		/**
		 * @noinspection ReferenceMismatchInspection
		 */
		$backup = $current;
		$current = $value;

		return $backup;
	}

	/**
	 * Invokes the given callable if it is callable, otherwise treats the given callable as either a scalar or array
	 * value and returns it.
	 *
	 * <p>In case the given callable parameter is a delegate, it is invoked with the the object of the row ($item) and
	 * the table options as its two only parameters and the result of the delegate is returned.
	 *
	 * <p>In case the given callable is no delegate, but an array and the given $arrayGlue parameter is not null, the array
	 * is joined using the given parameter as the glue and the resulting string is returned.
	 *
	 * @param mixed $callable
	 *            the potential delegate to be called
	 * @param mixed $item
	 *            the object to be passed to the delegate
	 * @param string|null $arrayGlue
	 *            a string to be used as a glue in case the given $callable parameter is an array or
	 *            null in case no array to string conversion shall happen and instead an array shall
	 *            be returned
	 * @return mixed the result of the delegate (if it was a delegate), the initial value of the given $callable
	 *            parameter or a string if the given $callable parameter was an array and a value for $arrayGlue was provided
	 */
	protected function invokeCallableOrDefault($callable, $item, $arrayGlue = null) {
		if(is_callable($callable)) {
			return $callable($item, $this->options);
		}

		if(is_array($callable) && $arrayGlue !== null) {
			return implode($arrayGlue, $callable);
		}
		return $callable;
	}

	/**
	 * Creates a table view object for the table type and its options.
	 *
	 * @return \StingerSoft\DatatableBundle\Table\TableView
	 */
	public function createView() {
		$this->orderColumns();
		$tableView = new TableView($this, $this->tableType, $this->options, $this->columns);
		$this->buildView($tableView, $this->tableType);
		return $tableView;
	}

	protected function orderColumns() {
		// order columns according to position!
		$tmpColumns = $this->columns;
		$newColumnKeys = $this->orderer->order($this);
		$this->columns = array();

		foreach($newColumnKeys as $name) {
			if(!isset($tmpColumns[$name])) {
				continue;
			}

			$this->columns[$name] = $tmpColumns[$name];
			unset($tmpColumns[$name]);
		}

		foreach($tmpColumns as $name => $child) {
			$this->columns[$name] = $child;
		}
	}

	protected function buildView(TableView $view, TableTypeInterface $tableType) {
		if($tableType->getParent()) {
			$parentType = $this->getTableTypeInstance($tableType->getParent());
			$this->buildView($view, $parentType);
		}
		$tableType->buildView($view, $this, $this->options, $this->columns);
	}

	/**
	 * Get whether this selection column
	 *
	 * @return bool
	 */
	protected function hasSelectionColumn() {
		if($this->selectionColumn === null) {
			foreach($this->columns as $column) {
				if($column instanceof SelectColumnType) {
					$this->selectionColumn = true;
					break;
				}
			}
			$this->selectionColumn = false;
		}
		return $this->selectionColumn;
	}
}
