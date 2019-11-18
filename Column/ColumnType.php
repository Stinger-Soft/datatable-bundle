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

use StingerSoft\DatatableBundle\Filter\TextFilterType;
use StingerSoft\DatatableBundle\Transformer\LinkDataTransformer;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Base type which includes the default column configuration and default settings generation
 */
final class ColumnType extends AbstractColumnType {

	/**
	 * @var LinkDataTransformer
	 */
	protected $linkTransformer;

	public function __construct(LinkDataTransformer $linkTransformer) {
		$this->linkTransformer = $linkTransformer;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \StingerSoft\DatatableBundle\Column\AbstractColumnType::configureOptions()
	 */
	public function configureOptions(OptionsResolver $resolver, array $tableOptions = array()) {

		$resolver->setDefault('path', null);
		$resolver->setAllowedTypes('path', array('null', 'string'));

		$resolver->setDefault('label', '');
		$resolver->setAllowedTypes('label', array(
			'string',
			'null'
		));

		$resolver->setDefault('translation_domain', null);
		$resolver->setAllowedTypes('translation_domain', array(
			'string',
			'null',
			'boolean'
		));

		$resolver->setDefault('abbreviation_label', null);
		$resolver->setAllowedTypes('abbreviation_label', array(
			'string',
			'null'
		));

		$resolver->setDefault('abbreviation_translation_domain', null);
		$resolver->setAllowedTypes('abbreviation_translation_domain', array(
			'string',
			'boolean',
			'null'
		));

		$resolver->setDefault('tooltip_label', null);
		$resolver->setAllowedTypes('tooltip_label', array(
			'string',
			'null'
		));

		$resolver->setDefault('tooltip_translation_domain', null);
		$resolver->setAllowedTypes('tooltip_translation_domain', array(
			'string',
			'boolean',
			'null'
		));

		$resolver->setDefault('orderSequence', array(
			'asc',
			'desc',
			''
		));
		$resolver->setAllowedTypes('orderSequence', array(
			'array',
			'null'
		));

		$resolver->setDefault('empty_value', null);
		$resolver->setAllowedTypes('empty_value', array(
			'string',
			'null'
		));

		$resolver->setDefault('searchable', true);
		$resolver->setAllowedValues('searchable', array(
			true,
			false,
			AbstractColumnType::CLIENT_SIDE_ONLY,
			AbstractColumnType::SERVER_SIDE_ONLY
		));
		$resolver->setDefault('filterable', false);
		$resolver->setAllowedValues('filterable', array(
			true,
			false,
			AbstractColumnType::CLIENT_SIDE_ONLY,
			AbstractColumnType::SERVER_SIDE_ONLY
		));

		$resolver->setDefault('filter_type', function (Options $options) {
			if(false !== $options['filterable']) {
				return TextFilterType::class;
			}
			return null;
		});
		$resolver->setAllowedTypes('filter_type', array(
			'null',
			'string'
		));
		$resolver->setNormalizer('filter_type', function (Options $options, $value) {
			if($value !== null && !$options['filterable']) {
				throw new InvalidOptionsException(sprintf('When using "filter_type" with a value of "%s" you must set "filterable" to true!', $value));
			}
			return $value;
		});

		$resolver->setDefault('filter_options', array());
		$resolver->setAllowedTypes('filter_options', array(
			'array'
		));

		$resolver->setDefault('orderable', true);
		$resolver->setAllowedValues('orderable', array(
			true,
			false,
			AbstractColumnType::CLIENT_SIDE_ONLY,
			AbstractColumnType::SERVER_SIDE_ONLY
		));

		$resolver->setDefault('route', null);
		$resolver->setAllowedTypes('route', array(
			'string',
			'array',
			'callable',
			'null'
		));
		$resolver->setNormalizer('route', function (Options $options, $value) {
			if(is_array($value)) {
				if(!array_key_exists('route', $value)) {
					throw new InvalidOptionsException('When using "route" option with an array value, you must add a "route" key pointing to the route to be used!');
				}
				if(!array_key_exists('route_params', $value)) {
					$value['route_params'] = array();
				}
			}
			return $value;
		});

		$resolver->setDefault('query_path', null);
		$resolver->setAllowedTypes('query_path', array(
			'null',
			'string'
		));

		$resolver->setDefault('filter_query_path', null);
		$resolver->setAllowedTypes('filter_query_path', array(
			'null',
			'string'
		));

		$resolver->setDefault('class_name', null);
		$resolver->setAllowedTypes('class_name', array(
			'string',
			'null'
		));

		$resolver->setDefault('visible', true);
		$resolver->setAllowedTypes('visible', array(
			'boolean'
		));

		$resolver->setDefault('toggleable', true);
		$resolver->setAllowedTypes('toggleable', array(
			'boolean'
		));

		$resolver->setDefault('toggle_visible', true);
		$resolver->setAllowedTypes('toggle_visible', array(
			'boolean'
		));

		$resolver->setDefault('column_group', null);
		$resolver->setAllowedValues('column_group', function ($value) use ($tableOptions) {
			if($value === null) {
				return true;
			}
			return isset($tableOptions['column_groups']) && isset($tableOptions['column_groups'][$value]);
		});

		$resolver->setDefault('search_server_delegate', null);
		$resolver->setAllowedTypes('search_server_delegate', array(
			'null',
			'callable'
		));
		$resolver->setDefault('filter_server_delegate', null);
		$resolver->setAllowedTypes('filter_server_delegate', array(
			'null',
			'callable'
		));
		$resolver->setDefault('search_client_delegate', null);
		$resolver->setAllowedTypes('search_client_delegate', array(
			'null',
			'string',
			'callable'
		));
		$resolver->setDefault('order_server_delegate', null);
		$resolver->setAllowedTypes('order_server_delegate', array(
			'null',
			'callable'
		));
		$resolver->setDefault('order_client_delegate', null);
		$resolver->setAllowedTypes('order_client_delegate', array(
			'null',
			'string',
			'callable'
		));
		$resolver->setDefault('value_delegate', null);
		$resolver->setAllowedTypes('value_delegate', array(
			'null',
			'callable'
		));
		$that = $this;
		$resolver->setNormalizer('value_delegate', function (Options $options, $value) use ($that) {
			if($value === null) {
				$value = function ($item, $path, $options) use ($that) {
					return $that->generateItemValue($item, $path, $options);
				};
			}
			return $value;
		});

		$resolver->setDefault('position', null);
		$resolver->setAllowedTypes('position', array(
			'null',
			'string',
			'array'
		));
		$resolver->setAllowedValues('position', function ($valueToCheck) {
			if(is_string($valueToCheck)) {
				return !($valueToCheck !== 'last' && $valueToCheck !== 'first');
			}
			if(is_array($valueToCheck)) {
				return !(!isset($valueToCheck['before']) && !isset($valueToCheck['after']));
			}
			if($valueToCheck === null)
				return true;
			return false;
		});

		$resolver->setDefault('width', null);
		$resolver->setAllowedValues('width', function ($value) {
			if($value == null) {
				// null is default and means no specific column width
				return true;
			}
			if(is_numeric($value)) {
				// any numeric without unit prefix is considered as being px
				return true;
			}
			$widthRegex = '/^(([-+]?([\d]*\.)?[\d]+)(px|em|ex|%|in|cm|mm|pt|pc|vh))/i';
			return preg_match($widthRegex, $value) === 1;
		});
		$resolver->setDefault('js_column_template', 'StingerSoftDatatableBundle:Column:column.js.twig');
		$resolver->setAllowedTypes('js_column_template', 'string');
	}

	/**
	 * @inheritdoc
	 *
	 * @see \StingerSoft\DatatableBundle\Column\AbstractColumnType::buildView()
	 */
	public function buildView(ColumnView $view, ColumnInterface $column, array $options) {
		$view->template = $options['js_column_template'];
		$view->path = $column->getPath();

		$view->vars['label'] = $options['label'];
		$view->vars['translation_domain'] = $options['translation_domain'];
		$view->vars['abbreviation_label'] = $options['abbreviation_label'];
		$view->vars['abbreviation_translation_domain'] = $options['abbreviation_translation_domain'] !== null ? $options['abbreviation_translation_domain'] : $options['translation_domain'];
		$view->vars['tooltip_label'] = $options['tooltip_label'];
		$view->vars['tooltip_translation_domain'] = $options['tooltip_translation_domain'] !== null ?  $options['tooltip_translation_domain'] : $options['translation_domain'];
		$view->vars['orderSequence'] = $options['orderSequence'];
		$view->vars['empty_value'] = $options['empty_value'];

		$serverSide = $column->getTableOptions()['serverSide'] === true;
		$view->vars['searchable'] = AbstractColumnType::getBooleanValueDependingOnClientOrServer($options['searchable'], $serverSide);
		$view->vars['filterable'] = AbstractColumnType::getBooleanValueDependingOnClientOrServer($options['filterable'], $serverSide);
		$view->vars['orderable'] = AbstractColumnType::getBooleanValueDependingOnClientOrServer($options['orderable'], $serverSide);

		$view->vars['route'] = $options['route'];
		$view->vars['class_name'] = $options['class_name'];
		$view->vars['visible'] = $options['visible'];
		$view->vars['toggleable'] = $options['toggleable'];
		$view->vars['toggle_visible'] = $options['toggle_visible'];
		$view->vars['column_group'] = $options['column_group'];
		$view->vars['width'] = $options['width'];
	}

	/**
	 * @inheritdoc
	 *
	 * @see \StingerSoft\DatatableBundle\Column\AbstractColumnType::buildData()
	 */
	public function buildData(ColumnInterface $column, array $options) {
		if($options['route']) {
			$column->addDataTransformer($this->linkTransformer, true);
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \StingerSoft\DatatableBundle\Column\AbstractColumnType::getParent()
	 */
	final public function getParent() {
		return null;
	}
}