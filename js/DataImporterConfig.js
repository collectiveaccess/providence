import React, { useContext, useEffect, useState } from 'react';

import DataImporterContextProvider from './DataImporterContext';
import { DataImporterContext }  from './DataImporterContext';

import { getImporterList } from './DataImporterQueries';
import ImportMappingEditor from './ImportMappingEditor';
import ImportMappingList from './ImportMappingList';
import ImportMappingPreview from './ImportMappingPreview';

const selector = providenceUIApps.DataImporterConfig.selector;

const DataImporterConfig = () => {

  const { importerListItems, setImporterListItems, currentView, setCurrentView } = useContext(DataImporterContext);

  useEffect(() => {
    getImporterList('http://belkindebug.whirl-i-gig.com:8085/service.php/DataImporterConfiguration', (data) => {
      console.log('data: ', data);
      setImporterListItems(data.importers);
    })
  }, [])

  if(currentView == 'import_mapping_list'){
    return(
      <ImportMappingList />
    )
  }else if (currentView == 'import_mapping_editor'){
    return(
      <ImportMappingEditor />
    )
  }else if(currentView == "import_mapping_preview"){
    return(
      <ImportMappingPreview />
    )
  }

}

/**
 * Initialize browse and render into DOM. This function is exported to allow the Providence
 * app loaders to insert this application into the current view.
 */
export default function _init() {
  ReactDOM.render(<DataImporterContextProvider> <DataImporterConfig /> </DataImporterContextProvider>, document.querySelector(selector));
}
