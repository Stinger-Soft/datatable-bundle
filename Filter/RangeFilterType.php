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
use StingerSoft\DatatableBundle\Filter\AbstractFilterType;
use StingerSoft\DatatableBundle\Filter\AutoCompleteFilterType;
use StingerSoft\DatatableBundle\Filter\FilterInterface;
use StingerSoft\DatatableBundle\Filter\FilterTypeInterface;
use StingerSoft\DatatableBundle\Filter\FilterView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RangeFilterType extends AbstractFilterType {

	/**
	 * @inheritdoc
	 */
	public function configureOptions(OptionsResolver $resolver, array $columnOptions = array(), array $tableOptions = array()) {
		$resolver->setDefault('jsTemplate', 'StingerSoftDatatableBundle:Filter:range.json.twig');
		$resolver->setDefault('type', FilterTypeInterface::FILTER_TYPE_TEXT);
		$resolver->setDefault('style_class', 'range_number_single_text_field range_filter');
		$resolver->setDefault('treat_null_as_zero', false);
		$resolver->setDefault('step', 1);
		$resolver->setAllowedTypes('step', array('double', 'int'));
		$resolver->setDefault('plugin_options', []);
		$resolver->setAllowedTypes('plugin_options', 'array');
	}

	public function applyFilter(QueryBuilder $queryBuilder, $filterValue, $filterRegex, $parameterBindingName, $queryPath, $filterTypeOptions, $rootAlias) {
		$expr = null;
		if($this->filterIsValid($filterValue, $filterRegex, $filterTypeOptions)) {
			$expr = $queryBuilder->expr()->between('COALESCE(' . $queryPath . ', 0)', $parameterBindingName . '_from', $parameterBindingName . '_to');
			$queryBuilder->setParameter($parameterBindingName . '_from', $filterValue[0]);
			$queryBuilder->setParameter($parameterBindingName . '_to', $filterValue[1]);
		}
		return $expr;
	}

	protected function filterIsValid($filterValue, $filterRegex, $filterTypeOptions) {
		// Regular Expressions are ignored on server side, as there is no built-in regexp handling in doctrine
		return !empty($filterValue) && is_array($filterValue) && count($filterValue) === 2 && (!empty($filterValue[0]) || !empty($filterValue[1]));
	}

	/**
	 * @inheritdoc
	 */
	public function buildView(FilterView $view, FilterInterface $filter, array $options, $dataSource, $queryPath, $rootAlias) {
		$view->vars['data'] = array();
		if($dataSource instanceof QueryBuilder) {
			$qbClone = clone $dataSource;
			if($options['treat_null_as_zero']) {
				$qbClone->select('COALESCE(MIN(' . $queryPath . '), 0) as fromValue');
				$qbClone->addSelect('COALESCE(MAX(' . $queryPath . '), 0) as toValue');
			} else {
				$qbClone->select('MIN(' . $queryPath . ') as fromValue');
				$qbClone->addSelect('MAX(' . $queryPath . ') as toValue');
			}
			$qbClone->resetDQLPart('groupBy');
			$qbClone->resetDQLPart('orderBy');

			$result = current($qbClone->getQuery()->getScalarResult());
			$view->vars = array_merge($view->vars, $result);
			$view->vars['data'] = $result;
		}

		$view->vars['style_class'] = $options['style_class'];
		$view->vars['highlight_mode'] = FilterTypeInterface::HIGHLIGHT_MODE_MANUAL;
		$view->vars['data']['step'] = $options['step'];
		$view->vars['data']['options'] = $options['plugin_options'];
	}

	/**
	 * @inheritdoc
	 */
	public function getParent() {
		return AutoCompleteFilterType::class;
	}
}
