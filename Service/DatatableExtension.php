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

namespace StingerSoft\DatatableBundle\Service;

use StingerSoft\DatatableBundle\Table\TableView;

/**
 * Twig extension to support the rendering of the table
 *
 */
class DatatableExtension extends \Twig_Extension {
	
	/**
	 * @var \Twig_Environment
	 */
	protected $environment;
	
	/**
	 * @var string
	 */
	protected $twigHtmlTemplate;
	
	/**
	 * @var string
	 */
	protected $twigJsTemplate;

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Twig_Extension::getFunctions()
	 */
	public function getFunctions() {
		return array(
			new \Twig_SimpleFunction('datatable_table_render', array(
				$this,
				'render' 
			), array(
				'is_safe' => array(
					'html' 
				) 
			)) 
		);
	}
	
	/**
	 * @param \Twig_Environment $environment
	 * @param string $twigHtmlTemplate
	 * @param string $twigJsTemplate
	 */
	public function __construct(\Twig_Environment $environment, $twigHtmlTemplate, $twigJsTemplate) {
		$this->environment = $environment;
		$this->twigHtmlTemplate = $twigHtmlTemplate;
		$this->twigJsTemplate = $twigJsTemplate;
	}

	/**
	 * Renders a table with the specified renderer.
	 *
	 * @param \StingerSoft\DatatableBundle\Table\TableView $table
	 * @param array $options
	 * @param string $renderer
	 *
	 * @return string
	 */
	public function render(TableView $table, array $options = array(), $renderer = null) {
		$options = array_merge(array(
			'html_template' => $this->twigHtmlTemplate,
			'js_template' => $this->twigJsTemplate,
			'table' => $table
		), $options);
		return $this->environment->render($options['html_template'], $options) . "\n" . $this->environment->render($options['js_template'], $options);
	}
}

