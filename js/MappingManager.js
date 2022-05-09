import React, { useContext, useEffect } from 'react';
import MappingContextProvider from './MappingManager/MappingContext';
import { MappingContext } from './MappingManager/MappingContext';

import ImporterList from './MappingManager/ImporterList';
import ImporterMapping from './MappingManager/ImporterMapping';

const selector = providenceUIApps.MappingManager.selector;
const appData = providenceUIApps.MappingManager.data;

// console.log("key: ", providenceUIApps.MappingManager.key);
  
const MappingManager = (props) => {
	const { id, currentView, setCurrentView } = useContext(MappingContext)

	if(currentView == "importers_list"){
		return( 
			<div className='import-container'>
				<ImporterList />
			</div>
		)
	}else {
		return(
			<ImporterMapping />
		)
	}
}

/**
 * Initialize browse and render into DOM. This function is exported to allow the Pawtucket
 * app loaders to insert this application into the current view.
 */
export default function _init() {
	ReactDOM.render(<MappingContextProvider><MappingManager/></MappingContextProvider>, document.querySelector(selector));
}