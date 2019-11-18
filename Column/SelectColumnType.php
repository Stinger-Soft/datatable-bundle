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

use StingerSoft\DatatableBundle\Helper\TemplatingTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Templating\EngineInterface;
use Twig\Environment;

class SelectColumnType extends AbstractColumnType {

	use ColumnTrait;
	use TemplatingTrait;

	public function __construct(?EngineInterface $templating, ?Environment $twig) {
		$this->templating = $templating;
		$this->twig = $twig;
	}

	public function configureOptions(OptionsResolver $resolver, array $tableOptions = array()) {
		$resolver->setDefault('multiple', true);
		$resolver->setAllowedValues('multiple', array(true, false));

		$resolver->setDefault('id_value', null);
		$resolver->setAllowedTypes('id_value', array('null', 'string', 'callable'));

		$resolver->setDefault('disabled', false);
		$resolver->setAllowedTypes('disabled', array('boolean', 'callable'));

		$resolver->setDefault('checked', false);
		$resolver->setAllowedTypes('checked', array('boolean', 'callable'));

		$resolver->setDefault('mapped', true);
		$resolver->setDefault('template', 'StingerSoftDatatableBundle:Column:select_column.html.twig');

		$resolver->setRequired('form_id');
		$resolver->setAllowedTypes('form_id', 'string');

		$that = $this;
		$resolver->setDefault('value_delegate', function ($item, $path, $options) use ($that, $tableOptions) {
			$value = $options['mapped'] ? $this->generateItemValue($item, $path, $options) : null;
			$disabled = $this->getDelegateValueOrScalar('disabled', $item, $path, $options);
			$checked = $this->getDelegateValueOrScalar('checked', $item, $path, $options);
			$idValue = $value;
			if(is_string($options['id_value'])) {
				$idValue = $this->generateItemValue($item, $options['id_value'], $options);
			} else if(is_callable($options['id_value'])) {
				$idValue = call_user_func($options['id_value'], $item, $path, $options);
			}
			return trim($that->renderView($options['template'], array(
				'item' => $item,
				'path' => $path,
				'value' => $value,
				'idValue' => $idValue,
				'formId' => $options['form_id'],
				'options' => $options,
				'tableOptions' => $tableOptions,
				'disabled' => $disabled,
				'checked' => $checked,
				'multiple' => $options['multiple']
			)));
		});

		$resolver->setDefault('js_column_template', 'StingerSoftDatatableBundle:Column:select_column.js.twig');
	}

	public function buildView(ColumnView $view, ColumnInterface $column, array $options) {
//		$view->template = 'StingerSoftDatatableBundle:Column:select_column.js.twig';
		$view->vars['form_id'] = $options['form_id'];
	}

	public function getParent() {
		return TemplatedColumnType::class;
	}

}