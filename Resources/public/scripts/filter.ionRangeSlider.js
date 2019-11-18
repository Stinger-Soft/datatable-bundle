jQuery('body').on('filter-initialized.pec-datatable', function(event, data){
	var $field = jQuery(data.field);
	var $container = $field.closest('.filter_container');
	var columnId = data.columnId;
	if($field.hasClass('range_number_single_text_field')) {
		if(typeof jQuery.ionRangeSlider === undefined) {
			console.log('Please add the ionRangeSlide library!');
			return;
		}

		var $copyField = jQuery('<input type="hidden"/>');
		$field.parent().append($copyField);
		$field.hide();

		var defaultOptions = {
			type: "double",
			input_values_separator: "-yadcf_delim-",
			min: data.filterSettings.data.fromValue,
			max: data.filterSettings.data.toValue,
			step: data.filterSettings.data.step || 1,
			onStart: function (sliderData) {
				var active = $field.val() !== "";
				jQuery(data.dataTable.table().header()).find('.datatable-filter-popup-trigger[data-column=' + columnId + ']').each(function (index, indicator) {
					data.pecDataTable.doHighlightColumnFilter(jQuery(indicator), active, false);
				});
			}
		};
		var sliderOptions = jQuery.extend(true, defaultOptions, data.filterSettings.data.options);
		$copyField.ionRangeSlider(sliderOptions);
		var $slider = $copyField.data('ionRangeSlider');

		var clearFilter = function() {
			$field.val('');
			var sliderData = $slider.result;
			sliderData.from = $slider.options.min;
			sliderData.to = $slider.options.max;
			sliderData.step = $slider.options.step;
			$slider.update(sliderData);
			jQuery(data.dataTable.table().header()).find('.datatable-filter-popup-trigger[data-column='+columnId+']').each(function(index,  indicator){
				data.pecDataTable.doHighlightColumnFilter(jQuery(indicator), false, true);
			});
		};

		$container.find('.datatable-filter-apply').on('datatable-filter-apply.pec-datatable', function () {
			$field.val($copyField.val());
			var sliderData = $slider.result;
			jQuery(data.dataTable.table().header()).find('.datatable-filter-popup-trigger[data-column='+columnId+']').each(function(index,  indicator){
				data.pecDataTable.doHighlightColumnFilter(jQuery(indicator), true, true);
			});
		});
		$container.find('.datatable-filter-clear').on('datatable-filter-clear.pec-datatable', function () {
			clearFilter();
		});
		jQuery('body').on('datatable-filter-clear-all.pec-datatable', function() {
			clearFilter();
		});
	}
});