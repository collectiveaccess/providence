import React, { useContext, useEffect } from 'react';

const selector = providenceUIApps.DataImporterConfig.selector;

const DataImporterConfig = () => {
	return (<h2>Hello world; data importer configuration ui goes here</h2>);
}

/**
 * Initialize browse and render into DOM. This function is exported to allow the Providence
 * app loaders to insert this application into the current view.
 */
export default function _init() {
  ReactDOM.render(<DataImporterConfig />, document.querySelector(selector));
}
