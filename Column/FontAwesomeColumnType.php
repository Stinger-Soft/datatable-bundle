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

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Renders a font awesome icon.
 */
class FontAwesomeColumnType extends AbstractColumnType {

	/**
	 * @inheritdoc
	 *
	 * @see \StingerSoft\DatatableBundle\Column\AbstractColumnType::configureOptions()
	 */
	public function configureOptions(OptionsResolver $resolver, array $tableOptions = array()) {
		$resolver->setDefault('mapped', true);
		$resolver->setDefault('no_value_icon', null);
		$resolver->setAllowedTypes('no_value_icon', array(
			'null',
			'string'
		));
		$resolver->setDefault('template', 'StingerSoftDatatableBundle:Column:fontawesomeicon.html.twig');
	}

	/**
	 *
	 * {@inheritdoc}
	 */
	public function getParent() {
		return TemplatedColumnType::class;
	}
}