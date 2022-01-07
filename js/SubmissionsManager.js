import React from 'react';
import '../css/main.scss';

const selector = providenceUIApps.SubmissionsManager.selector;
const appData = providenceUIApps.SubmissionsManager.data;

const SubmissionsManager = (props) => {
  return(
    <div>
      Message is {props.message}
    </div>
  );
}

/**
 * Initialize browse and render into DOM. This function is exported to allow the Pawtucket
 * app loaders to insert this application into the current view.
 */
export default function _init() {
	ReactDOM.render(
  		<SubmissionsManager message="meow"/> , document.querySelector(selector));
}
