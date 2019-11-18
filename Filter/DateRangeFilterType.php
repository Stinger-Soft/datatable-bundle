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
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateRangeFilterType extends AbstractFilterType {

	/**
	 *
	 * @inheritdoc
	 */
	public function configureOptions(OptionsResolver $resolver, array $columnOptions = [], array $tableOptions = []) {
		$resolver->setDefault('jsTemplate', 'StingerSoftDatatableBundle:Filter:date_range.json.twig');
		$resolver->setDefault('type', FilterTypeInterface::FILTER_TYPE_RANGE_DATE);
		$resolver->setDefault('filter_default_label', function (Options $options, $previousValue) {
			if($options['type'] === FilterTypeInterface::FILTER_TYPE_RANGE_DATE) {
				return [
					'stinger_soft_datatables.filter.placeholder.from',
					'stinger_soft_datatables.filter.placeholder.to',
				];
			}
			return 'stinger_soft_datatables.filter.placeholder.select_date';
		});
		$resolver->setDefault('auto_focus', false);
		$resolver->setDefault('date_format', 'dd.mm.yyyy');
		$resolver->setAllowedTypes('date_format', [
			'string',
			'null',
		]);
		$resolver->setAllowedTypes('filter_default_label', [
			'array',
			'string',
			'null',
		]);
		$resolver->setDefault('filter_plugin_options', [
			'autoclose'      => true,
			'calendarWeeks'  => true,
			'todayHighlight' => true,
			'todayBtn'       => true,
			'language'       => \Locale::getDefault(),
		]);
	}

	/**
	 * Checks if the date range values are valid.
	 *
	 * In case of FilterTypeInterface::FILTER_TYPE_RANGE_DATE is set as the filter_type_range_date value, checks 3 cases
	 *
	 * In case-1, checks from date is not empty and to date is empty, greater than equal to expersison
	 * will be added to the query builders where clauses when filtering.
	 *
	 * In case-2, checks from date is empty and to date is not empty, less than equal to expersison
	 * will be added to the query builders where clauses when filtering.
	 *
	 * In case-3, checks from and to dates are not empty, greate than equal, less than equal, AND expersison
	 * will be added to the query builders where clauses when filtering.
	 *
	 * In case of FilterTypeInterface::FILTER_TYPE_DATE is set as the filter_type_date value, a like expression with the
	 * percent % wildcard at the end of the search string will be added to the query builders where clauses when filtering.
	 *
	 * Please note: if all values are null the filter will not be applied in the default implementation.
	 */
	public function applyFilter(QueryBuilder $queryBuilder, $filterValue, $filterRegex, $parameterBindingName, $queryPath, $filterTypeOptions, $rootAlias) {
		$expr = null;
		if($this->filterIsValid($filterValue, $filterRegex, $filterTypeOptions)) {
			if($filterTypeOptions['type'] === FilterTypeInterface::FILTER_TYPE_RANGE_DATE) {
				[$dateFrom, $dateTo] = $filterValue;
				$formattedDateFrom = date('Y-m-d H:i:s', strtotime($dateFrom));
				$formattedDateTo = date('Y-m-d H:i:s', strtotime('235959', strtotime($dateTo)));
				// Case: FILTER_TYPE_RANGE_DATE
				if(!empty($dateFrom) && empty($dateTo)) {
					// From date is not empty and To date is empty.
					$expr = $queryBuilder->expr()->gte($queryPath, $parameterBindingName);
					$queryBuilder->setParameter($parameterBindingName, $formattedDateFrom);
				} else if(empty($dateFrom) && !empty($dateTo)) {
					// From date is empty and To date is not empty.
					$expr = $queryBuilder->expr()->lte($queryPath, $parameterBindingName);
					$queryBuilder->setParameter($parameterBindingName, $formattedDateTo);
				} else {
					// From and To dates are not empty.
					$expr = $queryBuilder->expr()->andX($queryBuilder->expr()->gte($queryPath, $parameterBindingName . '_start'), $queryBuilder->expr()->lte($queryPath, $parameterBindingName . '_end'));
					$queryBuilder->setParameter($parameterBindingName . '_start', $formattedDateFrom);
					$queryBuilder->setParameter($parameterBindingName . '_end', $formattedDateTo);
				}
			} else {
				// Case: FILTER_TYPE_DATE
				$expr = $queryBuilder->expr()->like($queryPath, $parameterBindingName);
				$queryBuilder->setParameter($parameterBindingName, '%' . date('Y-m-d', strtotime($filterValue)) . '%');
			}
		}
		return $expr;
	}

	protected function filterIsValid($filterValue, $filterRegex, $filterTypeOptions) {
		if(is_array($filterValue)) {
			return (!empty($filterValue[0]) || !empty($filterValue[1]));
		}
		return !empty($filterValue);
	}

	/**
	 *
	 * @inheritdoc
	 */
	public function buildView(FilterView $view, FilterInterface $filter, array $options, $dataSource, $queryPath, $rootAlias) {
		$view->vars['highlight_mode'] = FilterTypeInterface::HIGHLIGHT_MODE_MANUAL;
		$view->vars['date_format'] = $options['date_format'];
	}
}