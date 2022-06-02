import React, { createContext, useState } from 'react';
export const MappingContext = createContext();

const appData = providenceUIApps.MappingManager.data;

const MappingContextProvider = (props) => {

  const [currentView, setCurrentView] = useState("importers_list"); // current component view, importers_list, importer_mapping
  const [importerList, setImporterList] = useState([]) //list of importers

  const [importerId, setImporterId] = useState(null); // id of an importer mapping
  const [importerName, setImporterName] = useState(null); // name of an importer mapping
  const [importerCode, setImporterCode] = useState(null); // code for an importer mapping
  const [importType, setImportType] = useState(null); // type for an importer, ex objects etc.
  
  const [importerSchema, setImporterSchema] = useState({}) // schema for the form in the MappingIntro
  const [importerFormData, setImporterFormData] = useState({}) //formdata for the form in the Mapping Intro

  const [settingSchema, setSettingSchema] = useState({}) // schema for the importer settings form
  const [settingFormData, setSettingFormData] = useState({}) // formdata for the importer setting form
  
  const [mappingListGroups, setMappingListGroups] = useState([]) // list of groups with mappings
  const [mappingDataList, setMappingDataList] = useState([]) //array of objects containing data for a mapping
  const [changesMade, setChangesMade] = useState(false) //if there have been importer changes that need to be saved
  
  const [availableBundles, setAvailableBundles] = useState([])
  
  return (
    <MappingContext.Provider
      value={{
        importerList, setImporterList,
        importerId, setImporterId,
        importerName, setImporterName,
        importerCode, setImporterCode,
        importType, setImportType,
        currentView, setCurrentView,
        settingSchema, setSettingSchema,
        settingFormData, setSettingFormData,
        importerSchema, setImporterSchema,
        importerFormData, setImporterFormData,
        mappingDataList, setMappingDataList,
        availableBundles, setAvailableBundles,
        changesMade, setChangesMade,
        mappingListGroups, setMappingListGroups
      }}>
      {props.children}
    </MappingContext.Provider>
  )
}

export default MappingContextProvider;