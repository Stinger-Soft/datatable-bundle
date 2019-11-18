jQuery(document).ready(function(){
	handleDataTables();
});

var handleDataTables = function(container){
	if(!container){
		container = jQuery('body');
	}
	container.find('.datatable').each(function(){
		var $this = jQuery(this);
		var tabPane = $this.parents('.tab-pane:not(.active)');
		if(tabPane.length > 0){
			jQuery('a[data-toggle="tab"][href="#'+tabPane.attr('id')+'"]').on('shown.bs.tab', function (e) {
				initDataTables($this);
				jQuery(jQuery.fn.dataTable.tables(true)).DataTable().columns.adjust();
			});
		}else{
			initDataTables($this);
		}
	});
};

var initDataTables = function($this){
	var isInitialized = $this.data('isInitialized');
	if(isInitialized == '1'){
		return;
	}

	var columnSelector = $this.data('column-selector');
	var aLengthMenu = [
		[5, 20, 100, -1],
		[5, 20, 100, Translator.trans("stinger_soft_datatables.all")]
	];

	var paging 			= $this.getDataValueOrDefault('paging', true);
	var sorting		 	= $this.getDataValueOrDefault('sorting', false);
	var filtering	   	= $this.getDataValueOrDefault('filtering', false);
	var excluded		= $this.getDataValueOrDefault('filtering-exclude', false);
	var scrollY			= $this.getDataValueOrDefault('scroll-y', '');
	var scrollX			= $this.getDataValueOrDefault('scroll-x', '');
	var scrollCollapse	= $this.getDataValueOrDefault('scroll-collapse', false);
	var isEmpty 		= $this.find('tbody tr').length == 0;

	var ordering = [[0, 'asc']];
	if(sorting !== false) {
		ordering = sorting;
	}

	var oTable = $this.dataTable({
		"aLengthMenu"	: aLengthMenu,
		"paging"		: paging,
		"scrollY"		: scrollY,
		"scrollX"		: scrollX,
		"scrollCollapse": scrollCollapse,
		"order"		 : ordering
	});

	fixedLeftColumns = $this.data('fixed-left-columns');
	if(fixedLeftColumns){
		new $.fn.dataTable.FixedColumns( oTable, {
			"leftColumns": fixedLeftColumns
		});
	}

	if(columnSelector){

		var $jqColumnSelector = jQuery(columnSelector);
		var switcherAllData = $jqColumnSelector.getDataValueOrDefault('switcher-all', false);
		var switcherNoneData = $jqColumnSelector.getDataValueOrDefault('switcher-none', false);

		var checkBoxes = jQuery(columnSelector +' input[type="checkbox"]');

		checkBoxes.change(function(){
			toggleColumnVis(oTable, jQuery(this));
		});

		createAllToggler(switcherAllData, checkBoxes, true, oTable);
		createAllToggler(switcherNoneData, checkBoxes, false, oTable);
	}

	if(filtering !== false && !isEmpty) {
		var excludedColumns = [];
		if(excluded !== false) {
			excluded += '';
			excludedColumns = excluded.replace(' ', '').split(',');
		}
		var table = $this.DataTable();
		$this.find(filtering).each( function ( i ) {
			if(jQuery.inArray(i+'', excludedColumns) == -1) {
				var container = jQuery('<div class="column-selector"></div>"');
				container.appendTo(jQuery(this));
				var select = jQuery('<select><option value=""></option></select>')
					.appendTo(container)
					.on('change', function () {
						var val = $(this).val();
						table.column(i)
							.search(val ? '^' + $(this).val() : val, true, false)
							.draw();
					}).on('click', function (e) {
						e.stopPropagation();
					});
				table.column(i).data().unique().sort().each(function (d, j) {
					var text = decodeNastyHtmlEntitiesForSearch(d);
					select.append('<option value="' + text + '">' + text + '</option>');
				});
			}
		} );
	}

	$this.data('isInitialized', '1');
};

var createAllToggler = function(selector, checkBoxes, newValue, table) {
	if(selector) {
		var switcher = jQuery(selector);
		switcher.click(function() {
			checkBoxes.each(function() {
				jQuery(this).prop('checked', newValue).uniform();
				toggleColumnVis(table, jQuery(this));
			});
		});
	}

};

var toggleColumnVis = function(table, box) {
	var aCol = box.data("column");
	var bVis = box.is(':checked');
	jQuery(aCol).each(function(i, iCol){
		table.fnSetColumnVis(iCol, bVis);
	});
};

var decodeNastyHtmlEntitiesForSearch = (function() {
	// this prevents any overhead from creating the object each time
	var element = jQuery('<div />');

	function decodeHTMLEntities (str) {
		if(str && typeof str === 'string') {
			// strip script/html tags
			str = str.replace(/<script[^>]*>([\S\s]*?)<\/script>/gmi, '');
			str = str.replace(/<\/?\w(?:[^"'>]|"[^"]*"|'[^']*')*>/gmi, '');
			element.html(str);
			str = element.text();
			//str = str.replace(/[\u00AD\u002D\u2011]+/g,'');
			element.html('');
		}

		return str;
	}

	return decodeHTMLEntities;
})();
