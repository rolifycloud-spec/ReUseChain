import metadata from './block.json';
import IconPicker from '../../components/icon-picker';

const { __ } = wp.i18n;
const { icon } = metadata;
const { registerBlockType } = wp.blocks;

const {
	InspectorControls,
	InnerBlocks,
	useBlockProps
} = wp.blockEditor;

const {
	PanelBody,
	TextControl,
	SelectControl,
	ToggleControl,
	RangeControl,
} = wp.components;

const { useState, useEffect, useRef, useLayoutEffect } = wp.element;

const EASING_OPTIONS = [
	{ label: __( 'Linear', 'jet-tricks' ), value: 'linear' },
	{ label: __( 'Sine', 'jet-tricks' ), value: 'easeOutSine' },
	{ label: __( 'Expo', 'jet-tricks' ), value: 'easeOutExpo' },
	{ label: __( 'Circ', 'jet-tricks' ), value: 'easeOutCirc' },
	{ label: __( 'Back', 'jet-tricks' ), value: 'easeOutBack' },
	{ label: __( 'InOutSine', 'jet-tricks' ), value: 'easeInOutSine' },
	{ label: __( 'InOutExpo', 'jet-tricks' ), value: 'easeInOutExpo' },
	{ label: __( 'InOutCirc', 'jet-tricks' ), value: 'easeInOutCirc' },
	{ label: __( 'InOutBack', 'jet-tricks' ), value: 'easeInOutBack' },
];

const UNFOLD_TEMPLATE = [
	[ 'core/paragraph', { placeholder: __( 'Add content to unfold...', 'jet-tricks' ) } ],
];

/**
 * Inline SVG component — fetches .svg URL and renders it as inline markup
 * so CSS color/fill can style it (unlike <img> which isolates SVG from DOM styles).
 */
function InlineSvg( { url, style } ) {
	const [ html, setHtml ] = useState( null );

	useEffect( () => {
		if ( ! url || ! /\.svg(\?.*)?$/i.test( url ) ) {
			setHtml( null );
			return;
		}
		let cancelled = false;
		fetch( url )
			.then( ( r ) => r.text() )
			.then( ( text ) => {
				if ( ! cancelled && text.indexOf( '<svg' ) !== -1 ) {
					setHtml( text );
				}
			} )
			.catch( () => {} );
		return () => {
			cancelled = true;
		};
	}, [ url ] );

	if ( html ) {
		return <span style={ style } dangerouslySetInnerHTML={ { __html: html } } />;
	}
	return <img src={ url } alt="" style={ style } />;
}

function getEditorIconHtml( icon ) {
	if ( ! icon ) return null;
	if ( icon.url ) return <InlineSvg url={ icon.url } style={ { width: '1em', height: '1em' } } />;
	if ( icon.value ) return <i className={ icon.value } />;
	return null;
}

registerBlockType( metadata, {
	icon: <span dangerouslySetInnerHTML={ { __html: icon } }></span>,
	edit: props => {
		const blockProps = useBlockProps();
		const {
			attributes,
			setAttributes,
		} = props;

		const [ previewExpanded, setPreviewExpanded ] = useState( 'unfolded' );
		const [ maskHeightInput, setMaskHeightInput ] = useState( null );
		const [ separatorHeightInput, setSeparatorHeightInput ] = useState( null );
		const [ wordCountHeight, setWordCountHeight ] = useState( null );
		const contentInnerRef = useRef( null );
		const isPreviewExpanded = previewExpanded === 'unfolded';

		const maskHeight = attributes.maskHeight ?? { size: 50, unit: 'px' };
		const heightControl = attributes.heightControlType ?? 'height';
		const wordCount = attributes.wordCount ?? 20;
		const separatorType = attributes.separatorType ?? 'div';
		const separatorHeight = attributes.separatorHeight ?? { size: 30, unit: 'px' };
		const unfoldText = attributes.unfoldText ?? __( 'Show', 'jet-tricks' );
		const foldText = attributes.foldText ?? __( 'Hide', 'jet-tricks' );
		const unfoldIcon = getEditorIconHtml( attributes.unfoldIcon );
		const foldIcon = getEditorIconHtml( attributes.foldIcon );

		function measureWordCountHeight() {
			if ( ! contentInnerRef.current ) return;
			var el = contentInnerRef.current;
			var text = el.textContent.trim();
			if ( ! text ) {
				setWordCountHeight( 80 );
				return;
			}
			var words = text.split( /\s+/ );
			var wordsToShow = Math.min( wordCount, words.length );
			if ( wordsToShow >= words.length ) {
				setWordCountHeight( el.offsetHeight );
				return;
			}
			var visibleText = words.slice( 0, wordsToShow ).join( ' ' );
			var targetLength = visibleText.length;
			var range = document.createRange();
			var walker = document.createTreeWalker( el, NodeFilter.SHOW_TEXT, null, false );
			var charCount = 0;
			var startNode = null, endNode = null, endOffset = 0;
			while ( walker.nextNode() ) {
				var node = walker.currentNode;
				if ( ! startNode ) startNode = node;
				var len = node.textContent.length;
				if ( charCount + len >= targetLength ) {
					endNode = node;
					endOffset = targetLength - charCount;
					break;
				}
				charCount += len;
			}
			if ( ! endNode || ! startNode ) {
				setWordCountHeight( 80 );
				return;
			}
			try {
				range.setStart( startNode, 0 );
				range.setEnd( endNode, endOffset );
				var rect = range.getBoundingClientRect();
				var containerRect = el.getBoundingClientRect();
				setWordCountHeight( Math.ceil( rect.bottom - containerRect.top ) );
			} catch ( e ) {
				setWordCountHeight( 80 );
			}
		}

		useLayoutEffect( () => {
			if ( heightControl !== 'word_count' || isPreviewExpanded ) {
				setWordCountHeight( null );
				return;
			}
			var el = contentInnerRef.current;
			if ( ! el ) return;
			measureWordCountHeight();
			var ro = typeof ResizeObserver !== 'undefined' ? new ResizeObserver( measureWordCountHeight ) : null;
			if ( ro ) ro.observe( el );
			return function () { if ( ro ) ro.disconnect(); };
		}, [ heightControl, wordCount, isPreviewExpanded ] );

		const maskStyle = {};
		if ( ! isPreviewExpanded ) {
			if ( heightControl === 'height' && maskHeight?.size != null && maskHeight?.unit ) {
				maskStyle.height = `${ maskHeight.size }${ maskHeight.unit }`;
			} else if ( heightControl === 'word_count' ) {
				maskStyle.height = wordCountHeight != null ? `${ wordCountHeight }px` : '80px';
			}
		}
		if ( separatorType === 'gradient' && separatorHeight?.size != null && separatorHeight?.unit ) {
			const sh = `${ separatorHeight.size }${ separatorHeight.unit }`;
			maskStyle.maskImage = `linear-gradient(to top, transparent 0%, black ${ sh })`;
			maskStyle.WebkitMaskImage = `linear-gradient(to top, transparent 0%, black ${ sh })`;
		}

		const separatorStyle = {};
		if ( separatorType === 'div' && separatorHeight?.size != null && separatorHeight?.unit ) {
			separatorStyle.height = `${ separatorHeight.size }${ separatorHeight.unit }`;
		}

		const maskClasses = [ 'jet-unfold__mask' ];
		if ( separatorType === 'gradient' && ! isPreviewExpanded ) {
			maskClasses.push( 'jet-unfold__mask-gradient' );
		}

		const instanceClasses = [ 'jet-unfold' ];
		if ( isPreviewExpanded ) {
			instanceClasses.push( 'jet-unfold-state' );
		}

		const togglePreview = ( e ) => {
			e.preventDefault();
			e.stopPropagation();
			setPreviewExpanded( prev => prev === 'folded' ? 'unfolded' : 'folded' );
		};

		return [
			props.isSelected && (
				<InspectorControls key="inspector">
						<PanelBody title={ __( 'Settings', 'jet-tricks' ) } initialOpen>
							<ToggleControl
								label={ __( 'Fold', 'jet-tricks' ) }
								checked={ attributes.fold }
								onChange={ ( fold ) => setAttributes( { fold } ) }
								__nextHasNoMarginBottom
							/>

							<ToggleControl
								label={ __( 'Scroll to Top After Hiding Content', 'jet-tricks' ) }
								checked={ attributes.foldScroll }
								onChange={ ( foldScroll ) => setAttributes( { foldScroll } ) }
								__nextHasNoMarginBottom
							/>

							{ attributes.foldScroll && (
								<RangeControl
									label={ __( 'Scroll to Top Offset (px)', 'jet-tricks' ) }
									value={ attributes.foldScrollOffset?.size ?? 0 }
									onChange={ ( size ) => setAttributes( { foldScrollOffset: { size, unit: 'px' } } ) }
									min={ 0 }
									max={ 500 }
									__nextHasNoMarginBottom
								/>
							) }

							<ToggleControl
								label={ __( 'Fold After a Specified Amount of Time', 'jet-tricks' ) }
								checked={ attributes.autohide }
								onChange={ ( autohide ) => setAttributes( { autohide } ) }
								__nextHasNoMarginBottom
							/>

							{ attributes.autohide && (
								<RangeControl
									label={ __( 'Autohide Time (seconds)', 'jet-tricks' ) }
									value={ attributes.autohideTime }
									onChange={ ( autohideTime ) => setAttributes( { autohideTime } ) }
									min={ 1 }
									max={ 20 }
									__nextHasNoMarginBottom
								/>
							) }

							<ToggleControl
								label={ __( 'Fold Content on Click Outside Widget', 'jet-tricks' ) }
								checked={ attributes.hideOutsideClick }
								onChange={ ( hideOutsideClick ) => setAttributes( { hideOutsideClick } ) }
								__nextHasNoMarginBottom
							/>

							<SelectControl
								label={ __( 'Height Type', 'jet-tricks' ) }
								value={ attributes.heightControlType }
								options={ [
									{ label: __( 'Fixed Height', 'jet-tricks' ), value: 'height' },
									{ label: __( 'Word Count', 'jet-tricks' ), value: 'word_count' },
								] }
								onChange={ ( heightControlType ) => setAttributes( { heightControlType } ) }
								__nextHasNoMarginBottom
							/>

							{ attributes.heightControlType === 'height' && (
								<TextControl
									label={ __( 'Closed Height', 'jet-tricks' ) }
									help={ __( 'e.g. 50px, 30vh, 10%', 'jet-tricks' ) }
									value={ maskHeightInput !== null
										? maskHeightInput
										: ( attributes.maskHeight?.size != null && attributes.maskHeight?.unit
											? `${ attributes.maskHeight.size }${ attributes.maskHeight.unit }`
											: '' ) }
									onChange={ ( value ) => {
										setMaskHeightInput( value );
										if ( value === '' || value == null ) {
											setAttributes( { maskHeight: undefined } );
											return;
										}
										const match = String( value ).trim().match( /^(\d+(?:\.\d+)?)\s*(px|%|vh|em|rem)?$/i );
										if ( match ) {
											setAttributes( {
												maskHeight: {
													size: parseFloat( match[ 1 ] ),
													unit: match[ 2 ] || 'px',
												},
											} );
										}
									} }
									onBlur={ () => setMaskHeightInput( null ) }
									__nextHasNoMarginBottom
								/>
							) }

							{ attributes.heightControlType === 'word_count' && (
								<RangeControl
									label={ __( 'Number of Words to Show', 'jet-tricks' ) }
									value={ attributes.wordCount }
									onChange={ ( wordCount ) => setAttributes( { wordCount } ) }
									min={ 1 }
									max={ 500 }
									__nextHasNoMarginBottom
								/>
							) }

							<SelectControl
								label={ __( 'Separator Type', 'jet-tricks' ) }
								value={ attributes.separatorType }
								options={ [
									{ label: __( 'DIV', 'jet-tricks' ), value: 'div' },
									{ label: __( 'Gradient Mask', 'jet-tricks' ), value: 'gradient' },
								] }
								onChange={ ( separatorType ) => setAttributes( { separatorType } ) }
								__nextHasNoMarginBottom
							/>

							<TextControl
								label={ __( 'Separator Height', 'jet-tricks' ) }
								help={ __( 'e.g. 30px, 10vh, 5%', 'jet-tricks' ) }
								value={ separatorHeightInput !== null
									? separatorHeightInput
									: ( attributes.separatorHeight?.size != null && attributes.separatorHeight?.unit
										? `${ attributes.separatorHeight.size }${ attributes.separatorHeight.unit }`
										: '' ) }
								onChange={ ( value ) => {
									setSeparatorHeightInput( value );
									if ( value === '' || value == null ) {
										setAttributes( { separatorHeight: undefined } );
										return;
									}
									const match = String( value ).trim().match( /^(\d+(?:\.\d+)?)\s*(px|%|vh|em|rem)?$/i );
									if ( match ) {
										setAttributes( {
											separatorHeight: {
												size: parseFloat( match[ 1 ] ),
												unit: match[ 2 ] || 'px',
											},
										} );
									}
								} }
								onBlur={ () => setSeparatorHeightInput( null ) }
								__nextHasNoMarginBottom
							/>

							<TextControl
								label={ __( 'Unfold Text', 'jet-tricks' ) }
								value={ attributes.unfoldText ?? '' }
								onChange={ ( unfoldText ) => setAttributes( { unfoldText } ) }
								__nextHasNoMarginBottom
							/>

							<TextControl
								label={ __( 'Fold Text', 'jet-tricks' ) }
								value={ attributes.foldText ?? '' }
								onChange={ ( foldText ) => setAttributes( { foldText } ) }
								__nextHasNoMarginBottom
							/>

							<IconPicker
								label={ __( 'Unfold Icon (SVG)', 'jet-tricks' ) }
								value={ attributes.unfoldIcon }
								onChange={ ( unfoldIcon ) => setAttributes( { unfoldIcon } ) }
							/>

							<IconPicker
								label={ __( 'Fold Icon (SVG)', 'jet-tricks' ) }
								value={ attributes.foldIcon }
								onChange={ ( foldIcon ) => setAttributes( { foldIcon } ) }
							/>
						</PanelBody>

						<PanelBody title={ __( 'Animation', 'jet-tricks' ) }>
							<RangeControl
								label={ __( 'Unfold Duration (ms)', 'jet-tricks' ) }
								value={ attributes.unfoldDuration?.size ?? 500 }
								onChange={ ( size ) => setAttributes( { unfoldDuration: { size, unit: 'ms' } } ) }
								min={ 100 }
								max={ 3000 }
								step={ 100 }
								__nextHasNoMarginBottom
							/>

							<SelectControl
								label={ __( 'Unfold Easing', 'jet-tricks' ) }
								value={ attributes.unfoldEasing }
								options={ EASING_OPTIONS }
								onChange={ ( unfoldEasing ) => setAttributes( { unfoldEasing } ) }
								__nextHasNoMarginBottom
							/>

							<RangeControl
								label={ __( 'Fold Duration (ms)', 'jet-tricks' ) }
								value={ attributes.foldDuration?.size ?? 300 }
								onChange={ ( size ) => setAttributes( { foldDuration: { size, unit: 'ms' } } ) }
								min={ 100 }
								max={ 3000 }
								step={ 100 }
								__nextHasNoMarginBottom
							/>

							<SelectControl
								label={ __( 'Fold Easing', 'jet-tricks' ) }
								value={ attributes.foldEasing }
								options={ EASING_OPTIONS }
								onChange={ ( foldEasing ) => setAttributes( { foldEasing } ) }
								__nextHasNoMarginBottom
							/>
						</PanelBody>
					</InspectorControls>
			),

			<div { ...blockProps } key="preview">
				<div className="jet-tricks-unfold jet-tricks-unfold-editor">
					<div className={ instanceClasses.join( ' ' ) }>
						<div className="jet-unfold__inner">
							<div className={ maskClasses.join( ' ' ) } style={ maskStyle }>
								<div className="jet-unfold__content">
									<div ref={ contentInnerRef } className="jet-unfold__content-inner jet-unfold-editor-inner-blocks">
										<InnerBlocks
											template={ UNFOLD_TEMPLATE }
											templateLock={ false }
											style={ { padding: '0', width: '100%' } }
										/>
									</div>
								</div>
								{ separatorType === 'div' && (
									<div className="jet-unfold__separator" style={ separatorStyle } />
								) }
							</div>
							<div className="jet-unfold__trigger">
								<div
									className="jet-unfold__button"
									role="button"
									tabIndex={ 0 }
									onClick={ togglePreview }
								>
									<span className="jet-unfold__button-icon jet-tricks-icon">
										{ isPreviewExpanded ? foldIcon : unfoldIcon }
									</span>
									<span className="jet-unfold__button-text">
										{ isPreviewExpanded ? foldText : unfoldText }
									</span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>,
		];
	},
	save: () => <InnerBlocks.Content />
} );
