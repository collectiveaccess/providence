import React, { createContext, useState } from 'react';
export const DataImporterContext = createContext();

const DataImporterContextProvider = (props) => {

  const [importerListItems, setImporterListItems] = useState();
  const [currentView, setCurrentView] = useState('import_mapping_list');
  const [openOptionsPanel, setOpenOptionsPanel] = useState(false);
  const [ mappingsList, setMappingsList ] = useState([]);

  
  return (
    <DataImporterContext.Provider
      value={{
        importerListItems, setImporterListItems,
        currentView, setCurrentView,
        openOptionsPanel, setOpenOptionsPanel,
        mappingsList, setMappingsList
      }}>
      {props.children}
    </DataImporterContext.Provider>
  )
}

export default DataImporterContextProvider;