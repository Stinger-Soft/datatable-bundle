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
use Symfony\Component\PropertyAccess\PropertyAccessor;

class EntitySelectFilterType extends SelectFilterType {

	protected $propertyAccessor;

	public function __construct() {
		$this->propertyAccessor = new PropertyAccessor();
	}

	public function configureOptions(OptionsResolver $resolver, array $columnOptions = array(), array $tableOptions = array()) {
		parent::configureOptions($resolver, $columnOptions);

		$resolver->setRequired('data_class');
		$resolver->setDefault('label_property', null);
		$resolver->setAllowedTypes('label_property', array('null', 'string'));

		$that = $this;
		$resolver->setDefault('label_function', function (Options $options, $previousValue) use ($that) {
			if($previousValue === null) {
				$dataClass = $options['data_class'];
				$labelProperty = $options['label_property'];
				return function ($parsedData, $rawData, $filter, $options, $queryBuilder, $queryPath) use ($dataClass, $labelProperty, $that) {
					/** @var QueryBuilder $queryBuilder */
					$object = $queryBuilder->getEntityManager()->find($dataClass, $parsedData);
					$label = $parsedData;
					if($object) {
						if($labelProperty !== null) {
							$label = $that->propertyAccessor->getValue($object, $labelProperty);
						} else {
							$label = (string)$object;
						}
					}
					return $label;
				};
			}
			return $previousValue;
		});
	}
}