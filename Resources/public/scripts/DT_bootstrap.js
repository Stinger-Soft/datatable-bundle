/* Set the defaults for DataTables initialisation */
$.extend( true, $.fn.dataTable.defaults, {
	//"sDom": "<'row'<'col-md-6 col-sm-12'l><'col-md-6 col-sm-12'f>r><'table-scrollable't><'row'<'col-md-5 col-sm-12'i><'col-md-7 col-sm-12'p>>", // horizobtal scrollable datatable
	//"sDom": "<'row'<'col-md-6 col-sm-12'l><'col-md-6 col-sm-12'f>r>t<'row'<'col-md-5 col-sm-12'i><'col-md-7 col-sm-12'p>>", // defaukt datatable without  horizobtal scroll
	"sDom":	"<'row'<'col-xs-6'l><'col-xs-6'f>r>t<'row'<'col-xs-6'i><'col-xs-6'p>>",
//	"sPaginationType": "bootstrap",
    "language": {
        "emptyTable":     Translator.trans('stinger_soft_datatables.emptyTable', {}, 'StingerSoftDatatableBundle'),
        "info":           Translator.trans('stinger_soft_datatables.info', {'total': '_TOTAL_ ', 'start':'_START_', 'end':'_END_'}, 'StingerSoftDatatableBundle'),
        "infoEmpty":      Translator.trans('stinger_soft_datatables.infoEmpty', {}, 'StingerSoftDatatableBundle'),
        "infoFiltered":   Translator.trans('stinger_soft_datatables.infoFiltered', {'max': '_MAX_'}, 'StingerSoftDatatableBundle'),
        "infoPostFix":    Translator.trans('stinger_soft_datatables.infoPostFix', {}, 'StingerSoftDatatableBundle'),
        "thousands":      Translator.trans('stinger_soft_datatables.thousands', {}, 'StingerSoftDatatableBundle'),
        "lengthMenu":     Translator.trans('stinger_soft_datatables.lengthMenu', {'menu': '_MENU_'}, 'StingerSoftDatatableBundle'),
        "loadingRecords": Translator.trans('stinger_soft_datatables.loadingRecords', {}, 'StingerSoftDatatableBundle'),
        "processing":     Translator.trans('stinger_soft_datatables.processing', {}, 'StingerSoftDatatableBundle'),
        "search":         Translator.trans('stinger_soft_datatables.search', {}, 'StingerSoftDatatableBundle'),
        "zeroRecords":    Translator.trans('stinger_soft_datatables.zeroRecords', {}, 'StingerSoftDatatableBundle'),
        "paginate": {
            "first":      Translator.trans('stinger_soft_datatables.paginate.first', {}, 'StingerSoftDatatableBundle'),
            "last":       Translator.trans('stinger_soft_datatables.paginate.last', {}, 'StingerSoftDatatableBundle'),
            "next":       Translator.trans('stinger_soft_datatables.paginate.next', {}, 'StingerSoftDatatableBundle'),
            "previous":   Translator.trans('stinger_soft_datatables.paginate.previous', {}, 'StingerSoftDatatableBundle')
        },
        "aria": {
            "sortAscending":  Translator.trans('stinger_soft_datatables.aria.sortAscending', {}, 'StingerSoftDatatableBundle'),
            "sortDescending": Translator.trans('stinger_soft_datatables.aria.sortDescending', {}, 'StingerSoftDatatableBundle')
        }
    }
    /*,
	"oLanguage": {
		"sLengthMenu": 	Translator.trans('stinger_soft_datatables.per_page', {'menu': '_MENU_'}, 'StingerSoftDatatableBundle'),
		"sSearch": 		Translator.trans('stinger_soft_datatables.search', {}, 'StingerSoftDatatableBundle')+':',
		"sInfoEmpty": 	Translator.trans('stinger_soft_datatables.no_entries', {}, 'StingerSoftDatatableBundle'),
		"sInfo":		Translator.trans('stinger_soft_datatables.entries', {'total': '_TOTAL_ ', 'start':'_START_', 'end':'_END_'}, 'StingerSoftDatatableBundle'),
		"sEmptyTable":	Translator.trans('stinger_soft_datatables.no_entries', {}, 'StingerSoftDatatableBundle'),
		"oPaginate": {
			"sPrevious": Translator.trans('stinger_soft_datatables.previous_page', {}, 'StingerSoftDatatableBundle'),
			"sNext": Translator.trans('stinger_soft_datatables.next_page', {}, 'StingerSoftDatatableBundle')
		}
	}*/
} );


/* Default class modification */
$.extend( $.fn.dataTableExt.oStdClasses, {
	"sWrapper": "dataTables_wrapper form-inline",
	"sFilterInput": "form-control input-sm",
	"sLengthSelect": "form-control input-sm"
} );

// In 1.10 we use the pagination renderers to draw the Bootstrap paging,
// rather than  custom plug-in
if ( $.fn.dataTable.Api ) {
	$.fn.dataTable.defaults.renderer = 'bootstrap';
	$.fn.dataTable.ext.renderer.pageButton.bootstrap = function ( settings, host, idx, buttons, page, pages ) {
		var api = new $.fn.dataTable.Api( settings );
		var classes = settings.oClasses;
		var lang = settings.oLanguage.oPaginate;
		var btnDisplay, btnClass;

		var attach = function( container, buttons ) {
			var i, ien, node, button;
			var clickHandler = function ( e ) {
				e.preventDefault();
				if ( e.data.action !== 'ellipsis' ) {
					api.page( e.data.action ).draw( false );
				}
			};

			for ( i=0, ien=buttons.length ; i<ien ; i++ ) {
				button = buttons[i];

				if ( $.isArray( button ) ) {
					attach( container, button );
				}
				else {
					btnDisplay = '';
					btnClass = '';

					switch ( button ) {
						case 'ellipsis':
							btnDisplay = '&hellip;';
							btnClass = 'disabled';
							break;

						case 'first':
							btnDisplay = lang.sFirst;
							btnClass = button + (page > 0 ?
								'' : ' disabled');
							break;

						case 'previous':
							btnDisplay = lang.sPrevious;
							btnClass = button + (page > 0 ?
								'' : ' disabled');
							break;

						case 'next':
							btnDisplay = lang.sNext;
							btnClass = button + (page < pages-1 ?
								'' : ' disabled');
							break;

						case 'last':
							btnDisplay = lang.sLast;
							btnClass = button + (page < pages-1 ?
								'' : ' disabled');
							break;

						default:
							btnDisplay = button + 1;
							btnClass = page === button ?
								'active' : '';
							break;
					}

					if ( btnDisplay ) {
						node = $('<li>', {
								'class': classes.sPageButton+' '+btnClass,
								'aria-controls': settings.sTableId,
								'tabindex': settings.iTabIndex,
								'id': idx === 0 && typeof button === 'string' ?
									settings.sTableId +'_'+ button :
									null
							} )
							.append( $('<a>', {
									'href': '#'
								} )
								.html( btnDisplay )
							)
							.appendTo( container );

						settings.oApi._fnBindAction(
							node, {action: button}, clickHandler
						);
					}
				}
			}
		};

		attach(
			$(host).empty().html('<ul class="pagination"/>').children('ul'),
			buttons
		);
	}
}
else {
	// Integration for 1.9-
	$.fn.dataTable.defaults.sPaginationType = 'bootstrap';

	/* API method to get paging information */
	$.fn.dataTableExt.oApi.fnPagingInfo = function ( oSettings )
	{
		return {
			"iStart":         oSettings._iDisplayStart,
			"iEnd":           oSettings.fnDisplayEnd(),
			"iLength":        oSettings._iDisplayLength,
			"iTotal":         oSettings.fnRecordsTotal(),
			"iFilteredTotal": oSettings.fnRecordsDisplay(),
			"iPage":          oSettings._iDisplayLength === -1 ?
				0 : Math.ceil( oSettings._iDisplayStart / oSettings._iDisplayLength ),
			"iTotalPages":    oSettings._iDisplayLength === -1 ?
				0 : Math.ceil( oSettings.fnRecordsDisplay() / oSettings._iDisplayLength )
		};
	};

	/* Bootstrap style pagination control */
	$.extend( $.fn.dataTableExt.oPagination, {
		"bootstrap": {
			"fnInit": function( oSettings, nPaging, fnDraw ) {
				var oLang = oSettings.oLanguage.oPaginate;
				var fnClickHandler = function ( e ) {
					e.preventDefault();
					if ( oSettings.oApi._fnPageChange(oSettings, e.data.action) ) {
						fnDraw( oSettings );
					}
				};

				$(nPaging).append(
					'<ul class="pagination">'+
						'<li class="prev disabled"><a href="#">&larr; '+oLang.sPrevious+'</a></li>'+
						'<li class="next disabled"><a href="#">'+oLang.sNext+' &rarr; </a></li>'+
					'</ul>'
				);
				var els = $('a', nPaging);
				$(els[0]).bind( 'click.DT', { action: "previous" }, fnClickHandler );
				$(els[1]).bind( 'click.DT', { action: "next" }, fnClickHandler );
			},

			"fnUpdate": function ( oSettings, fnDraw ) {
				var iListLength = 5;
				var oPaging = oSettings.oInstance.fnPagingInfo();
				var an = oSettings.aanFeatures.p;
				var i, ien, j, sClass, iStart, iEnd, iHalf=Math.floor(iListLength/2);

				if ( oPaging.iTotalPages < iListLength) {
					iStart = 1;
					iEnd = oPaging.iTotalPages;
				}
				else if ( oPaging.iPage <= iHalf ) {
					iStart = 1;
					iEnd = iListLength;
				} else if ( oPaging.iPage >= (oPaging.iTotalPages-iHalf) ) {
					iStart = oPaging.iTotalPages - iListLength + 1;
					iEnd = oPaging.iTotalPages;
				} else {
					iStart = oPaging.iPage - iHalf + 1;
					iEnd = iStart + iListLength - 1;
				}

				for ( i=0, ien=an.length ; i<ien ; i++ ) {
					// Remove the middle elements
					$('li:gt(0)', an[i]).filter(':not(:last)').remove();

					// Add the new list items and their event handlers
					for ( j=iStart ; j<=iEnd ; j++ ) {
						sClass = (j==oPaging.iPage+1) ? 'class="active"' : '';
						$('<li '+sClass+'><a href="#">'+j+'</a></li>')
							.insertBefore( $('li:last', an[i])[0] )
							.bind('click', function (e) {
								e.preventDefault();
								oSettings._iDisplayStart = (parseInt($('a', this).text(),10)-1) * oPaging.iLength;
								fnDraw( oSettings );
							} );
					}

					// Add / remove disabled classes from the static elements
					if ( oPaging.iPage === 0 ) {
						$('li:first', an[i]).addClass('disabled');
					} else {
						$('li:first', an[i]).removeClass('disabled');
					}

					if ( oPaging.iPage === oPaging.iTotalPages-1 || oPaging.iTotalPages === 0 ) {
						$('li:last', an[i]).addClass('disabled');
					} else {
						$('li:last', an[i]).removeClass('disabled');
					}
				}
			}
		}
	} );
}


/*
 * TableTools Bootstrap compatibility
 * Required TableTools 2.1+
 */
if ( $.fn.DataTable.TableTools ) {
	// Set the classes that TableTools uses to something suitable for Bootstrap
	$.extend( true, $.fn.DataTable.TableTools.classes, {
		"container": "DTTT btn-group",
		"buttons": {
			"normal": "btn btn-default",
			"disabled": "disabled"
		},
		"collection": {
			"container": "DTTT_dropdown dropdown-menu",
			"buttons": {
				"normal": "",
				"disabled": "disabled"
			}
		},
		"print": {
			"info": "DTTT_print_info modal"
		},
		"select": {
			"row": "active"
		}
	} );

	// Have the collection use a bootstrap compatible dropdown
	$.extend( true, $.fn.DataTable.TableTools.DEFAULTS.oTags, {
		"collection": {
			"container": "ul",
			"button": "li",
			"liner": "a"
		}
	} );
}