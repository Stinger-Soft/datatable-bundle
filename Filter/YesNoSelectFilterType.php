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

namespace StingerSoft\DatatableBundle\Filter;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class YesNoSelectFilterType extends SelectFilterType {

	public function configureOptions(OptionsResolver $resolver, array $columnOptions = array(), array $tableOptions = array()) {
		parent::configureOptions($resolver, $columnOptions);
		$resolver->setRequired('yes_value');
		$resolver->setDefault('label_function', function (Options $options, $previousValue) {
			if($previousValue === null) {
				return function ($parsedData) use ($options) {
					return $parsedData == $options['yes_value'] ? 'stinger_soft_datatables.column_types.yes_no.yes' : 'stinger_soft_datatables.column_types.yes_no.no';
				};
			}
			return $previousValue;
		});
		$resolver->setDefault('label_function_translation_domain', 'StingerSoftDatatableBundle');
	}

}