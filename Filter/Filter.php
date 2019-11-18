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
use StingerSoft\DatatableBundle\Exception\InvalidArgumentTypeException;
use StingerSoft\DatatableBundle\Extension\DependencyInjection\DependencyInjectionExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The Filter class encapsulates all information required for handling filter types, creating views for filters and processing
 * filters items in order to retrieve the matching values to be used for cells bound to the column the filter is bound to.
 */
class Filter implements FilterInterface {
	
	/**
	 *
	 * @var FilterTypeInterface the filter type used for this filter instance
	 */
	protected $filterType;

	/**
	 *
	 * @var array the options defined for the filter type.
	 */
	protected $filterOptions;

	/**
	 *
	 * @var array the options defined for the original column this filter belongs to
	 */
	protected $columnOptions;

	/**
	 *
	 * @var array the options defined for the original table this filter belongs to
	 */
	protected $tableOptions;

	/**
	 *
	 * @var FilterInterface the parent filter (if any)
	 */
	protected $parent;

	/**
	 *
	 * @var null|callable the delegate to be used for filtering (if any), allowing to update query builder
	 */
	protected $filterDelegate = null;

	/**
	 *
	 * @var QueryBuilder the query builder of the table the column and this the filter will be attached to
	 */
	protected $queryBuilder;

	/** @var  QueryBuilder|array */
	protected $dataSource;

	/**
	 * @var DependencyInjectionExtension
	 */
	protected $dependencyInjectionExtension;

	/**
	 * Filter constructor.
	 *
	 * @param FilterTypeInterface $filterType
	 *        	the filter type for this filter
	 * @param array $filterTypeOptions
	 *        	the options for the filter type
	 * @param array $columnOptions
	 *        	the options of the column the filter belongs to
	 * @param array $tableOptions
	 *        	the options of the table the filter belongs to
	 * @param QueryBuilder|array $dataSource
	 *        	the data source of the table the column and this the filter will be attached to.
	 *        	In case it is a query builder, it will be cloned in order to allow the filter to
	 *        	modify it if necessary.
	 * @param FilterInterface|null $parent
	 *        	the parent filter (if any) or null.
	 */
	public function __construct(FilterTypeInterface $filterType, DependencyInjectionExtension $dependencyInjectionExtension, array $filterTypeOptions = array(), array $columnOptions = array(), array $tableOptions = array(), $dataSource, FilterInterface $parent = null) {
		$this->dependencyInjectionExtension = $dependencyInjectionExtension;
		$this->columnOptions = $columnOptions;
		$this->tableOptions = $tableOptions;
		$this->filterType = $filterType;
		$this->filterOptions = $this->setupFilterOptionsResolver($filterType, $filterTypeOptions);
		$this->parent = $parent;
		$this->dataSource = $dataSource;
		if($dataSource instanceof QueryBuilder) {
			$this->queryBuilder = clone $dataSource;
		}
		
		$this->configureFilter();
	}

	/**
	 * @inheritdoc
	 */
	public function getFilterType() {
		return $this->filterType;
	}

	/**
	 * @inheritdoc
	 */
	public function getFilterOptions() {
		return $this->filterOptions;
	}

	/**
	 * @inheritdoc
	 */
	public function setFilterOptions(array $filterOptions) {
		$this->filterOptions = $filterOptions;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function getColumnOptions() {
		return $this->columnOptions;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \StingerSoft\DatatableBundle\Filter\FilterInterface::getColumnOption()
	 */
	public function getColumnOption($key) {
		return $this->columnOptions[$key];
	}

	/**
	 * @inheritdoc
	 */
	public function setColumnOptions(array $columnOptions) {
		$this->columnOptions = $columnOptions;
		return $this;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \StingerSoft\DatatableBundle\Filter\FilterInterface::setColumnOption()
	 */
	public function setColumnOption($key, $value) {
		$this->columnOptions[$key] = $value;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function getFilterDelegate() {
		return $this->filterDelegate;
	}

	/**
	 * @inheritdoc
	 */
	public function createView(FilterView $parent = null) {
		if(null === $parent && $this->parent) {
			$parent = $this->parent->createView();
		}
		
		$view = new FilterView($parent);
		$this->buildView($view, $this->filterType, $this->filterOptions);
		
		if($view->vars['translation_domain'] === null) {
			$view->vars['translation_domain'] = $this->columnOptions['translation_domain'];
		}
		
		return $view;
	}

	/**
	 * Configures any fields of the filter according to the internal filter options, such as filter delegate defined
	 * on the column etc.
	 */
	protected function configureFilter() {
		if(isset($this->columnOptions['filter_server_delegate'])) {
			$this->filterDelegate = $this->columnOptions['filter_server_delegate'];
		}
	}

	/**
	 * Updates the given view.
	 *
	 * @param FilterView $filterView
	 *        	the view to be updated
	 * @param FilterTypeInterface $filterType
	 *        	the filter type containing the information that may be relevant for the view
	 * @param array $filterOptions
	 *        	the options defined for the filter type, containing information
	 *        	such as the filter_server_delegate etc.
	 */
	protected function buildView(FilterView $filterView, FilterTypeInterface $filterType, array $filterOptions = array()) {
		if($filterType->getParent()) {
			$parentType = $this->getFilterTypeInstance($filterType->getParent());
			$this->buildView($filterView, $parentType, $filterOptions);
		}
		$rootAliases = $this->queryBuilder ? $this->queryBuilder->getRootAliases() : array();
		if($this->columnOptions['filter_query_path'] !== null) {
			$path = $this->columnOptions['filter_query_path'];
		} else if($this->columnOptions['query_path'] !== null) {
			$path = $this->columnOptions['query_path'];
		} else {
			$path = $this->columnOptions['path'];
			if($this->queryBuilder && false === strpos($path, '.')) {
				$path = current($rootAliases) . '.' . $path;
			}
		}
		$filterType->buildView($filterView, $this, $filterOptions, $this->queryBuilder ?: $this->dataSource, $path, current($rootAliases));
	}

	/**
	 * Sets up the options resolver for the given filter type and the initial options.
	 * Setting up means that the filter type options and all applicable parent options will be validated and
	 * resolved according to the hierarchy grandparent, then parent, then instance etc.
	 *
	 * @param FilterTypeInterface $filterType
	 *        	the type to resolve the options for, also used for determining any parents
	 *        	whose options are to be resolved as well
	 * @param array $options
	 *        	the initial options to also be resolved (if any).
	 * @return array the resolved options for the given filter type.
	 */
	protected function setupFilterOptionsResolver(FilterTypeInterface $filterType, array $options = array()) {
		$resolver = new OptionsResolver();
		$this->resolveOptions($filterType, $resolver);
		return $resolver->resolve($options);
	}

	/**
	 * Merges the configurations of each type in the hierarchy starting from the top most type.
	 *
	 * @param FilterTypeInterface $filterType
	 *        	the filter type to resolve the options from
	 * @param OptionsResolver $resolver
	 *        	the resolver used for checking option values and defaults etc.
	 */
	private function resolveOptions(FilterTypeInterface $filterType, OptionsResolver $resolver) {
		if($filterType->getParent()) {
			$parentType = $this->getFilterTypeInstance($filterType->getParent());
			$this->resolveOptions($parentType, $resolver);
		}
		$filterType->configureOptions($resolver, $this->columnOptions);
	}

	/**
	 * Creates an instance of the given filter type class.
	 *
	 * @param string $class
	 *        	Class name of the filter type
	 * @return object|FilterTypeInterface an instance of the given filter type
	 * @throws InvalidArgumentTypeException in case the given class does not implement the FilterTypeInterface interface
	 */
	private function getFilterTypeInstance($class) {
		return $this->dependencyInjectionExtension->resolveFilterType($class);
	}

	/**
	 * Apply any filtering on the given QueryBuilder using the given value to filter by.
	 *
	 * In case the column the filter belongs to has a filter_server_delegate defined, the delegate is called in order to perform
	 * any filtering. In case no filter_server_delegate is defined for the column the filter belongs to, the applyFilter method
	 * of the underlying filter type is used.
	 *
	 * @param QueryBuilder $queryBuilder
	 *        	the query builder to create filter expressions for.
	 * @param string|string[] $filterValue
	 *        	the value to be used for filtering, in case a filter has to handle a
	 *        	range, an array with start / end or min / max values is provided.
	 * @param boolean $filterRegex
	 *        	<code>true</code> in case the filter is considered as a regular
	 *        	expression,
	 *        	<code>false</code> otherwise.
	 * @param string $parameterBindingName
	 *        	the initial name of the parameter to be used for binding the filter
	 *        	value to any query builder expression, the binding name is suffixed
	 *        	with a counter value. The value can and should be used to bind
	 *        	parameters on the query builder, like this:
	 *        	<code>$queryBuilder->setParameter($parameterBindingName, $filterValue)</code>
	 * @param string $queryPath
	 *        	the path determining the field to filter on. If you for instance performed
	 *        	a <code>$queryBuilder->leftJoin('user.address', 'address')</code> and
	 *        	the column to be filtered shall display the addresses city, the query path
	 *        	would be something like <code>address.city</code>. Use <code>$rootAlias</code>
	 *        	in order to be able to query on <code>user.address.city</code> (if required).
	 * @param array $filterTypeOptions
	 *        	an array containing all resolved and configured options of the filter type.
	 *        	These options may contain additional information useful for filtering, such as
	 *        	performing case insensitive filtering, matching information (exact matches only,
	 *        	substring matches, etc.)
	 * @return Expr|null an expression (and set parameters!) to be added to the filter query or <code>null</code> in
	 *         case no filtering will be applied for the given values. If this method
	 *         returns any expression, its parameters MUST be bound in here!.
	 *         Any expression returned will be added to an <code>andWhere</code> clause
	 *         to the underlying query builder.
	 *
	 * @see FilterTypeInterface::applyFilter()
	 */
	public function applyFilter(QueryBuilder $queryBuilder, $filterValue, $filterRegex, $parameterBindingName, $queryPath, $filterTypeOptions, $rootAlias) {
		$delegate = $this->filterDelegate;
		if($delegate && is_callable($delegate)) {
			return $delegate($queryBuilder, $filterValue, $filterRegex, $parameterBindingName, $queryPath, $filterTypeOptions, $rootAlias);
		}
		
		return $this->getFilterType()->applyFilter($queryBuilder, $filterValue, $filterRegex, $parameterBindingName, $queryPath, $filterTypeOptions, $rootAlias);
	}
}