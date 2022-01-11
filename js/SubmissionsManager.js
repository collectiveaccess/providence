import React, { useContext } from 'react';
import '../css/main.scss';
import { SubmissionsManagerContext, SubmissionsManagerContextProvider } from './SubmissionsManager/SubmissionsManagerContext';
import SubmissionManagerFilterPanel from './SubmissionsManager/SubmissionManagerFilterPanel';

import SubmissionList from './SubmissionsManager/SubmissionList';

const selector = providenceUIApps.SubmissionsManager.selector;
const appData = providenceUIApps.SubmissionsManager.data;

  
const SubmissionsManager = (props) => {

  const { viewMode } = useContext(SubmissionsManagerContext);
  const setInitialState = (e) => {
    setViewMode("submission_list");
    e.preventDefault();
  }

  if(viewMode == "submission_list"){
    return (
    <>
      <SubmissionManagerFilterPanel />
      <div className='import-list'>
        <SubmissionList />
      </div>
      </>
    )
    } else {
     return(<div className='import-list'>
       Invalid view mode
      </div>)
    }
}

/**
 * Initialize browse and render into DOM. This function is exported to allow the Pawtucket
 * app loaders to insert this application into the current view.
 */
export default function _init() {
	ReactDOM.render(
		<SubmissionsManagerContextProvider> <SubmissionsManager/> </SubmissionsManagerContextProvider> 
		,
		document.querySelector(selector));
}
