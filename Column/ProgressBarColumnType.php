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

/**
 * Renders a cells value as a progress bar with a percentage value ranging from 0 to 100.
 */
class ProgressBarColumnType extends AbstractColumnType {

	use TemplatingTrait;

	public function __construct(?EngineInterface $templating, ?Environment $twig) {
		$this->templating = $templating;
		$this->twig = $twig;
	}

	/**
	 * {@inheritdoc}
	 * @see \StingerSoft\DatatableBundle\Column\AbstractColumnType::configureOptions()
	 */
	public function configureOptions(OptionsResolver $resolver, array $tableOptions = array()) {
		$resolver->setDefault('min', 0);
		$resolver->setAllowedTypes('min', array('numeric', 'callable'));

		$resolver->setDefault('max', 100);
		$resolver->setAllowedTypes('max', array('numeric', 'callable'));

		$resolver->setDefault('striped', false);
		$resolver->setAllowedTypes('striped', array('boolean', 'callable'));

		$resolver->setDefault('animated', false);
		$resolver->setAllowedTypes('animated', array('boolean', 'callable'));

		$resolver->setDefault('show_progress', true);
		$resolver->setAllowedTypes('show_progress', array('boolean', 'callable'));

		$resolver->setDefault('progress', null);
		$resolver->setAllowedTypes('progress', array(
			'null',
			'numeric',
			'callable'
		));

		$resolver->setDefault('additional_classes', null);
		$resolver->setAllowedTypes('additional_classes', array(
			'null',
			'string',
			'callable'
		));

		$that = $this;
		$resolver->setDefault('value_delegate', function($item, $path, $options) use ($that) {
			$progress = null;
			if($options['progress'] === null) {
				$progress = $this->generateItemValue($item, $path, $options);
			} else {
				$progress = $that->getDelegateValueOrScalar('progress', $item, $path, $options);
			}
			return $this->renderView('StingerSoftDatatableBundle:Column:progress_bar.html.twig', array(
				'item'              => $item,
				'path'              => $path,
				'progress'          => $progress,
				'min'               => $that->getDelegateValueOrScalar('min', $item, $path, $options),
				'max'               => $that->getDelegateValueOrScalar('max', $item, $path, $options),
				'additionalClasses' => $that->getDelegateValueOrScalar('additional_classes', $item, $path, $options),
				'animated'          => $that->getDelegateValueOrScalar('animated', $item, $path, $options),
				'striped'           => $that->getDelegateValueOrScalar('striped', $item, $path, $options),
				'showProgress'      => $that->getDelegateValueOrScalar('show_progress', $item, $path, $options),
			));
		});
	}

	/**
	 * {@inheritDoc}
	 * @see \StingerSoft\DatatableBundle\Column\AbstractColumnType::getParent()
	 */
	public function getParent() {
		return ColumnType::class;
	}
}