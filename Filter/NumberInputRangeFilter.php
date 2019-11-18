<?php
/*
 * This file is part of the PEC Platform StreetScooterApp.
 *
 * (c) PEC project engineers &amp; consultants
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace StingerSoft\DatatableBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NumberInputRangeFilter extends AbstractFilterType {


	/**
	 * @inheritdoc
	 */
	public function configureOptions(OptionsResolver $resolver, array $columnOptions = array(), array $tableOptions = array()) {
		$resolver->setDefault('jsTemplate', '@PecDatatable/Filter/number_range_input.json.twig');
		$resolver->setDefault('type', FilterTypeInterface::FILTER_TYPE_RANGE_NUMBER);
		$resolver->setDefault('filter_default_label', ['stinger_soft_datatables.filter.placeholder.from', 'stinger_soft_datatables.filter.placeholder.to']);
		$resolver->setAllowedTypes('filter_default_label', array(
			'array',
			'null'
		));
	}

	public function applyFilter(QueryBuilder $queryBuilder, $filterValue, $filterRegex, $parameterBindingName, $queryPath, $filterTypeOptions, $rootAlias) {
		$expr = null;
		if($this->filterIsValid($filterValue, $filterRegex, $filterTypeOptions)) {
			[$from, $to] = $filterValue;
			if(!$this->isEmptyString($from) && !$this->isEmptyString($to)) {
				$expr = $queryBuilder->expr()->between('COALESCE(' . $queryPath . ', 0)', $parameterBindingName . '_from', $parameterBindingName . '_to');
				$queryBuilder->setParameter($parameterBindingName . '_from', $from);
				$queryBuilder->setParameter($parameterBindingName . '_to', $to);
				return $expr;
			}
			if($this->isEmptyString($from)) {
				$expr = $queryBuilder->expr()->lte('COALESCE(' . $queryPath . ', 0)', $parameterBindingName . '_to');
				$queryBuilder->setParameter($parameterBindingName . '_to', $to);
			}
			if($this->isEmptyString($to)) {
				$expr = $queryBuilder->expr()->gte('COALESCE(' . $queryPath . ', 0)', $parameterBindingName . '_from');
				$queryBuilder->setParameter($parameterBindingName . '_from', $from);
			}
		}
		return $expr;
	}

	protected function filterIsValid($filterValue, $filterRegex, $filterTypeOptions) {
		// Regular Expressions are ignored on server side, as there is no built-in regexp handling in doctrine
		return !empty($filterValue) && is_array($filterValue) && count($filterValue) === 2 && (!$this->isEmptyString($filterValue[0]) || !$this->isEmptyString($filterValue[1]));
	}

	protected function isEmptyString($val) {
		return trim($val) === '';
	}

	/**
	 * @inheritdoc
	 */
	public function buildView(FilterView $view, FilterInterface $filter, array $options, $dataSource, $queryPath, $rootAlias) {
		$view->vars['highlight_mode'] = FilterTypeInterface::HIGHLIGHT_MODE_MANUAL;
		$view->vars['data'] = array();
		if($dataSource instanceof QueryBuilder) {
			$qbClone = clone $dataSource;
			$qbClone->select('COALESCE(MIN(' . $queryPath . '), 0) as fromValue');
			$qbClone->addSelect('COALESCE(MAX(' . $queryPath . '), 0) as toValue');
			$qbClone->resetDQLPart('groupBy');
			$result = current($qbClone->getQuery()->getScalarResult());
			$view->vars = array_merge($view->vars, $result);
			$view->vars['data'] = $result;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getParent() {
		return AutoCompleteFilterType::class;
	}
}
