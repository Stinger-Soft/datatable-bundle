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

use StingerSoft\DatatableBundle\Transformer\CurrencyFormatterDataTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Renders a currency formatted value in a column using PHPs number format capabilities and a definable currency.
 *
 * @see NumberFormatterColumnType for formatting numbers in a more generic way
 */
class CurrencyColumnType extends AbstractColumnType {

	protected $transformer;

	public function __construct(CurrencyFormatterDataTransformer $transformer) {
		$this->transformer = $transformer;
	}

	/**
	 * @inheritdoc
	 *
	 * @see AbstractColumnType::configureOptions()
	 */
	public function configureOptions(OptionsResolver $resolver, array $tableOptions = array()) {
		$resolver->setDefault('number_formatter_style', \NumberFormatter::CURRENCY);

		$resolver->setDefault('currency', 'EUR');
		$resolver->setAllowedTypes('currency', array('string', 'callable'));

		$resolver->setDefault('format_null', true);
		$resolver->setAllowedTypes('format_null', 'boolean');
	}

	/**
	 * @inheritdoc
	 */
	public function buildData(ColumnInterface $column, array $options) {
		$column->resetDataTransformers();
		$column->addDataTransformer($this->transformer);
	}

	/**
	 * @inheritdoc
	 */
	public function getParent() {
		return NumberFormatterColumnType::class;
	}
}