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
use StingerSoft\DatatableBundle\Column\ColumnView;
use StingerSoft\DatatableBundle\Column\SelectColumnType;

/**
 * The TableView is a helper class to store information for a table  used during rendering of a table, providing access
 * to the views of the tables columns and filters etc.
 *
 * This class and its public properties can be used in the table js and html templates.
 */
class TableView {

	/**
	 * @var array the options for the table type, containing information such as the translation_domain etc.
	 */
	protected $tableOptions;

	/**
	 * @var ColumnView[] the views for all columns belonging to the table
	 */
	protected $columnViews;

	/**
	 * @var Column[] the columns belonging to the table
	 */
	protected $columns;

	/**
	 * @var TableTypeInterface the table type instance
	 */
	protected $tableType;

	/**
	 * @var TableInterface the table instance
	 */
	protected $table;

	/**
	 * @var string the id of the table
	 */
	protected $tableId;

	/**
	 * @var Column[]|null helper array containing all filterable columns, will be populated upon first access
	 */
	protected $filterColumns;

	/**
	 * @var array helper array containing columns grouped by their column group
	 */
	protected $toggleableColumns;

	/**
	 * @var array
	 */
	public $vars;

	/**
	 * TableView Constructor.
	 *
	 * @param TableInterface     $tableInterface the table instance
	 * @param TableTypeInterface $tableType      the table type instance
	 * @param array              $tableOptions   the options for the table type, containing information such as the
	 *                                           translation_domain etc.
	 * @param Column[]           $columns        the columns belonging to the table, required for generating the column
	 *                                           views
	 */
	public function __construct(TableInterface $tableInterface, TableTypeInterface $tableType, array $tableOptions, array $columns) {
		$this->tableOptions = $tableOptions;
		$this->tableType = $tableType;
		$this->table = $tableInterface;
		$this->tableId = $this->tableType->getId($this->tableOptions);
		$this->columns = $columns;
		$this->vars = array();

		$this->configureColumnViews();
		$this->configureTableSelection();
		$this->configureTableView();
	}

	protected function configureTableView() {
//		$classes = $additionalClasses = $this->tableOptions['classes'] === null ? array() : explode(' ', $this->tableOptions['classes']);
//		if($this->tableOptions['serverSide']) {
//			$classes = array_merge($classes, array('serverSide'));
//		}
//		if(array_key_exists('class', $this->tableOptions['attr'])) {
//			$classes = explode(' ', $this->tableOptions['attr']['class']);
//			$classes = array_unique(array_merge($classes, $additionalClasses));
//		}
//		$this->tableOptions['attr']['class'] = implode(' ', $classes);
//		if($this->tableOptions['version_hash'] === true) {
//			$hashing = hash_init('sha256', HASH_HMAC, 'pec-datatable');
//			foreach($this->columns as $column) {
//				hash_update($hashing, (string)$column->getHashCode());
//			}
//			$this->tableOptions['version_hash'] = hash_final($hashing);
//		}
	}

	protected function configureColumnViews() {
		$this->columnViews = array();
		foreach($this->columns as $column) {
			if($column->getFilter()) {
				$filterOptions = $column->getFilter()->getFilterOptions();
				if($this->tableOptions['filter_external']) {
					if(!isset($filterOptions['filter_container_id']) && !isset($filterOptions['filter_container_selector'])) {
						$filterOptions['filter_container_id'] = $this->tableId . '_column_filter_' . count($this->columnViews);
					}
					$column->getFilter()->setFilterOptions($filterOptions);
				}
			}
			$this->columnViews[] = $column->createView();
		}
	}

	protected function configureTableSelection() {
		$this->vars['select'] = false;

		foreach($this->columns as $column) {
			$columnOptions = $column->getColumnOptions();
			$translationDomain = $columnOptions['translation_domain'];
			if($translationDomain === null) {
				$columnOptions['translation_domain'] = $this->tableOptions['translation_domain'];
				$column->setColumnOptions($columnOptions);
			}

			if($column->getFilter() !== null) {
				$filterOptions = $column->getFilter()->getFilterOptions();
				if(!isset($filterOptions['translation_domain'])) {
					$filterOptions['translation_domain'] = $columnOptions['translation_domain'];
					$column->getFilter()->setFilterOptions($filterOptions);
				}
			}

			if($column->getColumnType() instanceof SelectColumnType) {
				$this->vars['select'] = array('style' => 'api', 'className' => 'selected');
			}
		}
	}

	public function hasPrePopulatedData() {
		return isset($this->tableOptions['data']) && $this->tableOptions['data'] !== null;
	}

	public function getPrePopulatedData($serverSide = null) {
		if($this->hasPrePopulatedData()) {
			return $this->table->createJsonData($serverSide);
		}
		return '';
	}

	public function getTotalResults() {
		return $this->table->getTotalResults();
	}

	/**
	 * Get the options for the table.
	 *
	 * @return array an array containing table options.
	 */
	public function getTableOptions() {
		return $this->tableOptions;
	}

	/**
	 * Set the options for the table.
	 *
	 * @param array $tableOptions an array containing table options.
	 * @return $this
	 */
	public function setTableOptions(array $tableOptions) {
		$this->tableOptions = $tableOptions;
		return $this;
	}

	/**
	 * Get the id of the table.
	 *
	 * @return string the id of the table.
	 */
	public function getTableId() {
		return $this->tableId;
	}

	/**
	 * Gets the column views for the table.
	 *
	 * @return ColumnView[] an array containing the views for all the columns belonging to the table
	 */
	public function getColumns() {
		return $this->columnViews;
	}

	/**
	 * Sets the column views for the table.
	 *
	 * @param ColumnView[] $columns the column views array to set
	 * @return $this
	 */
	public function setColumns($columns) {
		$this->columnViews = $columns;
		return $this;
	}

	/**
	 * <p>Get an array that contains all toggleable columns, grouped by the column group they are associated to.</p>
	 *
	 * <p>The group a column belongs to can be defined for every individual column via the <code>column_group</code>
	 * option. Any non-null value must also be defined in the <code>column_groups</code> option of the table.</p>
	 *
	 * <p>In case a column does not provide a specific column group alias, the column will belong to the column group
	 * <code>null</code> (i.e. the default group, all columns belong to), indexed by <code>PHP_INT_MAX</code>.</p>
	 *
	 * <p>The resulting array has the following structure:</p>
	 * <code>
	 *  array(
	 *     0 => array(
	 *       'label' => columnLabel,
	 *       'translation_domain' => translationDomain,
	 *       'columns' => array(
	 *         column1,
	 *         column2, ...
	 *       )
	 *     ), ...
	 *  )
	 * </code>
	 * <p>where <code>columnGroupAlias</code> is an entry of the table types <code>column_group</code> option and for every
	 * column group, a <code>label</code> and <code>translation_domain</code> is defined, as well as an array of all
	 * <code>columns</code> (<code>\StingerSoft\DatatableBundle\Column\ColumnView</code> instances) belonging the
	 * column group.</p>
	 *
	 * @return array an array that contains all toggleable columns, grouped by the column group they are associated to.
	 */
	public function getToggleableColumns() {
		if($this->toggleableColumns === null) {
			$this->toggleableColumns = array();
			$columnGroups = $this->tableOptions['column_groups'];
			$keys = $columnGroups !== null ? array_keys($columnGroups) : array();
			foreach($this->columnViews as $column) {
				$columnGroup = $column->vars['column_group'];
				$columnIndex = $columnGroup !== null ? array_search($columnGroup, $keys, true) : PHP_INT_MAX;
				if($column->vars['toggle_visible']) {
					if(!array_key_exists($columnIndex, $this->toggleableColumns)) {
						$this->toggleableColumns[$columnIndex] = array(
							'label' => $this->getColumnGroupLabel($columnGroup),
							'translation_domain' => $this->getColumnGroupTranslationDomain($columnGroup),
							'columns' => array($column)
						);
					} else {
						$this->toggleableColumns[$columnIndex]['columns'][] = $column;
					}
				}
			}
			ksort($this->toggleableColumns);
		}
		return $this->toggleableColumns;
	}

	/**
	 * Get whether there is at least one column that can be filtered.
	 *
	 * @return boolean <code>true</code> if there is at least one column that can be filtered, <code>false</code>
	 * otherwise
	 */
	public function hasFilterableColumns() {
		return count($this->getFilterableColumns()) > 0;
	}

	/**
	 * Get all columns that are filterable and provide a filter instance.
	 *
	 * @return Column[] an array of filterable columns, may be empty
	 */
	public function getFilterableColumns() {
		if($this->filterColumns === null) {
			$this->filterColumns = array();
			foreach($this->columnViews as $index => $column) {
				if($column->filter !== null) {
					$this->filterColumns[$index] = $column;
				}
			}
		}
		return $this->filterColumns;
	}

	/**
	 * Get the label that is defined for a specific column group.
	 *
	 * <p>In case no column group definition can be found under the given alias, the given alias is returned.
	 * Otherwise the value for the <code>label</code> option is returned (which may be <code>null</code>).
	 *
	 * @param string $columnGroupAlias the key or alias of the column group to get the label for.
	 * @return string|null the value for the label option of the column group (may be <code>null</code>),
	 *                                 if no column group can be found for the given alias, the given alias itself
	 *                                 will be returned and used as the label as it may be humanized in the rendered
	 *                                 view.
	 */
	protected function getColumnGroupLabel($columnGroupAlias) {
		if($this->tableOptions['column_groups']
			&& array_key_exists($columnGroupAlias, $this->tableOptions['column_groups'])
			&& array_key_exists('label', $this->tableOptions['column_groups'][$columnGroupAlias])
		) {
			// we return the label!
			return $this->tableOptions['column_groups'][$columnGroupAlias]['label'];
		}
		return $columnGroupAlias;
	}

	/**
	 * Get the translation domain that is defined for a specific column group.
	 *
	 * <p>In case no column group definition can be found under the given alias, <code>false</code> is returned.
	 * In case the column group defined a translation domain, it is returned, otherwise the value for the
	 * <code>translation_domain</code> option of the table is returned.
	 *
	 * @param string $columnGroupAlias the key or alias of the column group to get the translation domain for.
	 * @return bool|string|null <code>false</code> in case no column group cannot be found for the given alias, the
	 *                                 value for the translation_domain option of the column group or the value for
	 *                                 translation_domain option of the table otherwise
	 */
	protected function getColumnGroupTranslationDomain($columnGroupAlias) {
		if($this->tableOptions['column_groups']
			&& array_key_exists($columnGroupAlias, $this->tableOptions['column_groups'])
		) {
			$groupEntry = $this->tableOptions['column_groups'][$columnGroupAlias];
			if(array_key_exists('translation_domain', $groupEntry)) {
				// we return the translation_domain !
				return $groupEntry['translation_domain'];
			}

			return $this->tableOptions['translation_domain'];
		}
		return false;
	}
}