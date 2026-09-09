/** JetTricks Tooltip — Gutenberg. */
import PopoverContainer from '../components/popover-container';
import ExtensionTrigger from '../components/extension-trigger';

const {
	ToggleControl,
	TextareaControl,
	TextControl,
	SelectControl,
	RangeControl,
	CheckboxControl,
	BaseControl,
} = wp.components;

const { useEffect } = wp.element;
const { __ } = wp.i18n;

function getCanvasBlockWrapper( clientId ) {
	const selector = '.block-editor-block-list__block[data-block="' + clientId + '"]';
	const docs = [ document ];
	document.querySelectorAll( 'iframe[name="editor-canvas"], .block-editor-iframe iframe' ).forEach( ( iframe ) => {
		if ( iframe.contentDocument ) {
			docs.push( iframe.contentDocument );
		}
	} );

	for ( let d = 0; d < docs.length; d++ ) {
		const all = docs[ d ].querySelectorAll( selector );
		for ( var i = 0; i < all.length; i++ ) {
			if ( ! all[ i ].closest( '.block-editor-list-view-tree' ) ) {
				return all[ i ];
			}
		}
	}
	return null;
}

export const TOOLTIP_BLOCKS = ( window.JetTricksBlocksData && window.JetTricksBlocksData.tooltipBlocks ) || [];

export function getTooltipAttributes() {
	return {
		jetTricksTooltip: { type: 'boolean', default: false },
		jetTricksTooltipContent: { type: 'string', default: '' },
		jetTricksTooltipPlacement: { type: 'string', default: 'top' },
		jetTricksTooltipArrow: { type: 'boolean', default: true },
		jetTricksTooltipTrigger: { type: 'string', default: 'mouseenter' },
		jetTricksTooltipAnimation: { type: 'string', default: 'fade' },
		jetTricksTooltipDelay: { type: 'number', default: 0 },
		jetTricksTooltipOffset: { type: 'object', default: { x: 0, y: 0 } },
		jetTricksTooltipFollowCursor: { type: 'string', default: 'false' },
		jetTricksTooltipAppendTo: { type: 'string', default: 'block' },
		jetTricksTooltipZIndex: { type: 'string', default: '999' },
		jetTricksTooltipCustomSelector: { type: 'string', default: '' },
		jetTricksTooltipDevices: {
			type: 'array',
			default: [],
		},
	};
}

function toggleTooltipDevice( devices, deviceKey, checked ) {
	const next = new Set( Array.isArray( devices ) ? [ ...devices ] : [] );
	if ( checked ) {
		next.add( deviceKey );
	} else {
		next.delete( deviceKey );
	}
	return Array.from( next );
}

export function getTooltipPanel( props ) {
	const {
		attributes,
		setAttributes,
	} = props;

	const {
		jetTricksTooltip,
		jetTricksTooltipContent,
		jetTricksTooltipPlacement,
		jetTricksTooltipArrow,
		jetTricksTooltipTrigger,
		jetTricksTooltipAnimation,
		jetTricksTooltipDelay,
		jetTricksTooltipOffset,
		jetTricksTooltipFollowCursor,
		jetTricksTooltipAppendTo,
		jetTricksTooltipZIndex,
		jetTricksTooltipCustomSelector,
		jetTricksTooltipDevices,
	} = attributes;

	const tooltipDevices = Array.isArray( jetTricksTooltipDevices ) ? jetTricksTooltipDevices : [];

	return (
		<PopoverContainer
			label={ __( 'Tooltip Settings', 'jet-tricks' ) }
			trigger={ <ExtensionTrigger label={ __( 'Tooltip', 'jet-tricks' ) } isActive={ !! jetTricksTooltip } /> }
		>
			<>
				<ToggleControl
					label={ __( 'Enable Tooltip', 'jet-tricks' ) }
					checked={ !! jetTricksTooltip }
					onChange={ ( value ) => setAttributes( { jetTricksTooltip: !! value } ) }
				/>
				{ jetTricksTooltip && (
					<TextareaControl
						label={ __( 'Tooltip Content', 'jet-tricks' ) }
						value={ jetTricksTooltipContent || '' }
						onChange={ ( value ) => setAttributes( { jetTricksTooltipContent: value || '' } ) }
						help={ __( 'HTML is allowed.', 'jet-tricks' ) }
						rows={ 3 }
					/>
				) }
				{ jetTricksTooltip && (
					<SelectControl
						label={ __( 'Placement', 'jet-tricks' ) }
						value={ jetTricksTooltipPlacement || 'top' }
						options={ [
							{ value: 'top', label: __( 'Top', 'jet-tricks' ) },
							{ value: 'top-start', label: __( 'Top Start', 'jet-tricks' ) },
							{ value: 'top-end', label: __( 'Top End', 'jet-tricks' ) },
							{ value: 'bottom', label: __( 'Bottom', 'jet-tricks' ) },
							{ value: 'bottom-start', label: __( 'Bottom Start', 'jet-tricks' ) },
							{ value: 'bottom-end', label: __( 'Bottom End', 'jet-tricks' ) },
							{ value: 'left', label: __( 'Left', 'jet-tricks' ) },
							{ value: 'left-start', label: __( 'Left Start', 'jet-tricks' ) },
							{ value: 'left-end', label: __( 'Left End', 'jet-tricks' ) },
							{ value: 'right', label: __( 'Right', 'jet-tricks' ) },
							{ value: 'right-start', label: __( 'Right Start', 'jet-tricks' ) },
							{ value: 'right-end', label: __( 'Right End', 'jet-tricks' ) },
						] }
						onChange={ ( value ) => setAttributes( { jetTricksTooltipPlacement: value || 'top' } ) }
					/>
				) }
				{ jetTricksTooltip && (
					<ToggleControl
						label={ __( 'Show Arrow', 'jet-tricks' ) }
						checked={ jetTricksTooltipArrow !== false }
						onChange={ ( value ) => setAttributes( { jetTricksTooltipArrow: !! value } ) }
					/>
				) }
				{ jetTricksTooltip && (
					<SelectControl
						label={ __( 'Animation', 'jet-tricks' ) }
						value={ jetTricksTooltipAnimation || 'fade' }
						options={ [
							{ value: 'fade', label: __( 'Fade', 'jet-tricks' ) },
							{ value: 'shift-away', label: __( 'Shift Away', 'jet-tricks' ) },
							{ value: 'shift-toward', label: __( 'Shift Toward', 'jet-tricks' ) },
							{ value: 'scale', label: __( 'Scale', 'jet-tricks' ) },
							{ value: 'perspective', label: __( 'Perspective', 'jet-tricks' ) },
						] }
						onChange={ ( value ) => setAttributes( { jetTricksTooltipAnimation: value || 'fade' } ) }
					/>
				) }
				{ jetTricksTooltip && (
					<SelectControl
						label={ __( 'Trigger', 'jet-tricks' ) }
						value={ jetTricksTooltipTrigger || 'mouseenter' }
						options={ [
							{ value: 'mouseenter', label: __( 'Mouse Enter', 'jet-tricks' ) },
							{ value: 'click', label: __( 'Click', 'jet-tricks' ) },
							{ value: 'focusin', label: __( 'Focus', 'jet-tricks' ) },
							{ value: 'mouseenter click', label: __( 'Mouse Enter + Click', 'jet-tricks' ) },
							{ value: 'mouseenter focusin', label: __( 'Mouse Enter + Focus', 'jet-tricks' ) },
						] }
						onChange={ ( value ) => setAttributes( { jetTricksTooltipTrigger: value || 'mouseenter' } ) }
					/>
				) }
				{ jetTricksTooltip && (
					<BaseControl
						className="jet-tricks-tooltip-devices"
						label={ __( 'Show tooltip on', 'jet-tricks' ) }
					>
						<CheckboxControl
							label={ __( 'Desktop', 'jet-tricks' ) }
							checked={ tooltipDevices.includes( 'desktop' ) }
							onChange={ ( checked ) => setAttributes( {
								jetTricksTooltipDevices: toggleTooltipDevice( tooltipDevices, 'desktop', checked ),
							} ) }
						/>
						<CheckboxControl
							label={ __( 'Tablet', 'jet-tricks' ) }
							checked={ tooltipDevices.includes( 'tablet' ) }
							onChange={ ( checked ) => setAttributes( {
								jetTricksTooltipDevices: toggleTooltipDevice( tooltipDevices, 'tablet', checked ),
							} ) }
						/>
						<CheckboxControl
							label={ __( 'Mobile', 'jet-tricks' ) }
							checked={ tooltipDevices.includes( 'mobile' ) }
							onChange={ ( checked ) => setAttributes( {
								jetTricksTooltipDevices: toggleTooltipDevice( tooltipDevices, 'mobile', checked ),
							} ) }
						/>
					</BaseControl>
				) }
				{ jetTricksTooltip && (
					<SelectControl
						label={ __( 'Follow Cursor', 'jet-tricks' ) }
						value={ jetTricksTooltipFollowCursor || 'false' }
						options={ [
							{ value: 'false', label: __( 'Disabled', 'jet-tricks' ) },
							{ value: 'true', label: __( 'Default', 'jet-tricks' ) },
							{ value: 'initial', label: __( 'Initial', 'jet-tricks' ) },
							{ value: 'horizontal', label: __( 'Horizontal', 'jet-tricks' ) },
							{ value: 'vertical', label: __( 'Vertical', 'jet-tricks' ) },
						] }
						onChange={ ( value ) => setAttributes( { jetTricksTooltipFollowCursor: value || 'false' } ) }
					/>
				) }
				{ jetTricksTooltip && (
					<RangeControl
						label={ __( 'Delay (ms)', 'jet-tricks' ) }
						value={ jetTricksTooltipDelay ?? 0 }
						onChange={ ( value ) => setAttributes( { jetTricksTooltipDelay: value ?? 0 } ) }
						min={ 0 }
						max={ 1000 }
						step={ 100 }
					/>
				) }
				{ jetTricksTooltip && (
					<RangeControl
						label={ __( 'Offset X', 'jet-tricks' ) }
						value={ jetTricksTooltipOffset?.x ?? 0 }
						onChange={ ( value ) => setAttributes( {
							jetTricksTooltipOffset: {
								...( jetTricksTooltipOffset || {} ),
								x: value ?? 0,
							},
						} ) }
						min={ -1000 }
						max={ 1000 }
						step={ 1 }
					/>
				) }
				{ jetTricksTooltip && (
					<RangeControl
						label={ __( 'Offset Y', 'jet-tricks' ) }
						value={ jetTricksTooltipOffset?.y ?? 0 }
						onChange={ ( value ) => setAttributes( {
							jetTricksTooltipOffset: {
								...( jetTricksTooltipOffset || {} ),
								y: value ?? 0,
							},
						} ) }
						min={ -1000 }
						max={ 1000 }
						step={ 1 }
					/>
				) }
				{ jetTricksTooltip && (
					<TextControl
						label={ __( 'Z-Index', 'jet-tricks' ) }
						value={ jetTricksTooltipZIndex ?? '999' }
						onChange={ ( value ) => setAttributes( { jetTricksTooltipZIndex: value !== undefined && value !== null ? String( value ) : '999' } ) }
						defaultValue="999"
					/>
				) }
				{ jetTricksTooltip && (
					<SelectControl
						label={ __( 'Append tooltip to', 'jet-tricks' ) }
						value={ jetTricksTooltipAppendTo || 'block' }
						options={ [
							{ value: 'block', label: __( 'Block container', 'jet-tricks' ) },
							{ value: 'body', label: __( 'Document body', 'jet-tricks' ) },
						] }
						onChange={ ( value ) => setAttributes( { jetTricksTooltipAppendTo: value || 'block' } ) }
					/>
				) }
				{ jetTricksTooltip && (
					<TextControl
						label={ __( 'Custom Selector', 'jet-tricks' ) }
						value={ jetTricksTooltipCustomSelector || '' }
						onChange={ ( value ) => setAttributes( { jetTricksTooltipCustomSelector: value || '' } ) }
					/>
				) }
			</>
		</PopoverContainer>
	);
}

function getTooltipWrapperClass( el, attributes ) {
	const fromAttrs = ( attributes?.crocoblock_styles?._uniqueClassName || '' ).trim();
	if ( fromAttrs ) {
		return fromAttrs;
	}
	if ( ! el || el.nodeType !== 1 ) {
		return '';
	}
	const candidates = [ el ];
	if ( el.querySelectorAll ) {
		el.querySelectorAll( '[class*="cb-"]' ).forEach( ( n ) => candidates.push( n ) );
	}
	for ( let d = 0, p = el.parentElement; d < 8 && p; d++, p = p.parentElement ) {
		candidates.push( p );
	}
	for ( let i = 0; i < candidates.length; i++ ) {
		const list = candidates[ i ].classList;
		if ( ! list ) {
			continue;
		}
		for ( let j = 0; j < list.length; j++ ) {
			const c = list[ j ];
			if ( /^cb-[a-z0-9_-]+$/i.test( c ) ) {
				return c;
			}
		}
	}
	return '';
}

function getTooltipTippyAppendRoot( targetEl ) {
	const doc = targetEl.ownerDocument || document;
	return (
		targetEl.closest( '.editor-styles-wrapper' ) ||
		targetEl.closest( '.block-editor-iframe__body' ) ||
		targetEl.closest( '.block-editor-block-list__layout' ) ||
		targetEl.closest( '.edit-post-visual-editor' ) ||
		doc.body
	);
}

function initTooltipInEditor( clientId, attributes ) {
	if ( ! window.tippy ) {
		return () => {};
	}
	const tryInit = () => {
		const wrapper = getCanvasBlockWrapper( clientId );
		if ( ! wrapper ) {
			return null;
		}

		const customSelector = attributes?.jetTricksTooltipCustomSelector?.trim();
		const targetEl = customSelector ? wrapper.querySelector( customSelector ) : wrapper;
		if ( ! targetEl ) {
			return null;
		}

		const content = attributes?.jetTricksTooltipContent || '';
		if ( ! content ) {
			return null;
		}

		if ( targetEl._tippy ) {
			targetEl._tippy.destroy();
		}

		const wrapperClass = getTooltipWrapperClass( wrapper, attributes );
		const appendRoot = getTooltipTippyAppendRoot( targetEl );

		const options = {
			content,
			allowHTML: true,
			arrow: attributes?.jetTricksTooltipArrow !== false,
			placement: attributes?.jetTricksTooltipPlacement || 'top',
			trigger: 'manual',
			appendTo: () => appendRoot,
			maxWidth: 'none',
			animation: attributes?.jetTricksTooltipAnimation || 'fade',
			offset: [ attributes?.jetTricksTooltipOffset?.x ?? 0, attributes?.jetTricksTooltipOffset?.y ?? 0 ],
			zIndex: parseInt( attributes?.jetTricksTooltipZIndex, 10 ) || 999,
		};

		if ( wrapperClass ) {
			options.onCreate = ( instance ) => {
				instance.popper.classList.add( wrapperClass );
			};
		}

		window.tippy( targetEl, options );
		if ( targetEl._tippy ) {
			targetEl._tippy.show();
		}

		return () => {
			if ( targetEl._tippy ) {
				targetEl._tippy.destroy();
			}
		};
	};

	return tryInit() || ( () => {} );
}

export function withBlockTooltipEditor( BlockListBlock ) {
	return ( props ) => {
		const hasTooltip =
			TOOLTIP_BLOCKS.includes( props.name ) &&
			props.attributes?.jetTricksTooltip &&
			( props.attributes?.jetTricksTooltipContent || '' ).trim();

		useEffect( () => {
			if ( ! hasTooltip ) {
				return;
			}

			const clientId = props.clientId;
			const attributes = props.attributes;
			const customSelector = attributes?.jetTricksTooltipCustomSelector?.trim();
			let cleanup = null;
			let rafId = null;
			let retryTimer = null;
			let ob = null;

			const setup = () => {
				cleanup = initTooltipInEditor( clientId, attributes );

				const wrapper = getCanvasBlockWrapper( clientId );
				if ( ! wrapper ) {
					retryTimer = setTimeout( setup, 100 );
					return;
				}

				const parent = wrapper.closest( '.block-editor-block-list__layout' ) || wrapper.parentElement;
				if ( ! parent ) {
					return;
				}

				const maybeReinit = () => {
					if ( rafId ) {
						return;
					}
					rafId = requestAnimationFrame( () => {
						rafId = null;
						const current = getCanvasBlockWrapper( clientId );
						if ( ! current ) {
							return;
						}
						const target = customSelector ? current.querySelector( customSelector ) : current;
						if ( target && ! target._tippy ) {
							if ( cleanup ) {
								cleanup();
							}
							cleanup = initTooltipInEditor( clientId, attributes );
						}
					} );
				};

				ob = new MutationObserver( maybeReinit );
				ob.observe( parent, { childList: true, subtree: true } );
			};

			setup();

			return () => {
				if ( ob ) {
					ob.disconnect();
				}
				if ( rafId ) {
					cancelAnimationFrame( rafId );
				}
				if ( retryTimer ) {
					clearTimeout( retryTimer );
				}
				if ( cleanup ) {
					cleanup();
				}
			};
		}, [
			hasTooltip,
			props.clientId,
			props.isSelected,
			props.attributes?.jetTricksTooltip,
			props.attributes?.jetTricksTooltipContent,
			props.attributes?.jetTricksTooltipPlacement,
			props.attributes?.jetTricksTooltipArrow,
			props.attributes?.jetTricksTooltipCustomSelector,
			props.attributes?.jetTricksTooltipOffset,
			props.attributes?.jetTricksTooltipZIndex,
			props.attributes?.jetTricksTooltipAnimation,
			props.attributes?.jetTricksTooltipDevices,
			props.attributes?.crocoblock_styles,
			props.attributes?.style,
			props.attributes?.className,
		] );

		return <BlockListBlock { ...props } />;
	};
}
