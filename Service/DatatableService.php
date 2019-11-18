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

use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use StingerSoft\DatatableBundle\Extension\DependencyInjection\DependencyInjectionExtension;
use StingerSoft\DatatableBundle\Table\Table;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Templating\EngineInterface;
use Twig\Environment;

/**
 * The DatatableService allows creating new table instances by providing a query builder, a table type and some options.
 *
 * Similar to the Symfony form concept, the service may be used as a factory for creating table instances in order to
 * render tables or to handle sorting and filtering requests etc.
 */
class DatatableService {

	public const ID = 'stinger_soft_datatable.datatable_service';

	/**
	 *
	 * @var DependencyInjectionExtension
	 */
	protected $dependencyInjectionExtension;

	/**
	 * @var PaginatorInterface
	 */
	protected $paginator;


	/**
	 * @var null|EngineInterface
	 */
	protected $templating;

	/**
	 * @var null|Environment
	 */
	protected $twig;

	/**
	 * DatatableService constructor.
	 *
	 * @param ContainerInterface $container the container, injected
	 */
	public function __construct(DependencyInjectionExtension $dependencyInjectionExtension, PaginatorInterface $paginator, ?EngineInterface $templating, ?Environment $twig) {
		$this->dependencyInjectionExtension = $dependencyInjectionExtension;
		$this->paginator = $paginator;
		$this->templating = $templating;
		$this->twig = $twig;
	}

	/**
	 * Creates a new table instance for the given type and query builder.
	 *
	 * @param string             $type       the type of table to be created
	 * @param QueryBuilder|array $dataSource the data source to be used for retrieving table rows and column values
	 * @param array              $options    the options for the table type
	 * @param string             $tableClass the FQCN of the table class to be created
	 * @return Table the table instance
	 */
	public function createTable($type, $dataSource, array $options = array(), $tableClass = Table::class) {
		return new $tableClass($type, $dataSource, $this->dependencyInjectionExtension, $this->paginator, $this->templating, $this->twig, $options);
	}
}