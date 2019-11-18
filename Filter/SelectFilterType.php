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

/**
 * A filter type based on select / dropdown box that provides quick access to all filterable values.
 */
class SelectFilterType extends AbstractFilterType {

	/**
	 * @inheritdoc
	 */
	public function configureOptions(OptionsResolver $resolver, array $columnOptions = array(), array $tableOptions = array()) {
		$resolver->setDefault('jsTemplate', 'StingerSoftDatatableBundle:Filter:select.json.twig');

		$resolver->setDefault('select_type', 'select2');
		$resolver->setAllowedValues('select_type', array(null, 'select2', 'chosen'));

		$resolver->setDefault('select_type_options', function (Options $options, $previousValue){
			if($previousValue === null && $options['select_type'] === 'select2') {
				return array(
					'dropdownAutoWidth' => true,
					'width' => '100%'
				);
			}
			return $previousValue;
		});


		$resolver->setDefault('multiple', true);
		$resolver->setAllowedValues('multiple', array(true, false));

		$resolver->setDefault('type', function (Options $options) {
			if($options['multiple']) {
				return FilterTypeInterface::FILTER_TYPE_MULTI_SELECT;
			}

			return FilterTypeInterface::FILTER_TYPE_SELECT;
		});

		$resolver->setDefault('style_class', null);
		$resolver->setAllowedTypes('style_class', array('null', 'string'));

		$resolver->setDefault('filter_default_label', function (Options $options) {
			return $options['multiple'] === true ? 'stinger_soft_datatables.filter.placeholder.select_multiple' : 'stinger_soft_datatables.filter.placeholder.select';
		});
	}

	/**
	 * @inheritdoc
	 */
	public function buildView(FilterView $view, FilterInterface $filter, array $options, $dataSource, $queryPath, $rootAlias) {
		$view->vars['select_type'] = $options['select_type'];
		$view->vars['style_class'] = $options['style_class'];
		$view->vars['select_type_options'] = $options['select_type_options'];
	}

	/**
	 * Apply any filtering on the given QueryBuilder using the given value to filter by.
	 *
	 * For the select filter type, the filter is applying using 'eq' expressions for every selected filter value.
	 * The 'eq' expressions are combined via an 'or' expression, generating where clauses such as
	 *    'field = selectValue1 OR field = selectedValue2 OR field = selectedValue3 ...'
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
	 * @return \Doctrine\ORM\Query\Expr|\Doctrine\ORM\Query\Expr\Base|\Doctrine\ORM\Query\Expr\Comparison|null
	 *                                              added to the filter query or <code>null</code> in
	 *                                              case no filtering will be applied for the given values. If this method
	 *                                              returns any expression, its parameters MUST be bound in here!.
	 *                                              Any expression returned will be added to an <code>andWhere</code> clause
	 *                                              to the underlying query builder. */
	public function applyFilter(QueryBuilder $queryBuilder, $filterValue, $filterRegex, $parameterBindingName, $queryPath, $filterTypeOptions, $rootAlias) {
		if($filterRegex === true) {
			$filterValues = preg_split('/(?<!\\\\)\\|/im', $filterValue);
			$filterEqs = array();
			$filterBindingCounter = 0;
			$allowNullValues = $filterTypeOptions['allow_null_value'];
			$nullValue = $filterTypeOptions['null_value'];
			foreach($filterValues as $value) {
				if($allowNullValues && $value == $nullValue) {
					$filterEqs[] = $queryBuilder->expr()->isNull($queryPath);
				} else {
					$bindingName = $parameterBindingName . '_select_' . ++$filterBindingCounter;
					$filterEqs[] = $queryBuilder->expr()->eq($queryPath, $bindingName);
					$queryBuilder->setParameter($bindingName, self::inversePregQuote($value));
				}
			}
			return $queryBuilder->expr()->orX()->addMultiple($filterEqs);
		}

		return parent::applyFilter($queryBuilder, $filterValue, $filterRegex, $parameterBindingName, $queryPath, $filterTypeOptions, $rootAlias);
	}

	public static function inversePregQuote($str) {
		return strtr($str, array(
			'\\.' => '.',
			'\\\\' => '\\',
			'\\+' => '+',
			'\\*' => '*',
			'\\?' => '?',
			'\\[' => '[',
			'\\^' => '^',
			'\\]' => ']',
			'\\$' => '$',
			'\\(' => '(',
			'\\)' => ')',
			'\\{' => '{',
			'\\}' => '}',
			'\\=' => '=',
			'\\!' => '!',
			'\\<' => '<',
			'\\>' => '>',
			'\\|' => '|',
			'\\:' => ':',
			'\\-' => '-'
		));
	}

	/**
	 * @inheritdoc
	 */
	public function getParent() {
		return AutoCompleteFilterType::class;
	}

}