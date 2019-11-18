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
use Symfony\Component\Translation\TranslatorInterface;

class ClassSelectFilterType extends AbstractFilterType {

	protected $labelCache = array();

	/**
	 * @var TranslatorInterface
	 */
	protected $translator;

	public function __construct(TranslatorInterface $translator) {
		$this->translator = $translator;
	}

	public function configureOptions(OptionsResolver $resolver, array $columnOptions = array(), array $tableOptions = array()) {
		$that = $this;
		$resolver->setDefault('filter_match_node', FilterTypeInterface::FILTER_MATCH_MODE_CONTAINS);
		$resolver->setDefault('data', function(FilterInterface $filter, array $options, QueryBuilder $queryBuilder, $queryPath, $rootAlias) use ($that) {
			$path = sprintf('%s', $queryPath);
			$queryBuilder->select($path);
			$queryBuilder->where('SUBSTRING(' . $path . ', 0, 9) != :proxy')->setParameter(':proxy', 'Proxies\\');
			$queryBuilder->distinct(true);
			$result = $queryBuilder->getQuery()->getScalarResult();
			$result = array_map('current', $result);
			$result = array_map(function($item) use ($that) {
				return $that->escapeClassName($item);
			}, $result);
			$data = array();
			foreach($result as $item) {
				$name = $that->getEntityLabel($item);
				$data[$name] = $item;
			}
			ksort($data);
			return $data;
		});
		$resolver->setDefault('label_function', function($className) use ($that) {
			return $that->getEntityLabel($className);
		});
	}

	protected function escapeClassName($className) {
		return str_replace('\\', '-_-', $className);
	}

	protected function getEntityLabel($className) {
		$className = $this->unescapeClassName($className);
		if(!array_key_exists($className, $this->labelCache)) {
			try {
				$reflector = new \ReflectionClass($className);
				$label = $reflector->getShortName();
				$this->labelCache[$className] = $label;
				return $label;
			} catch(\ReflectionException $e) {
				$this->labelCache[$className] = $className;
			}
		}
		return $this->labelCache[$className];
	}

	protected function unescapeClassName($className) {
		return str_replace('-_-', '\\', $className);
	}

	/**
	 * Apply any filtering on the given QueryBuilder using the given value to filter by.
	 *
	 * For the select filter type, the filter is applying using 'eq' expressions for every selected filter value.
	 * The 'eq' expressions are combined via an 'or' expression, generating where clauses such as
	 *    'field = selectValue1 OR field = selectedValue2 OR field = selectedValue3 ...'
	 *
	 * @param QueryBuilder $queryBuilder the query builder to create filter expressions for.
	 * @param string|string[] $filterValue the value to be used for filtering, in case a filter has to handle a
	 *                                              range, an array with start / end or min / max values is provided.
	 * @param boolean $filterRegex <code>true</code> in case the filter is considered as a regular
	 *                                              expression,
	 *                                              <code>false</code> otherwise.
	 * @param string $parameterBindingName the initial name of the parameter to be used for binding the filter
	 *                                              value to any query builder expression, the binding name is suffixed
	 *                                              with a counter value. The value can and should be used to bind
	 *                                              parameters on the query builder, like this:
	 *                                              <code>$queryBuilder->setParameter($parameterBindingName, $filterValue)</code>
	 * @param string $queryPath the path determining the field to filter on. If you for instance performed
	 *                                              a <code>$queryBuilder->leftJoin('user.address', 'address')</code> and
	 *                                              the column to be filtered shall display the addresses city, the query path
	 *                                              would be something like <code>address.city</code>. Use <code>$rootAlias</code>
	 *                                              in order to be able to query on <code>user.address.city</code> (if required).
	 * @param array $filterTypeOptions an array containing all resolved and configured options of the filter type.
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
				$value = $this->unescapeClassName($value);
				if($allowNullValues && $value == $nullValue) {
					$filterEqs[] = $queryBuilder->expr()->isNull($queryPath);
				} else {
					$bindingName = $parameterBindingName . '_select_' . ++$filterBindingCounter;
					$filterEqs[] = $queryBuilder->expr()->like($queryPath, $bindingName);
					$queryBuilder->setParameter($bindingName, '%' . SelectFilterType::inversePregQuote($value));
				}
			}
			return $queryBuilder->expr()->orX()->addMultiple($filterEqs);
		}
		$filterValue = $this->unescapeClassName($filterValue);
		return parent::applyFilter($queryBuilder, $filterValue, $filterRegex, $parameterBindingName, $queryPath, $filterTypeOptions, $rootAlias);
	}

	public function getParent() {
		return SelectFilterType::class;
	}
}