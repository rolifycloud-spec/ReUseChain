/** JetTricks Satellite — Gutenberg. */
import IconPicker from '../components/icon-picker';
import PopoverContainer, { PopoverContainerContext } from '../components/popover-container';
import ExtensionTrigger from '../components/extension-trigger';

const {
	Button,
	ToggleControl,
	TextControl,
	SelectControl,
	RangeControl,
} = wp.components;
const { MediaUpload, MediaUploadCheck } = wp.blockEditor;
const { useContext, useLayoutEffect } = wp.element;
const { __ } = wp.i18n;

export const SATELLITE_BLOCKS = ( window.JetTricksBlocksData && window.JetTricksBlocksData.satelliteBlocks ) || [];

function escapeHtml( str ) {
	if ( ! str ) {
		return '';
	}
	const d = document.createElement( 'div' );
	d.textContent = str;
	return d.innerHTML;
}

function buildLinkAttributes( link ) {
	if ( ! link || ! link.url ) {
		return '';
	}
	const external = link.opensInNewTab || link.is_external;
	const rel = [];
	if ( external ) {
		rel.push( 'noopener', 'noreferrer' );
	}
	const relStr = rel.length ? ' rel="' + rel.join( ' ' ) + '"' : '';
	const target = external ? ' target="_blank"' : '';
	return 'href="' + escapeHtml( link.url ) + '"' + target + relStr;
}

export function getSatelliteLayoutStyleAttr( attributes ) {
	const x = Math.max( -500, Math.min( 500, parseInt( attributes.jetTricksSatelliteOffsetX, 10 ) || 0 ) );
	const y = Math.max( -500, Math.min( 500, parseInt( attributes.jetTricksSatelliteOffsetY, 10 ) || 0 ) );
	let rot = parseFloat( attributes.jetTricksSatelliteRotate );
	if ( Number.isNaN( rot ) ) {
		rot = 0;
	}
	rot = Math.max( -180, Math.min( 180, rot ) );

	let z = attributes.jetTricksSatelliteZIndex;
	if ( z === '' || z === undefined || z === null ) {
		z = 2;
	} else {
		z = parseInt( z, 10 );
		if ( Number.isNaN( z ) ) {
			z = 2;
		}
	}
	z = Math.max( -10, Math.min( 999, z ) );

	const styleParts = [
		'--jet-satellite-offset-x:' + x + 'px',
		'--jet-satellite-offset-y:' + y + 'px',
		'--jet-satellite-rotate:' + String( Math.round( rot * 100 ) / 100 ) + 'deg',
		'--jet-satellite-z:' + z,
	];

	return ' style="' + styleParts.join( ';' ) + '"';
}

function normalizeTagName( tagName ) {
	return String( tagName || '' ).toLowerCase();
}

function rootRequiresPhrasingChildren( tagName ) {
	return [ 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ].includes( normalizeTagName( tagName ) );
}

export function buildSatellitePreviewHtml( attributes, wrapperTag = 'div' ) {
	if ( ! attributes.jetTricksSatellite ) {
		return '';
	}

	const safeWrapperTag = rootRequiresPhrasingChildren( wrapperTag ) || normalizeTagName( wrapperTag ) === 'span' ? 'span' : 'div';
	const instanceTag = safeWrapperTag === 'span' ? 'span' : 'div';
	const type = attributes.jetTricksSatelliteType || 'text';
	const position = attributes.jetTricksSatellitePosition || 'top-center';
	const posClass = 'jet-tricks-satellite jet-tricks-satellite--blocks jet-tricks-satellite--' + String( position ).replace( /[^a-z-]/gi, '' );
	const link = attributes.jetTricksSatelliteLink || {};
	const linkAttr = buildLinkAttributes( link );
	const linkStart = linkAttr ? '<a class="jet-tricks-satellite__link" ' + linkAttr + '>' : '';
	const linkEnd = linkAttr ? '</a>' : '';

	const layoutStyle = getSatelliteLayoutStyleAttr( attributes );

	if ( type === 'text' ) {
		const text = ( attributes.jetTricksSatelliteText || '' ).trim();
		if ( ! text ) {
			return '';
		}
		return '<' + safeWrapperTag + ' class="' + posClass + '"' + layoutStyle + '><' + safeWrapperTag + ' class="jet-tricks-satellite__inner"><' + safeWrapperTag + ' class="jet-tricks-satellite__text">' + linkStart + '<span>' + escapeHtml( text ) + '</span>' + linkEnd + '</' + safeWrapperTag + '></' + safeWrapperTag + '></' + safeWrapperTag + '>';
	}

	if ( type === 'icon' ) {
		const icon = attributes.jetTricksSatelliteIcon || {};
		if ( ! icon.url ) {
			return '';
		}
		const inner = '<img src="' + escapeHtml( icon.url ) + '" alt="" />';
		const iconHtml =
			'<' + instanceTag + ' class="jet-tricks-satellite__icon-instance jet-tricks-icon">' +
			inner +
			'</' + instanceTag + '>';
		return (
			'<' +
			safeWrapperTag +
			' class="' +
			posClass +
			'"' +
			layoutStyle +
			'><' +
			safeWrapperTag +
			' class="jet-tricks-satellite__inner"><' +
			safeWrapperTag +
			' class="jet-tricks-satellite__icon">' +
			linkStart +
			iconHtml +
			linkEnd +
			'</' +
			safeWrapperTag +
			'></' +
			safeWrapperTag +
			'></' +
			safeWrapperTag +
			'>'
		);
	}

	if ( type === 'image' ) {
		const img = attributes.jetTricksSatelliteImage || {};
		if ( ! img.url ) {
			return '';
		}
		return '<' + safeWrapperTag + ' class="' + posClass + '"' + layoutStyle + '><' + safeWrapperTag + ' class="jet-tricks-satellite__inner"><' + safeWrapperTag + ' class="jet-tricks-satellite__image">' + linkStart + '<img class="jet-tricks-satellite__image-instance" src="' + escapeHtml( img.url ) + '" alt="">' + linkEnd + '</' + safeWrapperTag + '></' + safeWrapperTag + '></' + safeWrapperTag + '>';
	}

	return '';
}

export function getSatelliteAttributes() {
	return {
		jetTricksSatellite: { type: 'boolean', default: false },
		jetTricksSatelliteType: { type: 'string', default: 'text' },
		jetTricksSatelliteText: { type: 'string', default: '' },
		jetTricksSatelliteIcon: { type: 'object', default: {} },
		jetTricksSatelliteImage: { type: 'object', default: { id: 0, url: '' } },
		jetTricksSatelliteLink: {
			type: 'object',
			default: { url: '', opensInNewTab: false },
		},
		jetTricksSatellitePosition: { type: 'string', default: 'top-center' },
		jetTricksSatelliteOffsetX: { type: 'number', default: 0 },
		jetTricksSatelliteOffsetY: { type: 'number', default: 0 },
		jetTricksSatelliteRotate: { type: 'number', default: 0 },
		jetTricksSatelliteZIndex: { type: 'string', default: '2' },
	};
}

function SatelliteImagePicker( { image, setAttributes } ) {
	const { closePopover } = useContext( PopoverContainerContext );

	return (
		<MediaUploadCheck>
			<div className="jet-tricks-satellite-image-picker" style={ { marginBottom: '16px' } }>
				<div className="components-base-control__label" style={ { marginBottom: '8px' } }>
					{ __( 'Image', 'jet-tricks' ) }
				</div>
				{ image?.url && (
					<div className="preview-jet-tricks-media" style={ { marginBottom: '8px' } }>
						<img
							src={ image.url }
							alt=""
							style={ { maxWidth: '100%', height: 'auto' } }
						/>
					</div>
				) }
				<MediaUpload
					onSelect={ ( media ) =>
						setAttributes( {
							jetTricksSatelliteImage: {
								id: media.id,
								url: media.url,
							},
						} ) }
					allowedTypes={ [ 'image' ] }
					value={ image?.id }
					render={ ( { open } ) => (
						<div style={ { display: 'flex', gap: '8px' } }>
							<Button
								variant="secondary"
								icon={ image?.url ? 'edit' : 'format-image' }
								onClick={ () => {
									closePopover();
									setTimeout( open, 0 );
								} }
							>
								{ image?.url ? __( 'Replace Image', 'jet-tricks' ) : __( 'Choose Image', 'jet-tricks' ) }
							</Button>
							{ image?.url && (
								<Button
									isDestructive={ true }
									icon="trash"
									label={ __( 'Remove', 'jet-tricks' ) }
									onClick={ () => setAttributes( { jetTricksSatelliteImage: { id: 0, url: '' } } ) }
								/>
							) }
						</div>
					) }
				/>
			</div>
		</MediaUploadCheck>
	);
}

export function getSatellitePanel( props ) {
	const { attributes, setAttributes } = props;
	const {
		jetTricksSatellite,
		jetTricksSatelliteType,
		jetTricksSatelliteText,
		jetTricksSatelliteIcon,
		jetTricksSatelliteImage,
		jetTricksSatelliteLink,
		jetTricksSatellitePosition,
		jetTricksSatelliteOffsetX,
		jetTricksSatelliteOffsetY,
		jetTricksSatelliteRotate,
		jetTricksSatelliteZIndex,
	} = attributes;

	const link = jetTricksSatelliteLink || { url: '', opensInNewTab: false };

	return (
		<PopoverContainer
			label={ __( 'Satellite Settings', 'jet-tricks' ) }
			trigger={ <ExtensionTrigger label={ __( 'Satellite', 'jet-tricks' ) } isActive={ !! jetTricksSatellite } /> }
		>
			<>
				<ToggleControl
					label={ __( 'Use Satellite', 'jet-tricks' ) }
					checked={ !! jetTricksSatellite }
					onChange={ ( value ) => setAttributes( { jetTricksSatellite: !! value } ) }
				/>
				{ jetTricksSatellite && (
					<SelectControl
						label={ __( 'Type', 'jet-tricks' ) }
						value={ jetTricksSatelliteType || 'text' }
						options={ [
							{ value: 'text', label: __( 'Text', 'jet-tricks' ) },
							{ value: 'icon', label: __( 'SVG Icon', 'jet-tricks' ) },
							{ value: 'image', label: __( 'Image', 'jet-tricks' ) },
						] }
						onChange={ ( value ) => setAttributes( { jetTricksSatelliteType: value || 'text' } ) }
					/>
				) }
				{ jetTricksSatellite && jetTricksSatelliteType === 'text' && (
					<TextControl
						label={ __( 'Text', 'jet-tricks' ) }
						value={ jetTricksSatelliteText || '' }
						onChange={ ( value ) => setAttributes( { jetTricksSatelliteText: value || '' } ) }
					/>
				) }
				{ jetTricksSatellite && jetTricksSatelliteType === 'icon' && (
					<IconPicker
						label={ __( 'Icon (SVG)', 'jet-tricks' ) }
						value={ jetTricksSatelliteIcon || {} }
						onChange={ ( next ) =>
							setAttributes( {
								jetTricksSatelliteIcon: next && typeof next === 'object' ? next : {},
							} ) }
					/>
				) }
				{ jetTricksSatellite && jetTricksSatelliteType === 'image' && (
					<SatelliteImagePicker
						image={ jetTricksSatelliteImage }
						setAttributes={ setAttributes }
					/>
				) }
				{ jetTricksSatellite && (
					<TextControl
						label={ __( 'Link URL', 'jet-tricks' ) }
						value={ link.url || '' }
						onChange={ ( value ) =>
							setAttributes( {
								jetTricksSatelliteLink: { ...link, url: value || '' },
							} ) }
					/>
				) }
				{ jetTricksSatellite && ( link.url || '' ) && (
					<ToggleControl
						label={ __( 'Open in new tab', 'jet-tricks' ) }
						checked={ !! link.opensInNewTab }
						onChange={ ( value ) =>
							setAttributes( {
								jetTricksSatelliteLink: { ...link, opensInNewTab: !! value },
							} ) }
					/>
				) }
				{ jetTricksSatellite && (
					<SelectControl
						label={ __( 'Position', 'jet-tricks' ) }
						value={ jetTricksSatellitePosition || 'top-center' }
						options={ [
							{ value: 'top-left', label: __( 'Top Left', 'jet-tricks' ) },
							{ value: 'top-center', label: __( 'Top Center', 'jet-tricks' ) },
							{ value: 'top-right', label: __( 'Top Right', 'jet-tricks' ) },
							{ value: 'middle-left', label: __( 'Middle Left', 'jet-tricks' ) },
							{ value: 'middle-center', label: __( 'Middle Center', 'jet-tricks' ) },
							{ value: 'middle-right', label: __( 'Middle Right', 'jet-tricks' ) },
							{ value: 'bottom-left', label: __( 'Bottom Left', 'jet-tricks' ) },
							{ value: 'bottom-center', label: __( 'Bottom Center', 'jet-tricks' ) },
							{ value: 'bottom-right', label: __( 'Bottom Right', 'jet-tricks' ) },
						] }
						onChange={ ( value ) => setAttributes( { jetTricksSatellitePosition: value || 'top-center' } ) }
					/>
				) }
				{ jetTricksSatellite && (
					<RangeControl
						label={ __( 'Offset X', 'jet-tricks' ) }
						value={ jetTricksSatelliteOffsetX ?? 0 }
						onChange={ ( value ) => setAttributes( { jetTricksSatelliteOffsetX: value ?? 0 } ) }
						min={ -500 }
						max={ 500 }
						step={ 1 }
					/>
				) }
				{ jetTricksSatellite && (
					<RangeControl
						label={ __( 'Offset Y', 'jet-tricks' ) }
						value={ jetTricksSatelliteOffsetY ?? 0 }
						onChange={ ( value ) => setAttributes( { jetTricksSatelliteOffsetY: value ?? 0 } ) }
						min={ -500 }
						max={ 500 }
						step={ 1 }
					/>
				) }
				{ jetTricksSatellite && (
					<RangeControl
						label={ __( 'Rotate', 'jet-tricks' ) }
						value={ jetTricksSatelliteRotate ?? 0 }
						onChange={ ( value ) => setAttributes( { jetTricksSatelliteRotate: value ?? 0 } ) }
						min={ -180 }
						max={ 180 }
						step={ 1 }
					/>
				) }
				{ jetTricksSatellite && (
					<TextControl
						label={ __( 'Z-Index', 'jet-tricks' ) }
						value={
							jetTricksSatelliteZIndex !== undefined && jetTricksSatelliteZIndex !== null
								? String( jetTricksSatelliteZIndex )
								: '2'
						}
						onChange={ ( value ) =>
							setAttributes( {
								jetTricksSatelliteZIndex: value !== undefined && value !== null ? String( value ) : '2',
							} ) }
					/>
				) }
			</>
		</PopoverContainer>
	);
}

function inlineSvgsInContainer( container ) {
	if ( ! container ) {
		return;
	}
	container.querySelectorAll( '.jet-tricks-icon img' ).forEach( function ( img ) {
		var src = img.getAttribute( 'src' ) || '';
		if ( ! /\.svg(\?.*)?$/i.test( src ) ) {
			return;
		}
		fetch( src )
			.then( function ( r ) {
				return r.text();
			} )
			.then( function ( text ) {
				if ( text.indexOf( '<svg' ) !== -1 && img.parentNode ) {
					var tmp = document.createElement( 'div' );
					tmp.innerHTML = text;
					var svg = tmp.querySelector( 'svg' );
					if ( svg ) {
						img.parentNode.replaceChild( svg, img );
					}
				}
			} )
			.catch( function () {} );
	} );
}

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

export function injectSatelliteInEditor( clientId, attributes ) {
	const run = () => {
		const wrapper = getCanvasBlockWrapper( clientId );
		if ( ! wrapper ) {
			return () => {};
		}

		const html = buildSatellitePreviewHtml( attributes, wrapper.tagName );

		const cleanupSat = () => {
			wrapper.classList.remove( 'jet-satellite-widget' );
			wrapper.classList.remove( 'jet-tricks-block-satellite' );
			const existing = wrapper.querySelector( ':scope > .jet-tricks-satellite' );
			if ( existing ) {
				existing.remove();
			}
		};

		if ( ! attributes.jetTricksSatellite || ! html ) {
			cleanupSat();
			return () => cleanupSat();
		}

		wrapper.classList.add( 'jet-satellite-widget' );
		wrapper.classList.add( 'jet-tricks-block-satellite' );
		const prev = wrapper.querySelector( ':scope > .jet-tricks-satellite' );
		if ( prev ) {
			prev.remove();
		}
		wrapper.insertAdjacentHTML( 'afterbegin', html );

		inlineSvgsInContainer( wrapper.querySelector( ':scope > .jet-tricks-satellite' ) );

		return () => cleanupSat();
	};

	const cleanup = run();
	return () => cleanup();
}

export function withBlockSatelliteEditor( BlockListBlock ) {
	return ( props ) => {
		useLayoutEffect( () => {
			if ( ! SATELLITE_BLOCKS.includes( props.name ) ) {
				return;
			}

			const clientId = props.clientId;
			const attributes = props.attributes;
			let cleanup = injectSatelliteInEditor( clientId, attributes );

			const wrapper = getCanvasBlockWrapper( clientId );
			if ( ! wrapper ) {
				return () => {
					if ( cleanup ) {
						cleanup();
					}
				};
			}

			const parent = wrapper.closest( '.block-editor-block-list__layout' ) || wrapper.parentElement;
			if ( ! parent ) {
				return () => {
					if ( cleanup ) {
						cleanup();
					}
				};
			}

			let rafId = null;
			const maybeReinject = () => {
				if ( rafId ) {
					return;
				}
				rafId = requestAnimationFrame( () => {
					rafId = null;
					const current = getCanvasBlockWrapper( clientId );
					if ( ! current ) {
						return;
					}
					const needsSatellite =
						attributes.jetTricksSatellite && buildSatellitePreviewHtml( attributes );
					if ( needsSatellite && ! current.querySelector( ':scope > .jet-tricks-satellite' ) ) {
						if ( cleanup ) {
							cleanup();
						}
						cleanup = injectSatelliteInEditor( clientId, attributes );
					}
				} );
			};

			const ob = new MutationObserver( maybeReinject );
			ob.observe( parent, { childList: true, subtree: true } );

			return () => {
				ob.disconnect();
				if ( rafId ) {
					cancelAnimationFrame( rafId );
				}
				if ( cleanup ) {
					cleanup();
				}
			};
		}, [
			props.name,
			props.clientId,
			props.attributes?.jetTricksSatellite,
			props.attributes?.jetTricksSatelliteType,
			props.attributes?.jetTricksSatelliteText,
			props.attributes?.jetTricksSatelliteIcon,
			props.attributes?.jetTricksSatelliteImage,
			props.attributes?.jetTricksSatelliteLink,
			props.attributes?.jetTricksSatellitePosition,
			props.attributes?.jetTricksSatelliteOffsetX,
			props.attributes?.jetTricksSatelliteOffsetY,
			props.attributes?.jetTricksSatelliteRotate,
			props.attributes?.jetTricksSatelliteZIndex,
		] );

		return <BlockListBlock { ...props } />;
	};
}
