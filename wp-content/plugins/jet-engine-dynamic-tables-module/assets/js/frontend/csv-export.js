( function( $ ) {

    const {
        addFilter,
        addAction,
        doAction,
        removeAction,
    } = window.JetPlugins.hooks;

    addFilter(
        'jet-smart-filters.request.data',
        'jet-dynamic-tables-export',
        function( data ) {
            data.extra_props ??= JetSmartFilterSettings.extra_props || {};
            data.extra_props.jetDynTablesExportUrl = getCurrentUrl();
            return data;
        }
    );
    
    document.addEventListener( 'jet-smart-filters/inited', function() {
        JetSmartFilters.events.subscribe(
            'ajaxFilters/start-loading',
            function( provider, queryId ) {
                if ( provider !== 'jet-data-table' ) {
                    return;
                }

                const filterGroup = window?.JetSmartFilters?.filterGroups?.[ getFilterGroupName( provider, queryId ) ];

                if ( ! filterGroup?.$provider?.length || ! filterGroup.$provider.is( '[data-export-signature]' ) ) {
                    return;
                }

                disableImport( filterGroup.$provider.closest( '.jet-dynamic-table-export-wrapper' ) );
            }
        );

        JetSmartFilters.events.subscribe(
            'ajaxFilters/updated',
            function( provider, queryId, response ) {
                if ( provider !== 'jet-data-table' ) {
                    return;
                }

                const filterGroup = window?.JetSmartFilters?.filterGroups?.[ getFilterGroupName( provider, queryId ) ];

                if ( ! filterGroup?.$provider?.length || ! filterGroup.$provider.is( '[data-export-signature]' ) ) {
                    return;
                }

                const $filteredContent = jQuery( response?.content || '<div></div>' );

                //signature is not updated after JSF pagination load more, so we do it here
                if ( $filteredContent?.[0]?.dataset?.exportSignature ) {
                    filterGroup.$provider[0].setAttribute( 'data-export-signature', $filteredContent?.[0]?.dataset?.exportSignature );
                }

                enableImport( filterGroup.$provider.closest( '.jet-dynamic-table-export-wrapper' ) );

                filterGroup.jetDynTablesExport ??= {};
                filterGroup.jetDynTablesExport.filtersLoaded = true;

                doAction(
                    'jet-dynamic-tables.ajax-filters-updated',
                    provider,
                    queryId
                );
            }
        );
    } );

    $( document )
        .on( 'click.JetEngine', '.jet-dynamic-table__export-button', exportTable );

    function getFilterGroupName( provider, queryId ) {
        return `${provider}/${queryId}`;
    }

    async function updateProvider( providerId ) {
        if ( ! window?.JetSmartFilters ) {
            return true;
        }

        const name = 'jet-data-table/' + providerId;

        if ( ! window?.JetSmartFilters?.filterGroups[ name ] ) {
            return true;
        }

        const filterGroup = window?.JetSmartFilters?.filterGroups[ name ];

        filterGroup.jetDynTablesExport ??= {};

        if ( filterGroup.jetDynTablesExport.filtersLoaded ) {
            return true;
        }

        let filteringCompleted = new Promise(
            ( resolve ) => {
                setTimeout( () => resolve( false ), 10000 );

                const namespace = 'crocoblock/jet-dynamic-tables/update-promise-filtering-completed-' + providerId;

                addAction(
                    'jet-dynamic-tables.ajax-filters-updated',
                    namespace,
                    function( provider, queryId ) {
                        if ( getFilterGroupName( provider, queryId ) !== name ) {
                            return;
                        }

                        removeAction( 'jet-dynamic-tables.ajax-filters-updated', namespace );

                        resolve( true );
                    }
                );
            }
        );

        filterGroup.currentQuery.update = 1;
        filterGroup.apply( 'ajax' );

        return filteringCompleted;
        
    }

    function arrayToCsv( data, separator = ',' ){
        return data.map( row =>
            row
                .map( String )
                .map( v => v.replaceAll( '"', '""' ) )
                .map( v => `"${v}"` )
                .join( separator )
        ).join( '\r\n' );
    }

    function getCurrentUrl() {
        return window.location.pathname + window.location.search;
    }

    async function getBlobFromEndpoint( $button, source = 'current_page', includedColumns = [] ) {
        const $builderElement = $button.closest( '.elementor-widget-jet-dynamic-table, .brxe-jet-dynamic-table, [data-is-block="jet-engine/dynamic-table"]' );
        const builderElementId = $builderElement.attr( 'id' ) || 'default';

        disableImport( $builderElement );

        let providerUpdated;

        await updateProvider( builderElementId ).then( result => providerUpdated = result );

        if ( ! providerUpdated ) {
            showError(
                $builderElement,
                {
                    timeout: 5
                }
            );

            enableImport( $builderElement );

            return false;
        }

        const $tableBody = $builderElement.find( 'tbody' ).first();
        const $table = $tableBody.closest( 'table' );
        const tableId = $table[0].dataset.tableId || 0;
        const queryId = $table[0].dataset.queryId || 0;
        const signature = $tableBody[0].dataset.exportSignature;

        let filterParams = window?.JetSmartFilters?.filterGroups?.[ 'jet-data-table/' + builderElementId ]?.query || [];

        filterParams = normalizeJSF( filterParams );

        let blob = false;

        await wp.apiFetch(
            {
                path: JetEngineSettings.dynamic_table_export_url,
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify( {
                    table_id: tableId,
                    query_id: queryId,
                    signature: signature,
                    filter_params: filterParams,
                    source: source,
                    included_columns: includedColumns,
                    jetDynTablesExportUrl: getCurrentUrl(),
                } )
            }
        ).then(
            ( response ) => {
                if ( ! response.success || ! response.content ) {
                    showError( $builderElement );
                    return;
                }

                blob = new Blob( [ response.content ], { type: 'text/csv;charset=utf-8;' } );

                enableImport( $builderElement );
            }
        ).catch(
            ( response ) => {
                showError( $builderElement, response );
                enableImport( $builderElement );
            }
        );

        return blob;
    }

    function normalizeJSF( data ) {
        if ( Array.isArray( data ) ) {
            data = data.map( e => normalizeJSF( e ) );
        } else if ( typeof data === 'object' ) {
            for ( const key of Object.keys( data ) ) {
                data[ key ] = normalizeJSF( data[ key ] );
            }
        }

        if ( typeof data === 'number' ) {
            data = data.toString();
        }

        return data;
    }

    function showError( $builderElement, response ) {
        const $exportWrapper = $builderElement.find( '.jet-dynamic-table__export-controls-box' ).first();
        const $errorMessage  = $builderElement.find( '.jet-dynamic-table__export-error' ).first();
        $exportWrapper.addClass( 'jet-dynamic-table__export-hidden' );
        $errorMessage.removeClass( 'jet-dynamic-table__export-hidden' );
        
        const timeout = ( +response.timeout || 3 ) * 1000;

        $errorMessage.text(
            response.error 
            ? JetEngineSettings.dynamic_table_export_error_prefix + ' ' + response.error
            : JetEngineSettings.dynamic_table_export_generic_error
        );

        if ( timeout > 0 ) {
            setTimeout(
                () => {
                    $errorMessage.addClass( 'jet-dynamic-table__export-hidden' );
                    $exportWrapper.removeClass( 'jet-dynamic-table__export-hidden' );
                }, timeout
            );
        }
    }

    async function getBlobFromCurrentPage( $table, includedColumns = [] ) {
        let content = [];
        const table = $table[0];
        const separator = table.querySelector( ':scope > tbody' ).dataset.exportSeparator || ',';

        let result = [];

        for ( const tableRow of table.querySelectorAll( 'thead > tr, tbody > tr' ) ) {
            let rowArray = [];
            let i = 0;

            for ( const tableCell of tableRow.querySelectorAll( 'td, th' ) ) {
                if ( includedColumns.length && ! includedColumns.includes( ( i++ ).toString() ) ) {
                    continue;
                }

                rowArray.push( tableCell.innerText );    
            }

            result.push( rowArray );
        }

        const blob = new Blob( [ arrayToCsv( result, separator ) ], { type: 'text/csv;charset=utf-8;' } );

        return blob;
    }

    async function exportTable() {
        const $this = $( this );
        const tableWrapper = $this.closest( '.jet-dynamic-table-export-wrapper' );
        const exportWrapper = $this.closest( '.jet-dynamic-table__export-controls-box' );
        const $table = tableWrapper.find( 'table' );
        const $tableBody = $table.find( 'tbody' );

        if ( ! $tableBody.length ) {
            return;
        }

        const source = exportWrapper.find( 'select[name="export_source"]' )?.val() || 'current_page';
        const includedColumns = tableWrapper.find( 'select.jet-dynamic-table__included-columns' ).val();

        let blob;

        switch ( source ) {
            case 'current_page':
                blob = await getBlobFromCurrentPage( $table, includedColumns );
                break;
            default:
                blob = await getBlobFromEndpoint( $this, source, includedColumns );
        }

        if ( ! blob ) {
            return;
        }

        const url = URL.createObjectURL( blob );
        const a = document.createElement( 'a' );

        a.href = url;
        a.download = 'export.csv';

        document.body.appendChild( a );
        a.click();
        a.remove();

        URL.revokeObjectURL( url );
    }

    $( window ).on( 'elementor/frontend/init', elementorInit );
    initBlocks();

    function initBlocks( $scope ) {
        $scope = $scope || $( 'body' );

        window.JetPlugins.init( $scope, [
            {
                block: 'jet-engine/dynamic-table',
                callback: initExportControls
            },
        ] );
    }

    function elementorInit() {
        var widgets = {
            'jet-dynamic-table.default' : initExportControls,
        };

        $.each( widgets, function( widget, callback ) {
            window.elementorFrontend.hooks.addAction( 'frontend/element_ready/' + widget, callback );
        });

        // Re-init sliders in nested tabs
        window.elementorFrontend.elements.$window.on(
            'elementor/nested-tabs/activate',
            ( event, content ) => {
                const $content = $( content );

                setTimeout( () => {
                    JetEngine.maybeReinitSlider( event, $content );
                    JetEngine.widgetDynamicField( $content );
                } );
            }
        );

        JetEngine.updateAddedStyles();
    }

    function updateColumnOptions( $scope ) {
        const $headers = $scope.find( 'tr.jet-dynamic-table__row--header' ).first().find( 'th' );
        const $columnsSelect = $scope.find( 'select.jet-dynamic-table__included-columns' ).first();
        const includedColumns = $columnsSelect.val();

        $columnsSelect.find( 'option' ).remove();

        let includedOptions = [];

        $headers.each( ( i, el ) => {
            const opt = document.createElement( 'option' );
            opt.value = i;
            opt.innerHTML = el.innerText;
            $columnsSelect.append( opt );
            includedOptions.push( i.toString() );
        } );

        const currentValue = includedColumns.filter( item => includedOptions.includes( item ) );

        if ( currentValue.length ) {
            $columnsSelect.val( currentValue );
        } else {
            $columnsSelect.val( includedOptions );
        }
        
        $columnsSelect.trigger( 'chosen:updated' );

        return includedOptions;
    }

    function initExportControls( $scope ) {
        const $sourceSelect = $scope.find( 'select.jet-dynamic-table__export-source' ).first();
        const $columnsSelect = $scope.find( 'select.jet-dynamic-table__included-columns' ).first();
        const includedColumns = updateColumnOptions( $scope );

        $sourceSelect.chosen( { disable_search: true } );
        $columnsSelect.chosen( { max_selected_options: Infinity } );

        let lastSelection = [];

        $columnsSelect.on(
            'change',
            function( e ) {
                let selectedOptions = $( this ).val();

                if (! selectedOptions || selectedOptions.length === 0) {
                    $( this ).val( lastSelection ).trigger( "chosen:updated" );
                } else {
                    lastSelection = selectedOptions;
                }
            }
        );
    }

    function getControlBox( $scope ) {
        return $scope.find( '.jet-dynamic-table__export-controls-box' );
    }

    function enableImport( $scope ) {
        getControlBox( $scope ).removeClass( 'controls-disabled' );
    }

    function disableImport( $scope ) {
        getControlBox( $scope ).addClass( 'controls-disabled' );
    }

    if ( window.BricksFunction ) {
        new BricksFunction({
            parentNode: document,
            selector: '.brxe-jet-dynamic-table',
            eachElement: ( table ) => {
                initExportControls( $( table ) );
            },
        }).run();
    }
} )( jQuery )
