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
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * A filter type based on an input box that provides auto completion functionality, providing quick access to all
 * filterable values.
 */
class AutoCompleteFilterType extends AbstractFilterType {

	/**
	 * @var TranslatorInterface
	 */
	protected $translator;

	public function __construct(TranslatorInterface $translator) {
		$this->translator = $translator;
	}

	/**
	 * @inheritdoc
	 * @see AbstractFilterType::configureOptions()
	 */
	public function configureOptions(OptionsResolver $resolver, array $columnOptions = array(), array $tableOptions = array()) {
		$resolver->setDefault('jsTemplate', 'StingerSoftDatatableBundle:Filter:autocomplete.json.twig');
		$resolver->setDefault('type', FilterTypeInterface::FILTER_TYPE_AUTO_COMPLETE);

		$resolver->setDefault('data', true);
		$resolver->setAllowedTypes('data', array('null', 'array', 'boolean', 'callable'));

		$resolver->setDefault('allow_null_value', false);
		$resolver->setAllowedTypes('allow_null_value', 'boolean');

		$resolver->setDefault('null_value', null);
		$resolver->setNormalizer('null_value', function (Options $options, $valueToNormalize) {
			if($options['allow_null_value'] === true && $valueToNormalize === null) {
				throw new \InvalidArgumentException('When setting "allow_null_value" to true, you must provide a non-null value for the "null_value" option!');
			}
			return $valueToNormalize;
		});

		$resolver->setDefault('null_label', null);
		$resolver->setAllowedTypes('null_label', array('null', 'string'));

		$resolver->setDefault('null_label_translation_domain', false);
		$resolver->setAllowedValues('null_label_translation_domain', function ($value) {
			if($value === false) {return true;}
			if(is_string($value)) {return true;}
			return false;
		});
		$resolver->setDefault('label_function', null);
		$resolver->setAllowedTypes('label_function', array('null', 'callable'));

		$resolver->setDefault('value_function', null);
		$resolver->setAllowedTypes('value_function', array('null', 'callable'));

		$resolver->setDefault('label_function_translation_domain', false);
		$resolver->setAllowedValues('label_function_translation_domain', function ($value) {
			if($value === false) {return true;}
			if(is_string($value)) {return true;}
			if(is_callable($value)) {return true;}
			return false;
		});
	}

	/**
	 * @inheritdoc
	 * @see AbstractFilterType::buildView()
	 */
	public function buildView(FilterView $view, FilterInterface $filter, array $options, $dataSource, $queryPath, $rootAlias) {
		$rawData = $options['data'];
		$allowNullValue = $options['allow_null_value'];

		if($options['data'] === true) {
			if($dataSource instanceof QueryBuilder) {
				$queryBuilder = $dataSource;
				$queryBuilder->select($queryPath);
				$queryBuilder->distinct(true);
				$queryBuilder->groupBy($queryPath);
				$queryBuilder->orderBy($queryPath);
				$result = $queryBuilder->getQuery()->getScalarResult();
				$rawData = array_map("current", $result);
				if(!$allowNullValue) {
					$rawData = array_filter($rawData, function ($value) {
						return $value !== null;
					});
				} else {
					$nullValue = $options['null_value'];
					array_walk($rawData, function (&$value) use ($nullValue) {
						if($value === null) {
							$value = $nullValue;
						}
					});
				}
				$rawData = array_values($rawData);
			}
		} else if(is_callable($options['data'])) {
			if($dataSource instanceof QueryBuilder) {
				$rawData = call_user_func($options['data'], $filter, $options, $dataSource, $queryPath, $rootAlias);
			}
		}

		$labelFunction = $options['label_function'];
		$valueFunction = $options['value_function'];
		$nullLabel = $options['null_label'];
		$nullValue = $options['null_value'];

		$data = array();
		if(is_array($rawData)) {
			foreach($rawData as $rawDatum) {
				$parsedDatum = null;
				if($valueFunction !== null && is_callable($valueFunction)) {
					$parsedDatum = $valueFunction($rawDatum, $filter, $options, $dataSource, $queryPath, $rootAlias);
				}
				$entry = array(
					'raw' => $rawDatum,
					'parsed' => $parsedDatum !== null ? $parsedDatum : $rawDatum
				);
				$data[] = $entry;
			}
		}

		if(is_array($data) && count($data) > 0) {
			$translate = false;
			if($options['label_function_translation_domain'] !== false || $options['null_label_translation_domain']) {
				$translate = true;
			}
			$finalData = array();
			foreach($data as $datum) {
				$rawData = $datum['raw'];
				$parsedDatum = $datum['parsed'];
				$finalLabel = $parsedDatum;
				if($allowNullValue && $parsedDatum === $nullValue && $nullLabel !== null) {
					$labelTranslationDomain = $options['null_label_translation_domain'];
					$finalLabel = $translate && $labelTranslationDomain !== false ? $this->translator->trans($nullLabel, array(), $labelTranslationDomain) : $nullLabel;
				} else if($labelFunction !== null && is_callable($labelFunction)) {
					$labelTranslationDomain = $options['label_function_translation_domain'];
					if(is_callable($labelTranslationDomain)) {
						$labelTranslationDomain = $labelTranslationDomain($parsedDatum, $rawData, $filter, $options, $dataSource, $queryPath);
					}
					$label = $labelFunction($parsedDatum, $rawData, $filter, $options, $dataSource, $queryPath);
					$finalLabel = $translate && $labelTranslationDomain !== false ? $this->translator->trans($label, array(), $labelTranslationDomain) : $label;
				}

				$finalData[] = array(
					'value' => $parsedDatum,
					'label' => $finalLabel
				);
			}
		} else {
			$finalData = $rawData;
		}
		$view->vars = array_replace($view->vars, array(
			'data' => $finalData
		));
	}

}