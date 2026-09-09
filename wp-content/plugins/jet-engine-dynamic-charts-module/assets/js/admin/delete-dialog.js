Vue.component( 'jet-charts-delete-dialog', {
	name: 'jet-charts-delete-dialog',
	template: '#jet-charts-delete-dialog',
	props: {
		value: {
			type: Boolean,
			default: false,
		},
		itemId: {
			type: String,
			default: '',
		},
		itemName: {
			type: String,
			default: '',
		},
	},
	data: function() {
		return {
			isVisible: this.value,
		};
	},
	watch: {
		value: function( val ) {
			this.setVisibility( val );
		}
	},
	methods: {
		handleCancel: function() {
			this.setVisibility( false );
			this.$emit( 'input', false );
			this.$emit( 'on-cancel' );
		},
		handleOk: function() {

			var self = this;

			self.setVisibility( false );

			var params = new URLSearchParams();

			params.append( 'instance', JetEngineChartsDeleteDialog.instance );

			wp.apiFetch( {
				method: 'delete',
				path: JetEngineChartsDeleteDialog.api_path + self.itemId + '?' + params.toString(),
			} ).then( function( response ) {

				if ( response.success ) {
					window.location = JetEngineChartsDeleteDialog.redirect;
				} else {
					self.$CXNotice.add( {
						message: response.message,
						type: 'error',
						duration: 15000,
					} );
				}

				self.$emit( 'input', false );
				self.$emit( 'on-ok' );

			} ).catch( function( e ) {

				self.$emit( 'input', false );
				self.$emit( 'on-ok' );

				self.$CXNotice.add( {
					message: e.message,
					type: 'error',
					duration: 7000,
				} );

			} );

		},
		setVisibility: function( value ) {

			if ( this.isVisible === value ) {
				return;
			}

			this.isVisible = value;
		},
	},
} );
