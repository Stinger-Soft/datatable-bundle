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

use StingerSoft\DatatableBundle\Transformer\StringFormatterDataTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Renders the data value of a cell using a formatted string.
 *
 * The parameters and string format can be defined via the options according to the PHP printf / sprintf / vsprintf syntax.
 */
class FormattedStringColumnType extends AbstractColumnType {

	/**
	 * @var StringFormatterDataTransformer
	 */
	protected $transformer;

	public function __construct(StringFormatterDataTransformer $transformer) {
		$this->transformer = $transformer;
	}


	/**
	 * {@inheritdoc}
	 * @see \StingerSoft\DatatableBundle\Column\AbstractColumnType::configureOptions()
	 */
	public function configureOptions(OptionsResolver $resolver, array $tableOptions = array()) {
		$resolver->setDefault('string_format', '%s');
		$resolver->setAllowedTypes('string_format', array('string', 'callable'));

		$resolver->setDefault('string_format_parameters', null);
		$resolver->setAllowedTypes('string_format_parameters', array('null', 'array', 'callable'));
	}

	/**
	 * @inheritdoc
	 */
	public function buildData(ColumnInterface $column, array $options) {
		$column->addDataTransformer($this->transformer);
	}

	/**
	 * {@inheritDoc}
	 * @see \StingerSoft\DatatableBundle\Column\AbstractColumnType::getParent()
	 */
	public function getParent() {
		return StringColumnType::class;
	}

}