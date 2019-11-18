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

use StingerSoft\DatatableBundle\Column\Column;

interface TableInterface {

	/**
	 * Get all columns belonging to the table.
	 *
	 * @return Column[] all columns belonging to the table.
	 */
	public function getColumns();

	/**
	 * <p>Creates a json string containing the data to be rendered in the table, depending on the table configuration and
	 * potentially a request in case of server side tables.
	 *
	 * <p>In case the table option for server side processing is defined or the parameter for <code>serverSide</code>
	 * evaluates to <code>true</code>, the following will happen in order to create the json data:
	 *
	 * <p>According to the previously handled request, the underlying query builder will be used to:
	 * 1. determine the total amount of results for the whole table
	 * 2. apply an ordering on the columns as requested
	 * 3. apply a table-wide search on ALL columns as requested
	 * 4. apply column-specific filters as requested
	 * 5. determine the amount of results for the filtered table
	 *
	 * <p>The resulting response content will contain the information required by th datatable library, such as:
	 * 1. draw : The draw counter that this object is a response to
	 * 2. recordsTotal : Total records, before filtering (i.e. the total number of records in the database)
	 * 3. recordsFiltered : Total records, after filtering (i.e. the total number of records after filtering has been
	 * applied - not just the number of records being returned for this page of data).
	 * 4. data : The data to be displayed in the table. This is an array of data source objects, one for each row, which
	 * will be used by DataTables
	 *
	 * <hr>
	 *
	 * <p>In case the table option for server side processing is not defined  or the parameter for <code>serverSide</code>
	 * evaluates to <code>false</code>, the following will happen in order to create the json data:
	 * 1. determine items to be returned, checking if there is a paging enabled for the table and page length is defined
	 *  if there is no page length defined
	 *
	 * @param null|boolean $serverSide
	 * @return string a json string containing the data to be rendered in the table, depending on the table configuration and
	 * potentially a request in case of server side tables
	 * @see \StingerSoft\DatatableBundle\Table\Table::handleRequest() for handling a request before calling this method
	 * @see https://datatables.net/manual/server-side#Returned-data Server side processing for details on the response body
	 */
	public function createJsonData($serverSide = null);

	/**
	 * Returns the amount of total results of the query before applying any filter by performing a count query using
	 * the root alias of the underlying query builder.
	 *
	 * @return integer the amount of total results of the query before applying any filter.
	 */
	public function getTotalResults();

}