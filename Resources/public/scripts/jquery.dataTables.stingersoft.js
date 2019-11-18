var StateSaveKeys = {};
Object.defineProperty(StateSaveKeys, "start", {
	value: "_start",
	writable: false
});
Object.defineProperty(StateSaveKeys, "order", {
	value: "_order",
	writable: false
});
Object.defineProperty(StateSaveKeys, "scroller", {
	value: "_scroller",
	writable: false
});
Object.defineProperty(StateSaveKeys, "pageLength", {
	value: "_page_length",
	writable: false
});
Object.defineProperty(StateSaveKeys, "filter", {
	value: "_filter",
	writable: false
});
Object.defineProperty(StateSaveKeys, "visibility", {
	value: "_visibility",
	writable: false
});
Object.defineProperty(StateSaveKeys, "search", {
	value: "_search",
	writable: false
});

/*global StingerSoftPlatform, yadcf */
var StingerSoftJQueryDataTables = {
	"tables": {}
};

StingerSoftJQueryDataTables.setTable = function (id, table) {
	"use strict";
	this.tables[id] = table;
};
StingerSoftJQueryDataTables.getTable = function (id) {
	"use strict";
	return this.tables[id];
};
StingerSoftJQueryDataTables.getTableIds = function () {
	"use strict";
	return Object.keys(this.tables);
};
StingerSoftJQueryDataTables.getTables = function () {
	"use strict";
	return this.tables;
};


function StingerSoftJQueryDataTable(tableId) {
	"use strict";
	this.tableId = tableId;
	this.$columnSelector = jQuery('#' + this.tableId + '_column_selector');
	this.$clearButton = jQuery('#' + this.tableId + '_clear');
	this.$pageLengthSelect = jQuery('#' + this.tableId + '_length');
	this.$searchField = jQuery('#' + this.tableId + '_search');
	this.$reloadButton = jQuery('#' + this.tableId + '_reload');
	this.$table = jQuery("#" + this.tableId);

	this.dataTable = null;
	this.dataTableOptions = {};
	this.columns = [];
	this.stateSaveKey = null;
	this.rowSelectableIds = {};
	this.versionHash = null;

	this.filterableColumns = [];
	this.filterOptions = {};
	this.visibleChildRows = [];

	StingerSoftJQueryDataTables.setTable(tableId, this);
}

StingerSoftJQueryDataTable.prototype.getTableWrapper = function () {
	"use strict";
	return jQuery("#" + this.tableId + "_wrapper");
};

StingerSoftJQueryDataTable.prototype.bindXhr = function () {
	"use strict";
	var that = this;

	this.$table.on('preXhr.dt', function (e, settings, data) {
		if (settings && settings.jqXHR) {
			settings.jqXHR.abort();
		}
		var $body = that.$table.closest('.dataTables_scrollBody');
		$body.addClass('loading').removeClass('loaded');
	});

	this.$table.on('xhr.dt', function (e, settings, data) {
		var $body = that.$table.closest('.dataTables_scrollBody');
		$body.removeClass('loading').addClass('loaded');
		StingerSoftPlatform.tooltip.destroyTooltips($body);
		setTimeout(function () {
			StingerSoftPlatform.tooltip.initTooltips(false, $body);
			that.highlightAllFilteredColumns(false);
		}, 50);
	});
};

/**
 * Highlight filtered columns if shown
 */
StingerSoftJQueryDataTable.prototype.bindColumnVisibility = function () {
	"use strict";
	var that = this;
	this.$table.on('column-visibility.dt', function (e, settings, column, state) {
		if (state) {
			that.highlightAllFilteredColumns(false);
		}
	});
};

/**
 *
 */
StingerSoftJQueryDataTable.prototype.bindInit = function () {
	"use strict";
	var that = this;
	this.$table.on('init.dt', function (e, settings, data) {
		that.markVisibleColumns();
		that.validatePage();
		that.selectCorrectPageLength();
		that.populateSearchField();
		that.moveFooterTools();
		that.bindTooltip();
		that.bindTabHandler();
		that.bindPortletFullscreen();
		that.bindWidgetMoved();
		that.bindColumnVisibility();

		// we trigger a re-draw after a small delay
		setTimeout(that.dataTable.columns.adjust, 50);

		var $wrapper = that.$table.parents('.dataTables_wrapper');
		if ($wrapper.length && that.dataTableOptions.serverSide && that.dataTableOptions.select !== false) {
			$wrapper.addClass('server-side-select');
		}

		that.$table.trigger(that.getEvent('stingersoft.init-dt'), {
			type: 'init-dt-complete',
			tableId: that.tableId,
			tableJQuery: jQuery(that.$table),
			stingerSoftDataTable: that,
			dataTable: that.dataTable
		});

		that.triggerExternalFilters({
			tableId: that.tableId,
			tableJQuery: jQuery(that.$table),
			stingerSoftDataTable: that,
			dataTable: that.dataTable
		});

	});
};

StingerSoftJQueryDataTable.prototype.triggerExternalFilters = function (data) {
	"use strict";
	var that = this;
	var filterField = jQuery('#' + data.tableId + '_filter_container .filter_container');
	if (filterField.length > 0) {
		filterField.each(function (i, item) {
			var $item = jQuery(item);
			var columnId = $item.data('column');
			$item.trigger(that.getEvent('filter-initialized'), jQuery.extend(data, {
				'container': $item,
				'field': $item.find('input, select'),
				'filterSettings': data.stingerSoftDataTable.getFilterableColumnByIndex(columnId),
				'columnId': columnId
			}));
		});
	}
};

StingerSoftJQueryDataTable.prototype.bindPortletFullscreen = function () {
	"use strict";
	var $portlet = this.$table.parents('.portlet:first');
	if ($portlet.length) {
		var that = this;
		$portlet.on('click', '.portlet-title .fullscreen', function (e) {
			e.preventDefault();
			setTimeout(that.dataTable.columns.adjust, 50);
		});
	}
};

StingerSoftJQueryDataTable.prototype.bindWidgetMoved = function () {
	"use strict";
	var that = this;
	jQuery('body').on('dashboard-widget-moved.pec', function (evt, container) {
		if (jQuery.contains(container, that.$table.get(0))) {
			// somebody move a widget where our table is contained in!
			// as we cannot ensure that the widget has the same size, we resize the columns
			setTimeout(that.dataTable.columns.adjust, 50);
		}
	});
};

StingerSoftJQueryDataTable.prototype.bindTooltip = function () {
	"use strict";
	this.$table.find('tbody td').on('mouseenter', '[data-toggle="tooltip"]', function () {
		jQuery(this).tooltip('show');
	});
	this.$table.find('tbody td').on('mouseleave', '[data-toggle="tooltip"]', function () {
		jQuery(this).tooltip('hide');
	});
};

StingerSoftJQueryDataTable.prototype.bindPageLengthSelect = function () {
	"use strict";
	if (this.$pageLengthSelect) {
		var that = this;
		this.$pageLengthSelect.on('change', function () {
			that.dataTable.page.len(jQuery(this).val()).draw();
		});
	}
};

StingerSoftJQueryDataTable.prototype.bindClearButton = function () {
	"use strict";
	if (this.$clearButton) {
		var that = this;
		this.$clearButton.on('click', function () {
			var event = jQuery.Event(that.getEvent('datatable-filter-clear-all'));
			jQuery(this).trigger(event);
			if (event.isDefaultPrevented()) {
				return;
			}
			if (that.$searchField) {
				that.$searchField.val('');
			}
			that.dataTable.order(that.initialOrdering);
			yadcf.exResetAllFilters(that.dataTable);
			that.highlightAllFilteredColumns(true);
			//Reset any url search parameter
			var urlWithoutParameters = window.location.href.replace(window.location.search, removeSearchParameters([that.filterOptions.filterRequestUrlColumnKey, that.filterOptions.filterRequestUrlValueKey]));
			window.history.pushState("", "", urlWithoutParameters);
		});
	}
};

StingerSoftJQueryDataTable.prototype.bindSearchField = function () {
	"use strict";
	if (this.$searchField) {
		var that = this;
		this.searchTimeout = null;
		this.previousSearch = "";
		this.$searchField.on('input', function () {

			// IE is a bastard and triggers the input event because this stupid placeholder disappears...
			// And that is quite obviously a change.... Well played IE, well played...
			if (that.previousSearch === that.$searchField.val()) {
				return;
			}
			that.previousSearch = that.$searchField.val();
			if (that.searchTimeout) {
				clearTimeout(that.searchTimeout);
				that.searchTimeout = null;
			}
			that.searchTimeout = setTimeout(function () {
				that.dataTable.search(that.$searchField.val()).draw();
			}, that.searchDelay);
		});
	}
};

StingerSoftJQueryDataTable.prototype.bindReloadButton = function () {
	"use strict";
	if (this.$reloadButton) {
		var that = this;
		this.$reloadButton.on('click', function () {
			that.reload();
		});
	}
};

StingerSoftJQueryDataTable.prototype.preBindFilterPopups = function () {
	"use strict";
	if (this.domBased) {
		var $ths = this.$table.find('th');
		$ths.each(function (i, item) {
			var $item = jQuery(item);
			if ($item.getDataValueOrDefault('filterable', false)) {
				var $target = $item.getDataValueOrDefault('filter-container', false);
				if ($target) {
					$item.append('<a href="javascript:void(0);" ' +
						'class="datatable-filter-popup-trigger" ' +
						'data-column="' + i + '" ' +
						'data-target="#' + $target + '" ' +
						'data-popover-options=\'{"url": "#' + $target + '", "animation": "pop", "placement": "auto-top", "closeable": true }\'>' +
						'<i class="fa fa-filter"></i>');
				}
			}
		});
	}
};

StingerSoftJQueryDataTable.prototype.bindFilterPopups = function () {
	"use strict";
	var that = this;
	this.$table.find('.datatable-filter-popup-trigger')
		.on('click', function (evt) {
			evt.preventDefault();
			evt.stopPropagation();
			var $a = jQuery(this);
			var $target = jQuery($a.data('target'));
			$target.css('left', '').css('position', '');
		})
		.each(function () {
			var $a = jQuery(this);
			var $target = jQuery($a.data('target'));
			var autoFocus = $a.data('auto-focus');
			var popoverOptions = $a.data('popover-options');
			if (autoFocus) {
				popoverOptions.onShow = function ($popover) {
					$popover.find('input,textarea,select').focus();
				};
			}
			$a.webuiPopover(popoverOptions);
			$target.find('.datatable-filter-apply').on('click', function () {
				var event = jQuery.Event(that.getEvent('datatable-filter-apply'));
				jQuery(this).trigger(event);
				if (event.isDefaultPrevented()) {
					return;
				}
				$a.webuiPopover('hide');
				that.highlightColumnFilter($a, true);
				yadcf.exFilterExternallyTriggered(that.dataTable);
			});
			$target.find('.datatable-filter-clear').on('click', function () {
				var columnIndex = jQuery(this).data('column');
				var event = jQuery.Event(that.getEvent('datatable-filter-clear'));
				event.columnIndex = columnIndex;
				jQuery(this).trigger(event);
				if (event.isDefaultPrevented()) {
					return;
				}
				$a.webuiPopover('hide');
				yadcf.exResetFilters(that.dataTable, [columnIndex]);
				that.highlightColumnFilter($a, true);
			});
		});
};

StingerSoftJQueryDataTable.prototype.bindColumnSelectors = function () {
	"use strict";
	var that = this;

	this.$columnSelector.find('input[type="checkbox"].column-toggle-vis').on('change', function () {
		var columnSelector = jQuery(this).data('column-selector');
		if (columnSelector) {
			var column = that.dataTable.column(columnSelector);
			if (column) {
				column.visible(!column.visible());
			}
		}
	});

	this.$columnSelector.find('.columnSelectorToggleBtn').on('click', function () {
		var $a = jQuery(this);
		var toggleMode = $a.getDataValueOrDefault('toggle-mode', false);
		var scope = $a.getDataValueOrDefault('toggle-scope', false);
		if (toggleMode !== false && scope !== false) {
			var $group = $a.parents(scope);
			var $checkBoxes = $group.find('input[type="checkbox"].column-toggle-vis:not(:disabled)');
			var needToRedraw = false;
			$checkBoxes.each(function () {
				var $checkBox = jQuery(this);
				var columnSelector = jQuery(this).data('column-selector');
				if (columnSelector) {
					var column = that.dataTable.column(columnSelector);
					if (column) {
						var visible;
						if (toggleMode === 'reset') {
							visible = that.columnsMap[columnSelector.replace(':name', '')].visible;
						} else {
							visible = toggleMode === 'check';
						}
						if (column.visible() !== visible) {
							needToRedraw = true;
							column.visible(visible, false);
							if (visible) {
								$checkBox.prop('checked', true).attr('checked', 'checked');
							} else {
								$checkBox.removeProp('checked').removeAttr('checked');
							}
						}
					}
				}
			});
			if (needToRedraw) {
				that.realignColumns();
				var childDataTables = that.getVisibleChildDataTables();
				childDataTables.forEach(function (table) {
					that.realignColumns(table.DataTable());
				});
				that.dataTable.state.save();
			}
		}
	});
};

StingerSoftJQueryDataTable.prototype.bindDetailTrigger = function () {
	"use strict";
	if (this.detailTriggersSelector !== null && this.detailTriggersSelector.length > 0) {
		var that = this;
		var triggers = jQuery.unique(this.detailTriggersSelector);
		triggers.forEach(function (selector) {
			that.$table.on('click', selector, function (e) {
				e.preventDefault();
				e.stopPropagation();

				var $detailTrigger = jQuery(this);
				var $tr = $detailTrigger.closest('tr');
				var row = that.dataTable.row($tr);

				that.triggerDetailsRow(row, $tr, $detailTrigger);
			});
		});
	}
};

StingerSoftJQueryDataTable.prototype.preBindHeaderSortTrigger = function () {
	"use strict";
	var $ths = this.$table.find('thead th');
	var headerSortingClass = this.sortOnHeaderLabel ? 'label-triggers-sorting' : 'header-triggers-sorting';
	$ths.addClass(headerSortingClass);
	if (this.sortOnHeaderLabel && this.domBased) {
		$ths.each(function (i, item) {
			var $item = jQuery(item);
			if ($item.children('.column-header-title').length === 0) {
				$item.append('<span class="sort-indicator"></span>').wrapInner("<span class='column-header-title'></span>");
			}
		});
	}
};

StingerSoftJQueryDataTable.prototype.bindHeaderSortTrigger = function () {
	"use strict";
	if (this.sortOnHeaderLabel) {
		var that = this;
		this.$table.find('thead th').on('click', function (e) {
			e.stopImmediatePropagation();
			e.preventDefault();
		});
		var settings = this.dataTable.settings()[0];
		settings.aoColumns.forEach(function (column) {
			if (column.bSortable !== false) {
				if (column.nTh) {
					that.dataTable.order.listener(jQuery(column.nTh).children('.column-header-title').first(), column.idx);
				}
			}
		});
	}
};

StingerSoftJQueryDataTable.prototype.bindRowSelectTrigger = function () {
	"use strict";
	var that = this;
	this.$table.on('change', '.pec-datatable-row-select-trigger', function () {
		var $trigger = jQuery(this);
		var $input = $trigger.find('input[type=checkbox], input[type=radio]').first();
		var $tr = $trigger.closest('tr');
		var row = that.dataTable.row($tr);
		var checked = $input.prop('checked');
		if (checked) {
			row.select();
		} else {
			row.deselect();
		}
	});
};

StingerSoftJQueryDataTable.prototype.bindTabHandler = function () {
	"use strict";
	this.detectContext();
	if (this._tabPane) {
		var that = this;
		jQuery('a[data-toggle="tab"][href="#' + this._tabPane.attr('id') + '"]').on('shown.bs.tab', function (e) {
			that.realignColumns();
		});
	}
};

StingerSoftJQueryDataTable.prototype.detectContext = function () {
	"use strict";

	this._insideTab = this.isInsideTab();
	this._tabPane = null;
	if (this._insideTab) {
		this._tabPane = this.$table.parents('.tab-pane');
	}
};

StingerSoftJQueryDataTable.prototype.isHidden = function () {
	"use strict";

	//noinspection RedundantIfStatementJS
	if (this._insideTab && !this._tabPane.hasClass('active')) {
		return true;
	}
	return false;
};

StingerSoftJQueryDataTable.prototype.isInsideTab = function () {
	"use strict";

	var tabPane = this.$table.parents('.tab-pane');
	return tabPane.length > 0;
};

StingerSoftJQueryDataTable.prototype.selectRow = function (id) {
	"use strict";
	this.rowSelectableIds[id] = true;
	this.updateInputStatusForRow(id);
	this.updateSelectedRowsFormField();
};

StingerSoftJQueryDataTable.prototype.deselectRow = function (id) {
	"use strict";
	if (this.rowSelectableIds.hasOwnProperty(id)) {
		delete this.rowSelectableIds[id];
		this.updateInputStatusForRow(id);
		this.updateSelectedRowsFormField();
	}
};

StingerSoftJQueryDataTable.prototype.updateSelectedRowsFormField = function () {
	"use strict";
	if (this.foreignFormSelectInputId) {
		var $field = jQuery('#' + this.foreignFormSelectInputId);
		if ($field.length > 0) {
			$field.val(Object.keys(this.rowSelectableIds).join(','));
		}
	}
};

StingerSoftJQueryDataTable.prototype.initializeSelectedRowsFromFormField = function () {
	"use strict";
	if (this.foreignFormSelectInputId) {
		var that = this;
		var $field = jQuery('#' + this.foreignFormSelectInputId);
		if ($field.length > 0) {
			var value = $field.val();
			if (value.length > 0) {
				var ids = value.split(',');
				if (ids.length > 0) {
					ids.forEach(function (id) {
						that.rowSelectableIds[id] = true;
					});
				}
			}
		}
	}
};

StingerSoftJQueryDataTable.prototype.updateInputStatusForRow = function (id) {
	"use strict";
	var selected = this.rowSelectableIds.hasOwnProperty(id);
	var $tr = this.dataTable.row('[data-selectable-id="' + id + '"]').nodes().toJQuery();
	var $input = $tr.find('input[type=checkbox], input[type=radio]').first();
	if (selected) {
		$input.prop('checked', true);
	} else {
		$input.removeProp('checked');
	}
};

StingerSoftJQueryDataTable.prototype.bindSelectEventHandlers = function () {
	"use strict";

	var that = this;

	this.initializeSelectedRowsFromFormField();

	this.$table.on('select.dt', function (e, dt, type, indexes) {
		if (type === 'row') {
			var $trs = that.dataTable.rows(indexes).nodes().toJQuery().filter('[data-selectable-id!=""]');
			$trs.each(function () {
				var $tr = jQuery(this);
				var selectableId = $tr.data('selectable-id') || false;
				if (selectableId !== false) {
					that.selectRow(selectableId);
				}
			});
		}
	});
	this.$table.on('deselect.dt', function (e, dt, type, indexes) {
		if (type === 'row') {
			var $trs = that.dataTable.rows(indexes).nodes().toJQuery().filter('[data-selectable-id!=""]');
			$trs.each(function () {
				var $tr = jQuery(this);
				var selectableId = $tr.data('selectable-id') || false;
				if (selectableId !== false) {
					that.deselectRow(selectableId);
				}
			});
		}
	});

	if (this.dataTableOptions.serverSide) {
		this.$table.on('preXhr.dt.pec', function (e, settings, data) {
			that.dataTable.one('draw.dt.pec', function () {
				var selectableIds = Object.keys(that.rowSelectableIds);
				selectableIds.forEach(function (id) {
					that.dataTable.rows('[data-selectable-id="' + id + '"]').select();
				});
			});
		});

		// Update the table information element with selected item summary
		this.$table.on('draw.dt.pec select.dt.pec deselect.dt.pec info.dt.pec', function () {
			var api = that.dataTable;
			var ctx = api.settings()[0];

			if (!ctx._select.info || !ctx.aanFeatures.i) {
				return;
			}

			var output = jQuery('<span class="select-info-pec"/>');
			var add = function (name, num) {
				output.append(jQuery('<span class="select-item-pec"/>').append(api.i18n(
					'select.' + name + 's',
					{_: '%d ' + name + 's selected', 0: '', 1: '1 ' + name + ' selected'},
					num
				)));
			};

			add('row', Object.keys(that.rowSelectableIds).length);
			add('column', api.columns({selected: true}).flatten().length);
			add('cell', api.cells({selected: true}).flatten().length);

			// Internal knowledge of DataTables to loop over all information elements
			jQuery.each(ctx.aanFeatures.i, function (i, el) {
				el = jQuery(el);

				var existing = el.children('span.select-info-pec');
				if (existing.length) {
					existing.remove();
				}
				if (output.text() !== '') {
					el.append(output);
				}
			});
		});
	}
};

/**
 * If data-state-save-key is defined, that key prefixed by "stingerSoftDataTable_"
 * is returned. Otherwise, it will fallback to the original datatables
 * key if the settings are given. If nothing has been passed, just
 * "stingerSoftDataTable" is used as fallback key.
 */
StingerSoftJQueryDataTable.prototype.getStateSaveKey = function (settings) {
	"use strict";
	//
	if (this.stateSaveKey) {
		//If another one is configured use that key, prefixed by stingerSoftDataTable to immediately know the overriding
		return 'StingerSoftJQueryDataTable_' + this.stateSaveKey;
	} else if (typeof settings !== 'undefined') {
		//The original datatables key
		return 'DataTables_' + settings.sInstance + '_' + location.pathname;
	} else {
		//Ultimate fallback if everything is missing
		return this.getFallbackStateSaveKey();
	}
};

StingerSoftJQueryDataTable.prototype.getFallbackStateSaveKey = function () {
	"use strict";
	return 'StingerSoftJQueryDataTable';
};

/**
 * Get the storage target to be used for loading and saving data table state.
 *
 * @return Storage if this.stateDuration is -1, window.sessionStorage is returned,
 * other window.localStorage
 */
StingerSoftJQueryDataTable.prototype.getStateTarget = function () {
	"use strict";
	return this.stateDuration === -1 ? window.sessionStorage : window.localStorage;
};

StingerSoftJQueryDataTable.prototype.stateSaveCallback = function (settings, data) {
	"use strict";
	try {
		var key = this.getStateSaveKey(settings);
		this.adjustStateParams(settings, data, true);
		this.getStateTarget().setItem(key, JSON.stringify(data));
	} catch (e) {
	}
};

StingerSoftJQueryDataTable.prototype.stateLoadCallback = function (settings) {
	"use strict";
	try {
		var key = this.getStateSaveKey(settings);
		var data = JSON.parse(this.getStateTarget().getItem(key));
		if (data === null || !this.hasCorrectVersionHash(data)) {
			data = this.createStateObject(settings);
		}
		this.adjustStateParams(settings, data, false);
		return data;
	} catch (e) {
	}
};

StingerSoftJQueryDataTable.prototype.createStateObject = function (settings) {
	"use strict";
	var that = this;
	var stateObject = {
		time: +new Date(),
		start: settings._iDisplayStart,
		length: settings._iDisplayLength,
		order: $.extend(true, [], settings.aaSorting),
		search: that.createSearchObject(settings.oPreviousSearch),
		columns: $.map(settings.aoColumns, function (col, i) {
			return {
				visible: col.bVisible,
				search: that.createSearchObject(settings.aoPreSearchCols[i])
			};
		})
	};
	return this.addVersionHash(stateObject);
};

StingerSoftJQueryDataTable.prototype.createSearchObject = function (obj) {
	"use strict";
	var searchObject = {
		search: obj ? obj.sSearch : '',
		smart: obj ? obj.bSmart : false,
		regex: obj ? obj.bRegex : false,
		caseInsensitive: obj ? obj.bCaseInsensitive : true
	};
	return this.addVersionHash(searchObject);
};

StingerSoftJQueryDataTable.prototype.adjustSearchState = function (settings, data, saving) {
	"use strict";
	if (typeof this.searchStateSaveKey !== 'undefined') {
		if (this.searchStateSaveKey === false) {
			delete data.search;
		} else {
			// var key = this.searchStateSaveKey === true ? this.getFallbackStateSaveKey() + '_search' : this.searchStateSaveKey;
			var key = this.getCustomStateKey(StateSaveKeys.search);
			var state;
			if (saving) {
				state = {
					search: data.search
				};
				this.addVersionHash(state);
				this.getStateTarget().setItem(key, JSON.stringify(state));
			} else {
				state = JSON.parse(this.getStateTarget().getItem(key)) || this.createSearchObject();
				if (state !== null && typeof state === 'object' && this.hasCorrectVersionHash(state) && state.hasOwnProperty('search')) {
					data.search = state.search;
				}
			}
		}
	}
};

StingerSoftJQueryDataTable.prototype.adjustVisibilityState = function (settings, data, saving) {
	"use strict";
	if (typeof this.visibilityStateSaveKey !== 'undefined') {
		if (this.visibilityStateSaveKey === false) {
			// persisting for column visibility is disabled
			data.columns.forEach(function (column) {
				// for every column we remove the visibility property
				delete column.visible;
			});
		} else {
			// var key = this.visibilityStateSaveKey === true ? this.getFallbackStateSaveKey() + '_visibility' : this.visibilityStateSaveKey;
			var key = this.getCustomStateKey(StateSaveKeys.visibility);
			var state = {
				columns: {}
			};
			this.addVersionHash(state);
			var that = this;
			if (saving) {
				data.columns.forEach(function (column, index) {
					state.columns[that.getColumnNameByIndex(index)] = column.visible;
				});
				this.getStateTarget().setItem(key, JSON.stringify(state));
			} else {
				state = JSON.parse(this.getStateTarget().getItem(key));
				if (state !== null && typeof state === 'object' && this.hasCorrectVersionHash(state) && state.hasOwnProperty('columns')) {
					Object.keys(state.columns).forEach(function (key) {
						var index = that.getColumnIndexByName(key);
						if (index !== undefined && data.columns.length > index) {
							data.columns[index].visible = state.columns[key];
						}
					});
				}
			}
		}
	}
};

StingerSoftJQueryDataTable.prototype.adjustFilterState = function (settings, data, saving) {
	"use strict";

	if (typeof this.filterStateSaveKey !== 'undefined') {
		if (this.filterStateSaveKey === false) {
			// persisting for column filter is disabled
			data.columns.forEach(function (column) {
				// for every column we remove the search filter
				delete column.search;
			});
		} else {
			// var key = this.filterStateSaveKey === true ? this.getFallbackStateSaveKey() + '_filter' : this.filterStateSaveKey;
			var key = this.getCustomStateKey(StateSaveKeys.filter);
			var state = {
				columns: []
			};
			this.addVersionHash(state);
			if (saving) {
				data.columns.forEach(function (column) {
					state.columns.push(column.search);
				});
				this.getStateTarget().setItem(key, JSON.stringify(state));
			} else {
				state = JSON.parse(this.getStateTarget().getItem(key));
				if (state !== null && typeof state === 'object' && this.hasCorrectVersionHash(state) && state.hasOwnProperty('columns') && Array.isArray(state.columns)) {
					data.columns.forEach(function (column, i) {
						if (state.columns.length > i) {
							column.search = state.columns[i];
						}
					});
				}
			}
		}
	}
};

StingerSoftJQueryDataTable.prototype.adjustPageLengthState = function (settings, data, saving) {
	"use strict";

	if (typeof this.pageLengthStateSaveKey !== 'undefined') {
		if (this.pageLengthStateSaveKey === false) {
			delete data.length;
		} else {
			// var key = this.pageLengthStateSaveKey === true ? this.getFallbackStateSaveKey() + '_page_length' : this.pageLengthStateSaveKey;
			var key = this.getCustomStateKey(StateSaveKeys.pageLength);
			var state;
			if (saving) {
				state = {
					length: data.length
				};
				this.addVersionHash(state);
				this.getStateTarget().setItem(key, JSON.stringify(state));
			} else {
				state = JSON.parse(this.getStateTarget().getItem(key));
				if (state !== null && typeof state === 'object' && this.hasCorrectVersionHash(state) && state.hasOwnProperty('length')) {
					data.length = state.length;
				}
			}
		}
	}
};

StingerSoftJQueryDataTable.prototype.adjustScrollerState = function (settings, data, saving) {
	"use strict";
	if (typeof this.scrollerStateSaveKey !== 'undefined') {
		if (this.scrollerStateSaveKey === false) {
			delete data.iScroller;
			delete data.iScrollerTopRow;
		} else {
			// var key = this.scrollerStateSaveKey === true ? this.getFallbackStateSaveKey() + '_scroller' : this.scrollerStateSaveKey;
			var key = this.getCustomStateKey(StateSaveKeys.scroller);
			var state;
			if (saving) {
				state = {
					'iScroller': data.iScroller,
					'iScrollerTopRow': data.iScrollerTopRow
				};
				this.addVersionHash(state);
				this.getStateTarget().setItem(key, JSON.stringify(state));
			} else {
				state = JSON.parse(this.getStateTarget().getItem(key));
				if (state !== null && typeof state === 'object' && this.hasCorrectVersionHash(state)) {
					if (state.hasOwnProperty('iScroller') && state.iScroller) {
						data.iScroller = state.iScroller;
					}
					if (state.hasOwnProperty('iScrollerTopRow') && state.iScrollerTopRow) {
						data.iScrollerTopRow = state.iScrollerTopRow;
					}
				}
			}
		}
	}
};

StingerSoftJQueryDataTable.prototype.adjustOrderState = function (settings, data, saving) {
	"use strict";

	if (typeof this.orderStateSaveKey !== 'undefined') {
		if (this.orderStateSaveKey === false) {
			delete data.order;
		} else {
			// var key = this.orderStateSaveKey === true ? this.getFallbackStateSaveKey() + '_order' : this.orderStateSaveKey;
			var key = this.getCustomStateKey(StateSaveKeys.order);
			var state;
			if (saving) {
				state = {
					order: data.order
				};
				this.addVersionHash(state);
				this.getStateTarget().setItem(key, JSON.stringify(state));
			} else {
				state = JSON.parse(this.getStateTarget().getItem(key));
				if (state !== null && typeof state === 'object' && this.hasCorrectVersionHash(state) && state.hasOwnProperty('order')) {
					data.order = state.order;
				} else {
					data.order = [];
				}
			}
		}
	}
};

/**
 *
 */
StingerSoftJQueryDataTable.prototype.adjustStartState = function (settings, data, saving) {
	"use strict";

	if (typeof this.startStateSaveKey !== 'undefined') {
		if (this.startStateSaveKey === false) {
			delete data.start;
		} else {
			// var key = this.startStateSaveKey === true ? this.getFallbackStateSaveKey() + '_start' : this.startStateSaveKey;
			var key = this.getCustomStateKey(StateSaveKeys.start);
			var state;
			if (saving) {
				state = {
					start: data.start
				};
				this.addVersionHash(state);
				this.getStateTarget().setItem(key, JSON.stringify(state));
			} else {
				state = JSON.parse(this.getStateTarget().getItem(key));
				if (state !== null && typeof state === 'object' && this.hasCorrectVersionHash(state) && state.hasOwnProperty('start')) {
					data.start = state.start;
				}
			}
		}
	}
};

StingerSoftJQueryDataTable.prototype._getCustomStateKeyValue = function (stateType) {
	"use strict";
	switch (stateType) {
		case StateSaveKeys.filter:
			return this.filterStateSaveKey;
		case StateSaveKeys.order:
			return this.orderStateSaveKey;
		case StateSaveKeys.pageLength:
			return this.pageLengthStateSaveKey;
		case StateSaveKeys.scroller:
			return this.startStateSaveKey;
		case StateSaveKeys.search:
			return this.searchStateSaveKey;
		case StateSaveKeys.start:
			return this.startStateSaveKey;
		case StateSaveKeys.visibility:
			return this.visibilityStateSaveKey;
	}
	return undefined;
};

StingerSoftJQueryDataTable.prototype.getCustomStateKey = function (stateType) {
	"use strict";
	var value = this._getCustomStateKeyValue(stateType);
	if (typeof value !== 'undefined' && value !== false) {
		return value === true ? (this.getFallbackStateSaveKey() + stateType) : value;
	}
	return value;
};

StingerSoftJQueryDataTable.prototype.hasCorrectVersionHash = function (obj) {
	"use strict";
	return obj.hasOwnProperty('pecVersion') && obj.pecVersion === this.versionHash;
};

StingerSoftJQueryDataTable.prototype.addVersionHash = function (obj) {
	"use strict";
	obj.pecVersion = this.versionHash;
	return obj;
};

StingerSoftJQueryDataTable.prototype.adjustStateParams = function (settings, data, saving) {
	"use strict";
	this.adjustStateToUseColumnNames(settings, data, saving);

	this.adjustSearchState(settings, data, saving);
	this.adjustVisibilityState(settings, data, saving);
	this.adjustFilterState(settings, data, saving);
	this.adjustPageLengthState(settings, data, saving);
	this.adjustOrderState(settings, data, saving);
	this.adjustScrollerState(settings, data, saving);

	this.adjustStartState(settings, data, saving);
	this.addVersionHash(data);
};

StingerSoftJQueryDataTable.prototype.adjustStateToUseColumnNames = function (settings, data, saving) {
	"use strict";
	var that = this;
	if (saving) {
		data.columnVisibility = data.columnVisibility || {};
		data.columns.forEach(function (columnToPersist, index) {
			data.columnVisibility[that.getColumnNameByIndex(index)] = columnToPersist.visible;
		});
	} else {
		if (data.columnVisibility) {
			Object.keys(data.columnVisibility).forEach(function (key) {
				var index = that.getColumnIndexByName(key);
				if (index !== undefined) {
					data.columns[index].visible = data.columnVisibility[key];
				}
			});
		}
	}
};

StingerSoftJQueryDataTable.prototype.getColumnIndexByName = function (name) {
	"use strict";
	return this.columnsMap.hasOwnProperty(name) ? this.columnsMap[name].index : undefined;
};

StingerSoftJQueryDataTable.prototype.getColumnNameByIndex = function (index) {
	"use strict";
	return this.columns.hasOwnProperty(index) ? this.columns[index].name : undefined;
};

StingerSoftJQueryDataTable.prototype.selectCorrectPageLength = function () {
	"use strict";

	if (this.$pageLengthSelect) {
		this.$pageLengthSelect.val(this.dataTable.page.len());
	}
};

StingerSoftJQueryDataTable.prototype.validatePage = function () {
	"use strict";

	if (this.dataTable.page() >= this.dataTable.page.info().pages) {
		this.dataTable.page('first').draw();
	}
};

StingerSoftJQueryDataTable.prototype.populateSearchField = function () {
	"use strict";

	if (this.$searchField) {
		this.$searchField.val(this.dataTable.search());
	}
};

/**
 * @param searchValue Put the given searchValue in the search field and trigger an input event
 */
StingerSoftJQueryDataTable.prototype.search = function (searchValue) {
	"use strict";

	if (this.$searchField) {
		this.$searchField.trigger('focus').val(searchValue).trigger('input');
	}
};

StingerSoftJQueryDataTable.prototype.reload = function () {
	"use strict";

	this.dataTable.ajax.reload(null, false);
};

StingerSoftJQueryDataTable.prototype.markVisibleColumns = function () {
	"use strict";

	var that = this;
	if (this.$columnSelector) {
		this.$columnSelector.find('input[type="checkbox"].column-toggle-vis').each(function () {
			var $item = jQuery(this);
			var columnSelector = $item.data('column-selector');
			if (columnSelector && that.dataTable) {
				var column = that.dataTable.column(columnSelector);
				if (column) {
					if (column.visible()) {
						$item.prop('checked', true).attr('checked', 'checked');
					} else {
						$item.removeProp('checked').removeAttr('checked');
					}
				}
			}
		});
	}
};

StingerSoftJQueryDataTable.prototype.highlightAllFilteredColumns = function (animate) {
	"use strict";
	var animated = typeof animate !== 'undefined' ? animate : false;

	var that = this;
	this.$table.closest('.dataTables_scroll').find('a.datatable-filter-popup-trigger').each(function () {
		that.highlightColumnFilter(jQuery(this), animated);
	});
};

StingerSoftJQueryDataTable.prototype.highlightColumnFilter = function ($a, highlight) {
	"use strict";

	var columnIndex = $a.data('column');
	var $filterIcon = $a.find('.column-filter');
	if ($filterIcon && $a.getDataValueOrDefault('highlight', true)) {
		var filterValue = yadcf.exGetColumnFilterVal(this.dataTable, columnIndex);
		this.doHighlightColumnFilter($a, filterValue !== '' && filterValue !== '-yadcf_delim-', highlight);
	}
};

StingerSoftJQueryDataTable.prototype.doHighlightColumnFilter = function ($a, active, highlight) {
	"use strict";

	var columnIndex = $a.data('column');
	var $filterIcon = $a.find('.column-filter');
	if ($filterIcon) {
		var filterValue = yadcf.exGetColumnFilterVal(this.dataTable, columnIndex);
		if (active) {
			$filterIcon.addClass('active');
		} else {
			$filterIcon.removeClass('active');
		}
		if (highlight) {
			$filterIcon.addClass('highlight');
			setTimeout(function () {
				$filterIcon.removeClass('highlight');
			}, 1000);
		}
	}
};

StingerSoftJQueryDataTable.prototype.moveFooterTools = function () {
	"use strict";

	if (this.footerToolContainerSelector) {
		var $wrapper = this.getTableWrapper();
		var $movableFooterContainer = jQuery(this.footerToolContainerSelector);
		var $actualFooterContainer = $wrapper.find('.pec-datatables-footer-tools > .tools').first();
		if ($movableFooterContainer.length && $actualFooterContainer.length) {
			$movableFooterContainer.find('> *').appendTo($actualFooterContainer);
		} else {
			if (!$movableFooterContainer.length) {
				console.warn('Cannot find container containing tools to be moved! Selector to find tools is "' + this.footerToolContainerSelector + '"');
			}
			if (!$actualFooterContainer.length) {
				console.warn('Cannot find container to attach table tools to! Selector to find container is "#' + this.tableId + '"_wrapper .pec-datatables-footer-tools > .tools" ');
			}
		}
	}
};

StingerSoftJQueryDataTable.prototype.triggerDetailsRow = function (row, $tr, $trigger) {
	"use strict";

	var content;
	var refreshing = $trigger.getDataValueOrDefault('refresh', false);
	var closeDetails = refreshing ? (row.child.isShown()) : (typeof row.child() !== 'undefined' && row.child().length > 0 && jQuery(row.child()[0]).is(':visible'));
	if (closeDetails) {
		// This row is already open - close it
		if (refreshing) {
			row.child.hide();
		} else {
			row.child().hide();
		}
		this.collapseTableRow($tr, $trigger);
	} else {
		this.expandTableRow($tr, $trigger);
		// Open this row
		this.callbackBeforeDetailShow($trigger, $tr, row);
		content = this.callbackCreateDetailContainer($trigger, $tr, row);
		var child;
		if (refreshing) {
			// we replace the existing child here with the container and show it
			child = row.child(content, 'child');
			child.show();
			this.callbackAfterDetailShow(content, $trigger, $tr, row, child);
		} else {
			var loadData = (typeof row.child() === 'undefined' && !row.child.isShown());
			if (loadData) {
				// there is no child data yet, so we add the container and show it
				child = row.child(content, 'child');
				child.show();
				this.callbackAfterDetailShow(content, $trigger, $tr, row, child);
			} else {
				// the child already was loaded and as we are not refreshing, we simply show it again
				row.child().show();
			}
		}
	}
};

StingerSoftJQueryDataTable.prototype.collapseTableRow = function ($tr, $trigger) {
	"use strict";
	this.visibleChildRows.remove(this.dataTable.row($tr));
	this.realignColumns();
	$tr.removeClass('expanded').removeClass('expanded-details');
	$tr.addClass('collapsed').addClass('collapsed-details');
	if ($trigger) {
		$trigger.addClass('collapsed').addClass('collapsed-details');
		$trigger.removeClass('expanded').removeClass('expanded-details');
		$trigger.attr('title', $trigger.getDataValueOrDefault('title-collapsed'));
		StingerSoftPlatform.tooltip.initTooltips(false, $trigger.parent());
		$trigger.tooltip('fixTitle');
	}
};

StingerSoftJQueryDataTable.prototype.collapseRow = function ($tr) {
	"use strict";
	if ($tr) {
		var row = this.dataTable.row($tr);
		var expanded = row.child.isShown() || (typeof row.child() !== 'undefined' && row.child().length > 0 && jQuery(row.child()[0]).is(':visible'));
		if (expanded) {
			var triggers = jQuery.unique(this.detailTriggersSelector);
			triggers.forEach(function (selector) {
				var $trigger = $tr.find(selector);
				if ($trigger && $trigger.length > 0) {
					$trigger.trigger('click');
				}
			});
		}
	}
};

StingerSoftJQueryDataTable.prototype.expandTableRow = function ($tr, $trigger) {
	"use strict";
	this.visibleChildRows.push(this.dataTable.row($tr));
	this.realignColumns();
	$tr.removeClass('collapsed').removeClass('collapsed-details');
	$tr.addClass('expanded').addClass('expanded-details');
	if ($trigger) {
		$trigger.removeClass('collapsed').removeClass('collapsed-details');
		$trigger.addClass('expanded').addClass('expanded-details');
		$trigger.attr('title', $trigger.getDataValueOrDefault('title-expanded'));
		StingerSoftPlatform.tooltip.initTooltips(false, $trigger.parent());
		$trigger.tooltip('fixTitle');
	}
};

StingerSoftJQueryDataTable.prototype.expandRow = function ($tr) {
	"use strict";
	if ($tr) {
		var row = this.dataTable.row($tr);
		var expanded = row.child.isShown() || (typeof row.child() !== 'undefined' && row.child().length > 0 && jQuery(row.child()[0]).is(':visible'));
		if (!expanded) {
			var triggers = jQuery.unique(this.detailTriggersSelector);
			triggers.forEach(function (selector) {
				var $trigger = $tr.find(selector);
				if ($trigger && $trigger.length > 0) {
					$trigger.trigger('click');
				}
			});
		}
	}
};

StingerSoftJQueryDataTable.prototype.getVisibleChildRows = function () {
	"use strict";
	var $nodes = [];
	this.visibleChildRows.forEach(function (item) {
		$nodes.push(jQuery(item.child()));
	});
	return $nodes;
};

StingerSoftJQueryDataTable.prototype.getVisibleChildDataTables = function () {
	"use strict";
	var $nodes = [];
	this.visibleChildRows.forEach(function (item) {
		var $tr = jQuery(item.child());
		var $table = $tr.find('.table.pec-datatable').filter(function () {
			return jQuery(this).parent().is(':not(.dataTables_scrollHeadInner)');
		});
		if (jQuery.fn.DataTable.isDataTable($table)) {
			$nodes.push($table);
		}
	});
	return $nodes;
};

StingerSoftJQueryDataTable.prototype.realignColumns = function (dataTable) {
	"use strict";
	if (void 0 === dataTable) {
		dataTable = this.dataTable;
	}
	dataTable.columns.adjust();
};

StingerSoftJQueryDataTable.prototype.beforeCallDetailShow = function ($trigger, $tr, row) {
};

StingerSoftJQueryDataTable.prototype.createDetailContainer = function ($trigger, $tr, row) {
	"use strict";

	return jQuery("<div class='details-scroll'><div class='details-container'></div></div>");
};

StingerSoftJQueryDataTable.prototype.afterCallDetailShow = function (content, $trigger, $tr, row, child) {
	"use strict";

	var target = $trigger.getDataValueOrDefault('target', $trigger.attr('href'));
	StingerSoftPlatform.blockUI(content);
	content.find('.details-container').load(target, function () {
		StingerSoftPlatform.unblockUI(content);
	});
};

StingerSoftJQueryDataTable.prototype.getEvent = function (name) {
	"use strict";
	if (this.eventsNamespace !== null && this.eventsNamespace.length > 0) {
		return name + '.' + this.eventsNamespace;
	}
	return name;
};

StingerSoftJQueryDataTable.prototype.initialize = function (options, dataTableOptions) {
	"use strict";

	this.domBased = options.domBased || false;
	this.initialOrdering = dataTableOptions.order || [];
	this.detailTriggersSelector = options.detailsTriggerSelector || null;
	this.foreignFormSelectInputId = options.foreignFormSelectInputId || false;
	this.versionHash = options.versionHash || null;
	this.eventsNamespace = options.eventsNamespace || null;
	this.searchDelay = options.searchDelay || 500;

	if (dataTableOptions.stateSave) {
		if (options.stateSaveKey === true) {
			this.stateSaveKey = this.$table.attr('id');
		} else if (typeof options.stateSaveKey === 'string') {
			this.stateSaveKey = options.stateSaveKey;
		}
	}
	this.searchStateSaveKey = options.searchStateSaveKey;
	this.filterStateSaveKey = options.filterStateSaveKey;
	this.pageLengthStateSaveKey = options.pageLengthStateSaveKey;
	this.startStateSaveKey = options.startStateSaveKey;
	this.orderStateSaveKey = options.orderStateSaveKey;
	this.scrollerStateSaveKey = options.scrollerStateSaveKey;
	this.visibilityStateSaveKey = options.visibilityStateSaveKey;
	this.sortOnHeaderLabel = options.sortOnHeaderLabel;
	this.footerToolContainerSelector = options.footerToolContainerSelector;

	this.callbackBeforeDetailShow = options.callbackBeforeDetailShow || this.beforeCallDetailShow;
	this.callbackCreateDetailContainer = options.callbackCreateDetailContainer || this.createDetailContainer;
	this.callbackAfterDetailShow = options.callbackAfterDetailShow || this.afterCallDetailShow;
};

StingerSoftJQueryDataTable.prototype.initializeListeners = function () {
	"use strict";

	this.bindXhr();
	this.bindSelectEventHandlers();
	this.bindInit();
	this.bindPageLengthSelect();
	this.bindClearButton();
	this.bindReloadButton();
	this.bindSearchField();
	this.bindColumnSelectors();
	this.bindDetailTrigger();
	this.bindRowSelectTrigger();
	StingerSoftPlatform.tooltip.initTooltips();
};

StingerSoftJQueryDataTable.prototype.initializeDataTable = function (dataTableOptions) {
	"use strict";

	this.columns = dataTableOptions.columns || [];
	var columnsMap = this.columnsMap = {};
	this.columns.forEach(function (column, index) {
		columnsMap[column.name] = column;
		columnsMap[column.name].index = index;
	});
	this.stateDuration = dataTableOptions.stateDuration;
	if (dataTableOptions.stateSave && typeof dataTableOptions.stateSaveCallback === 'undefined') {
		dataTableOptions.stateSaveCallback = jQuery.proxy(this.stateSaveCallback, this);
	}
	if (dataTableOptions.stateSave && typeof dataTableOptions.stateLoadCallback === 'undefined') {
		dataTableOptions.stateLoadCallback = jQuery.proxy(this.stateLoadCallback, this);
	}
	if (dataTableOptions.serverSide && dataTableOptions.deferLoading === undefined) {
		var predefinedValues = this.getPreFilteredValues();
		if (predefinedValues.length > 0) {
			dataTableOptions.deferLoading = 2147483647;
		}
	}

	this.preBindFilterPopups();
	this.preBindHeaderSortTrigger();
	this.bindFilterPopups();
	this.dataTable = this.$table.DataTable(dataTableOptions);
	this.bindHeaderSortTrigger();
};

/**
 *
 */
StingerSoftJQueryDataTable.prototype.getPreFilteredValues = function () {
	"use strict";

	var predefinedValues = [];
	if (this.filterableColumns && this.filterableColumns.length > 0) {
		var that = this;
		var filterParameters = this.getURLFilterParameters();
		this.filterableColumns.forEach(function (column) {
			if (column.hasOwnProperty('pre_filtered_value')) {
				var data = [column.column_number, column.pre_filtered_value];
				predefinedValues.push(data);
			}
			//Check url search parameter
			if (column.column_number in that.columns) {
				var columnName = that.columns[column.column_number].name;
				if (filterParameters.hasOwnProperty(columnName)) {
					var data = [column.column_number, filterParameters[columnName]];
					predefinedValues.push(data);
				}
			}
		});
	}
	return predefinedValues;
};

StingerSoftJQueryDataTable.prototype.getFilterableColumnByIndex = function (index) {
	"use strict";

	var result = null;
	if (this.filterableColumns && this.filterableColumns.length > 0) {
		this.filterableColumns.forEach(function (column) {
			if (column.column_number === index) {
				result = column;
				return;
			}
		});
	}
	return result;
}

/**
 *
 */
StingerSoftJQueryDataTable.prototype.initializeFilter = function () {
	"use strict";

	if (this.filterableColumns && this.filterableColumns.length > 0) {
		yadcf.init(this.dataTable, this.filterableColumns, this.filterOptions);
		var predefinedValues = this.getPreFilteredValues();
		if (predefinedValues.length > 0) {
			yadcf.exResetAllFilters(this.dataTable, true);
			yadcf.exFilterColumn(this.dataTable, predefinedValues);
			this.highlightAllFilteredColumns(false);
		}
	}
};

/**
 * @param array of filter options
 * @return JsonObject of the columnPath => values[]
 */
StingerSoftJQueryDataTable.prototype.getURLFilterParameters = function () {
	"use strict";

	var urlParameters = getURLParameters();
	if (urlParameters.hasOwnProperty(this.filterOptions.filterRequestUrlColumnKey)) {
		var columnPath = urlParameters[this.filterOptions.filterRequestUrlColumnKey];
		var filterValues = urlParameters[this.filterOptions.filterRequestUrlValueKey];
		var tmp = {};
		//If more than one path has been passed, check if the values are equal...
		if (jQuery.isArray(columnPath) && columnPath.length > 1 && jQuery.isArray(filterValues) && columnPath.length == filterValues.length) {
			jQuery.each(columnPath, function (index, value) {
				tmp[value] = [filterValues[index]];
			});
		} else {
			//...otherwise just set
			tmp[columnPath] = filterValues;
		}

		return tmp;
	}
	return {};
};

/**
 *
 */
StingerSoftJQueryDataTable.prototype.init = function (options, dataTableOptions, filterableColumns, filterOptions) {
	"use strict";

	this.dataTableOptions = jQuery.extend(true, {}, dataTableOptions);
	this.filterableColumns = filterableColumns || this.filterableColumns;
	this.filterOptions = filterOptions || this.filterOptions;

	this.initialize(options, dataTableOptions);
	this.initializeListeners();
	this.initializeDataTable(dataTableOptions);
	this.initializeFilter();

	jQuery('body').trigger(this.getEvent('initialized'), {
		type: 'init-complete',
		tableId: this.tableId,
		tableJQuery: jQuery(this.$table),
		stingerSoftDataTable: this,
		dataTable: this.dataTable
	});

	return this.dataTable;
};
