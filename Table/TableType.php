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

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use StingerSoft\PhpCommons\Builder\HashCodeBuilder;

/**
 * Basic table type defining some default options required for using the jQuery datatable library
 */
final class TableType extends AbstractTableType {

	/**
	 * {@inheritdoc}
	 * @see \StingerSoft\DatatableBundle\Table\AbstractTableType::configureOptions()
	 */
	public function configureOptions(OptionsResolver $resolver) {
		$this->configurePecDataTableOptions($resolver);
		$this->configureJQueryDataTableOptions($resolver);
	}

	public function buildView(TableView $view, TableInterface $table, array $tableOptions, array $columns) {
		$this->configureDefaultViewValues($view, $tableOptions, $columns);
		$this->configureJQueryDataTableViewValues($view, $tableOptions);
		$this->configurePecDataTableViewValues($view, $tableOptions, $columns);
	}

	protected function configureDefaultViewValues(TableView $view, array $tableOptions, array $columns) {
		if($tableOptions['version_hash'] === true) {
			$hashing = hash_init('sha256', HASH_HMAC, 'pec-datatable');
			foreach($columns as $column) {
				hash_update($hashing, (string)$column->getHashCode());
			}
			$view->vars['version_hash'] = hash_final($hashing);
		}

		$view->vars['id'] = $tableOptions['attr']['id'] = $view->getTableId();
		$classes = $additionalClasses = $tableOptions['classes'] === null ? array() : explode(' ', $tableOptions['classes']);
		if($tableOptions['serverSide']) {
			$classes = array_merge($classes, array('serverSide'));
		}
		if(array_key_exists('class', $tableOptions['attr'])) {
			$classes = explode(' ', $tableOptions['attr']['class']);
			$classes = array_unique(array_merge($classes, $additionalClasses));
		}
		$view->vars['class'] = $tableOptions['attr']['class'] = implode(' ', $classes);
		$view->vars['attr'] = $tableOptions['attr'];
	}

	protected function configureJQueryDataTableViewValues(TableView $view, array $tableOptions) {
		$view->vars['processing'] = $tableOptions['processing'];
		$view->vars['serverSide'] = $tableOptions['serverSide'];
		$view->vars['ajax_url'] = $tableOptions['ajax_url'];
		$view->vars['ajax_method'] = $tableOptions['ajax_method'];
		$view->vars['deferRender'] = $tableOptions['deferRender'];
		$view->vars['stateSave'] = $tableOptions['stateSave'];
		$view->vars['stateDuration'] = $tableOptions['stateDuration'];
		$view->vars['dom'] = $tableOptions['dom'];
		$view->vars['lengthMenu'] = $tableOptions['lengthMenu'];
		$view->vars['paging'] = $tableOptions['paging'];
		$view->vars['pageLength'] = $tableOptions['pageLength'];
		$view->vars['pagingType'] = $tableOptions['pagingType'];
		$view->vars['scrollX'] = $tableOptions['scrollX'];
		$view->vars['scrollY'] = $tableOptions['scrollY'];
		$view->vars['scrollCollapse'] = $tableOptions['scrollCollapse'];
		$view->vars['rowId'] = $tableOptions['rowId'];
		$view->vars['rowClass'] = $tableOptions['rowClass'];
		$view->vars['rowData'] = $tableOptions['rowData'];
		$view->vars['rowAttr'] = $tableOptions['rowAttr'];
		$view->vars['order'] = $tableOptions['order'];
		$view->vars['scroller'] = $tableOptions['scroller'];
		$view->vars['allowResize'] = $tableOptions['allowResize'];
		$view->vars['allowReorder'] = $tableOptions['allowReorder'];
	}

	protected function configurePecDataTableViewValues(TableView $view, array $tableOptions, array $columns) {
		$view->vars['translation_domain'] = $tableOptions['translation_domain'];
		$view->vars['total_results_query_builder'] = $tableOptions['total_results_query_builder'];
		$view->vars['data'] = $tableOptions['data'];
		$view->vars['paginationOptions'] = $tableOptions['paginationOptions'];
		$view->vars['scrollerWrapperClass'] = $tableOptions['scrollerWrapperClass'];
		$view->vars['filter_external'] = $tableOptions['filter_external'];
		$view->vars['search_enabled'] = $tableOptions['search_enabled'];
		$view->vars['search_placeholder'] = $tableOptions['search_placeholder'];
		$view->vars['search_delay'] = $tableOptions['search_delay'];
		$view->vars['reload_enabled'] = $tableOptions['reload_enabled'];
		$view->vars['reload_tooltip'] = $tableOptions['reload_tooltip'];
		$view->vars['clear_enabled'] = $tableOptions['clear_enabled'];
		$view->vars['clear_tooltip'] = $tableOptions['clear_tooltip'];
		$view->vars['column_selector_enabled'] = $tableOptions['column_selector_enabled'];
		$view->vars['column_selector_label'] = $tableOptions['column_selector_label'];
		$view->vars['column_selector_label_domain'] = $tableOptions['column_selector_label_domain'];
		$view->vars['column_selector_tooltip'] = $tableOptions['column_selector_tooltip'];
		$view->vars['state_save_key'] = $tableOptions['state_save_key'];
		$view->vars['search_state_save_key'] = $tableOptions['search_state_save_key'];
		$view->vars['filter_state_save_key'] = $tableOptions['filter_state_save_key'];
		$view->vars['visibility_state_save_key'] = $tableOptions['visibility_state_save_key'];
		$view->vars['page_length_state_save_key'] = $tableOptions['page_length_state_save_key'];
		$view->vars['order_state_save_key'] = $tableOptions['order_state_save_key'];
		$view->vars['scroller_state_save_key'] = $tableOptions['scroller_state_save_key'];
		$view->vars['start_state_save_key'] = $tableOptions['start_state_save_key'];
		$view->vars['sort_on_header_label'] = $tableOptions['sort_on_header_label'];
		$view->vars['footer_tool_container_selector'] = $tableOptions['footer_tool_container_selector'];
		$view->vars['events_namespace'] = $tableOptions['events_namespace'];
		$view->vars['default_order_property'] = $tableOptions['default_order_property'];
		$view->vars['default_order_direction'] = $tableOptions['default_order_direction'];
		$view->vars['version_hash'] = $tableOptions['version_hash'];
		$view->vars['version_hash_modifier'] = $tableOptions['version_hash_modifier'];
		$view->vars['rows_selectable'] = $tableOptions['rows_selectable'];
		$view->vars['row_selection_id'] = $tableOptions['row_selection_id'];
		$view->vars['column_groups'] = $tableOptions['column_groups'];

		if($tableOptions['version_hash'] === true) {
			$hashing = hash_init('sha256', HASH_HMAC, 'pec-datatable');
			foreach($columns as $column) {
				hash_update($hashing, (string)$column->getHashCode());
			}
			$hashBuilder = new HashCodeBuilder();
			$hashBuilder
				->append($tableOptions['state_save_key'])
				->append($tableOptions['search_state_save_key'])
				->append($tableOptions['filter_state_save_key'])
				->append($tableOptions['visibility_state_save_key'])
				->append($tableOptions['page_length_state_save_key'])
				->append($tableOptions['order_state_save_key'])
				->append($tableOptions['scroller_state_save_key'])
				->append($tableOptions['start_state_save_key']);

			hash_update($hashing, (string)$hashBuilder->toHashCode());
			if($tableOptions['version_hash_modifier'] !== null) {
				hash_update($hashing, $tableOptions['version_hash_modifier']);
			}
			$tableOptions['version_hash'] = hash_final($hashing);
		}
		$view->vars['version_hash'] = $tableOptions['version_hash'];
		$view->vars['filter_requesturl_column_key'] = $tableOptions['filter_requesturl_column_key'];
		$view->vars['filter_requesturl_value_key'] = $tableOptions['filter_requesturl_value_key'];
	}

	/**
	 * Sets the defaults, normalizers, allowed values and types for all standard options of the table type
	 * which are more or less directly related to the jQuery Datatable library.
	 *
	 * @param OptionsResolver $resolver the options resolver to be used for defining defaults, allowed types etc.
	 */
	protected function configureJQueryDataTableOptions(OptionsResolver $resolver) {
		// Processing is handled by the server?
		$resolver->setDefault('processing', true);
		$resolver->setAllowedValues('processing', array(
			true,
			false
		));

		// Data is fetched from the server?
		$resolver->setDefault('serverSide', true);
		$resolver->setAllowedValues('serverSide', array(
			true,
			false
		));

		$resolver->setDefault('ajax_url', null);
		$resolver->setAllowedTypes('ajax_url', array(
			'string',
			'null'
		));

		$resolver->setNormalizer('ajax_url', function (Options $options, $valueToNormalize) {
			if($valueToNormalize === null && $options['serverSide'] === true) {
				throw new InvalidOptionsException('When using "serverSide" with a value of true you must set "ajax_url"!');
			}
			if($valueToNormalize === null && $options['processing'] === true) {
				throw new InvalidOptionsException('When using "processing" with a value of true you must set "ajax_url"!');
			}
			return $valueToNormalize;
		});

		$resolver->setDefault('ajax_method', 'POST');
		$resolver->setAllowedValues('ajax_method', array(
			'GET',
			'POST'
		));

		// Activate defered rendering
		$resolver->setDefault('deferRender', true);
		$resolver->setAllowedTypes('deferRender', 'boolean');

		$resolver->setDefault('stateSave', true);
		$resolver->setAllowedTypes('stateSave', 'boolean');

		$resolver->setDefault('stateDuration', 0);
		$resolver->setAllowedValues('stateDuration', function ($value) {
			return $value >= -1;
		});

		$resolver->setDefault('dom', "<'row'<'col-sm-12'tr>>" . "<'row pec-datatables-footer-tools'<'col-sm-4 paginate left'p><'col-sm-4 information center'i><'col-sm-4 tools right'>>");
		$resolver->setAllowedTypes('dom', array('null', 'string'));

		$resolver->setDefault('lengthMenu', null);
		$resolver->setAllowedTypes('lengthMenu', array('null', 'array'));

		$resolver->setDefault('paging', true);
		$resolver->setAllowedTypes('paging', 'boolean');

		$resolver->setDefault('pageLength', 25);
		$resolver->setAllowedTypes('pageLength', 'integer');
		$resolver->setAllowedValues('pageLength', function ($valueToValidate) {
			return is_int($valueToValidate) && $valueToValidate > 0;
		});

		$resolver->setDefault('pagingType', 'simple_numbers');
		$resolver->setAllowedValues('pagingType', array(
			'numbers',
			'simple',
			'simple_numbers',
			'full',
			'full_numbers',
			'first_last_numbers'
		));

		/*
		$resolver->setDefault('plugins', array());
		$resolver->setAllowedTypes('plugins', 'array');
		*/

		$resolver->setDefault('scrollX', true);
		$resolver->setAllowedTypes('scrollX', 'boolean');

		$resolver->setDefault('scrollCollapse', false);
		$resolver->setAllowedTypes('scrollCollapse', 'boolean');

		$resolver->setDefault('scrollY', null);
		$resolver->setAllowedValues('scrollY', function ($value) {
			if($value === null) {
				// null is default and means no fixed scroll-height
				return true;
			}

			if(is_numeric($value)) {
				// any numeric without unit prefix is considered as being px
				return true;
			}
			$widthRegex = '/^(([-+]?([\d]*\.)?[\d]+)(px|em|ex|%|in|cm|mm|pt|pc|vh))/i';
			return preg_match($widthRegex, $value) === 1;
		});
		$resolver->setNormalizer('scrollY', function (Options $options, $valueToNormalize) {
			if($valueToNormalize === null && $options['scroller'] !== false) {
				$valueToNormalize = 500;
			}
			return $valueToNormalize;
		});

		$resolver->setDefault('rowId', null);
		$resolver->setAllowedTypes('rowId', array('null', 'string', 'callable'));

		$resolver->setDefault('rowClass', null);
		$resolver->setAllowedTypes('rowClass', array('null', 'string', 'callable'));

		$resolver->setDefault('rowData', null);
		$resolver->setAllowedTypes('rowData', array('null', 'array', 'callable'));

		$resolver->setDefault('rowAttr', null);
		$resolver->setAllowedTypes('rowAttr', array('null', 'array', 'callable'));

		$resolver->setDefault('order', null);
		$resolver->setAllowedTypes('order', array('null', 'array'));

		$resolver->setDefault('scroller', true);
		$resolver->setAllowedTypes('scroller', array('boolean', 'array'));

		$that = $this;
		$resolver->setNormalizer('scroller', function (Options $options, $valueToNormalize) use ($that) {
			return $that->validateScrollerOptions($options, $valueToNormalize);
		});

		$resolver->setDefault('allowResize', false);
		$resolver->setAllowedValues('allowResize', [true, false]);
		$resolver->setDefault('allowReorder', false);
		$resolver->setAllowedValues('allowReorder', [true, false]);
	}

	protected function validateScrollerOptions(Options $options, $valueToNormalize) {
		if($valueToNormalize !== false && $options['paging'] === false) {
			throw new InvalidOptionsException('When using "scroller" with a value of true you must set "paging" to true!');
		}
		if(is_array($valueToNormalize)) {
			$optionsResolver = new OptionsResolver();
			$optionsResolver->setDefault('boundaryScale', 0.5);
			$optionsResolver->setAllowedValues('boundaryScale', function ($valueToCheck) {
				return is_numeric($valueToCheck) && $valueToCheck > 0.0 && $valueToCheck <= 1.0;
			});
			$optionsResolver->setDefault('displayBuffer', 9);
			$optionsResolver->setAllowedTypes('displayBuffer', 'numeric');

			$optionsResolver->setDefault('loadingIndicator', false);
			$optionsResolver->setAllowedTypes('loadingIndicator', 'boolean');

			$optionsResolver->setDefault('rowHeight', 'auto');
			$optionsResolver->setAllowedValues('rowHeight', function ($valueToCheck) {
				return is_int($valueToCheck) || $valueToCheck === 'auto';
			});

			$optionsResolver->setDefault('serverWait', 200);
			$optionsResolver->setAllowedTypes('serverWait', 'numeric');

			$valueToNormalize = $optionsResolver->resolve($valueToNormalize);
		}
		return $valueToNormalize;
	}

	/**
	 * Sets the defaults, normalizers, allowed values and types for all extended options of the table type
	 * which are not directly related to the jQuery Datatable library.
	 *
	 * @param OptionsResolver $resolver the options resolver to be used for defining defaults, allowed types etc.
	 */
	protected function configurePecDataTableOptions(OptionsResolver $resolver) {
		$resolver->setDefault('translation_domain', 'messages');
		$resolver->setAllowedTypes('translation_domain', array(
			'string',
			'null',
			'boolean'
		));

		$resolver->setDefault('total_results_query_builder', null);
		$resolver->setAllowedTypes('total_results_query_builder', array('null', QueryBuilder::class));

		$resolver->setDefault('data', null);
		$resolver->setAllowedValues('data', function ($valueToValidate) {
			if($valueToValidate === null) {
				return true;
			}
			if($valueToValidate === true) {
				return true;
			}
			if($valueToValidate === false) {
				return true;
			}
			if(is_int($valueToValidate) && $valueToValidate > 0) {
				return true;
			}
			if(is_array($valueToValidate) || $valueToValidate instanceof \Traversable) {
				return true;
			}
			return false;
		});

		$resolver->setDefault('paginationOptions', null);
		$resolver->setAllowedTypes('paginationOptions', array('null', 'array'));

		$resolver->setDefault('scrollerWrapperClass', function (Options $options, $previousValue) {
			if($previousValue === null && $options['scroller'] !== false) {
				return 'scroller-content-wrapper';
			}
			return $previousValue;
		});
		$resolver->setAllowedTypes('scrollerWrapperClass', array('null', 'string'));

		$resolver->setDefault('classes', null);
		$resolver->setAllowedTypes('classes', array('null', 'string'));

		$resolver->setDefault('attr', array(
			'class' => 'table table-striped table-hover table-condensed expendable-table pec-datatable',
			'style' => 'width: 100%;'
		));
		$resolver->setAllowedTypes('attr', 'array');

		$resolver->setDefault('filter_external', true);
		$resolver->setAllowedTypes('filter_external', 'boolean');

		$resolver->setDefault('search_enabled', true);
		$resolver->setAllowedTypes('search_enabled', 'boolean');
		$resolver->setDefault('search_placeholder', null);
		$resolver->setAllowedTypes('search_placeholder', array('null', 'string'));
		$resolver->setDefault('search_delay', null);
		$resolver->setAllowedTypes('search_delay', array('null', 'integer'));

		$resolver->setDefault('reload_enabled', true);
		$resolver->setAllowedTypes('reload_enabled', 'boolean');
		$resolver->setDefault('reload_tooltip', null);
		$resolver->setAllowedTypes('reload_tooltip', array('null', 'string'));

		$resolver->setDefault('clear_enabled', true);
		$resolver->setAllowedTypes('clear_enabled', 'boolean');
		$resolver->setDefault('clear_tooltip', null);
		$resolver->setAllowedTypes('clear_tooltip', array('null', 'string'));

		$resolver->setDefault('column_selector_enabled', true);
		$resolver->setAllowedTypes('column_selector_enabled', array('boolean'));
		
		$resolver->setDefault('column_selector_label', 'stinger_soft_datatables.columns.label');
		$resolver->setAllowedTypes('column_selector_label', array('string'));
		
		$resolver->setDefault('column_selector_label_domain', 'StingerSoftDatatableBundle');
		$resolver->setAllowedTypes('column_selector_label_domain', array('boolean', 'string'));
		
		$resolver->setDefault('column_selector_tooltip', null);
		$resolver->setAllowedTypes('column_selector_tooltip', array('null', 'string'));

		$resolver->setDefault('state_save_key', true);
		$resolver->setAllowedValues('state_save_key', function ($value) {
			if($value === null) {
				return true;
			}
			if(is_string($value)) {
				return true;
			}
			if($value === true) {
				return true;
			}
			return false;
		});

		$resolver->setDefault('search_state_save_key', false);
		$resolver->setAllowedTypes('search_state_save_key', array('null', 'string', 'boolean'));
		$resolver->setDefault('filter_state_save_key', null);
		$resolver->setAllowedTypes('filter_state_save_key', array('null', 'string', 'boolean'));
		$resolver->setDefault('visibility_state_save_key', null);
		$resolver->setAllowedTypes('visibility_state_save_key', array('null', 'string', 'boolean'));
		$resolver->setDefault('page_length_state_save_key', null);
		$resolver->setAllowedTypes('page_length_state_save_key', array('null', 'string', 'boolean'));
		$resolver->setDefault('order_state_save_key', null);
		$resolver->setAllowedTypes('order_state_save_key', array('null', 'string', 'boolean'));
		$resolver->setDefault('scroller_state_save_key', false);
		$resolver->setAllowedTypes('scroller_state_save_key', array('null', 'string', 'boolean'));
		$resolver->setDefault('start_state_save_key', false);
		$resolver->setAllowedTypes('start_state_save_key', array('null', 'string', 'boolean'));

		$resolver->setDefault('sort_on_header_label', true);
		$resolver->setAllowedTypes('sort_on_header_label', 'boolean');

		$resolver->setDefault('footer_tool_container_selector', null);
		$resolver->setAllowedTypes('footer_tool_container_selector', array('null', 'string'));

		$resolver->setDefault('events_namespace', 'pec-datatable');
		$resolver->setAllowedTypes('events_namespace', 'string');

		$resolver->setDefault('default_order_property', 'id');
		$resolver->setAllowedTypes('default_order_property', array('string', 'null'));
		$resolver->setDefault('default_order_direction', 'asc');
		$resolver->setAllowedValues('default_order_direction', array('asc', 'desc'));

		$resolver->setDefault('version_hash', true);
		$resolver->setAllowedTypes('version_hash', array('boolean', 'string'));
		$resolver->setDefault('version_hash_modifier', null);
		$resolver->setAllowedTypes('version_hash_modifier', array('null', 'string'));

		$resolver->setDefault('filter_requesturl_column_key', 'tableFilterColumn');
		$resolver->setAllowedTypes('filter_requesturl_column_key', 'string');
		$resolver->setDefault('filter_requesturl_value_key', 'tableFilterValue');
		$resolver->setAllowedTypes('filter_requesturl_value_key', 'string');

		$this->configureSelectionDefaults($resolver);
		$this->configureColumnGroupsDefaults($resolver);
	}

	protected function configureSelectionDefaults(OptionsResolver $resolver) {
		$resolver->setDefault('rows_selectable', false);
		$resolver->setAllowedTypes('rows_selectable', 'boolean');

		$resolver->setDefault('row_selection_id', null);
		$resolver->setAllowedValues('row_selection_id', function ($valueToValidate) {
			if($valueToValidate === null) {
				return true;
			}
			if(is_int($valueToValidate) || is_string($valueToValidate)) {
				return true;
			}
			if(is_callable($valueToValidate)) {
				return true;
			}
			return false;
		});

		$resolver->setNormalizer('row_selection_id', function (Options $options, $valueToNormalize) {
			if($valueToNormalize === null && $options['rows_selectable'] === true) {
				throw new InvalidOptionsException('When using "rows_selectable" with a value of true you must set "row_selection_id"!');
			}
			return $valueToNormalize;
		});

	}

	/**
	 * Sets the defaults, normalizers, allowed values and types for all column group specific options.
	 *
	 * @param OptionsResolver $resolver the options resolver to be used for defining defaults, allowed types etc.
	 */
	protected function configureColumnGroupsDefaults(OptionsResolver $resolver) {
		$resolver->setDefault('column_groups', null);
		$resolver->setAllowedTypes('column_groups', array('null', 'array'));
		$resolver->setAllowedValues('column_groups', function ($valueToValidate) {
			if($valueToValidate === null) {
				return true;
			}

			if(is_array($valueToValidate)) {
				foreach($valueToValidate as $key => $value) {
					if(is_string($value)) {
						return true;
					}

					if(is_array($value)) {
						return array_key_exists('label', $value) && count(array_diff(array_keys($value), array('label', 'translation_domain'))) === 0;
					}
				}
			}
			return false;
		});
		$resolver->setNormalizer('column_groups', function (Options $options, $valueToNormalize) {
			if($valueToNormalize === null) {
				return $valueToNormalize;
			}
			/** @var array $valueToNormalize */
			$normalizedValue = $valueToNormalize;
			foreach($valueToNormalize as $key => $value) {
				if(is_string($value)) {
					$value = array('label' => $value, 'translation_domain' => $options['translation_domain']);
					$normalizedValue[$key] = $value;
				} else if(is_array($value) && !array_key_exists('translation_domain', $value)) {
					$value['translation_domain'] = $options['translation_domain'];
					$normalizedValue[$key] = $value;
				}
			}
			return $normalizedValue;
		});
	}

	/**
	 * {@inheritdoc}
	 * @see \StingerSoft\DatatableBundle\Table\AbstractTableType::getParent()
	 */
	public function getParent() {
		return null;
	}
}