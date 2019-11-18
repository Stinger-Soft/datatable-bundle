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

use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Renders a boolean value as "yes" or "no" string.
 */
class YesNoColumnType extends AbstractColumnType {

	const DISPLAY_ICON_ONLY = 'icon-only';
	const DISPLAY_LABEL_ONLY = 'label-only';
	const DISPLAY_ICON_WITH_LABEL = 'icon-with-label';
	const DISPLAY_ICON_WITH_TOOLTIP = 'icon-with-tooltip';

	/**
	 * @inheritdoc
	 * @see \StingerSoft\DatatableBundle\Column\AbstractColumnType::configureOptions()
	 */
	public function configureOptions(OptionsResolver $resolver, array $tableOptions = array()) {
		$resolver->setDefault('yes_label', 'stinger_soft_datatables.column_types.yes_no.yes');
		$resolver->setAllowedTypes('yes_label', array('null', 'string'));

		$resolver->setDefault('no_label', 'stinger_soft_datatables.column_types.yes_no.no');
		$resolver->setAllowedTypes('no_label', array('null', 'string'));

		$resolver->setDefault('yes_icon', 'fa fa-fw fa-check');
		$resolver->setAllowedTypes('yes_icon', array('null', 'string'));

		$resolver->setDefault('no_icon', 'fa fa-fw fa-times');
		$resolver->setAllowedTypes('no_icon', array('null', 'string'));

		$resolver->setDefault('display_type', YesNoColumnType::DISPLAY_ICON_WITH_TOOLTIP);
		$resolver->setAllowedValues('display_type', array(
			YesNoColumnType::DISPLAY_ICON_WITH_TOOLTIP, YesNoColumnType::DISPLAY_ICON_WITH_LABEL,
			YesNoColumnType::DISPLAY_LABEL_ONLY, YesNoColumnType::DISPLAY_ICON_ONLY));

		$resolver->setNormalizer('display_type', function (Options $options, $value) {
			if($value === YesNoColumnType::DISPLAY_ICON_ONLY || $value === YesNoColumnType::DISPLAY_ICON_WITH_LABEL || $value === YesNoColumnType::DISPLAY_ICON_WITH_TOOLTIP) {
				if($options['yes_icon'] === null || $options['no_icon'] === null) {
					throw new InvalidOptionsException(sprintf('When using "display_type" with a value of "%s" you must set "yes_icon" and "no_icon"!', $value));
				}
			}
			if($value === YesNoColumnType::DISPLAY_LABEL_ONLY || $value === YesNoColumnType::DISPLAY_ICON_WITH_LABEL || $value === YesNoColumnType::DISPLAY_ICON_WITH_TOOLTIP) {
				if($options['yes_label'] === null || $options['no_label'] === null) {
					throw new InvalidOptionsException(sprintf('When using "display_type" with a value of "%s" you must set "yes_label" and "no_label"!', $value));
				}
			}
			return $value;
		});

		$resolver->setDefault('label_translation_domain', 'StingerSoftDatatableBundle');
		$resolver->setAllowedValues('label_translation_domain', function ($value) {
			if(is_string($value)) return true;
			if($value === null) return true;
			if($value === false) return true;
			return false;
		});
		$resolver->setDefault('js_column_template', 'StingerSoftDatatableBundle:Column:yesno.js.twig');
	}

	/**
	 * {@inheritdoc}
	 * @see \StingerSoft\DatatableBundle\Column\AbstractColumnType::buildView()
	 */
	public function buildView(ColumnView $view, ColumnInterface $column, array $options) {
//		$view->template = 'StingerSoftDatatableBundle:Column:yesno.js.twig';

		if($options['label_translation_domain'] === null) {
			$view->vars['label_translation_domain'] = $options['translation_domain'];
		} else {
			$view->vars['label_translation_domain'] = $options['label_translation_domain'];
		}
		$view->vars['yes_label'] = $options['yes_label'];
		$view->vars['no_label'] = $options['no_label'];
		$view->vars['yes_icon'] = $options['yes_icon'];
		$view->vars['no_icon'] = $options['no_icon'];
		$view->vars['display_type'] = $options['display_type'];
	}
}