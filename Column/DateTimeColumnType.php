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

use StingerSoft\DatatableBundle\Transformer\DateTimeFormatterDataTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Basic implementation to render a column representing a php DateTime object with support for Symfony based date formatting.
 */
class DateTimeColumnType extends AbstractColumnType {

	protected $transformer;

	public function __construct(DateTimeFormatterDataTransformer $transformer) {
		$this->transformer = $transformer;
	}

	/**
	 * {@inheritdoc}
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public function configureOptions(OptionsResolver $resolver, array $tableOptions = array()) {
		$resolver->setDefault('locale', null);

		$dateTimeFormatValidator = function ($valueToCheck) {
			return array_key_exists($valueToCheck, DateTimeFormatterDataTransformer::getValidFormats());
		};
		$resolver->setDefault('time_format', 'medium');
		$resolver->setAllowedValues('time_format', $dateTimeFormatValidator);
		$resolver->setDefault('date_format', 'medium');
		$resolver->setAllowedValues('date_format', $dateTimeFormatValidator);

		$resolver->setDefault('format', null);
		$resolver->setDefault('calendar', 'gregorian');
		$resolver->setAllowedValues('calendar', array('gregorian', 'traditional'));

		$resolver->setDefault('order_client_delegate', function ($item, $path, $value) {
			if($value instanceof \DateTime) {
				return $value->getTimestamp();
			}
			return null;
		});
		$resolver->setDefault('search_client_delegate', function ($item, $path, $value) {
			if($value instanceof \DateTime) {
				return $value->format('d.m.Y');
			}
			return null;
		});
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildView(ColumnView $view, ColumnInterface $column, array $options) {
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildData(ColumnInterface $column, array $options) {
		$column->addDataTransformer($this->transformer);
	}
}