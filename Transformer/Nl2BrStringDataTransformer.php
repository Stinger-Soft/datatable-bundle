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
use StingerSoft\DatatableBundle\Column\StringColumnType;

/**
 * The Nl2BrStringDataTransformer is automatically appended to all columns of type StringColumnType
 * and allows the value inside the column to replace newline characters by HTML br tags.
 *
 * @see StringColumnType the column type that uses this formatter.
 */
class Nl2BrStringDataTransformer implements DataTransformerInterface {

	/**
	 * @var string Service Identifier
	 */
	const ID = 'stinger_soft_datatable.transformers.nl2br';

	/**
	 * @param ColumnInterface $column
	 * @param                 $item
	 * @param mixed           $value
	 *            The value in the original representation
	 * @return mixed The value in the transformed representation
	 */
	public function transform(ColumnInterface $column, $item, $value) {
		$options = $column->getColumnOptions();
		if (isset($options['nl2br'])) {
			return nl2br($value);
		}
		return $value;
	}
}