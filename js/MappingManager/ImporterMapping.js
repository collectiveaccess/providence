import React, { useEffect, useContext } from 'react'
import { MappingContext } from './MappingContext';
import MappingIntro from './ImporterMapping/MappingIntro'
import MappingList from './ImporterMapping/MappingList'

import { getImportersList, deleteImporter, addImporter, editImporter, editMappings } from './MappingQueries';

const appData = providenceUIApps.MappingManager.data;

const ImporterMapping = () => {
  const { 
    currentView, setCurrentView, 
    importerId, setImporterId, 
    importerName, setImporterName, 
    importerCode, setImporterCode, 
    mappingListGroups, setMappingListGroups,
    importerFormData, setImporterFormData, 
    mappingDataList, setMappingDataList, 
    settingFormData, setSettingFormData, 
    changesMade, setChangesMade 
  } = useContext(MappingContext)

  const viewImporterList = (e) => {
    setCurrentView("importers_list")

    setImporterId()
    setImporterName()
    setImporterCode()
    setMappingListGroups([])
    setMappingDataList([])

    e.preventDefault()
  }

  const saveImporter = () => {

    let name = importerFormData["ca_data_importers.preferred_labels.name"]
    let code = importerFormData["ca_data_importers.importer_code"]
    let type = importerFormData["ca_data_importers.table_num"]

    if (importerId) {
      //if importer already exists
      editImporter(
        appData.baseUrl + "/MetadataImport", importerId, name, settingFormData.setting_inputFormats, code, "ca_objects",
        type, [{ "code": "existingRecordPolicy", "value": "skip_on_idno" }], data => {
          console.log("editImporter: ", data);

          //re-call the importers list
          getImportersList();
        }
      )
      
      //save any mapping data
      editMappings(appData.baseUrl + "/MetadataImport", importerId, mappingDataList, data => {
        console.log("editMappings", data);
      })

      setChangesMade(false)

    } else {
      //if this importer does not already exist
      addImporter(appData.baseUrl + "/MetadataImport", name, ["XLSX"], code, "ca_objects", type, [{ "code": "existingRecordPolicy", "value": "skip_on_idno" }], data => {
        console.log("addImporter: ", data);

        //save any mapping data
        editMappings(appData.baseUrl + "/MetadataImport", importerId, mappingDataList, data => {
          console.log("editMappings", data);
        })

        //re-call the importers list
        getImportersList();
      })

      setChangesMade(false)
    }
  }
  
  return (
    <div>
      <div className='row justify-content-between my-2'>
        <button className={'btn btn-secondary btn-sm d-flex inline-block' + (changesMade? " disabled" : "") } onClick={(e) => viewImporterList(e)}>
          <span className="material-icons" style={{ fontSize: "20px" }}>arrow_back</span>
          <p className='mb-0 mt-1'>Importers</p>
        </button>

        <button className={changesMade ? 'btn btn-success' : 'btn btn-secondary'} onClick={saveImporter}>Save Changes</button>
      </div>

      <MappingIntro />
      <hr></hr>
      <MappingList />
    </div>
  )
}

export default ImporterMapping