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
 * The ChildRowTriggerColumnType is the base type for specific implementations of child row trigger columns.
 *
 * Column Types inheriting this base class are supposed to trigger a so-called child row, containing any additional details.
 * Depending on the type of the implementing class, additional information can be loaded in an asynchronous or synchronous manner.
 *
 * The only provides the option the column type provides is a jQuery selector for defining which elements actually trigger a display of a detail row.
 * Any inheriting class must take care of adding any additional options as required.
 *
 * @see AsyncChildRowTriggerColumnType for asynchronous detail column trigger type.
 */
abstract class ChildRowTriggerColumnType extends AbstractColumnType {

	/**
	 * @inheritdoc
	 */
	public function configureOptions(OptionsResolver $resolver, array $tableOptions = array()) {
		$resolver->setDefault('width', '16px');

		$resolver->setRequired('details_trigger_selector');
		$resolver->setDefault('details_trigger_selector', '.table-childrow-expander');
		$resolver->setAllowedTypes('details_trigger_selector', array('string'));

		$resolver->setDefault('label', null);

		$resolver->setDefault('toggleable', false);
		$resolver->setDefault('toggle_visible', false);

		$resolver->setDefault('searchable', false);
		$resolver->setAllowedValues('searchable', false);

		$resolver->setDefault('orderable', false);
		$resolver->setAllowedValues('orderable', false);

		$resolver->setDefault('filterable', false);
		$resolver->setAllowedValues('filterable', false);

		$resolver->setDefault('search_server_delegate', null);
		$resolver->setAllowedValues('search_server_delegate', null);

		$resolver->setDefault('order_server_delegate', null);
		$resolver->setAllowedValues('order_server_delegate', null);

		$resolver->setDefault('filter_server_delegate', null);
		$resolver->setAllowedValues('filter_server_delegate', null);

	}

	/**
	 * @inheritdoc
	 */
	public function buildView(ColumnView $view, ColumnInterface $column, array $options) {
		$view->vars['details_trigger_selector'] = $options['details_trigger_selector'];
	}

		/**
	 * {@inheritdoc}
	 * @see \StingerSoft\DatatableBundle\Column\AbstractColumnType::getParent()
	 */
	public final function getParent() {
		return ColumnType::class;
	}

}