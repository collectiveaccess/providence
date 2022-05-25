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

    console.log(name, code, type);

    if (importerId) {
      editImporter(
        appData.baseUrl + "/MetadataImport",
        importerId,
        name,
        settingFormData.setting_inputFormats,
        code,
        "ca_objects",
        type,
        [{ "code": "existingRecordPolicy", "value": "skip_on_idno" }],
        data => {
          console.log("editImporter: ", data);
          getImportersList();
        }
      )
      
      editMappings(appData.baseUrl + "/MetadataImport", importerId, mappingDataList, data => {
        console.log("editMappings", data);
      })

      setChangesMade(false)

    } else {
      addImporter(appData.baseUrl + "/MetadataImport", name, ["XLSX"], code, "ca_objects", type, [{ "code": "existingRecordPolicy", "value": "skip_on_idno" }], data =>
        {
          console.log("addImporter: ", data);
          
          editMappings(appData.baseUrl + "/MetadataImport", importerId, mappingDataList, data => {
            console.log("editMappings", data);
          })

          getImportersList();
        })
      setChangesMade(false)
    }
  }

  useEffect(() => {
    // console.log("state has changed");
  }, [mappingDataList, importerFormData])
  
  // console.log("importerFormData: ", importerFormData);

  return (
    <div>
      <div className='row justify-content-start my-2'>
        <button className={'btn btn-secondary btn-sm inline-block' + (changesMade? " disabled" : "") } onClick={(e) => viewImporterList(e)}>
          <span className="material-icons">arrow_back</span> 
        </button>
      </div>

      <MappingIntro />

      <div className='row my-2 d-flex'>
        <div className='col text-left p-0'>
          <button className={changesMade ? 'btn btn-success' : 'btn btn-secondary'} onClick={saveImporter}>Save Changes</button>
        </div>
        {/* <div className='col text-right p-0 ml-5'>
          <button className='btn btn-secondary'>Preview import +</button>
        </div> */}
      </div> 

      <MappingList />
    </div>
  )
}

export default ImporterMapping