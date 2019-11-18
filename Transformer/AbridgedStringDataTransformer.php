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

namespace StingerSoft\DatatableBundle\Transformer;

use StingerSoft\DatatableBundle\Column\ColumnInterface;
use StingerSoft\DatatableBundle\Column\ColumnTrait;

class AbridgedStringDataTransformer implements DataTransformerInterface {

	use ColumnTrait;

	/**
	 * @var string Service Identifier
	 */
	const ID = 'stinger_soft_datatable.transformers.abridged_string_formatter';

	/**
	 * @var \Twig_Environment the twig environment used for rendering views
	 */
	protected $environment;

	/**
	 * LinkDataTransformer constructor.
	 *
	 * @param \Twig_Environment $environment the twig environment used for rendering views
	 */
	public function __construct(\Twig_Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * @param ColumnInterface $column
	 * @param                 $item
	 * @param mixed           $value
	 *            The value in the original representation
	 * @return mixed The value in the transformed representation
	 */
	public function transform(ColumnInterface $column, $item, $value) {
		$options = $column->getColumnOptions();
		$path = $column->getPath();
		$value = $value ?: $this->getDelegateValueOrScalar('empty_value', $item, $path, $options);
		return $this->environment->render('StingerSoftDatatableBundle:Column:abridged_string.html.twig', array(
			'item' => $item,
			'path' => $path,
			'max' => $this->getDelegateValueOrScalar('max', $item, $path, $options),
			'wrap' => $this->getDelegateValueOrScalar('wrap', $item, $path, $options),
			'tooltipMax' => $this->getDelegateValueOrScalar('tooltipMax', $item, $path, $options),
			'value' => $value,
			'container' => $this->getDelegateValueOrScalar('container', $item, $path, $options),
			'tooltipWrap' => $this->getDelegateValueOrScalar('tooltip_wrap', $item, $path, $options),
			'fullscreen' => $this->getDelegateValueOrScalar('fullscreen', $item, $path, $options),
			'nl2space' => $options['nl2space'],
			'nl2br' => $options['nl2br'],
			//If newline should be replaced by spaces, abridge
			'valueCleansed' => isset($options['nl2space']) ? trim(preg_replace('/(\r\n|\r|\n|\t|\s+)+/', ' ', $value)) : $value
		));
	}
}
