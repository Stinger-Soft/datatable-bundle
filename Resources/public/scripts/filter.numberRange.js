jQuery('body').on('filter-initialized.pec-datatable', function(event, data) {
	var $container = data.container;
	var $fields = $container.find('.yadcf-filter-range-number')
	var columnId = data.columnId;

	if($fields.length > 0) {
		var filterData = data.filterSettings.hasOwnProperty('data') ? data.filterSettings.data : false;
		if(filterData) {
			$fields.each(function (i, field) {
				var $field = jQuery(field);
				if(filterData.hasOwnProperty('fromValue')) {
					$field.attr('min', Math.round(parseFloat(filterData.fromValue)));
				}
				if(filterData.hasOwnProperty('toValue')) {
					$field.attr('max', Math.round(parseFloat(filterData.toValue)));
				}
			});
		}

		/**
		 * Clear the filter fields
		 */
		var clearFilter = function() {
			$fields.val('');
			jQuery(data.dataTable.table().header()).find('.datatable-filter-popup-trigger[data-column=' + columnId + ']').each(function(index, indicator) {
				data.pecDataTable.doHighlightColumnFilter(jQuery(indicator), false, true);
			});
		};

		var fieldsEmpty = function() {
			var _fieldsEmpty = true;
			$fields.each(function(i, field){
				var $field = jQuery(field);
				if($field.val() !== "") {
					_fieldsEmpty = false;
				}
			});
			return _fieldsEmpty;
		}

		/**
		 * Highlight filter icon if filter is set
		 */
		jQuery(data.dataTable.table().header()).find('.datatable-filter-popup-trigger[data-column='+columnId+']').each(function(index,  indicator) {
			data.pecDataTable.doHighlightColumnFilter(jQuery(indicator), !fieldsEmpty(), false);
		});

		/**
		 * Highlight filter icon if filter is applied
		 */
		$container.find('.datatable-filter-apply').on('datatable-filter-apply.pec-datatable', function() {
			jQuery(data.dataTable.table().header()).find('.datatable-filter-popup-trigger[data-column=' + columnId + ']').each(function(index, indicator) {
				data.pecDataTable.doHighlightColumnFilter(jQuery(indicator), !fieldsEmpty(), true);
			});
		});

		$container.find('.datatable-filter-clear').on('datatable-filter-clear.pec-datatable', function() {
			clearFilter();
		});

		jQuery('body').on('datatable-filter-clear-all.pec-datatable', function() {
			clearFilter();
		});
	}
});