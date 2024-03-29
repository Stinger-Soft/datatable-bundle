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

use StingerSoft\DatatableBundle\Transformer\MappingDataTransformer;
use StingerSoft\DatatableBundle\Transformer\TranslateStringDataTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MappingColumnType extends AbstractColumnType {

	/**
	 * @var MappingDataTransformer
	 */
	protected $mappingDataTransformer;

	/**
	 * @var TranslateStringDataTransformer
	 */
	protected $translateStringDataTransformer;

	public function __construct(MappingDataTransformer $mappingDataTransformer, TranslateStringDataTransformer $translateStringDataTransformer) {
		$this->mappingDataTransformer = $mappingDataTransformer;
		$this->translateStringDataTransformer = $translateStringDataTransformer;
	}

	/**
	 * @inheritdoc
	 *
	 * @see AbstractColumnType::configureOptions()
	 */
	public function configureOptions(OptionsResolver $resolver, array $tableOptions = array()) {
		$resolver->setRequired('mapping');
		$resolver->setAllowedTypes('mapping', array('array', 'callable'));

		$resolver->setDefault('value_translation_domain', null);
		$resolver->setAllowedTypes('value_translation_domain', array('string', 'boolean', 'null'));

	}

	/**
	 * @inheritdoc
	 */
	public function buildData(ColumnInterface $column, array $options) {
		$column->addDataTransformer($this->mappingDataTransformer);

		if($options['value_translation_domain'] !== false) {
			$column->addDataTransformer($this->translateStringDataTransformer, true);
		}
	}
}