import React, { useContext } from 'react';
import '../css/main.scss';
import { SubmissionsManagerContext, SubmissionsManagerContextProvider } from './SubmissionsManager/SubmissionsManagerContext';
import SubmissionManagerFilterPanel from './SubmissionsManager/SubmissionManagerFilterPanel';

import SubmissionList from './SubmissionsManager/SubmissionList';
import SubmissionInfo from './SubmissionsManager/SubmissionInfo';

const selector = providenceUIApps.SubmissionsManager.selector;
const appData = providenceUIApps.SubmissionsManager.data;
  
const SubmissionsManager = (props) => {

  const { viewMode, setViewMode, setSessionList, setSessionKey, filterData, setFilterData, setSessionLabel,
  setSessionSearchUrl } = useContext(SubmissionsManagerContext);

  const setInitialState = (e) => {
    setViewMode("submission_list");
    setSessionList([]);
    setSessionKey();
    // setSessionLabel();
    // setSessionSearchUrl()
    // setFilterData();
    e.preventDefault();
  }

  if(viewMode == "submission_list"){
    return (
      <>
        <SubmissionManagerFilterPanel />
        <div className='import-list mt-4'><SubmissionList /></div>
      </>
    )
  } else if (viewMode == "submission_info"){
    return (<SubmissionInfo setInitialState={(e) => setInitialState(e)} />)
  }
  else {
    return(
      <div className='import-list'>Invalid view mode</div>
    )
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
