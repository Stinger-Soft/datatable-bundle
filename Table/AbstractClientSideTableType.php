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

namespace StingerSoft\DatatableBundle\Table;

use Symfony\Component\OptionsResolver\OptionsResolver;

class AbstractClientSideTableType extends AbstractTableType {

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefault('serverSide', false);
		$resolver->setDefault('reload_enabled', false);
		$resolver->setDefault('serverSide', false);
		$resolver->setDefault('processing', false);
	}

}