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

namespace StingerSoft\DatatableBundle\Orderer;

use StingerSoft\DatatableBundle\Table\TableInterface;

interface TableOrdererInterface {

	/**
	 * Orders the columns of the given table.
	 *
	 * @param TableInterface $table The table to order the columns of.
	 *
	 * @return array The ordered column child names.
	 */
	public function order(TableInterface $table);
}