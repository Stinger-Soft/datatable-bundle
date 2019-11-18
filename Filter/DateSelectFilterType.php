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
use StingerSoft\DatatableBundle\Transformer\DateTimeFormatterDataTransformer;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateSelectFilterType extends SelectFilterType {

	public function configureOptions(OptionsResolver $resolver, array $columnOptions = array(), array $tableOptions = array()) {
		parent::configureOptions($resolver, $columnOptions);
		$resolver->setDefault('value_function', function ($rawData) {
			$date = date_create($rawData);
			return $date->getTimestamp();
		});
		$resolver->setDefault('label_function', function (Options $otherOptions, $previousDefault) {
			if($previousDefault === null) {
				$options = array(
					'date_format' => $otherOptions['date_format'],
					'time_format' => $otherOptions['time_format'],
					'format' => $otherOptions['format'],
					'calendar' => $otherOptions['calendar'],
					'locale' => $otherOptions['locale'],
				);
				return function ($parsedData) use ($options) {
					$formattedDate = DateTimeFormatterDataTransformer::doTransform($options, $parsedData);
					return $formattedDate;
				};
			}
			return $previousDefault;
		});

		$dateTimeFormatValidator = function ($valueToCheck) {
			return in_array($valueToCheck, array_keys(DateTimeFormatterDataTransformer::getValidFormats()));
		};
		$resolver->setDefault('time_format', 'none');
		$resolver->setAllowedValues('time_format', $dateTimeFormatValidator);
		$resolver->setDefault('date_format', 'medium');
		$resolver->setAllowedValues('date_format', $dateTimeFormatValidator);
		$resolver->setDefault('format', null);
		$resolver->setDefault('calendar', 'gregorian');
		$resolver->setAllowedValues('calendar', array('gregorian', 'traditional'));
		$resolver->setDefault('locale', null);
	}

	public function applyFilter(QueryBuilder $queryBuilder, $filterValue, $filterRegex, $parameterBindingName, $queryPath, $filterTypeOptions, $rootAlias) {
		if($filterRegex == true) {
			$filterValues = explode('|', $filterValue);
			$filterEqs = array();
			$filterBindingCounter = 0;
			foreach($filterValues as $value) {
				$dateValue = new \DateTime();
				$dateValue->setTimestamp($value);
				$bindingName = $parameterBindingName . '_select_' . ++$filterBindingCounter;
				$filterEqs[] = $queryBuilder->expr()->eq($queryPath, $bindingName);
				$queryBuilder->setParameter($bindingName, $dateValue);
			}
			return $queryBuilder->expr()->orX()->addMultiple($filterEqs);
		} else {
			return parent::applyFilter($queryBuilder, $filterValue, $filterRegex, $parameterBindingName, $queryPath, $filterTypeOptions, $rootAlias);
		}
	}

}