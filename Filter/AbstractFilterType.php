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

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The default filter type which should be used for *ALL* specialized sub types.
 */
abstract class AbstractFilterType implements FilterTypeInterface {

	/**
	 * {@inheritdoc}
	 * @see \StingerSoft\DatatableBundle\Filter\FilterTypeInterface::configureOptions()
	 */
	public function configureOptions(OptionsResolver $resolver, array $columnOptions = [], array $tableOptions = []) {
	}

	/**
	 * {@inheritdoc}
	 * @see \StingerSoft\DatatableBundle\Filter\FilterTypeInterface::getParent()
	 */
	public function getParent() {
		return FilterType::class;
	}

	/**
	 * {@inheritdoc}
	 * @see \StingerSoft\DatatableBundle\Filter\FilterTypeInterface::buildView()
	 */
	public function buildView(FilterView $view, FilterInterface $filter, array $options, $dataSource, $queryPath, $rootAlias) {
	}

	/**
	 * Apply any filtering on the given QueryBuilder using the given value to filter by.
	 *
	 * The default behaviour of the applyFilter method takes into account the option filter_match_mode defined for the
	 * AbstractFilterType.
	 *
	 * In case FilterTypeInterface::FILTER_MATCH_MODE_EXACT is set as the filter_match_mode value, an equals expression
	 * will be added to the query builders where clauses when filtering.
	 *
	 * In case FilterTypeInterface::FILTER_MATCH_MODE_STARTS_WITH is set as the filter_match_mode value, a like expression with the
	 * percent % wildcard at the end of the search string will be added to the query builders where clauses when filtering.
	 *
	 * In case any other value is set as filter_match_mode value, a like expression with the percent % wildcard at the
	 * beginning and the end of the search string will be added to the query builders where clauses when filtering.
	 *
	 * Please note: if the value FilterTypeInterface::FILTER_MATCH_MODE_REGEX is set, the filter will not be applied in
	 * the default implementation.
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
	 * @return \Doctrine\ORM\Query\Expr|\Doctrine\ORM\Query\Expr\Comparison|null an expression (and set parameters!) to be added to the filter query or <code>null</code> in
	 *                                              case no filtering will be applied for the given values. If this method
	 *                                              returns any expression, its parameters MUST be bound in here!.
	 *                                              Any expression returned will be added to an <code>andWhere</code> clause
	 *                                              to the underlying query builder.
	 */
	public function applyFilter(QueryBuilder $queryBuilder, $filterValue, $filterRegex, $parameterBindingName, $queryPath, $filterTypeOptions, $rootAlias) {
		$expr = null;
		if($this->filterIsValid($filterValue, $filterRegex, $filterTypeOptions)) {
			if(isset($filterTypeOptions['filter_match_mode']) && $filterTypeOptions['filter_match_mode'] === FilterTypeInterface::FILTER_MATCH_MODE_EXACT) {
				$expr = $queryBuilder->expr()->eq($queryPath, $parameterBindingName);
				$queryBuilder->setParameter($parameterBindingName, $filterValue);
			} else if(!isset($filterTypeOptions['match_mode']) || (isset($filterTypeOptions['filter_match_mode']) && $filterTypeOptions['filter_match_mode'] !== FilterTypeInterface::FILTER_MATCH_MODE_REGEX)) {
				$expr = $queryBuilder->expr()->like($queryPath, $parameterBindingName);
				if($filterTypeOptions['filter_match_mode'] === FilterTypeInterface::FILTER_MATCH_MODE_STARTS_WITH) {
					$queryBuilder->setParameter($parameterBindingName, $filterValue . '%');
				} else {
					$queryBuilder->setParameter($parameterBindingName, '%' . $filterValue . '%');
				}
			}
		}
		return $expr;
	}

	/**
	 * Checks if the given filter is valid.
	 *
	 * A filter is considered valid when there is a filter value (i.e. it is not empty) and it is not a regular expression.
	 *
	 * @param string|string[] $filterValue       the filter value
	 * @param boolean         $filterRegex       true in case the filter is a regular expression, false otherwise.
	 * @param array           $filterTypeOptions the options of the filter type.
	 * @return bool true in case the filter value is not empty and the filter is no regular expression, false otherwise.
	 */
	protected function filterIsValid($filterValue, $filterRegex, $filterTypeOptions) {
		// Regular Expressions are ignored on server side, as there is no built-in regexp handling in doctrine
		if($filterTypeOptions['filter_validation_delegate'] !== null && is_callable($filterTypeOptions['filter_validation_delegate'])) {
			return $filterTypeOptions['filter_validation_delegate']($filterValue, $filterRegex, $filterTypeOptions);
		}

		if($filterTypeOptions['filter_validate_empty'] === false) {
			return !$filterRegex;
		}
		if($filterTypeOptions['filter_validate_empty'] === true) {
			return !empty($filterValue) && !$filterRegex;
		}
		return !$filterRegex;
	}
}