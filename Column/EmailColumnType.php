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
 * Renders an email address as an HTML link.
 */
class EmailColumnType extends AbstractColumnType {

	/**
	 * {@inheritdoc}
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public function configureOptions(OptionsResolver $resolver, array $tableOptions = array()) {
		$resolver->setDefault('route', function ($item, $value) {
			return 'mailto:' . $value;
		});
	}
}