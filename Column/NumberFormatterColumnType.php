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

use Locale;
use NumberFormatter;
use StingerSoft\DatatableBundle\Transformer\NumberFormatterDataTransformer;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Renders a numeric column value using PHPs number format capabilities.
 *
 * @see CurrencyColumnType for rendering currency values.
 */
class NumberFormatterColumnType extends AbstractColumnType {

	/**
	 * @var NumberFormatterDataTransformer
	 */
	protected $transformer;

	public function __construct(NumberFormatterDataTransformer $transformer) {
		$this->transformer = $transformer;
	}

	/**
	 * @inheritdoc
	 */
	public function configureOptions(OptionsResolver $resolver, array $tableOptions = array()) {
		$resolver->setDefault('format_null', true);
		$resolver->setAllowedTypes('format_null', 'boolean');

		$resolver->setRequired('number_formatter_style');
		$resolver->setDefault('number_formatter_style', NumberFormatter::DEFAULT_STYLE);
		$resolver->setAllowedValues('number_formatter_style', array(
			NumberFormatter::PATTERN_DECIMAL,
			NumberFormatter::DECIMAL,
			NumberFormatter::CURRENCY,
			NumberFormatter::PERCENT,
			NumberFormatter::SCIENTIFIC,
			NumberFormatter::SPELLOUT,
			NumberFormatter::ORDINAL,
			NumberFormatter::DURATION,
			NumberFormatter::PATTERN_RULEBASED,
			NumberFormatter::DEFAULT_STYLE,
			NumberFormatter::IGNORE
		));

		$resolver->setDefault('number_formatter_pattern', null);
		$resolver->setAllowedTypes('number_formatter_pattern', array('string', 'null'));
		$resolver->setNormalizer('number_formatter_pattern', function (Options $options, $valueToNormalize) {
			if($valueToNormalize === null) {
				if($options['number_formatter_style'] === NumberFormatter::PATTERN_DECIMAL || $options['number_formatter_style'] === NumberFormatter::PATTERN_RULEBASED) {
					throw new InvalidOptionsException(sprintf('When using "number_formatter_style" with a value of %d ("%s") or %d ("%s"), you must provide a value for the "number_formatter_pattern" option!',
						NumberFormatter::PATTERN_DECIMAL, "NumberFormatter::PATTERN_DECIMAL", NumberFormatter::PATTERN_RULEBASED, "NumberFormatter::PATTERN_RULEBASED"));
				}
			}
			return $valueToNormalize;
		});

		$resolver->setDefault('number_formatter_locale', null);
		$resolver->setAllowedTypes('number_formatter_locale', array('string', 'null'));

		$that = $this;
		$resolver->setNormalizer('number_formatter_locale', function (Options $options, $valueToNormalize) use ($that) {
			if($valueToNormalize === null) {
				return Locale::getDefault();
			}
			return $valueToNormalize;
		});

		$resolver->setRequired('number_formatter_currency');
		$resolver->setAllowedTypes('number_formatter_currency', array('string', 'null'));
		$resolver->setDefault('number_formatter_currency', 'EUR');
		$resolver->setNormalizer('number_formatter_currency', function (Options $options, $valueToNormalize) use ($that) {
			if($valueToNormalize === null) {
				if($options['number_formatter_style'] === NumberFormatter::CURRENCY) {
					throw new InvalidOptionsException(sprintf('When using "number_formatter_style" with a value of %d ("%s"), you must provide a value for the "number_formatter_currency" option!',
						NumberFormatter::CURRENCY, "NumberFormatter::CURRENCY"));
				}
			}
			return $valueToNormalize;
		});
	}

	/**
	 * @inheritdoc
	 */
	public function buildView(ColumnView $view, ColumnInterface $column, array $options) {
		$view->vars['number_formatter_locale'] = $options['number_formatter_locale'];
		$view->vars['number_formatter_style'] = $options['number_formatter_style'];
		$view->vars['number_formatter_pattern'] = $options['number_formatter_pattern'];
	}

	/**
	 * @inheritdoc
	 */
	public function buildData(ColumnInterface $column, array $options) {
		$column->addDataTransformer($this->transformer);
	}
}
