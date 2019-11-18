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
 * Renders a date time column using the Moment.js library and therefor supports formatting.
 */
class MomentDateTimeColumnType extends AbstractColumnType {

	/**
	 * {@inheritdoc}
	 * @see \StingerSoft\DatatableBundle\Column\AbstractColumnType::configureOptions()
	 */
	public function configureOptions(OptionsResolver $resolver, array $tableOptions = array()) {
		$resolver->setDefault('date_format', 'L LTS');
		$resolver->setAllowedTypes('date_format', array(
			'string'
		));
		$resolver->setDefault('js_column_template', 'StingerSoftDatatableBundle:Column:datetime_moment.js.twig');

	}

	/**
	 * {@inheritdoc}
	 * @see \StingerSoft\DatatableBundle\Column\AbstractColumnType::buildView()
	 */
	public function buildView(ColumnView $view, ColumnInterface $column, array $options) {
//		$view->template = 'StingerSoftDatatableBundle:Column:datetime_moment.js.twig';
		$view->vars['date_format'] = $options['date_format'];
	}
}