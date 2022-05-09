import React, { useContext } from 'react'
import { MappingContext } from './MappingContext';
import MappingIntro from './ImporterMapping/MappingIntro'
import MappingList from './ImporterMapping/MappingList'

import { getImportersList, deleteImporter, addImporter, editImporter, editMappings } from './MappingQueries';

const ImporterMapping = () => {
  const { id, currentView, setCurrentView, importerId, setImporterId, importerName, setImporterName, importerCode, setImporterCode, mappingList, setMappingList, importerFormData, setImporterFormData, mappingDataList, setMappingDataList, settingFormData, setSettingFormData } = useContext(MappingContext)

  const viewImporterList = (e) => {
    setCurrentView("importers_list")

    setImporterId()
    setImporterName()
    setImporterCode()
    setMappingList([])

    e.preventDefault()
  }

  const saveImporter = () => {
    let name = importerFormData["ca_data_importers.preferred_labels.name"]
    let code = importerFormData["ca_data_importers.importer_code"]
    let type = importerFormData["ca_data_importers.table_num"]

    console.log(name, code, type);

    if (importerId) {
      editImporter(
        "http://importui.whirl-i-gig.com:8085/service.php/MetadataImport",
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
      editMappings("http://importui.whirl-i-gig.com:8085/service.php/MetadataImport", importerId, mappingDataList, data => {
        console.log("editMappings", data);
      })
    } else {
      addImporter("http://importui.whirl-i-gig.com:8085/service.php/MetadataImport",
        name,
        ["XLSX"],
        code,
        "ca_objects",
        type,
        [{ "code": "existingRecordPolicy", "value": "skip_on_idno" }],
        data => {
          console.log("addImporter: ", data);
          
          editMappings("http://importui.whirl-i-gig.com:8085/service.php/MetadataImport", importerId, mappingDataList, data => {
            console.log("editMappings", data);
          })
          getImportersList();
        })
    }
  }

  return (
    <div>
      <div className='row justify-content-start my-2'>
        <button className='btn btn-secondary btn-sm inline-block' onClick={(e) => viewImporterList(e)}>
          <span className="material-icons">arrow_back</span> 
        </button>
      </div>

      <MappingIntro />
      <div className='d-flex justify-content-end my-2'>
        <button className='btn btn-secondary btn-sm'>Preview import +</button>
      </div>
      <MappingList />
      <div className='mr-3'>
        <button className='btn btn-outline-secondary btn-sm' onClick={saveImporter}>Save</button>
      </div> 
    </div>
  )
}

export default ImporterMapping