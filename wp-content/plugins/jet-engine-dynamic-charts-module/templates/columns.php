<cx-vui-repeater
	button-label="<?php _e( 'New column', 'jet-engine' ); ?>"
	button-style="accent"
	button-size="mini"
	v-model="columnsList"
	@input="onInput"
	@add-new-item="addNewField( $event, [], columnsList )"
>
	<cx-vui-repeater-item
		v-for="( column, index ) in columnsList"
		:title="columnsList[ index ].name"
		:collapsed="isCollapsed( column )"
		:index="index"
		@clone-item="cloneField( $event, column._id, columnsList )"
		@delete-item="deleteField( $event, column._id, columnsList )"
		:key="column._id"
	>
		<div class="cx-vui-component cx-vui-component__meta" v-if="'candlestick' === type && 0 < index && 5 > index">
			<div class="cx-vui-component__label"><?php _e( 'Note!', 'jet-engine' ); ?></div>
			<div class="cx-vui-component__desc" v-if="1 == index"><?php _e( 'This column specifying the low/minimum value of this marker. This is the base of the candle`s center line.', 'jet-engine' ); ?></div>
			<div class="cx-vui-component__desc" v-else-if="2 == index"><?php _e( 'This column specifying the opening/initial value of this marker. This is one vertical border of the candle. If less than the column 3 value, the candle will be filled; otherwise it will be hollow.', 'jet-engine' ); ?></div>
			<div class="cx-vui-component__desc" v-else-if="3 == index"><?php _e( 'This column specifying the closing/final value of this marker. This is the second vertical border of the candle. If less than the column 2 value, the candle will be hollow; otherwise it will be filled.', 'jet-engine' ); ?></div>
			<div class="cx-vui-component__desc" v-else-if="4 == index"><?php _e( 'This column specifying the high/maximum value of this marker. This is the top of the candle`s center line.', 'jet-engine' ); ?></div>
		</div>
		<cx-vui-input
			label="<?php _e( 'Column Name', 'jet-engine' ); ?>"
			description="<?php _e( 'Column name is displayed in the table heading.', 'jet-engine' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			size="fullwidth"
			:value="columnsList[ index ].name"
			@input="setFieldProp( column._id, 'name', $event, columnsList )"
		></cx-vui-input>
		<cx-vui-select
			label="<?php _e( 'Data Source', 'jet-engine' ); ?>"
			description="<?php _e( 'Select data source for current column', 'jet-engine' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:options-list="allowedDataSources()"
			size="fullwidth"
			:value="columnsList[ index ].data_source"
			@input="setFieldProp( column._id, 'data_source', $event, columnsList )"
		></cx-vui-select>
		<cx-vui-select
			label="<?php _e( 'Select Field', 'jet-engine' ); ?>"
			description="<?php _e( 'Select field from the current object to show in this column', 'jet-engine' ); ?>"
			:groups-list="objects"
			:wrapper-css="[ 'equalwidth' ]"
			size="fullwidth"
			v-if="'object' === column.data_source"
			:value="columnsList[ index ].object_field"
			@input="setFieldProp( column._id, 'object_field', $event, columnsList )"
			:key="column._id + '_field'"
		></cx-vui-select>
		<cx-vui-select
			label="<?php _e( 'Select Column', 'jet-engine' ); ?>"
			description="<?php _e( 'Select column from previously fetched columns. Press Re-fetch button to update columns list.', 'jet-engine' ); ?>"
			:options-list="allowedColumnsForOptions()"
			:wrapper-css="[ 'equalwidth' ]"
			size="fullwidth"
			v-if="'fetched' === column.data_source"
			:value="columnsList[ index ].fetched_column"
			@input="setFieldProp( column._id, 'fetched_column', $event, columnsList )"
			:key="column._id + '_columns'"
		></cx-vui-select>
		<cx-vui-select
			label="<?php _e( 'Select Field', 'jet-engine' ); ?>"
			description="<?php _e( 'Select meta field from JetEngine fields to show in this column', 'jet-engine' ); ?>"
			:groups-list="metaFields"
			:wrapper-css="[ 'equalwidth' ]"
			size="fullwidth"
			placeholder="<?php _e( 'Select...', 'jet-engine' ); ?>"
			v-if="'meta' === column.data_source"
			:value="columnsList[ index ].meta_key"
			@input="setFieldProp( column._id, 'meta_key', $event, columnsList )"
			:key="column._id + '_meta_key'"
		></cx-vui-select>
		<cx-vui-input
			label="<?php _e( 'Custom Field Key', 'jet-engine' ); ?>"
			description="<?php _e( 'Or set any custom field key to get data from', 'jet-engine' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			size="fullwidth"
			v-if="'meta' === column.data_source"
			:value="columnsList[ index ].custom_meta_key"
			@input="setFieldProp( column._id, 'custom_meta_key', $event, columnsList )"
		></cx-vui-input>
		<cx-vui-switcher
			label="<?php _e( 'Filter Column Output', 'jet-engine' ); ?>"
			description="<?php _e( 'Apply filtering function to the column value to change display format etc.', 'jet-engine' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:value="columnsList[ index ].apply_callback"
			@input="setFieldProp( column._id, 'apply_callback', $event, columnsList )"
		></cx-vui-switcher>
		<cx-vui-select
			label="<?php _e( 'Filter Callback', 'jet-engine' ); ?>"
			description="<?php _e( 'Select callback function to filter the column value', 'jet-engine' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:options-list="callbacks"
			size="fullwidth"
			v-if="column.apply_callback"
			:value="columnsList[ index ].filter_callback"
			@input="setFieldProp( column._id, 'filter_callback', $event, columnsList )"
		></cx-vui-select>
		<component
			v-for="control in callbacksArgs"
			v-if="column.apply_callback && control.if.includes( column.filter_callback )"
			:is="control.type"
			:options-list="control.options"
			:label="control.label"
			:description="control.description"
			:wrapper-css="[ 'equalwidth' ]"
			size="fullwidth"
			:value="columnsList[ index ][ control.id ]"
			@input="setFieldProp( column._id, control.id, $event, columnsList )"
			:key="'callback__' + control.id"
		/>
		<cx-vui-select
			label="<?php _e( 'Data Type', 'jet-engine' ); ?>"
			description="<?php _e( 'Converts column data into selected type', 'jet-engine' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			size="fullwidth"
			:options-list="[
				{
					value: '',
					label: '<?php _e( 'Default', 'jet-engine' ); ?>'
				},
				{
					value: 'number',
					label: '<?php _e( 'Ensure number', 'jet-engine' ); ?>'
				},
				{
					value: 'string',
					label: '<?php _e( 'Ensure string', 'jet-engine' ); ?>'
				},
				{
					value: 'date',
					label: '<?php _e( 'Ensure date', 'jet-engine' ); ?>'
				},
			]"
			:value="columnsList[ index ].data_type"
			@input="setFieldProp( column._id, 'data_type', $event, columnsList )"
			:key="column._id + '_data_type'"
		></cx-vui-select>
	</cx-vui-repeater-item>
</cx-vui-repeater>
