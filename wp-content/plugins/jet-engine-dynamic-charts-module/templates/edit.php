<div
	class="jet-engine-edit-page jet-dynamic-chart-page jet-engine-edit-page--loading"
	:class="{
		'jet-engine-edit-page--loaded': true,
	}"
>
	<div class="jet-engine-edit-page__fields">
		<div class="cx-vui-panel">
			<cx-vui-tabs
				:in-panel="false"
				value="general"
				layout="vertical"
			>
				<cx-vui-tabs-panel
					name="general"
					label="<?php _e( 'General Settings', 'jet-engine' ); ?>"
					key="general"
				>
					<cx-vui-input
						label="<?php _e( 'Name', 'jet-engine' ); ?>"
						description="<?php _e( 'Name of Chart will be shown in the admin menu`', 'jet-engine' ); ?>"
						:wrapper-css="[ 'equalwidth' ]"
						:size="'fullwidth'"
						:error="errors.name"
						v-model="generalSettings.name"
						@on-focus="handleFocus( 'name' )"
					></cx-vui-input>
					<cx-vui-select
						label="<?php _e( 'Data Query', 'jet-engine' ); ?>"
						description="<?php _e( 'Select previously created Query to get data from', 'jet-engine' ); ?>"
						:wrapper-css="[ 'equalwidth' ]"
						:options-list="queries"
						:size="'fullwidth'"
						v-model="generalSettings.query_id"
					>
						<div>
							<a v-if="generalSettings.query_id" target="_blank" :href="'<?php echo admin_url( 'admin.php?page=jet-engine-query&query_action=edit&id=' ) ?>' + generalSettings.query_id"><?php
								_e( 'Edit selected query', 'jet-engine' );
							?></a><span v-if="generalSettings.query_id">&nbsp;<?php _e( 'or', 'jet-engine' ); ?>&nbsp;</span><a target="_blank" href="<?php echo admin_url( 'admin.php?page=jet-engine-query&query_action=add' ); ?>"><?php
								_e( 'Create new query', 'jet-engine' );
							?></a>
						</div>
					</cx-vui-select>
					<cx-vui-component-wrapper
						label="<?php _e( 'Fetch the data', 'jet-engine' ); ?>"
						description="<?php _e( 'Fetch sample of the data to find all possible columns and preview the chart', 'jet-engine' ); ?>"
						:wrapper-css="[ 'equalwidth' ]"
					>
						<cx-vui-button
							button-style="accent"
							size="mini"
							:loading="fetchingData"
							@click="makeFetchDataRequest"
						>
							<span
								slot="label"
								v-if="! generalSettings.allowed_columns"
							><?php _e( 'Fetch Data', 'jet-engine' ); ?></span>
							<span
								slot="label"
								v-else
							><?php _e( 'Re-fetch Data', 'jet-engine' ); ?></span>
						</cx-vui-button>
					</cx-vui-component-wrapper>
					<cx-vui-component-wrapper
						v-if="generalSettings.allowed_columns && ! generalSettings.allowed_columns.length"
						label="<?php _e( 'Note!', 'jet-engine' ); ?>"
						description="<?php _e( 'We have not found any visible columns in the fetched data. You can process & build the chart, but the `Fetched columns` data source won`t be available for the chart columns.', 'jet-engine' ); ?>"
					>
					</cx-vui-component-wrapper>
				</cx-vui-tabs-panel>
				<cx-vui-tabs-panel
					name="chart_type"
					label="<?php _e( 'Chart Type', 'jet-engine' ); ?>"
					key="chart_type"
				>
					<div class="jet-chart-types">
						<div class="jet-chart-type" v-for="type in chartsTypes" :class="{ 'jet-chart-type--is-active': generalSettings.type === type.id }" :dataChart="type.id">
							<div class="jet-chart-type__content" @click="setCurrentType( type.id )">
								<img :src="'data:image/png;base64,' + type.image" class="jet-chart-type__image" :alt="type.name">
								<div class="jet-chart-type__name">{{ type.name }}</div>
							</div>
						</div>
					</div>
					<cx-vui-input
						label="<?php _e( 'Maps API Key', 'jet-engine' ); ?>"
						description="<?php _e( 'Google maps API key. Video tutorial about creating Google Maps API key <a href=\'https://www.youtube.com/watch?v=t2O2a2YiLJA\' target=\'_blank\'>here</a>', 'jet-engine' ); ?>"
						v-if="'geo' === generalSettings.type"
						:wrapper-css="[ 'equalwidth' ]"
						:size="'fullwidth'"
						v-model="generalSettings.maps_api_key"
					></cx-vui-input>
				</cx-vui-tabs-panel>
				<cx-vui-tabs-panel
					name="columns"
					label="<?php _e( 'Columns', 'jet-engine' ); ?>"
					key="columns"
				>
					<div class="cx-vui-component__meta" :style="{ 'padding-top': '10px', 'padding-bottom': '20px' }">
						<div class="cx-vui-component__label"><?php _e( 'Note!', 'jet-engine' ); ?></div>
						<div class="cx-vui-component__desc"><?php _e( 'First column is used for chart group labels. All next columns are used for chart data.', 'jet-engine' ); ?></div>
					</div>
					<div class="cx-vui-component__meta" v-if="'candlestick' === generalSettings.type" :style="{ 'padding-top': '10px', 'padding-bottom': '20px' }">
						<div class="cx-vui-component__label"><?php _e( 'Watch this!', 'jet-engine' ); ?></div>
						<div class="cx-vui-component__desc"><?php _e( 'For <b>candlestick</b> chart you need 1 columns for chart labels and 4 columns for the values.', 'jet-engine' ); ?></div>
					</div>
					<jet-charts-columns
						v-model="metaFields"
						:allowed-columns="generalSettings.allowed_columns"
						:key="fieldsLoaded"
						:type="generalSettings.type"
					></jet-charts-columns>
				</cx-vui-tabs-panel>
				<cx-vui-tabs-panel
					name="config"
					label="<?php _e( 'Chart Config', 'jet-engine' ); ?>"
					key="config"
				>
					<cx-vui-select
						label="<?php _e( 'Legend', 'jet-engine' ); ?>"
						description="<?php _e( 'Set chat legend position or disable it', 'jet-engine' ); ?>"
						:wrapper-css="[ 'equalwidth' ]"
						:options-list="filteredLegendOptions()"
						size="fullwidth"
						v-if="'geo' !== generalSettings.type"
						v-model="generalSettings.legend"
					></cx-vui-select>
					<cx-vui-switcher
						label="<?php _e( 'Is Stacked', 'jet-engine' ); ?>"
						description="<?php _e( 'Stacks chart elements', 'jet-engine' ); ?>"
						:wrapper-css="[ 'equalwidth' ]"
						v-model="generalSettings.is_stacked"
					></cx-vui-switcher>
					<cx-vui-switcher
						label="<?php _e( 'Advanced Options', 'jet-engine' ); ?>"
						description="<?php _e( 'Check this to allow set more configuration options manually', 'jet-engine' ); ?>"
						:wrapper-css="[ 'equalwidth' ]"
						v-model="generalSettings.allow_advanced"
					></cx-vui-switcher>
					<cx-vui-textarea
						label="<?php _e( 'Set Advanced Options', 'jet-engine' ); ?>"
						:description="'<?php _e( 'Here you can set advanced options for the chart in JSON format.', 'jet-engine' ); ?>' + ' ' + configLink()"
						v-if="generalSettings.allow_advanced"
						:rows="20"
						:wrapper-css="[ 'equalwidth' ]"
						:size="'fullwidth'"
						:value="generalSettings.advanced_options"
						@input="setAdvancedOptions"
					><div v-if="parsingError" :style="{ color: '#c92c2c' }">{{ parsingError }}</div></cx-vui-textarea>
					<?php
					/**

					@TODO - series:
{
	"series": {
		"0": { "axis": "Total"},
		"1": { "axis": "Avg Age"}
	},
	"hAxes"/"vAxes": {
		"Avg Age": {"format": "decimal"},
		"Total": {"format":"currency"}
	},
	"axes": {
		"x": {
			"Total": {"label": "Total", "side": "top"},
			"Avg Age": {"label": "Avg Age"}
		}
	}
}

					*/

					?>
				</cx-vui-tabs-panel>
				<cx-vui-tabs-panel
					name="styles"
					label="<?php _e( 'Chart Styles', 'jet-engine' ); ?>"
					key="styles"
				>
					<cx-vui-input
						label="<?php _e( 'Width', 'jet-engine' ); ?>"
						description="<?php _e( 'Chart canvas width in pixels', 'jet-engine' ); ?>"
						type="number"
						:wrapper-css="[ 'equalwidth' ]"
						size="fullwidth"
						v-model="generalSettings.width"
					></cx-vui-input>
					<cx-vui-input
						label="<?php _e( 'Height', 'jet-engine' ); ?>"
						description="<?php _e( 'Chart canvas height in pixels', 'jet-engine' ); ?>"
						type="number"
						:wrapper-css="[ 'equalwidth' ]"
						size="fullwidth"
						v-model="generalSettings.height"
					></cx-vui-input>
					<?php

					/**

					@TODO - series:
					{
					backgroundColor
					chartArea.backgroundColor
					colors
					dataOpacity
					hAxis.baselineColor
					hAxis.gridlines.color
					hAxis.gridlines.minSpacing

					bar:
					bar.groupWidth

					geo:
					colorAxis

					lines:
					curveType
					lineWidth
					*/
					?>

				</cx-vui-tabs-panel>
			</cx-vui-tabs>
		</div>
	</div>
	<div class="jet-engine-edit-page__actions">
		<div class="jet-engine-edit-page__actions-panel">
			<div class="jet-engine-edit-page__actions-buttons">
				<div class="jet-engine-edit-page__actions-save">
					<cx-vui-button
						:button-style="'accent'"
						:custom-css="'fullwidth'"
						:loading="saving"
						@click="save"
					>
						<svg slot="label" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.6667 5.33333V1.79167H1.79167V5.33333H10.6667ZM6.125 13.4167C6.65278 13.9444 7.27778 14.2083 8 14.2083C8.72222 14.2083 9.34722 13.9444 9.875 13.4167C10.4028 12.8889 10.6667 12.2639 10.6667 11.5417C10.6667 10.8194 10.4028 10.1944 9.875 9.66667C9.34722 9.13889 8.72222 8.875 8 8.875C7.27778 8.875 6.65278 9.13889 6.125 9.66667C5.59722 10.1944 5.33333 10.8194 5.33333 11.5417C5.33333 12.2639 5.59722 12.8889 6.125 13.4167ZM12.4583 0L16 3.54167V14.2083C16 14.6806 15.8194 15.0972 15.4583 15.4583C15.0972 15.8194 14.6806 16 14.2083 16H1.79167C1.29167 16 0.861111 15.8194 0.5 15.4583C0.166667 15.0972 0 14.6806 0 14.2083V1.79167C0 1.31944 0.166667 0.902778 0.5 0.541667C0.861111 0.180556 1.29167 0 1.79167 0H12.4583Z" fill="white"/></svg>
						<span slot="label">{{ buttonLabel }}</span>
					</cx-vui-button>
				</div>
				<div
					class="jet-engine-edit-page__actions-delete"
					v-if="isEdit"
				>
					<cx-vui-button
						:button-style="'link-error'"
						:size="'link'"
						@click="showDeleteDialog = true"
					>
						<svg slot="label" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2.28564 14.1921V3.42857H13.7142V14.1921C13.7142 14.6686 13.5208 15.089 13.1339 15.4534C12.747 15.8178 12.3005 16 11.7946 16H4.20529C3.69934 16 3.25291 15.8178 2.866 15.4534C2.4791 15.089 2.28564 14.6686 2.28564 14.1921Z"/><path d="M14.8571 1.14286V2.28571H1.14282V1.14286H4.57139L5.56085 0H10.4391L11.4285 1.14286H14.8571Z"/></svg>
						<span slot="label"><?php _e( 'Delete', 'jet-engine' ); ?></span>
					</cx-vui-button>
				</div>
			</div>
			<div
				class="jet-engine-edit-page__notice-error"
				v-if="this.errorNotices.length"
			>
				<div class="jet-engine-edit-page__notice-error-content">
					<div class="jet-engine-edit-page__notice-error-items">
						<div
							v-for="( notice, index ) in errorNotices"
							:key="'notice_' + index"
						>{{ notice }}</div>
					</div>
				</div>
			</div>
			<div class="cx-vui-hr"></div>
			<div class="cx-vui-subtitle jet-engine-help-list-title"><?php _e( 'Need Help?', 'jet-engine' ); ?></div>
			<div class="cx-vui-panel">
				<div class="jet-engine-help-list">
					<div class="jet-engine-help-list__item" v-for="link in helpLinks">
						<a :href="link.url" target="_blank">
							<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.4413 7.39906C10.9421 6.89828 11.1925 6.29734 11.1925 5.59624C11.1925 4.71987 10.8795 3.9687 10.2535 3.34272C9.62754 2.71674 8.87637 2.40376 8 2.40376C7.12363 2.40376 6.37246 2.71674 5.74648 3.34272C5.1205 3.9687 4.80751 4.71987 4.80751 5.59624H6.38498C6.38498 5.17058 6.54773 4.79499 6.87324 4.46948C7.19875 4.14398 7.57434 3.98122 8 3.98122C8.42566 3.98122 8.80125 4.14398 9.12676 4.46948C9.45227 4.79499 9.61502 5.17058 9.61502 5.59624C9.61502 6.02191 9.45227 6.3975 9.12676 6.723L8.15024 7.73709C7.52426 8.41315 7.21127 9.16432 7.21127 9.99061V10.4038H8.78873C8.78873 9.57747 9.10172 8.82629 9.7277 8.15024L10.4413 7.39906ZM8.78873 13.5962V12.0188H7.21127V13.5962H8.78873ZM2.32864 2.3662C3.9061 0.788732 5.79656 0 8 0C10.2034 0 12.0814 0.788732 13.6338 2.3662C15.2113 3.91862 16 5.79656 16 8C16 10.2034 15.2113 12.0939 13.6338 13.6714C12.0814 15.2238 10.2034 16 8 16C5.79656 16 3.9061 15.2238 2.32864 13.6714C0.776213 12.0939 0 10.2034 0 8C0 5.79656 0.776213 3.91862 2.32864 2.3662Z" fill="#007CBA"/></svg>
							{{ link.label }}
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<jet-charts-delete-dialog
		v-if="showDeleteDialog"
		v-model="showDeleteDialog"
		:item-id="isEdit"
	></jet-charts-delete-dialog>
	<div class="jet-dynamic-chart-preview">
		<cx-vui-button
			button-style="accent"
			size="mini"
			:disabled="!isCheckListCompleted"
			@click="reloadPreview"
			:loading="isReloadingPreview"
		>
			<span slot="label"><?php _e( 'Reload Preview', 'jet-engine' ); ?></span>
		</cx-vui-button>
		<div class="jet-dynamic-chart-preview-canvas">
			<div class="jet-engine-check-list" v-show="!isCheckListCompleted">
				<div v-if="!isCheckListCompleted">
					<strong><?php _e( 'Please complete these steps to load preview:', 'jet-engine' ); ?></strong>
				</div>
				<div v-for="check in checkList" :class="{ 'jet-engine-check-list__item': true, 'jet-engine-check-list__item-completed': isCheckFieldCompleted( check.field ) }">
					<svg v-if="isCheckFieldCompleted( check.field )" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect x="0" fill="none" width="20" height="20"/><g><path d="M14.83 4.89l1.34.94-5.81 8.38H9.02L5.78 9.67l1.34-1.25 2.57 2.4z"/></g></svg>
					<svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect x="0" fill="none" width="20" height="20"/><g><path d="M10 2c4.42 0 8 3.58 8 8s-3.58 8-8 8-8-3.58-8-8 3.58-8 8-8zm1.13 9.38l.35-6.46H8.52l.35 6.46h2.26zm-.09 3.36c.24-.23.37-.55.37-.96 0-.42-.12-.74-.36-.97s-.59-.35-1.06-.35-.82.12-1.07.35-.37.55-.37.97c0 .41.13.73.38.96.26.23.61.34 1.06.34s.8-.11 1.05-.34z"/></g></svg>
					{{ check.label }}
				</div>
			</div>
			<div class="jet-dynamic-chart-preview-body" ref="preview" v-show="isCheckListCompleted">
				<?php _e( 'Click "Reload Preview" button to draw the chart', 'jet-engine' ); ?>
			</div>
		</div>
	</div>
</div>
