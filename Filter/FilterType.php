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

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Base type which includes the default filter configuration and default settings generation
 */
final class FilterType extends AbstractFilterType {

	/**
	 * @inheritdoc
	 */
	public function configureOptions(OptionsResolver $resolver, array $columnOptions = array(), array $tableOptions = array()) {
		$resolver->setRequired('type');
		$resolver->setDefault('type', FilterTypeInterface::FILTER_TYPE_TEXT);
		$resolver->setAllowedValues('type', array(
			FilterTypeInterface::FILTER_TYPE_TEXT,
			FilterTypeInterface::FILTER_TYPE_SELECT,
			FilterTypeInterface::FILTER_TYPE_MULTI_SELECT,
			FilterTypeInterface::FILTER_TYPE_AUTO_COMPLETE,
			FilterTypeInterface::FILTER_TYPE_DATE,
			FilterTypeInterface::FILTER_TYPE_RANGE_NUMBER,
			FilterTypeInterface::FILTER_TYPE_RANGE_NUMBER_SLIDER,
			FilterTypeInterface::FILTER_TYPE_RANGE_DATE,
			FilterTypeInterface::FILTER_TYPE_CUSTOM_FUNCTION,
			FilterTypeInterface::FILTER_TYPE_MULTI_SELECT_CUSTOM_FUNCTION
		));

		$resolver->setRequired('jsTemplate');
		$resolver->setDefault('jsTemplate', 'StingerSoftDatatableBundle:Filter:filter.json.twig');
		$resolver->setAllowedTypes('jsTemplate', 'string');

		$resolver->setDefault('filter_server_delegate', null);
		$resolver->setAllowedTypes('filter_server_delegate', array('null', 'callable'));

		$resolver->setDefault('translation_domain', 'StingerSoftDatatableBundle');
		$resolver->setAllowedTypes('translation_domain', array(
			'string',
			'null',
			'boolean'
		));

		$resolver->setDefault('filter_default_label', 'stinger_soft_datatables.filter.placeholder.input');
		$resolver->setAllowedTypes('filter_default_label', array(
			'string',
			'null'
		));

		$resolver->setDefault('filter_reset_button_text', 'stinger_soft_datatables.filter.reset');
		$resolver->setAllowedValues('filter_reset_button_text', function ($value) {
			if($value === null) {
				// null is default and means js default will be used
				return true;
			}
			if(is_string($value)) {
				// any sting is considered as valid
				return true;
			}
			if($value === false) {
				// false is used for completely hiding the reset button
				return true;
			}
			return false;
		});

		$resolver->setDefault('filter_container_selector', null);
		$resolver->setAllowedTypes('filter_container_selector', array('null', 'string'));

		$resolver->setDefault('filter_container_class', null);
		$resolver->setAllowedTypes('filter_container_class', array('null', 'string'));

		$resolver->setDefault('filter_container_id', null);
		$resolver->setAllowedTypes('filter_container_id', array('null', 'string'));

		$resolver->setDefault('filter_plugin_options', null);
		$resolver->setAllowedTypes('filter_plugin_options', array('null', 'array'));

		$resolver->setDefault('column_data_type', null);
		$resolver->setAllowedValues('column_data_type', array(
			null,
			FilterTypeInterface::COLUMN_DATA_TYPE_TEXT,
			FilterTypeInterface::COLUMN_DATA_TYPE_HTML,
			FilterTypeInterface::COLUMN_DATA_TYPE_RENDERED_HTML
		));

		$resolver->setDefault('text_data_delimiter', null);
		$resolver->setAllowedTypes('text_data_delimiter', array('null', 'string'));

		$resolver->setDefault('html_data_type', function (Options $options) {
			if('html' === $options['column_data_type']) {
				return 'text';
			}
			return null;
		});
		$resolver->setAllowedValues('html_data_type', array(
			null,
			FilterTypeInterface::HTML_DATA_TYPE_TEXT,
			FilterTypeInterface::HTML_DATA_TYPE_VALUE,
			FilterTypeInterface::HTML_DATA_TYPE_ID,
			FilterTypeInterface::HTML_DATA_TYPE_SELECTOR
		));

		$resolver->setDefault('filter_validate_empty', true);
		$resolver->setAllowedTypes('filter_validate_empty', ['boolean']);

		$resolver->setDefault('filter_validation_delegate', null);
		$resolver->setAllowedTypes('filter_validation_delegate', ['null', 'callable', \Closure::class]);

		$resolver->setDefault('html_data_selector', null);
		$resolver->setAllowedTypes('html_data_selector', array('string', 'null'));
		$resolver->setNormalizer('html_data_selector', function (Options $options, $value) {
			$htmlDataType = $options['html_data_type'];
			if($htmlDataType === FilterTypeInterface::HTML_DATA_TYPE_SELECTOR) {
				if(empty($value)) {
					throw new InvalidOptionsException(sprintf('When using "html_data_type" with a value of "%s" you must provide a string for "html_data_selector" option, but null / empty string given!', FilterTypeInterface::HTML_DATA_TYPE_SELECTOR));
				}

				return $value;
			}
			return null;
		});

		$resolver->setDefault('html5_data', null);
		$resolver->setAllowedValues('html5_data', array(
			null,
			FilterTypeInterface::HTML5_DATA_FILTER,
			FilterTypeInterface::HTML5_DATA_ORDER,
			FilterTypeInterface::HTML5_DATA_SEARCH,
			FilterTypeInterface::HTML5_DATA_SORT,
		));

		$resolver->setDefault('sort_as', null);
		$resolver->setAllowedValues('sort_as', array(
			null,
			FilterTypeInterface::SORT_AS_ALPHA,
			FilterTypeInterface::SORT_AS_NUM,
			FilterTypeInterface::SORT_AS_ALPHA_NUM,
			FilterTypeInterface::SORT_AS_NONE,
			FilterTypeInterface::SORT_AS_CUSTOM
		));

		$resolver->setDefault('sort_as_custom_func', null);
		$resolver->setAllowedTypes('sort_as_custom_func', array('string', 'null'));
		$resolver->setNormalizer('sort_as_custom_func', function (Options $options, $value) {
			$sortAs = $options['sort_as'];
			if($sortAs === FilterTypeInterface::SORT_AS_CUSTOM) {
				if(empty($value)) {
					throw new InvalidOptionsException(sprintf('When using "sort_as" with a value of "%s" you must provide a string for "sort_as_custom_func" option, but null / empty string given!', FilterTypeInterface::SORT_AS_CUSTOM));
				}

				return $value;
			}
			return null;
		});

		$resolver->setDefault('sort_order', null);
		$resolver->setAllowedValues('sort_order', array(
			null,
			FilterTypeInterface::SORT_ORDER_ASC,
			FilterTypeInterface::SORT_ORDER_DESC,
		));

		$resolver->setDefault('filter_match_mode', null);
		$resolver->setAllowedValues('filter_match_mode', array(
			null,
			FilterTypeInterface::FILTER_MATCH_MODE_CONTAINS,
			FilterTypeInterface::FILTER_MATCH_MODE_EXACT,
			FilterTypeInterface::FILTER_MATCH_MODE_STARTS_WITH,
			FilterTypeInterface::FILTER_MATCH_MODE_REGEX,
		));

		$resolver->setDefault('reset_button_style_class', 'btn btn-default');
		$resolver->setAllowedTypes('reset_button_style_class', array('null', 'string'));

		$resolver->setDefault('pre_filtered_value', null);

		$resolver->setDefault('auto_focus', true);
		$resolver->setAllowedTypes('auto_focus', 'boolean');

		$resolver->setDefault('highlight_mode', FilterTypeInterface::HIGHLIGHT_MODE_AUTO);
		$resolver->setAllowedValues('highlight_mode', array(
			FilterTypeInterface::HIGHLIGHT_MODE_AUTO,
			FilterTypeInterface::HIGHLIGHT_MODE_MANUAL,
		));
	}

	/**
	 * {@inheritdoc}
	 *
	 */
	final public function getParent() {
		return null;
	}

	/**
	 * @inheritdoc
	 */
	public function buildView(FilterView $view, FilterInterface $filter, array $options, $dataSource, $queryPath, $rootAlias) {
		$view->jsTemplate = $options['jsTemplate'];
		$view->vars = array_replace($view->vars, array(
			'type'                      => $options['type'],
			'translation_domain'        => $options['translation_domain'],
			'filter_default_label'      => $options['filter_default_label'],
			'filter_reset_button_text'  => $options['filter_reset_button_text'],
			'filter_container_selector' => $options['filter_container_selector'],
			'filter_container_class'    => $options['filter_container_class'],
			'filter_container_id'       => $options['filter_container_id'],
			'filter_plugin_options'     => $options['filter_plugin_options'],
			'column_data_type'          => $options['column_data_type'],
			'text_data_delimiter'       => $options['text_data_delimiter'],
			'html_data_type'            => $options['html_data_type'],
			'html_data_selector'        => $options['html_data_selector'],
			'html5_data'                => $options['html5_data'],
			'sort_as'                   => $options['sort_as'],
			'sort_as_custom_func'       => $options['sort_as_custom_func'],
			'sort_order'                => $options['sort_order'],
			'filter_match_mode'         => $options['filter_match_mode'],
			'reset_button_style_class'  => $options['reset_button_style_class'],
			'pre_filtered_value'        => $options['pre_filtered_value'],
			'highlight_mode'            => $options['highlight_mode'],
			'auto_focus'                => $options['auto_focus'],
		));
	}
}