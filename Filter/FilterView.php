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

/**
 * Helper class to store information for each configured filter, used during rendering of a column and its filter in the table context.
 *
 * This class and its public properties can be used in the filter js and html templates.
 */
class FilterView {

	/**
	 * @var string the path to the javascript twig template file to be used when rendering this filter upon
	 * initialization of the yacdf
	 */
	public $jsTemplate = '';

	/**
	 * @var null|FilterView the parent of this view (if any).
	 */
	public $parent = null;

	/**
	 * The variables assigned to this view.
	 *
	 * @var array
	 */
	public $vars = array();

	/**
	 * FilterView constructor.
	 *
	 * @param FilterView|null $parent the parent of this view (if any).
	 */
	public function __construct(FilterView $parent = null) {
		$this->parent = $parent;
	}
}