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
use StingerSoft\DatatableBundle\Exception\InvalidArgumentTypeException;
use StingerSoft\DatatableBundle\Extension\DependencyInjection\DependencyInjectionExtension;
use StingerSoft\DatatableBundle\Filter\Filter;
use StingerSoft\DatatableBundle\Filter\FilterTypeInterface;
use StingerSoft\DatatableBundle\Transformer\DataTransformerInterface;
use StingerSoft\PhpCommons\Builder\HashCodeBuilder;
use StingerSoft\PhpCommons\String\Utils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The column encapsulates all information required for handling column types, creating views for columns and processing
 * items in order to retrieve the value to be used for cells bound to the column.
 */
class Column implements ColumnInterface {

	use ColumnTrait;

	/**
	 * @var string The path / property / key under which the column is registered. This will be used for accessing an
	 * objects value with a property accessor.
	 */
	protected $path;

	/**
	 * @var string the path to be used by a query builder for sorting and ordering etc. This may differ from the
	 * $this->path especially for joined paths
	 */
	protected $queryPath;

	/**
	 * @var string the path to be used for querying the potential filter values for select or autocomplete filters
	 */
	protected $filterQueryPath;

	/**
	 * @var ColumnTypeInterface the column type used for this column instance
	 */
	protected $columnType;

	/**
	 * @var array the options defined for the column type.
	 */
	protected $columnOptions;

	/**
	 * @var array the options defined for the original table this column belongs to
	 */
	protected $tableOptions;

	/**
	 * @var ColumnInterface the parent column (if any)
	 */
	protected $parent;

	/**
	 * @var boolean whether the column is orderable
	 */
	protected $orderable = false;

	/**
	 * @var boolean whether the column is filterable
	 */
	protected $filterable = false;

	/**
	 * @var boolean whether the column is searchable globally
	 */
	protected $searchable = false;

	/**
	 * @var DataTransformerInterface[]
	 */
	protected $dataTransformers = array();

	/**
	 * @var Filter the filter object, resolved from filter type option and filter options option.
	 * The object is only created for the view and as such not available before the view was created.
	 */
	protected $filter;

	/**
	 * @var callable Callable to fetch the value of the bound object.
	 *      By default a property accessor will be used to fetch the value based on the configured path
	 */
	protected $valueDelegate;

	/**
	 * @var callable Callable to update the query builder of the bound object in order to add any required
	 * where clauses explicitly required for a global search.
	 * By default the query_path or path property will be used to perform a like query for a global search term.
	 */
	protected $serverSideSearchDelegate;

	/**
	 * @var callable Callable to fetch the value of the bound object to be used when performing a client side searching
	 * or filtering.
	 */
	protected $clientSideSearchDelegate;

	/**
	 * @var callable Callable to update the query builder of the bound object in order to add any required
	 * where clauses explicitly for that column to be used for filtering that column.
	 * By default the query_path or path property will be used to perform a like query for a column specific filter term.
	 */
	protected $serverSideFilterDelegate;

	/**
	 * @var callable Callable to return an array of queryPath => direction mappings, allowing to order by joined fields.
	 * By default the query_path or path will be used to perform order by.
	 */
	protected $serverSideOrderDelegate;

	/**
	 * @var callable Callable to fetch the value of the bound object to be used when performing a client side ordering.
	 */
	protected $clientSideOrderDelegate;

	/**
	 * @var bool flag indicating whether the buildData method was executed already, as data transformers may not be added
	 * multiple times.
	 */
	protected $dataConfigured = false;

	/**
	 * @var QueryBuilder the query builder of the table the column will be attached to. Will be used to create Filter
	 * instances as filters influence the query
	 */
	protected $queryBuilder;

	/**
	 * @var OptionsResolver
	 */
	protected $resolver;

	/** @var QueryBuilder|array */
	protected $dataSource;

	/**
	 * @var DependencyInjectionExtension
	 */
	protected $dependencyInjectionExtension;

	/**
	 * Column constructor.
	 *
	 * @param string                  $path              The path / property / key under which the column is registered. This will be used for accessing an
	 *                                                   objects value with a property accessor.
	 * @param ColumnTypeInterface     $columnType        the column type for this column
	 * @param array                   $columnTypeOptions the options for the column type
	 * @param array                   $tableOptions      the options of the table the column belongs to
	 * @param QueryBuilder|array      $dataSource        the data source of the table the column will be attached to. In case it is a query builder, it will be cloned in order to
	 *                                                   allow Filter instances to modify it if necessary.
	 * @param ContainerInterface|null $container         the container interface or null. May be used to retrieve other services, such as authorization checker or router etc.
	 * @param ColumnInterface|null    $parent            the parent column (if any) or null.
	 * @throws InvalidOptionsException in case the column type options contain invalid entries
	 */
	public function __construct($path, ColumnTypeInterface $columnType, DependencyInjectionExtension $dependencyInjectionExtension, array $columnTypeOptions = array(), array $tableOptions = array(), $dataSource, ColumnInterface $parent = null) {
		$this->columnType = $columnType;
		$this->tableOptions = $tableOptions;
		$this->dependencyInjectionExtension = $dependencyInjectionExtension;
		$this->path = $path;
		$this->resolver = new OptionsResolver();
		$this->columnOptions = $this->setupFilterOptionsResolver($columnType, $columnTypeOptions);
		$this->parent = $parent;
		$this->dataSource = $dataSource;
		if($dataSource instanceof QueryBuilder) {
			$this->queryBuilder = clone $dataSource;
		}

		if(!isset($this->columnOptions['path'])) {
			$this->columnOptions['path'] = $this->path;
		}

		$this->configureColumn();
	}

	/**
	 * @inheritdoc
	 */
	public function getDataSource() {
		return $this->dataSource;
	}

	/**
	 * @inheritdoc
	 */
	public function getQueryBuilder() {
		return $this->queryBuilder;
	}

	/**
	 * @inheritdoc
	 */
	public function getPath() {
		return $this->columnOptions['path'];
	}

	/**
	 * @inheritdoc
	 */
	public function setPath($path) {
		$this->path = $path;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function getQueryPath() {
		return $this->queryPath === null ? $this->getPath() : $this->queryPath;
	}

	/**
	 * @inheritdoc
	 */
	public function setQueryPath($queryPath) {
		$this->queryPath = $queryPath;
		return $this;
	}

	public function getFilterQueryPath() {
		return $this->filterQueryPath === null ? $this->getQueryPath() : $this->filterQueryPath;
	}

	/**
	 * @inheritdoc
	 */
	public function isFilterable() {
		return $this->filterable;
	}

	/**
	 * @inheritdoc
	 */
	public function isOrderable() {
		return $this->orderable;
	}

	/**
	 * @inheritdoc
	 */
	public function isSearchable() {
		return $this->searchable;
	}

	/**
	 * @inheritdoc
	 */
	public function getColumnType() {
		return $this->columnType;
	}

	/**
	 * @inheritdoc
	 */
	public function getColumnOptions() {
		return $this->columnOptions;
	}

	/**
	 * @inheritdoc
	 */
	public function setColumnOptions(array $columnOptions) {
		$this->columnOptions = $columnOptions;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function getColumnOption($option) {
		return $this->columnOptions[$option];
	}

	/**
	 * @inheritdoc
	 */
	public function setColumnOption($option, $value) {
		$this->columnOptions[$option] = $value;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function getTableOptions() {
		return $this->tableOptions;
	}

	/**
	 * @inheritdoc
	 */
	public function setTableOptions(array $tableOptions) {
		$this->tableOptions = $tableOptions;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function getFilter() {
		return $this->filter;
	}

	/**
	 * @inheritdoc
	 */
	public function setFilter($filter) {
		$this->filter = $filter;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function addDataTransformer(DataTransformerInterface $dataTransformer, $forceAppend = false) {
		if($forceAppend) {
			$this->dataTransformers[] = $dataTransformer;
		} else {
			array_unshift($this->dataTransformers, $dataTransformer);
		}
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function resetDataTransformers() {
		$this->dataTransformers = array();
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function getDataTransformers() {
		return $this->dataTransformers;
	}

	/**
	 * @inheritdoc
	 */
	public function getValueDelegate() {
		return $this->valueDelegate;
	}

	/**
	 * @inheritdoc
	 */
	public function setValueDelegate($valueDelegate) {
		$this->valueDelegate = $valueDelegate;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function getServerSideSearchDelegate() {
		return $this->serverSideSearchDelegate;
	}

	/**
	 * @inheritdoc
	 */
	public function setServerSideSearchDelegate($serverSideSearchDelegate) {
		$this->serverSideSearchDelegate = $serverSideSearchDelegate;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function getServerSideFilterDelegate() {
		return $this->serverSideFilterDelegate;
	}

	/**
	 * @inheritdoc
	 */
	public function setServerSideFilterDelegate($serverSideFilterDelegate) {
		$this->serverSideFilterDelegate = $serverSideFilterDelegate;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function getServerSideOrderDelegate() {
		return $this->serverSideOrderDelegate;
	}

	/**
	 * @inheritdoc
	 */
	public function setServerSideOrderDelegate($serverSideOrderDelegate) {
		$this->serverSideOrderDelegate = $serverSideOrderDelegate;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function createData($item, $rootAlias) {
		$this->buildData($this->columnType, $this->columnOptions);
		return $this->generateData($item, $rootAlias, $this->columnOptions, $this->tableOptions);
	}

	/**
	 * Get the hash code of the column.
	 *
	 * @return int the hash code of the column.
	 */
	public function getHashCode() {
		$builder = new HashCodeBuilder();
		return $builder
			->append($this->getPath())
			->append($this->isFilterable())
			->toHashCode();
	}

	/**
	 * @inheritdoc
	 */
	public function createView(ColumnView $parent = null) {
		if(null === $parent && $this->parent) {
			$parent = $this->parent->createView();
		}

		$view = new ColumnView($parent);
		$this->buildView($view, $this->columnType, $this->columnOptions);

		if($view->vars['translation_domain'] === null) {
			$view->vars['translation_domain'] = $this->tableOptions['translation_domain'];
		}
		if($view->vars['abbreviation_translation_domain'] === null) {
			$view->vars['abbreviation_translation_domain'] = $this->tableOptions['translation_domain'];
		}
		if($view->vars['tooltip_translation_domain'] === null) {
			$view->vars['tooltip_translation_domain'] = $this->tableOptions['translation_domain'];
		}

		if($this->filter) {
			$view->filter = $this->filter->createView();
		}

		return $view;
	}

	/**
	 * Fetches the configured value from the given item.
	 *
	 * @param object $item
	 *                              Bound object
	 * @param string $rootAlias
	 *                              the root alias is only necessary if no sub-objects (i.e. no joins) are used for this table.
	 * @param array  $columnOptions the options of the column type
	 * @param array  $tableOptions  the options of the table type the column belongs to
	 * @return mixed The value
	 */
	protected function generateData($item, $rootAlias, $columnOptions, $tableOptions) {
		$path = Utils::startsWith($this->getPath(), $rootAlias . '.') ? substr($this->getPath(), strlen($rootAlias) + 1) : $this->getPath();
		$displayValue = call_user_func($this->valueDelegate, $item, $path, $columnOptions);
		foreach($this->dataTransformers as $transformer) {
			$displayValue = $transformer->transform($this, $item, $displayValue);
		}
		$data = array('display' => $displayValue);
		if($tableOptions['serverSide'] === false) {
			$this->appendSortData($data, $item, $path, $rootAlias, $columnOptions);
			$this->appendFilterData($data, $item, $path, $rootAlias, $columnOptions);
		}
		return $data;
	}

	protected function appendSortData(&$data, $item, $path, $rootAlias, $columnOptions) {
		$value = $this->getClientDelegateValue($this->clientSideOrderDelegate, $item, $path, $rootAlias, $columnOptions);
		if($value !== null) {
			$data['sort'] = $value;
		}
		return $data;
	}

	protected function appendFilterData(&$data, $item, $path, $rootAlias, $columnOptions) {
		$value = $this->getClientDelegateValue($this->clientSideSearchDelegate, $item, $path, $rootAlias, $columnOptions);
		if($value !== null) {
			$data['filter'] = $value;
		}
	}

	protected function getClientDelegateValue($delegate, $item, $path, $rootAlias, $columnOptions) {
		if($delegate !== null) {
			if(is_string($delegate)) {
				$delegate = Utils::startsWith($delegate, $rootAlias . '.') ? substr($delegate, strlen($rootAlias) + 1) : $delegate;
				return $this->generateItemValue($item, $delegate, $columnOptions);
			}

			if(is_callable($delegate)) {
				$value = $this->generateItemValue($item, $path, $columnOptions);
				return $delegate($item, $path, $value, $rootAlias, $columnOptions);
			}
		}
		return null;
	}

	/**
	 * Calls the buildData method on the column type and all parent column types (if any), causing
	 * any data transformers along the hierarchy to be triggered.
	 * In case this method was already called once, it will immediately return.
	 *
	 * @param ColumnTypeInterface $columnType the column type to call the buildData method on, and all its parents.
	 * @param array               $options    the options for the column type.
	 */
	protected function buildData(ColumnTypeInterface $columnType, array $options = array()) {
		if($this->dataConfigured) {
			return;
		}

		if($columnType->getParent()) {
			$parentType = $this->getColumnTypeInstance($columnType->getParent());
			$this->buildData($parentType, $options);
		}
		$columnType->buildData($this, $options);

		$this->dataConfigured = true;
	}

	/**
	 * Configures any fields of the column according to the internal column options, such as filter delegate etc.
	 */
	protected function configureColumn() {
		$serverSide = $this->getTableOptions()['serverSide'] === true;
		if(!$serverSide && $this->columnOptions['orderable'] === true) {
			$this->columnOptions['orderable'] = AbstractColumnType::CLIENT_SIDE_ONLY;
		}
		if(!$serverSide && $this->columnOptions['searchable'] === true) {
			$this->columnOptions['searchable'] = AbstractColumnType::CLIENT_SIDE_ONLY;
		}
		if(!$serverSide && $this->columnOptions['filterable'] === true) {
			$this->columnOptions['filterable'] = AbstractColumnType::CLIENT_SIDE_ONLY;
		}

		$this->queryPath = $this->columnOptions['query_path'];
		$this->filterQueryPath = $this->columnOptions['filter_query_path'];
		$this->orderable = AbstractColumnType::getBooleanValueDependingOnClientOrServer($this->columnOptions['orderable'], $serverSide);
		$this->searchable = AbstractColumnType::getBooleanValueDependingOnClientOrServer($this->columnOptions['searchable'], $serverSide);
		$this->filterable = AbstractColumnType::getBooleanValueDependingOnClientOrServer($this->columnOptions['filterable'], $serverSide);
		$this->valueDelegate = $this->columnOptions['value_delegate'];

		$this->serverSideSearchDelegate = $this->columnOptions['search_server_delegate'];
		$this->serverSideFilterDelegate = $this->columnOptions['filter_server_delegate'];
		$this->serverSideOrderDelegate = $this->columnOptions['order_server_delegate'];

		$this->clientSideSearchDelegate = $this->columnOptions['search_client_delegate'];
		$this->clientSideOrderDelegate = $this->columnOptions['order_client_delegate'];

		if($this->filterable && $this->columnOptions['filter_type'] !== null) {
			$this->filter = new Filter(
				$this->getFilterTypeInstance($this->columnOptions['filter_type']),
				$this->dependencyInjectionExtension,
				$this->columnOptions['filter_options'],
				$this->columnOptions,
				$this->tableOptions,
				$this->dataSource
			);
		}
	}

	/**
	 * Updates the given view.
	 *
	 * @param ColumnView          $columnView    the view to be updated
	 * @param ColumnTypeInterface $columnType    the column type containing the information that may be relevant for the view
	 * @param array               $columnOptions the options defined for the column type, containing information
	 *                                           such as the translation_domain etc.
	 */
	protected function buildView(ColumnView $columnView, ColumnTypeInterface $columnType, array $columnOptions = array()) {
		if($columnType->getParent()) {
			$parentType = $this->getColumnTypeInstance($columnType->getParent());
			$this->buildView($columnView, $parentType, $columnOptions);
		}
		$columnType->buildView($columnView, $this, $columnOptions);

		if($columnView->vars['translation_domain'] === null) {
			$columnView->vars['translation_domain'] = $this->columnOptions['translation_domain'];
		}
	}

	/**
	 * Sets up the options resolver for the given column type and the initial options.
	 * Setting up means that the column type options and all applicable parent options will be validated and
	 * resolved according to the hierarchy grandparent, then parent, then instance etc.
	 *
	 * @param ColumnTypeInterface $columnType the type to resolve the options for, also used for determining any parents
	 *                                        whose options are to be resolved as well
	 * @param array               $options    the initial options to also be resolved (if any).
	 * @return array the resolved options for the given column type.
	 */
	protected function setupFilterOptionsResolver(ColumnTypeInterface $columnType, array $options = array()) {
		$this->resolveOptions($columnType, $this->resolver);
		return $this->resolver->resolve($options);
	}

	/**
	 * Merges the configurations of each type in the hierarchy starting from the top most type.
	 *
	 * @param ColumnTypeInterface $columnType the column type to resolve the options from
	 * @param OptionsResolver     $resolver   the resolver used for checking option values and defaults etc.
	 */
	protected function resolveOptions(ColumnTypeInterface $columnType, OptionsResolver $resolver) {
		if($columnType->getParent()) {
			$parentType = $this->getColumnTypeInstance($columnType->getParent());
			$this->resolveOptions($parentType, $resolver);
		}
		$columnType->configureOptions($resolver, $this->tableOptions);
	}

	/**
	 * Creates an instance of the given column type class.
	 *
	 * @param string $class
	 *            Class name of the column type to create an instance of
	 * @return ColumnTypeInterface an instance of the given column type
	 * @throws InvalidArgumentTypeException in case the given class does not implement the ColumnTypeInterface interface
	 */
	protected function getColumnTypeInstance($class) {
		return $this->dependencyInjectionExtension->resolveColumnType($class);
	}

	/**
	 * Creates an instance of the given filter type class.
	 *
	 * @param string $class
	 *            Class name of the filter type to create an instance of
	 * @return FilterTypeInterface an instance of the given filter type
	 * @throws InvalidArgumentTypeException in case the given class does not implement the FilterTypeInterface interface
	 */
	protected function getFilterTypeInstance($class) {
		return $this->dependencyInjectionExtension->resolveFilterType($class);
	}
}