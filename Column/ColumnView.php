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

use StingerSoft\DatatableBundle\Filter\Filter;
use StingerSoft\DatatableBundle\Filter\FilterView;

/**
 * Helper class to store information for each configured column, used during rendering of a column in the table context.
 *
 * This class and its public properties can be used in the column js and html templates.
 */
class ColumnView {

	/**
	 * @var string The path to access the property on the bound object
	 */
	public $path;

	/**
	 * @var string The template which should be used to create the JS configuration for this column
	 */
	public $template;

	/**
	 * @var array Array of data which can be used inside the template
	 */
	public $vars;

	/**
	 * @var FilterView|null the view for the filter of the column (if any).
	 */
	public $filter = null;

	/**
	 * @var null|ColumnView the parent of this view (if any).
	 */
	public $parent = null;

	/**
	 * ColumnView constructor.
	 *
	 * @param ColumnView|null $parent the parent of this view (if any).
	 */
	public function __construct(ColumnView $parent = null) {
		$this->parent = $parent;
	}

	/**
	 * Gets array of data which can be used inside the template
	 */
	public function getVars() {
		return $this->vars;
	}

	/**
	 * Sets array of data which can be used inside the template
	 *
	 * @param array $vars
	 *            Array of data which can be used inside the template
	 * @return \StingerSoft\DatatableBundle\Column\ColumnView
	 */
	public function setVars($vars) {
		$this->vars = $vars;
		return $this;
	}

}