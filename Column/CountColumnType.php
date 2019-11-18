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
 * Displays the count of an array or collections field instead of displaying the data itself
 */
class CountColumnType extends AbstractColumnType {

	/**
	 * {@inheritdoc}
	 * @see \StingerSoft\DatatableBundle\Column\AbstractColumnType::configureOptions()
	 */
	public function configureOptions(OptionsResolver $resolver, array $tableOptions = array()) {
		$resolver->setDefault('orderable', false);
		$resolver->setDefault('searchable', false);

		$propAccessor = $this->getPropertyAccessor();
		$resolver->setDefault('value_delegate', function ($item, $path) use ($propAccessor) {
			return count($propAccessor->getValue($item, $path));
		});
	}

	/**
	 * {@inheritDoc}
	 * @see \StingerSoft\DatatableBundle\Column\AbstractColumnType::getParent()
	 */
	public function getParent() {
		return IntegerColumnType::class;
	}
}