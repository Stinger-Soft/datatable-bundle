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

class DateTimeFormatterDataTransformer implements DataTransformerInterface {

	/**
	 * @var string Service Identifier
	 */
	const ID = 'stinger_soft_datatable.transformers.datetime_formatter';

	private static $FORMAT_VALUES = array(
		'none' => \IntlDateFormatter::NONE,
		'short' => \IntlDateFormatter::SHORT,
		'medium' => \IntlDateFormatter::MEDIUM,
		'long' => \IntlDateFormatter::LONG,
		'full' => \IntlDateFormatter::FULL,
	);

	public static function getValidFormats() {
		return self::$FORMAT_VALUES;
	}

	/**
	 * {@inheritdoc}
	 */
	public function transform(ColumnInterface $column, $item, $value) {
		return self::doTransform($column->getColumnOptions(), $value);
	}

	public static function doTransform($options, $value) {
		if($value !== null) {
			$timestamp = null;
			$timezone = null;
			if($value instanceof \DateTime) {
				$timestamp = $value->getTimestamp();
				$timezone = PHP_VERSION_ID >= 50500 ? $value->getTimezone() : $value->getTimezone()->getName();
			} else if(is_int($value)) {
				$timestamp = $value;
			} else {
				return $value;
			}

			if(!array_key_exists($options['date_format'], self::$FORMAT_VALUES)) {
				throw new \InvalidArgumentException('date_format must be one of the following values: ' . implode(', ', array_keys(self::$FORMAT_VALUES)));
			}

			if(!array_key_exists($options['time_format'], self::$FORMAT_VALUES)) {
				throw new \InvalidArgumentException('time_format must be one of the following values: ' . implode(', ', array_keys(self::$FORMAT_VALUES)));
			}

			$formatter = \IntlDateFormatter::create(
				$options['locale'],
				self::$FORMAT_VALUES[$options['date_format']],
				self::$FORMAT_VALUES[$options['time_format']],
				$timezone,
				'gregorian' === $options['calendar'] ? \IntlDateFormatter::GREGORIAN : \IntlDateFormatter::TRADITIONAL,
				$options['format']
			);
			$formattedDate = $formatter->format($timestamp);
			return $formattedDate;
		}
		return $value;
	}
}