import ControlsPopover from './controls-popover';

const { createContext, useState } = wp.element;

export const PopoverContainerContext = createContext( {
	closePopover: function() {},
} );

const PopoverContainer = ( { children, trigger, label } ) => {

	const [ popoverAnchor, setPopoverAnchor ] = useState( null );
	const [ showPopover, setShowPopover ] = useState( false );

	const closePopover = () => {
		setShowPopover( false );
	};

	return (
		<>
			<div
				ref={ setPopoverAnchor }
				onClick={ () => {
					setShowPopover( ! showPopover );
				} }
			>
				{ trigger }
			</div>
			<ControlsPopover
				anchor={ popoverAnchor }
				label={ label }
				isOpen={ showPopover }
				onClose={ () => {
					setShowPopover( false );
				} }
			>
				<PopoverContainerContext.Provider value={ { closePopover } }>
					{ children }
				</PopoverContainerContext.Provider>
			</ControlsPopover>
		</>
	);
}

export default PopoverContainer;
