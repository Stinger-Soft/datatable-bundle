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

use Knp\Component\Pager\PaginatorInterface;
use StingerSoft\DatatableBundle\Extension\DependencyInjection\DependencyInjectionExtension;
use Symfony\Component\Templating\EngineInterface;
use Twig\Environment;

class DomTable extends Table {

	public function __construct($tableTypeClass, $dataSource, DependencyInjectionExtension $dependencyInjectionExtension, PaginatorInterface $paginator, ?EngineInterface $templating, ?Environment $twig, array $options = array()) {
		parent::__construct($tableTypeClass, $dataSource, $dependencyInjectionExtension, $paginator, $templating, $twig, $options);
		$this->columns = array();
	}

	public function createJsonData($serverSide = null) {
		return json_encode(array());
	}

	/**
	 * Creates a table view object for the table type and its options.
	 *
	 * @return \StingerSoft\DatatableBundle\Table\TableView
	 */
	public function createView() {
		$tableView = new TableView($this, $this->tableType, $this->options, $this->columns);
		$this->buildView($tableView, $this->tableType);
		$tableView->vars['dom_based'] = true;
		return $tableView;
	}

}