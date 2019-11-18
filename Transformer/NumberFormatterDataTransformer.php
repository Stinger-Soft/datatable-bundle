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

namespace StingerSoft\DatatableBundle\Transformer;

use StingerSoft\DatatableBundle\Column\ColumnInterface;

/**
 * The NumberFormatterDataTransformer automatically formats a columns' value according to a defined locale, style and
 * pattern using PHPs number formatter capabilities for all NumberFormatterColumnType column types.
 *
 * @see \StingerSoft\DatatableBundle\Column\NumberFormatterColumnType the column type that uses this formatter.
 */
class NumberFormatterDataTransformer implements DataTransformerInterface {

	/**
	 * @var string Service Identifier
	 */
	const ID = 'stinger_soft_datatable.transformers.number_formatter';

	/**
	 * @param ColumnInterface $column
	 * @param                 $item
	 * @param mixed           $value
	 *            The value in the original representation
	 * @return mixed The value in the transformed representation
	 */
	public function transform(ColumnInterface $column, $item, $value) {
		$options = $column->getColumnOptions();
		$formatNullValue = $options['format_null'] ?? true;
		if($value === null && !$formatNullValue) {
			return $value;
		}

		$formatter = new \NumberFormatter($options['number_formatter_locale'], $options['number_formatter_style'], $options['number_formatter_pattern']);
		if($options['number_formatter_style'] === \NumberFormatter::CURRENCY) {
			return $formatter->formatCurrency($value, $options['number_formatter_currency']);
		}
		return $formatter->format($value);
	}
}