/**
 * @param tableId - The id to scan for columns/th's 
 * @param columnSelectorId - The id to append the created column inputs to
 */
PecDataTableColumnSelector = function(tableId, columnSelectorId) {
	//Store the Id's, just to be sure
	this._tableId = tableId;
	this._columnSelectorId = columnSelectorId;
	//
	this._table = jQuery('#' + tableId);
	this._columnSelector = jQuery('#' + columnSelectorId);
	
	//Array of column(int) => visible(bool)
	this._columnVisibility = [];
	
	//Column reset button
	this._showColumnReset = true;
	this._columnResetId = this._columnSelectorId + '_reset_selection';
	this._columnResetText = Translator.trans('stinger_soft_datatables.columns.reset', {}, 'StingerSoftDatatableBundle');
	this._columnResetIcon = "fa-undo";
	
	//
	this._columnSelectorWidth = 250;
	this._columnToggleClass = "column-toggle-vis";
	
	//
	var that = this;

	/**
	 *
	 */
	this.init = function() {
		//Extract the initial column setup
		this.getColumnVisibility();
		//Build the list
		this.initColumnSelector();
		//Connect to the datatable
		this.initInputListener();
	};
	
	/**
	 * Reset the column selector back to its original state as defined
	 * via data attributes on the table headers.
	 */
	this.resetColumnSelection = function() {
		this._columnSelector.find('input.' + this._columnToggleClass).each(function() {
			var $column = jQuery(this);
			var columnId = $column.data('column');
			//Emulate a click for every column not fitting the standard			
			if($column.prop('checked') != that._columnVisibility[columnId]) {
				$column.click();
			}
		}); 
	};

	/**
	 * Creates a global array with the visibility defined per column
	 * based on the original data-visible attribute.
	 */
	this.getColumnVisibility = function() {
		//		
		this._columnVisibility = [];
		var counter = 0;
		//
		this._table.find('th').each(function() {
			//
			var $th = jQuery(this);
			//The column counter matches the data-column attribute on the column selector
			if(typeof $th.data('visible') !== 'undefined') {
				that._columnVisibility[counter] = $th.data('visible') ? true : false;
			} else {
				that._columnVisibility[counter] = false;
			}
			
			//
			counter++;					
		});
	};

	/**
	 *
	 */
	this.initColumnSelector = function() {		
		//Counter to track if any column headers have been found
		var counter = 0;

		//Scan the given table id for headers and iterate over all
		this._table.find('th').each(function() {
			//
			var $th = jQuery(this);
			var thText = $th.text();
			//
			if(thText != '') {
				//Define the text to show in the column selection box
				var columnName = thText;				
				//Allow to override the column selector text via data attribute
				if(typeof $th.data('columnSelectorText') !== 'undefined') {
					columnName = $th.data('columnSelectorText');
				}
				//A little cleanup
				columnName = jQuery.trim(columnName);

				//Id unique by table and counter
				var id = tableId + '_column_' + counter;

				//Build the input
				var $div = jQuery('<div class="clearfix columnSelectorContent"></div>');
				var $label = jQuery('<label class="columnSelectorLabel mt-checkbox mt-checkbox-outline" for="' + id + '"></label>');
				$div.append($label);
				//Rough calculation to fit text and input checkbox on one line
				var spanLabelWidth = that._columnSelectorWidth-40;				
				var $spanLabel = jQuery('<div class="columnSelectorSpanLabel" style="width:' + spanLabelWidth + 'px; display: inline;">' + columnName + '</div>');
				var $spanControl = jQuery('<input id="' + id + '" class="' + that._columnToggleClass + ' columnSelectorSpanControl" type="checkbox" data-column="' + counter + '" /><span></span>');
				var $columnFilterItem = jQuery('<li></li>');
				$label.append($spanControl).append($spanLabel);
				$columnFilterItem.append($div);
				
				//Add the entry
				that._columnSelector.append($columnFilterItem);				
			}	
			counter++;
		});
		
		//
		if(counter > 0) {
			//Give the column selector a width
			this._columnSelector.css('width', this._columnSelectorWidth + 'px');
			
			//
			if(this._showColumnReset) {
				this._columnSelector.append('<li><div class="divider columnSelectorResetDivider"></div></li>');
				var $columnResetBtn = jQuery('<li><a id="' + this._columnResetId + '" class="columnSelectorResetBtn" href="javascript:void(0);"><i class="fa fa-fw ' + this._columnResetIcon + '"></i> ' + this._columnResetText + '</a></li>');
				this._columnSelector.append($columnResetBtn);
				//Register the reset function
				$columnResetBtn.click(function() {
					that.resetColumnSelection();
				});
			}
		}
	};

	/**
	 * Register the click listener on the column selector input boxes
	 * to show/hide the columns accordingly
	 */
	this.initInputListener = function() {
		//Listen for the initialization of the Datatable
		this._table.on('datatable-initialized.pec', function(event, table) {					
			//Verify that we are meant by this event 
			if(jQuery(table).attr('id') == that._tableId) {
				that._columnSelector.find('input.' + that._columnToggleClass).on('click', function (e) {
					// Toggle the visibility, aligned to the checkbox
					var $column = jQuery(this);
					table.DataTable().column($column.data('column')).visible($column.is(':checked'));
					//Tooltip
					if($column.is(':checked')) {
						if(typeof StingerSoftPlatform !== 'undefined') {
							//Header
							StingerSoftPlatform.tooltip.initTooltips(false, jQuery(table.DataTable().column($column.data('column')).header()));
							//Cells
							jQuery.each(table.DataTable().column($column.data('column')).nodes(), function(key, value) {
								StingerSoftPlatform.tooltip.initTooltips(false, jQuery(value));
							});	
						}
					}
					if(that.hasChildRowsOpen()) {
						that.delegateColumnRedraw();
					}
				}); 

				that._columnSelector.find('input.' + that._columnToggleClass).each(function() {
					//Initialize the checkboxes with the status of DataTables to rely on the dataTable state-save functionality
					var $column = jQuery(this);
					$column.prop('checked', table.DataTable().column($column.data('column')).visible());
				}); 
				//UNIC... na, lassen wir das 
				//jQuery.uniform.update();
			} 
		});
	};
	
	this.hasChildRowsOpen = function() {
		var childrenOpen = this._table.find('> tbody > tr.child').length > 0; 
		return childrenOpen;
	};
	
	this._columnChangedRedrawTimeout = undefined;
	
	this.delegateColumnRedraw = function() {
		if(this._columnChangedRedrawTimeout) {
			clearTimeout(this._columnChangedRedrawTimeout);
			this._columnChangedRedrawTimeout = undefined;
		}
		var that = this;
		this._columnChangedRedrawTimeout = setTimeout(function() {
			that._table.DataTable().draw(false);
		}, 200);
	};
	
	/**
	 * @param string columnToggleClass - The class to use as identifier for column selector inputs
	 */
	this.setColumnToggleClass = function(columnToggleClass) {
		this._columnToggleClass = columnToggleClass;
	};
	
	/**
	 * @param string columnResetIcon - The fa-ICON to use
	 */
	this.setColumnResetIcon = function(columnResetIcon) {
		this._columnResetIcon = columnResetIcon;
	};
	
	/**
	 * @param int columnSelectorWidth - The width of the dropdown/up
	 */
	this.setColumnSelectorWidth = function(columnSelectorWidth) {
		this._columnSelectorWidth = columnSelectorWidth;
	};
	
	/**
	 * @param string columnResetText - The translated text for the selector reset 
	 */
	this.setColumnResetText = function(columnResetText) {
		this._columnResetText = columnResetText;
	};
	
	/**
	 * @param boolean showColumnReset - To show a column reset button or not
	 */
	this.setShowColumnReset = function(showColumnReset) {
		this._showColumnReset = showColumnReset;
	};
};


var PecDataTableFilter = function (table, tr, td, columnIndex) {
	this._table = table;
	this._DataTable = undefined;
	this._tr = tr;
	this._td = td;
	this._columnIndex = columnIndex;
	this._column = undefined;
	this._container = undefined;
	this._filterFieldName = undefined;
	this._remoteSetTimeout = undefined;
};

PecDataTableFilter.prototype.initialize = function () {
	this._DataTable = this._table.getDataTable();
	this._column = this._DataTable.column(this._columnIndex);
	this._filterFieldName = this._td.getDataValueOrDefault('filtering-field-name', this._columnIndex);
	var filterType = this._td.getDataValueOrDefault('filtering-type', false);
	var width = Math.max(parseInt(this._td.css('width')), filterType === 'clear' ? 10 : 40);
	this._container = jQuery('<div class="value-selector" style="width: ' + width + 'px"></div>"');
	this._container.appendTo(this._td);
};

PecDataTableFilter.prototype.persistFilter = function (value) {
	var that = this;
	this.persistFilterLocal(value);
	if(this._remoteSetTimeout != undefined) {
		clearTimeout(this._remoteSetTimeout);
		this._remoteSetTimeout = undefined;
	}
	if(this._remoteSetTimeout == undefined) {
		this._remoteSetTimeout = setTimeout(function () {
			that.persistFilterRemote(value);
		}, 250);
	}
};

PecDataTableFilter.prototype.persistFilterLocal = function(value) {
	if (this._table.isFilterPersisted() && typeof simpleStorage !== "undefined" && simpleStorage.canUse()) {
		var key = this._table.getPersistFilterKey(this._column);
		if (value) {
			//StingerSoftPlatform.logger.debug('setting value', value, 'for key', key);
			simpleStorage.set(key, value);
		} else {
			//StingerSoftPlatform.logger.debug('deleting value for key', key);
			simpleStorage.deleteKey(key);
		}
	}
};

PecDataTableFilter.prototype.persistFilterRemote = function(value) {
	if (this._table.isFilterPersisted() && this._table.hasRemoteFilterEnabled()) {
		jQuery.ajax({
				type: 'POST',
				url: this._table.getRemoteFilterUri(),
				data: this.buildRemoteFilterRequestData(true, value)
			}
		);
	}
};

PecDataTableFilter.prototype.getPersistedFilter = function (functionDelegate) {
	if(this._table.hasRemoteFilterEnabled()) {
		this.getPersistedFilterRemote(functionDelegate);
	} else {
		this.getPersistedFilterLocal(functionDelegate);
	}
};

PecDataTableFilter.prototype.getPersistedFilterRemote = function(functionDelegate) {
	if (this._table.isFilterPersisted() && this._table.hasRemoteFilterEnabled()) {
		StingerSoftPlatform.blockUI(this._container);
		var that = this;
		jQuery.ajax({
				type: 'POST',
				url: this._table.getRemoteFilterUri(),
				data: this.buildRemoteFilterRequestData(false)
			}
		).success(function(data) {
				if(data && data.status == 'success') {
					functionDelegate.apply(this, [data.object]);
				}
			}).always(function() {
				StingerSoftPlatform.unblockUI(that._container);
			});
	}
};

PecDataTableFilter.prototype.getPersistedFilterLocal = function (functionDelegate) {
	if (this._table.isFilterPersisted() && typeof simpleStorage !== "undefined" && simpleStorage.canUse()) {
		var key = this._table.getPersistFilterKey(this._column);
		//StingerSoftPlatform.logger.debug('getting value for key', key, ' = ', value);
		var value = simpleStorage.get(key);
		functionDelegate.apply(this, [value]);
	}
	functionDelegate.apply(this, [undefined]);
};

PecDataTableFilter.prototype.buildRemoteFilterRequestData = function(persist, value) {
	var field = {field: this._filterFieldName};
	if(typeof value !== 'undefined') {
		field = jQuery.extend(field, {value: value});
	}
	return {
		filter: {
			persist: persist,
			tableId: this._table.getTableId(),
			filterPrefix: this._table.getPersistFilterPrefix(),
			fields: [
				field
			]
		}
	};
};

PecDataTableFilter.prototype.getUniqueSortedValuesForColumn = function () {
	var $tds = this._column.nodes().to$();
	var data = [];
	var addedValues = [];
	$tds.each(function (i, td) {
		var $td = jQuery(td);
		var value = $td.getDataValueOrDefault('filtering-value', false);
		if (value === false) value = $td.text() + "";
		value = isNaN(value) ? value.trim() : value.toString();
		var label = $td.getDataValueOrDefault('filtering-label', value);
		label = isNaN(value) ? label.trim() : label.toString();
		if (value.length > 0 && label.length > 0 && jQuery.inArray(value, addedValues) === -1) {
			addedValues.push(value);
			var entry = {"value": value, "label": label};
			data.push(entry);
		}
	});
	return data.sort(function (a, b) {
		if (isNaN(a.label) || isNaN(b.label)) {
			return a.label.localeCompare(b.label);
		} else {
			return a.label - b.label;
		}
	});
};

PecDataTableFilter.prototype.generate = function (table, tr, td, columnIndex) {
	var filterType = td.getDataValueOrDefault('filtering-type', false);
	var filter = false;
	if (filterType === 'select2') {
		filter = new PecDataTableFilterSelect2(table, tr, td, columnIndex);
	} else if (filterType === 'input') {
		filter = new PecDataTableFilterInput(table, tr, td, columnIndex);
	} else if (filterType === 'clear') {
		filter = new PecDataTableFilterReset(table, tr, td, columnIndex);
	}
	if (filter) {
		filter.initialize();
		filter.initializeUI();
		return filter;
	}
	return undefined;
};

PecDataTableFilter.prototype.refreshColumn = function() {
	this._column.draw();
};

PecDataTableFilter.prototype.initializeUI = function () { };
PecDataTableFilter.prototype.clear = function () { };
PecDataTableFilter.prototype.updateFieldWithPersistedValue = function (successDelegate) { if(successDelegate) {successDelegate.call(this, this);}};
PecDataTableFilter.prototype.applyFilter = function () {};


var PecDataTableFilterSelect2 = function (table, tr, td, columnIndex) {
	PecDataTableFilter.apply(this, arguments);

	this._field = undefined;
	var that = this;

	this.clear = function () {
		that._field.select2("val", '', false);
		that.applyFilter(true);
	};

	this.updateFieldWithPersistedValue = function (successDelegate) {
		that.getPersistedFilter(function(persistedFilterValue) {
			if (typeof persistedFilterValue !== 'undefined') {
				that._field.select2("val", persistedFilterValue, false);
				that.applyFilter(true);
			}
			if(successDelegate) {
				successDelegate.call(that, that);
			}
		});
	};

	this.applyFilter = function(queue) {
		var filterValue = that._field.select2("val");
		that.persistFilter(filterValue);
		if (filterValue) {
			filterValue = '^' + jQuery.fn.dataTable.util.escapeRegex(filterValue) + '$';
		}
		that._column.search(filterValue, true, false, false);
		if(!queue) {
			that._column.draw();
		}
	};

	this.initializeUI = function () {
		that._container.addClass('pec-data-filter-type-select2');
		that._field = jQuery('<select class="input-inline input-sm form-control" style="width: 100%"><option value=""></option></select>')
			.appendTo(that._container)
			.on('click', function (e) { e.stopPropagation(); })
			.on('change', function() { that.applyFilter(false) });
		var data = that.getUniqueSortedValuesForColumn();
		jQuery(data).each(function (i, entry) {
			that._field.append('<option value="' + entry.value + '">' + entry.label + '</option>');
		});
		that._field.select2({
				"dropdownAutoWidth": true,
				"width": "100%",
				"allowClear": true,
				"dropdownCss": {"max-width": "300px"}
			}
		);
	}
};
PecDataTableFilterSelect2.inheritsFrom(PecDataTableFilter);


var PecDataTableFilterInput = function (table, tr, td, columnIndex) {
	PecDataTableFilter.apply(this, arguments);

	this._field = undefined;
	var that = this;

	this.clear = function () {
		that._field.val('');
		that.applyFilter(true);
	};

	this.updateFieldWithPersistedValue = function (successDelegate) {
		that.getPersistedFilter(function(persistedFilterValue) {
			if (typeof persistedFilterValue !== 'undefined') {
				that._field.val(persistedFilterValue);
				that.applyFilter(true);
			}
			if(successDelegate) {
				successDelegate.call(that, that);
			}
		});
	};

	this.applyFilter = function(queue) {
		var filterValue = that._field.val();
		that.persistFilter(filterValue);
		that._column.search(filterValue, false, true, true);
		if(!queue) {
			that._column.draw();
		}
	};

	this.initializeUI = function () {
		that._container.addClass('pec-data-filter-type-input');
		that._field = jQuery('<input class="input-inline input-sm form-control" style="width: 100%" />')
			.appendTo(that._container)
			.on('click', function (e) { e.stopPropagation(); })
			.on('input', function() { that.applyFilter(false) });
	}
};
PecDataTableFilterInput.inheritsFrom(PecDataTableFilter);

var PecDataTableFilterReset = function (table, tr, td, columnIndex) {
	PecDataTableFilter.apply(this, arguments);

	this._field = undefined;
	var that = this;

	this.initializeUI = function () {
		that._container.addClass('pec-data-filter-type-reset');
		var title = Translator.trans('stinger_soft_datatables.filter.reset', {}, 'StingerSoftDatatableBundle');
		jQuery('<a href="javascript:void(0)" style="display: block;" class="text-center" title="' + title + '"><i class="fa fa-fw fa-trash"></i></a>')
			.appendTo(that._container)
			.on('click', function (e) {
				e.stopPropagation();
				that._DataTable.columns().every(function (index) {
					var filterDefinition = that._table.getFilterDefinition(index);
					if (filterDefinition) {
						filterDefinition.clear();
					}
				});
				that._DataTable.columns().draw();
			});
	}
};
PecDataTableFilterReset.inheritsFrom(PecDataTableFilter);

PecDataTable = function (table) {
	this._table = jQuery(table);
	this._dataTable = null;
	this._DataTable = null;
	this.isInitialized = false;
	this.markForRedraw = false;
	this._beforeDetailShowCallback = undefined;
	this._createDetailCallback = undefined;
	this._afterDetailShowCallback = undefined;
	this._persistFilter = false;
	this._persistFilterPrefix = undefined;
	this._orderCellsTop = true;
	this._filters = [];
	this._remoteFilterUri = false;
	this._stateSaveKey = false;
	this._columnResize = true;
	this._dom = 'lfrtip';
	this._saveDetails = false;
	var that = this;

	this.hasRemoteFilterEnabled = function() {
		return that._remoteFilterUri !== false;
	};

	this.getTableId = function() {
		return that._table.attr('id');
	};

	this.getRemoteFilterUri = function() {
		return that._remoteFilterUri;
	};

	this.getFilterDefinitions = function () {
		return that._filters;
	};

	this.getFilterDefinition = function (columnIndex) {
		return that._filters[columnIndex];
	};

	this.addFilterDefinition = function (tr, td, columnIndex) {
		that._filters[columnIndex] = PecDataTableFilter.prototype.generate(that, tr, td, columnIndex);
	};

	this.isFilterPersisted = function () {
		return that._persistFilter;
	};

	this.getPersistFilterPrefix = function() {
		return that._persistFilterPrefix;
	};

	this.getPersistFilterKey = function (column) {
		return 'pec-data-table.' + that.getPersistFilterPrefix() +  '_' + column.index();
	};

	/**
	 * Loads the settings configured as data-attributes or the default values
	 */
	this.loadSettings = function () {
		that._columnSelector = that._table.data('column-selector');
		that._aLengthMenu = [
			[5, 20, 100, -1],
			[5, 20, 100, Translator.trans("stinger_soft_datatables.all")]
		];

		that._paging = that._table.getDataValueOrDefault('paging', true);
		that._sorting = that._table.getDataValueOrDefault('sorting', false);
		that._columns = that._table.getDataValueOrDefault('columns', null);
		that._columnDefs = that._table.getDataValueOrDefault('column-defs', null);
		that._allowSorting = that._table.getDataValueOrDefault('allow-sorting', true);
		that._filtering = that._table.getDataValueOrDefault('filtering', false);
		that._excluded = that._table.getDataValueOrDefault('filtering-exclude', false);
		that._scrollY = that._table.getDataValueOrDefault('scroll-y', '');
		that._scrollX = that._table.getDataValueOrDefault('scroll-x', '');
		that._scrollCollapse = that._table.getDataValueOrDefault('scroll-collapse', false);
		that._detailsTriggerSelector = that._table.getDataValueOrDefault('details-trigger-selector', false);
		that._persistFilterPrefix = that._table.getDataValueOrDefault('filter-persist-prefix', false);
		that._isEmpty = that._table.find('tbody tr').length == 0;
		that._orderCellsTop = that._table.getDataValueOrDefault('order-cells-top', true);
		that._fixedLeftColumns = that._table.getDataValueOrDefault('fixed-left-columns', false);
		that._remoteFilterUri = that._table.getDataValueOrDefault('filter-persist-uri', false);
		that._language = that._table.getDataValueOrDefault('language', {});
		that._stateSaveKey = that._table.getDataValueOrDefault('state-save-key', false);
		that._columnResize = that._table.getDataValueOrDefault('column-resize', true);
		that._dom = that._table.getDataValueOrDefault('tables-dom', 'lfrtip');
		that._saveDetails = that._table.getDataValueOrDefault('save-details', false);

		that._ordering = [[0, 'asc']];

		if (that._persistFilterPrefix !== false) {
			if (typeof simpleStorage === 'undefined') {
				StingerSoftPlatform.loadScript('StingerSoftPlatform/plugins/simpleStorage.js', function () {
					that._persistFilter = true;
				});
			} else {
				that._persistFilter = true;
			}
		}
	};

	this.setBeforeDetailShowCallback = function (callback) {
		that._beforeDetailShowCallback = callback;
	};

	this.setCreateDetailCallback = function (callback) {
		that._createDetailCallback = callback;
	};

	this.setAfterDetailShowCallback = function (callback) {
		that._afterDetailShowCallback = callback;
	};

	this.getJQueryTable = function () {
		return that._table;
	};

	this.getDataTableJQuery = function () {
		return that._dataTable;
	};

	this.getDataTable = function () {
		return that._DataTable;
	};

	this.redraw = function () {
		that._DataTable.draw(true);
	};
	
	/**
	 * Using order.neutral() plugin
	 */
	this.resetSorting = function() {
		that.getDataTableJQuery().api().order.neutral().draw();
	};

	/**
	 * 
	 */
	this.resizeHeight = function (newHeight) {
		var table = this.getDataTableJQuery();
		if (table.parent().hasClass('dataTables_scrollBody')) {
			table.parent().css('height', newHeight + 'px');
			that._DataTable.columns.adjust().draw();
		}
	};

	this.setFixedColumns = function () {
		if (that._fixedLeftColumns) {
			new $.fn.dataTable.FixedColumns(that._dataTable, {
				"leftColumns": that._fixedLeftColumns
			});
		}
	};

	this.setColumnSelector = function () {
		if (that._columnSelector) {

			var $columnSelector = jQuery(that._columnSelector);
			var switcherAllData = $columnSelector.getDataValueOrDefault('switcher-all', false);
			var switcherNoneData = $columnSelector.getDataValueOrDefault('switcher-none', false);

			var checkBoxes = jQuery(that._columnSelector + ' input[type="checkbox"]');

			checkBoxes.change(function () {
				that.toggleColumnVis(that._dataTable, jQuery(this));
			});

			that.createAllToggler(switcherAllData, checkBoxes, true, that._dataTable);
			that.createAllToggler(switcherNoneData, checkBoxes, false, that._dataTable);
		}
	};

	this.setFiltering = function () {
		if (that._filtering !== false && !that._isEmpty) {
			var excludedColumns = [];
			if (that._excluded !== false) {
				that._excluded += '';
				excludedColumns = that._excluded.replace(' ', '').split(',');
			}
			that._table.find(that._filtering).each(function (i) {
				if (jQuery.inArray(i + '', excludedColumns) == -1) {
					var container = jQuery('<div class="column-selector"></div>"');
					container.appendTo(jQuery(this));
					var select = jQuery('<select><option value=""></option></select>')
						.appendTo(container)
						.on('change', function () {
							var val = $(this).val();
							that._dataTable.column(i)
								.search(val ? '^' + $(this).val() : val, true, false)
								.draw();
						}).on('click', function (e) {
							e.stopPropagation();
						});
					that._dataTable.column(i).data().unique().sort().each(function (d, j) {
						var text = decodeNastyHtmlEntitiesForSearch(d);
						select.append('<option value="' + text + '">' + text + '</option>');
					});
				}
			});
		}
	};

	this.initOnTabShown = function () {
		jQuery('a[data-toggle="tab"][href="#' + that._tabPane.attr('id') + '"]').on('shown.bs.tab', function (e) {
			that._initReal();
		});
	};

	this.initTabHandler = function () {
		jQuery('a[data-toggle="tab"][href="#' + that._tabPane.attr('id') + '"]').on('shown.bs.tab', function (e) {
			that._DataTable.columns.adjust().draw();
		});
	};

	this.detectContext = function () {
		that._insideTab = that.isInsideTab();
		that._tabPane = null;
		if (that._insideTab) {
			that._tabPane = that._table.parents('.tab-pane');
		}
	};


	this.init = function () {
		that.detectContext();
		if (that._insideTab) {
			if (!that._tabPane.hasClass('active')) {
				that.initOnTabShown();
			} else {
				that._initReal();
			}
			that.initTabHandler();
		} else {
			that._initReal();
		}
	};

	/**
	 * Initializes the data table and loads all configured plugins (e.g. filtering, column selector, etc.)
	 *
	 */
	this._initReal = function () {
		that.isInitialized = that._table.data('isInitialized');
		if (that.isInitialized == '1') {
			return;
		}
		that.loadSettings();
		if (that._sorting !== false) {
			that._ordering = that._sorting;
		}
		
		//Build datatables DOM
		var dataTableDOM = that._dom;
		if(that._columnResize) {
			dataTableDOM = dataTableDOM + 'Z';
		}
		
		//
		that._dataTable = that._table.dataTable({
			"aLengthMenu": that._aLengthMenu,
			"paging": that._paging,
			"scrollY": that._scrollY,
			"scrollX": that._scrollX,
			"scrollCollapse": that._scrollCollapse,
			"order": that._ordering,
			"ordering": that._allowSorting,
			"columns": that._columns,
			"columnDefs": that._columnDefs,
			"orderCellsTop": that._orderCellsTop,
			"language": that._language,
			"stateSaveCallback": that.fnStateSaveCallback,
			"stateLoadCallback": that.fnStateLoadCallback,
			dom: dataTableDOM
		});
		that._DataTable = that._table.DataTable();
		
		//https://jira.stinger-soft.net/browse/MTU-2241, to correct if https://www.datatables.net/forums/discussion/33118/q-column-visibility-bvisible-sometimes-true-sometimes-1-sometimes#latest gives an answer 
		that._DataTable.columns().every(function(index, tableLoop, columnLoop) {
			this.visible(this.visible() ? true : false);
		});

		that.appendFilter();
		that.setFixedColumns();
		that.setColumnSelector();
		that.setFiltering();
		that.appendDetailTrigger();

		that._table.data('isInitialized', '1');
		that._table.trigger('datatable-initialized.pec', [that._table]);
	};

	/**
	 * Set a state save callback for Datatables, which is
	 * called with the parameters settings and data to
	 * store the given JSON data somewhere (local, remote etc.)
	 */
	this.setStateSaveCallback = function(callback) {
		that.fnStateSaveCallback = callback;
	};
	
	/**
	 * Set a state load callback for Datatables, which is
	 * called with the parameter settings to load the previously
	 * stored JSON data from somewhere (local, remote etc.)
	 */
	this.setStateLoadCallback = function(callback) {
		that.fnStateLoadCallback = callback;
	};	

	/**
	 * Basic state save callback storing the given data
	 * locally or in the session.
	 */
	this.fnStateSaveCallback = function(settings, data) {
		try {
			var key = that.getStateSaveKey(settings);
			(settings.iStateDuration === -1 ? sessionStorage : localStorage).setItem(
					key,
					JSON.stringify(data)
			);
		} catch (e) {}
	};	
	
	/**
	 * Basic state load callback loading the stored data
	 * from a local storage or session.
	 */
	this.fnStateLoadCallback = function(settings) {
		try {
			var key = that.getStateSaveKey(settings);
			return JSON.parse(
				(settings.iStateDuration === -1 ? sessionStorage : localStorage).getItem(
						key
				)
			);
		} catch (e) {}
	};
	
	/**
	 * If data-state-save-key is defined, that key prefixed by "PecDataTable_"
	 * is returned. Otherwise, it will fallback to the original datatables
	 * key if the settings are given. If nothing has been passed, just
	 * "PecDataTable" is used as fallback key.
	 */
	this.getStateSaveKey = function(settings) {
		//
		if(that._stateSaveKey) {
			//If another one is configured use that key, prefixed by PecDataTable to immediately know the overriding
			return 'PecDataTable_' + that._stateSaveKey;
		} else if(settings !== 'undefined') {
			//The original datatables key
			return 'DataTables_' + settings.sInstance + '_' + location.pathname;
		} else {
			//Ultimate fallback if everything is missing
			return 'PecDataTable';
		}
	};
	
	/**
	 * 
	 */
	this.appendFilter = function () {
		var $header = jQuery(that._DataTable.table().header());
		var $filterRow = $header.find('tr.filtering');
		$filterRow.find('td').each(function (i, item) {
			var $td = jQuery(item);
			var filterType = $td.getDataValueOrDefault('filtering-type', false);
			if (filterType !== false) {
				that.addFilterDefinition($filterRow, $td, i);
			}
		});
		var filters = that.getFilterDefinitions();
		var filtersUpdatedYet = 0;
		var filtersToBeUpdated = filters.length;
		if(!that.hasRemoteFilterEnabled()) {
			var waitForFilterLoadTimeout = setInterval(function () {
				if (filtersUpdatedYet >= filtersToBeUpdated) {
					clearInterval(waitForFilterLoadTimeout);
					that._DataTable.columns().draw();
				}
			}, 50);
		}
		for (var key in filters) {
			if(filters.hasOwnProperty(key)) {
				var filter = filters[key];
				if (filter) {
					filter.updateFieldWithPersistedValue(function(filterPointer) {
						filtersUpdatedYet++;
						if(that.hasRemoteFilterEnabled()) {
							filterPointer.refreshColumn();
						}
					});
				}
			}
		}
	};

	this.expandAllChildren = function () {
		var $showDetailLinks = that._table.find('tr:not(.expanded)').find(that._detailsTriggerSelector);
		$showDetailLinks.trigger('click');
	};

	this.collapseAllChildren = function () {
		var $showDetailLinks = that._table.find('tr:not(.collapsed)').find(that._detailsTriggerSelector);
		$showDetailLinks.trigger('click');
	};

	this.collapseRow = function(tr) {
		if(tr && !tr.hasClass('collapsed')) {
			var $showDetailLinks = tr.find(that._detailsTriggerSelector);
			$showDetailLinks.trigger('click');
		}
	};

	this.expandRow = function(tr) {
		if(tr && !tr.hasClass('expanded')) {
			var $showDetailLinks = tr.find(that._detailsTriggerSelector);
			$showDetailLinks.trigger('click');
		}
	};

	/**
	 * 
	 */
	this.appendDetailTrigger = function () {
		if (that._detailsTriggerSelector !== false) {
			that._table.on('click', that._detailsTriggerSelector, function (e) {
				e.preventDefault();
				e.stopPropagation();

				var clicked = jQuery(this);
				var tr = clicked.closest('tr');
				var row = that._DataTable.row(tr);

				//
				if(that._saveDetails) {
					that.saveDetailTriggerAction(row, tr, clicked);
				} else {
					that.detailTriggerAction(row, tr, clicked);	
				}				
			});
		}
	};
	
	/**
	 * 
	 */
	this.detailTriggerAction = function(row, tr, clicked) {
		var content;
		if(row.child.isShown()) {
			// This row is already open - close it
			row.child.hide();
			that.collapseTableRow(tr);
		//
		} else {
			that.expandTableRow(tr);
			// Open this row
			if (that._beforeDetailShowCallback) {
				that._beforeDetailShowCallback(clicked, tr, row);
			} else {
				that.defaultBeforeDetailShowCallback(clicked, tr, row);
			}
			if (that._createDetailCallback) {
				content = that._createDetailCallback(clicked, tr, row);
			} else {
				content = that.defaultCreateDetailCallback(clicked, tr, row);
			}
			var child = row.child(content, 'child');
			child.show();
			if (that._afterDetailShowCallback) {
				that._afterDetailShowCallback(content, clicked, tr, row, child);
			} else {
				that.defaultAfterDetailShowCallback(content, clicked, tr, row, child);
			}
		}
	};
	
	/**
	 * 
	 */
	this.saveDetailTriggerAction = function(row, tr, clicked) {
		var content;
		if(typeof row.child() !== 'undefined' && row.child().length > 0 && jQuery(row.child()[0]).is(':visible')) {
			// This row is already open - close it
			row.child().hide();
			that.collapseTableRow(tr);
		//
		} else {
			that.expandTableRow(tr);
			// Open this row
			if (that._beforeDetailShowCallback) {
				that._beforeDetailShowCallback(clicked, tr, row);
			} else {
				that.defaultBeforeDetailShowCallback(clicked, tr, row);
			}
			if (that._createDetailCallback) {
				content = that._createDetailCallback(clicked, tr, row);
			} else {
				content = that.defaultCreateDetailCallback(clicked, tr, row);
			}
			//
			var loadData = (typeof row.child() === 'undefined' || !row.child.isShown());
			if(loadData) {						
				var child = row.child(content, 'child');
				child.show();
				if(that._afterDetailShowCallback) {
					that._afterDetailShowCallback(content, clicked, tr, row, child);
				} else {						
					that.defaultAfterDetailShowCallback(content, clicked, tr, row, child);
				}
			} else {
				row.child().show();
			}
		}
	};
	
	/**
	 * 
	 */
	this.collapseTableRow = function(tr) {
		tr.removeClass('expanded');
		tr.removeClass('expanded-details');
		tr.addClass('collapsed');
		tr.addClass('collapsed-details');
	};
	
	/**
	 * 
	 */
	this.expandTableRow = function(tr) {
		tr.removeClass('collapsed');
		tr.removeClass('collapsed-details');
		tr.addClass('expanded');
		tr.addClass('expanded-details');
	};

	this.defaultBeforeDetailShowCallback = function (clicked, tr, row) {
	};

	this.defaultCreateDetailCallback = function (clicked, tr, row) {
		return jQuery("<div class='details-scroll'><div class='details-container'></div></div>");
	};

	this.defaultAfterDetailShowCallback = function (content, clicked, tr, row, child) {
		var target = clicked.getDataValueOrDefault('target', clicked.attr('href'));
		StingerSoftPlatform.blockUI(content);
		content.find('.details-container').load(target, function () {
			StingerSoftPlatform.unblockUI(content);
		});
	};

	this.createAllToggler = function (selector, checkBoxes, newValue, table) {
		if (selector) {
			var switcher = jQuery(selector);
			switcher.click(function () {
				checkBoxes.each(function () {
					jQuery(this).prop('checked', newValue).uniform();
					toggleColumnVis(table, jQuery(this));
				});
			});
		}

	};

	this.toggleColumnVis = function (table, box) {
		var aCol = box.data("column");
		var bVis = box.is(':checked');
		jQuery(aCol).each(function (i, iCol) {
			table.fnSetColumnVis(iCol, bVis);
		});
	};

	// this prevents any overhead from creating the object each time
	var element = jQuery('<div />');

	this.decodeNastyHtmlEntitiesForSearch = function (str) {
		if (str && typeof str === 'string') {
			// strip script/html tags
			str = str.replace(/<script[^>]*>([\S\s]*?)<\/script>/gmi, '');
			str = str.replace(/<\/?\w(?:[^"'>]|"[^"]*"|'[^']*')*>/gmi, '');
			element.html(str);
			str = element.text();
			//str = str.replace(/[\u00AD\u002D\u2011]+/g,'');
			element.html('');
		}
		return str;
	};

	this.isHidden = function () {
		//noinspection RedundantIfStatementJS
		if (that._insideTab && !that._tabPane.hasClass('active')) {
			return true;
		}
		return false;
	};

	this.isInsideTab = function () {
		var tabPane = that._table.parents('.tab-pane');
		return tabPane.length > 0;
	};

	this.removeRow = function (rowIndex) {
		if (rowIndex == undefined || rowIndex < 0) {
			StingerSoftPlatform.logger.warn('Undefined row index: ' + rowIndex + ' given for deletion from a table!');
			return;
		}
		that._DataTable.rows(rowIndex).nodes().toJQuery().fadeOut(400, 'swing', function () {
			that._DataTable.rows(rowIndex).remove().draw();
		});
		if (that.isHidden()) {
			that.markForRedraw = true;
		}
	};

	this.updateDataTablesRow = function (rowIndex, data) {
		jQuery(data.columns).each(function (i, entry) {
			var column = entry.column;
			var cell = that._DataTable.cell(rowIndex, column);
			cell.data(entry.value);
		});
		that._DataTable.draw(false);
		if (that.isHidden()) {
			that.markForRedraw = true;
		}
		return that._DataTable.rows(rowIndex);
	};

	this.addDataTablesRow = function (data) {
		var columns = [];
		jQuery(data.columns).each(function (i, entry) {
			columns.push(entry.value);
		});
		var newRow = that._DataTable.row.add(columns);
		that._DataTable.draw(false);
		if (that.isHidden()) {
			that.markForRedraw = true;
		}
		return newRow;
	};

	this.pulsateUpdatedDataTablesRow = function (rowIndex, data) {
		var pulsateParams = {color: "#399bc3", repeat: false};
		if (!data || !data.columns) {
			that._DataTable.rows(rowIndex).nodes().to$().pulsate(pulsateParams);
		} else {
			jQuery(data.columns).each(function (i, entry) {
				var cell = that._DataTable.cell(rowIndex, entry.column);
				cell.nodes().to$().pulsate(pulsateParams);
			});
		}
	};
};

/*
 * Register custom Plugins.
 * In PecDataTable.js as multiple plugins would lead 
 * to a massive amount of extra JS files to load (by DT recommendation).
 * API registration direct and available everywhere.
 */
/**
 * This function will restore the order in which data was read into a DataTable
 * (for example from an HTML source). Although you can set `dt-api order()` to
 * be an empty array (`[]`) in order to prevent sorting during initialisation,
 * it can sometimes be useful to restore the original order after sorting has
 * already occurred - which is exactly what this function does.
 *
 * @name order.neutral()
 * @summary Change ordering of the table to its data load order
 * @author [Allan Jardine](http://datatables.net)
 * @requires DataTables 1.10+
 *
 * @returns {DataTables.Api} DataTables API instance
 *
 * @example
 *    // Return table to the loaded data order
 *    table.order.neutral().draw();
 */
jQuery.fn.dataTable.Api.register('order.neutral()', function() {
	return this.iterator('table', function(s) {
		s.aaSorting.length = 0;
		s.aiDisplay.sort(function(a, b) {
			return a-b;
		});
		s.aiDisplayMaster.sort(function (a, b) {
			return a-b;
		});
	});
});