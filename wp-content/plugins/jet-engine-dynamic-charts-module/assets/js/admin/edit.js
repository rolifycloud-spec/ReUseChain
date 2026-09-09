(function( $, JetEngineChartConfig ) {

	'use strict';

	Vue.component( 'jet-charts-columns', {
		name: 'jet-charts-columns',
		template: '#jet-charts-columns',
		props: [ 'value', 'allowedColumns', 'type' ],
		data() {
			return {
				columnsList: [],
				objects: JetEngineChartConfig.object_fields,
				callbacks: JetEngineChartConfig.callbacks,
				callbacksArgs: JetEngineChartConfig.callback_args,
				metaFields: JetEngineChartConfig.meta_fields,
			};
		},
		created() {

			let val = [];

			if ( this.value && this.value.length ) {
				val = this.value;
			}

			this.columnsList = [ ...val ];

		},
		methods: {
			onInput() {
				this.$emit( 'input', this.columnsList );
			},
			allowedColumnsForOptions() {

				const res = [ {
					value: '',
					label: 'Select column...',
				} ];

				for ( var i = 0; i < this.allowedColumns.length; i++ ) {
					res.push( {
						value: this.allowedColumns[i],
						label: this.allowedColumns[i],
					} );
				}
				return res;
			},
			allowedDataSources() {

				let sources = [ {
					value: '',
					label: 'Select source...',
				} ];

				for ( const property in JetEngineChartConfig.data_sources ) {

					if ( 'fetched' === property && ( ! this.allowedColumns || ! this.allowedColumns.length ) ) {
						continue;
					}

					sources.push( {
						value: property,
						label: JetEngineChartConfig.data_sources[ property ],
					} );

				}

				return sources;

			},
			addNewField( event, props, parent, callback ) {

				props = props || [];

				var field = {};

				for (var i = 0; i < props.length; i++) {
					field[ props[ i ] ] = '';
				}

				field._id = Math.round( Math.random() * 1000000 );
				field.collapsed = false;

				parent.push( field );

				if ( callback && 'function' === typeof callback ) {
					callback( field, parent );
				}

				this.onInput();

			},
			setFieldProp( id, key, value, parent ) {

				let index = this.searchByID( id, parent );

				if ( false === index ) {
					return;
				}

				let field = parent[ index ];

				field[ key ] = value;

				parent.splice( index, 1, field );

				this.onInput();

			},
			cloneField( index, id, parent, callback ) {

				let field = JSON.parse( JSON.stringify( parent[ index ] ) );

				field.collapsed = false;
				field._id = Math.round( Math.random() * 1000000 );

				parent.splice( index + 1, 0, field );

				if ( callback && 'function' === typeof callback ) {
					callback( field, parent, id );
				}

				this.onInput();

			},
			deleteField( index, id, parent, callback ) {

				index = this.searchByID( id, parent );

				if ( false === index ) {
					return;
				}

				parent.splice( index, 1 );

				if ( callback && 'function' === typeof callback ) {
					callback( id, index, parent );
				}

				this.onInput();

			},
			isCollapsed( parent ) {
				if ( undefined === parent.collapsed || true === parent.collapsed ) {
					return true;
				} else {
					return false;
				}
			},
			searchByID( id, list ) {

				for ( var i = 0; i < list.length; i++ ) {
					if ( id == list[ i ]._id ) {
						return i;
					}
				}

				return false;

			}
		}
	} );

	var JetEngineChart = new Vue( {
		el: '#jet_chart_form',
		template: '#jet-chart-form',
		data: {
			generalSettings: {
				width: 600,
				height: 400
			},
			metaFields: [],
			buttonLabel: JetEngineChartConfig.edit_button_label,
			isEdit: JetEngineChartConfig.item_id,
			helpLinks: JetEngineChartConfig.help_links,
			queries: JetEngineChartConfig.queries,
			chartsTypes: JetEngineChartConfig.types,
			legendOptions: JetEngineChartConfig.legend_options,
			showDeleteDialog: false,
			saving: false,
			suggestions: [],
			updatingPreview: false,
			previewOptions: {},
			fetchingData: false,
			parsingError: null,
			checkList: JetEngineChartConfig.checklist,
			data: [],
			errors: {
				name: false,
			},
			errorNotices: [],
			fieldsLoaded: 'not-loaded',
			isReloadingPreview: false,
		},
		created() {

			var self = this;

			if ( JetEngineChartConfig.item_id ) {

				var params = new URLSearchParams();

				params.append( 'instance', JetEngineChartConfig.instance );

				wp.apiFetch( {
					method: 'get',
					path: JetEngineChartConfig.api_path_get + JetEngineChartConfig.item_id + '?' + params.toString(),
				} ).then( function( response ) {

					if ( response.success && response.data ) {

						for ( const property in response.data ) {

							if ( 'meta_fields' === property ) {

								let fields = response.data.meta_fields;

								if ( ! fields || ! fields.length ) {
									fields = [];
								}

								for (var i = 0; i < fields.length; i++) {
									self.metaFields.push( { ...fields[ i ] } );
								}

							} else {
								self.$set( self.generalSettings, property, response.data[ property ] );
							}

						}

						self.fieldsLoaded = 'loaded';

						if ( ! self.generalSettings.inline_styles ) {
							self.$set( self.generalSettings, 'inline_styles', {} );
						}

					} else {
						if ( response.notices.length ) {
							response.notices.forEach( function( notice ) {
								self.$CXNotice.add( {
									message: notice.message,
									type: 'error',
									duration: 15000,
								} );
								//self.errorNotices.push( notice.message );
							} );
						}
					}
				} ).catch( function( e ) {
					self.$CXNotice.add( {
						message: e.message,
						type: 'error',
						duration: 7000,
					} );
				} );

			} else if ( ! this.generalSettings.inline_styles ) {
				this.$set( this.generalSettings, 'inline_styles', {} );
			}

		},
		computed: {
			isCheckListCompleted() {

				for ( var i = 0; i < this.checkList.length; i++ ) {
					if ( ! this.isCheckFieldCompleted( this.checkList[ i ].field ) ) {
						return false;
					}
				}

				return true;

			},
		},
		watch: {
			metaFields( value ) {
				if ( value.length ) {

					for ( var i = 0; i < value.length; i++ ) {

						let _id = value[ i ]._id;

						if ( ! this.generalSettings.inline_styles[ _id ] ) {
							this.$set( this.generalSettings.inline_styles, _id, {
								width: '',
								v_align: '',
								h_align: '',
							} );
						}

					}

				}
			},
		},
		methods: {
			configLink() {

				var pageSlug = this.generalSettings.type + 'chart';

				switch ( this.generalSettings.type ) {
					case 'histogram':
						pageSlug = 'histogram';
						break;

					case 'donut':
						pageSlug = 'piechart';
						break;

					case 'columns':
						pageSlug = 'columnchart';
						break;
				}

				if ( this.generalSettings.type ) {
					return '<a href="https://developers.google.com/chart/interactive/docs/gallery/' + pageSlug + '#configuration-options" target="_blank">Allowed options list</a>'
				} else {
					return '';
				}
			},
			isCheckFieldCompleted( field ) {

				if ( 'columns' === field ) {
					if ( 1 >= this.metaFields.length ) {
						return false;
					} else {

						var completed = true;

						for ( var i = 0; i < this.metaFields.length; i++ ) {
							if ( ! this.metaFields[ i ].data_source ) {
								completed = false;
							}
						}

						return completed;
					}
				}

				if ( this.generalSettings[ field ] ) {
					return true;
				} else {
					return false;
				}

			},
			filteredLegendOptions() {

				var materialTypes   = [ 'bar', 'line' ];
				var materialAllowed = [ 'left', 'right', 'none' ];

				return this.legendOptions.filter( ( item ) => {
					return ( ! materialTypes.includes( this.generalSettings.type ) || materialAllowed.includes( item.value ) );
				} );

			},
			setCurrentType( typeID ) {
				this.$set( this.generalSettings, 'type', typeID );
			},
			setAdvancedOptions( value ) {

				var hasError = false;

				if ( value ) {
					try {
						JSON.parse( value );
					} catch ( e ) {
						this.parsingError = e.name + ': ' + e.message;
						hasError = true;
					}
				}

				if ( ! hasError ) {
					this.parsingError = null;
					this.$set( this.generalSettings, 'advanced_options', value );
				}

			},
			makeFetchDataRequest() {

				this.fetchingData = true;

				wp.apiFetch( {
					method: 'post',
					path: JetEngineChartConfig.api_path_fetch_data,
					data: {
						query_id: this.generalSettings.query_id,
					}
				} ).then( ( response ) => {

					if ( response.success ) {

						this.$set( this.generalSettings, 'allowed_columns', response.columns );

						this.$CXNotice.add( {
							message: 'Done!',
							type: 'success',
							duration: 2000,
						} );

					} else {
						this.$CXNotice.add( {
							message: response.message,
							type: 'error',
							duration: 20000,
						} );
					}

					this.fieldsLoaded = 'reloaded';
					this.fetchingData = false;

				} ).catch( ( response ) => {
					this.fetchingData = false;
					this.$CXNotice.add( {
						message: response.message,
						type: 'error',
						duration: 20000,
					} );

				} );

			},
			reloadPreview() {

				this.isReloadingPreview = true;

				jQuery.ajax({
					url: window.ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'jet_engine_data_chart_preview',
						general_settings: this.generalSettings,
						meta_fields: this.metaFields,
					},
				}).done( ( response ) => {

					this.isReloadingPreview = false;

					var previewOptions = response.data.options;

					previewOptions.type = this.generalSettings.type;

					var chart = new JetChartRenderer(
						response.data.items,
						response.data.options,
						this.$refs.preview,
						JetEngineChartConfig.locale
					);

					chart.initChart();

				} ).fail( ( jqXHR, textStatus, errorThrown ) => {

					this.$CXNotice.add( {
						message: errorThrown,
						type: 'error',
						duration: 20000,
					} );

					this.isReloadingPreview = false;
				} );

			},
			applySuggestion( suggestion ) {
				this.$set( this.generalSettings, 'preview_page_title', suggestion.text );
				this.$set( this.generalSettings, 'preview_page', suggestion.id );
				this.suggestions = [];
			},
			ensureQueryType() {

				if ( this.generalSettings.chart_type && ! this.generalSettings[ this.generalSettings.chart_type ] ) {
					this.$set( this.generalSettings, this.generalSettings.chart_type, {} );
				}

				if ( this.generalSettings.chart_type && ! this.generalSettings[ '__dynamic_' + this.generalSettings.chart_type ] ) {
					this.$set( this.generalSettings, '__dynamic_' + this.generalSettings.chart_type, {} );
				}

			},
			handleFocus( where ) {

				if ( this.errors[ where ] ) {
					this.$set( this.errors, where, false );
					this.$CXNotice.close( where );
					//this.errorNotices.splice( 0, this.errorNotices.length );
				}

			},
			setDynamicQuery( prop, value ) {
				this.$set( this.generalSettings, prop, value );
			},
			save() {

				var self      = this,
					hasErrors = false,
					path      = JetEngineChartConfig.api_path_edit;

				if ( JetEngineChartConfig.item_id ) {
					path += JetEngineChartConfig.item_id;
				}

				for ( var errKey in this.errors ) {

					if ( ! self.generalSettings[ errKey ] ) {
						self.$set( this.errors, errKey, true );

						self.$CXNotice.add( {
							message: JetEngineChartConfig.notices[ errKey ],
							type: 'error',
							duration: 7000,
						}, 'name' );

						//self.errorNotices.push( JetEngineCCTConfig.notices.name );
						hasErrors = true;
					}

				}

				if ( hasErrors ) {
					return;
				}

				self.saving = true;

				let params = new URLSearchParams();

				params.append( 'instance', JetEngineChartConfig.instance );

				wp.apiFetch( {
					method: 'post',
					path: path + '?' + params.toString(),
					data: {
						general_settings: self.generalSettings,
						meta_fields: self.metaFields,
					}
				} ).then( function( response ) {

					if ( response.success ) {

						if ( JetEngineChartConfig.redirect ) {
							window.location = JetEngineChartConfig.redirect.replace( /%id%/, response.item_id );
						} else {

							self.$CXNotice.add( {
								message: JetEngineChartConfig.notices.success,
								type: 'success',
							} );

							self.saving = false;
						}

					} else {
						if ( response.notices && response.notices.length ) {
							response.notices.forEach( function( notice ) {

								self.$CXNotice.add( {
									message: notice.message,
									type: 'error',
									duration: 7000,
								} );

							} );

							self.saving = false;
						} else if ( response.message ) {
							self.$CXNotice.add( {
								message: response.message,
								type: 'error',
								duration: 7000,
							} );
						}
					}
				} ).catch( function( response ) {

					self.$CXNotice.add( {
						message: response.message,
						type: 'error',
						duration: 7000,
					} );

					self.saving = false;
				} );

			},
		}
	} );

})( jQuery, window.JetEngineChartConfig );
