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

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A filter type based on a simple text input field, allowing to enter any value to be filtered by.
 */
class TextFilterType extends AbstractFilterType {

	/**
	 * @inheritdoc
	 */
	public function configureOptions(OptionsResolver $resolver, array $columnOptions = array(), array $tableOptions = array()) {
		$resolver->setDefault('jsTemplate', 'StingerSoftDatatableBundle:Filter:text.json.twig');

		$resolver->setDefault('exclude', null);
		$resolver->setAllowedTypes('exclude', array('null', 'boolean'));

		$resolver->setDefault('exclude', false);
		$resolver->setAllowedTypes('exclude', array('boolean'));

		$resolver->setDefault('exclude_label', null);
		$resolver->setAllowedTypes('exclude_label', array('null', 'string'));

		$resolver->setDefault('case_insensitive', null);
		$resolver->setAllowedTypes('case_insensitive', array('null', 'boolean'));

		$resolver->setDefault('filter_delay', null);
		$resolver->setAllowedTypes('filter_delay', array('null', 'integer'));

		$resolver->setDefault('style_class', 'form-control');
		$resolver->setAllowedTypes('style_class', array('null', 'string'));
	}

	/**
	 * @inheritdoc
	 */
	public function buildView(FilterView $view, FilterInterface $filter, array $options, $dataSource, $queryPath, $rootAlias) {
		$view->vars['exclude'] = $options['exclude'];
		$view->vars['exclude_label'] = $options['exclude_label'];
		$view->vars['case_insensitive'] = $options['case_insensitive'];
		$view->vars['filter_delay'] = $options['filter_delay'];
		$view->vars['style_class'] = $options['style_class'];
	}

	/**
	 * @inheritdoc
	 */
	public function getParent() {
		return FilterType::class;
	}

}