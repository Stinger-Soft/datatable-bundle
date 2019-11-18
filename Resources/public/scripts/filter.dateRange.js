jQuery('body').on('filter-initialized.pec-datatable', function(event, data) {
	var $field = jQuery(data.field);
	var $container = $field.closest('.filter_container');
	var columnId = data.columnId;

	if($field.hasClass('yadcf-filter-range-date')) {
		/**
		 * Clear the filter fields
		 */
		var clearFilter = function() {
			$field.val('');
			jQuery(data.dataTable.table().header()).find('.datatable-filter-popup-trigger[data-column=' + columnId + ']').each(function(index, indicator) {
				data.pecDataTable.doHighlightColumnFilter(jQuery(indicator), false, true);
			});
		};

		jQuery(data.dataTable.table().header()).find('.datatable-filter-popup-trigger[data-column='+columnId+']').each(function(index,  indicator){
			data.pecDataTable.doHighlightColumnFilter(jQuery(indicator), $field.val() !== "", false);
		});

		$container.find('.datatable-filter-apply').on('datatable-filter-apply.pec-datatable', function() {
			jQuery(data.dataTable.table().header()).find('.datatable-filter-popup-trigger[data-column=' + columnId + ']').each(function(index, indicator) {
				data.pecDataTable.doHighlightColumnFilter(jQuery(indicator), true, true);
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