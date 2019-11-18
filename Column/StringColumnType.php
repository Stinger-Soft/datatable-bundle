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

use StingerSoft\DatatableBundle\Transformer\Nl2BrStringDataTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Renders a string value.
 *
 */
class StringColumnType extends AbstractColumnType  {
	/**
	 * @var Nl2BrStringDataTransformer
	 */
	protected $nl2BrStringDataTransformer;

	public function __construct(Nl2BrStringDataTransformer $nl2BrStringDataTransformer) {
		$this->nl2BrStringDataTransformer = $nl2BrStringDataTransformer;
	}

	/**
	 * @inheritdoc
	 *
	 * @see AbstractColumnType::configureOptions()
	 */
	public function configureOptions(OptionsResolver $resolver, array $tableOptions = array()) {
		$resolver->setDefault('nl2br', false);
		$resolver->setAllowedTypes('nl2br', 'boolean');
	}

	/**
	 * @inheritdoc
	 */
	public function buildData(ColumnInterface $column, array $options) {
		if(isset($options['nl2br']) && $options['nl2br'] === true) {
			$column->addDataTransformer($this->nl2BrStringDataTransformer);
		}
	}
}
