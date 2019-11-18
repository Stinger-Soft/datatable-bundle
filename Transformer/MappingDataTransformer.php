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
 *  <p>The MappingDataTransformer automatically applies the column value on an associated array and returns the value.
 */
class MappingDataTransformer implements DataTransformerInterface {

	/**
	 * @var string Service Identifier
	 */
	const ID = 'stinger_soft_datatable.transformers.mapping';

	/**
	 * {@inheritDoc}
	 * @see \StingerSoft\DatatableBundle\Transformer\DataTransformerInterface::transform()
	 */
	public function transform(ColumnInterface $column, $item, $value) {
		$options = $column->getColumnOptions();
		if (!isset($options['mapping'])) {
			throw new \InvalidArgumentException('The mapping option must be set!');
		}
		if (is_callable($options['mapping'])) {
			return call_user_func($options['mapping'], $item, $column, $value, $options);
		}
		if (!isset($options['mapping'][$value])) {
			return $options['empty_value'];
		}
		return $options['mapping'][$value];
	}

}