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
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\EngineInterface;
use Twig\Environment;

/**
 * Column type that allows to display or hide (toggle) another table row, containing more details or information for the
 * the current row where the toggle was used.
 *
 * The details for the row are loaded in an async (i.e. ajax) manner and as such, the AsyncChildRowTriggerColumnType required
 * detail route to be specified in order to load any details.
 */
class AsyncChildRowTriggerColumnType extends ChildRowTriggerColumnType {

	/**
	 * Trait used for rendering templates, including the templating service
	 */
	use TemplatingTrait;

	/**
	 * @var RouterInterface
	 */
	protected $router;

	public function __construct(RouterInterface $router, ?EngineInterface $templating, ?Environment $twig) {
		$this->templating = $templating;
		$this->twig = $twig;
		$this->router = $router;
	}


	/**
	 * @inheritdoc
	 * @see \StingerSoft\DatatableBundle\Column\AbstractColumnType::configureOptions()
	 */
	public function configureOptions(OptionsResolver $resolver, array $tableOptions = array()) {
		parent::configureOptions($resolver, $tableOptions);

		$resolver->setDefault('child_container_template', 'StingerSoftDatatableBundle:Column:async_childrow.html.twig');
		$resolver->setAllowedTypes('child_container_template', 'string');

		$resolver->setRequired('detail_route');
		$resolver->setAllowedValues('detail_route', function ($value) {
			if(is_string($value)) return true;
			if(is_callable($value)) return true;
			if(is_array($value) && array_key_exists('route', $value)) {
				if(array_key_exists('route_params', $value)) {
					return is_array($value['route_params']) || is_callable($value['route_params']);
				}
				return true;
			}
			return false;
		});
		$resolver->setNormalizer('detail_route', function (Options $options, $value) {
			if(is_string($value)) return $value;
			if(is_array($value)) {
				if(!array_key_exists('route', $value)) {
					throw new InvalidOptionsException('In an array is provided for the "detail_route" option, the key "route" must be present and not empty!');
				}
				if(!array_key_exists('route_params', $value)) {
					$value['route_params'] = array();
				}
				return $value;
			}
			return $value;
		});

		$resolver->setDefault('refresh', false);
		$resolver->setAllowedTypes('refresh', array('boolean', 'callable'));

		$resolver->setDefault('trigger_visible', true);
		$resolver->setAllowedTypes('trigger_visible', array('boolean', 'callable'));

		$that = $this;
		$resolver->setDefault('value_delegate', function ($item, $path, $options) use ($that) {
			$url = $options['detail_route'];
			if(is_array($options['detail_route'])) {
				$route = $options['detail_route']['route'];
				if(is_callable($route)) {
					$route = call_user_func($route, $item, $path, $options);
				}
				$routeParams = $options['detail_route']['route_params'];
				if(is_callable($routeParams)) {
					$routeParams = call_user_func($routeParams, $item, $path, $options);
				}
				$url = $this->router->generate($route, $routeParams, UrlGeneratorInterface::ABSOLUTE_PATH);
			} else if(is_callable($options['detail_route'])) {
				$url = call_user_func($options['detail_route'], $item, $path, $options, $this->router);
			}

			$refresh = $options['refresh'];
			if(is_callable($options['refresh'])) {
				$refresh = call_user_func($refresh, $item, $path, $options);
			}
			$visible = $options['trigger_visible'];
			if(is_callable($options['trigger_visible'])) {
				$visible = call_user_func($visible, $item, $path, $options);
			}
			if($url) {
				return $this->renderView($options['child_container_template'], array(
					'item' => $item,
					'path' => $path,
					'url' => $url,
					'refresh' => $refresh,
					'visible' => $visible
				));
			}
			return null;
		});
	}
}