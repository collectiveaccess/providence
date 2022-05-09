import React, {useContext, useEffect, useState} from 'react'
import { MappingContext } from '../MappingContext';
import { getImportersList, addImporter, getImporterForm, editImporter, getNewImporterForm } from '../MappingQueries';
import Form from "@rjsf/core";
import MappingSettings from './MappingSettings';

const MappingIntro = () => {

  const { importerId, setImporterId, settingFormData, setSettingFormData, importerSchema, setImporterSchema, importerFormData, setImporterFormData } = useContext(MappingContext)

  const [ isSaved, setIsSaved ] = useState(false)
  const [ saveMessage, setSaveMessage ] = useState()

  const [importerUiSchema, setImporterUiSchema] = useState({
    "ca_data_importers.importer_code": {
      classNames: "col"
    },
    "ca_data_importers.preferred_labels.name": {
      classNames: "col"
    },
    "ca_data_importers.table_num": {
      classNames: "col"
    }
  })

  var element = document.getElementById("root");
  if (element) {
    element.classList.add("row");
  }

  useEffect(() => {
    if(importerId){
      getImporterForm("http://importui.whirl-i-gig.com:8085/service.php/MetadataImport", importerId, data => {
        console.log("getImporterForm: ", data);
  
        let form = { ...data }
        let jsonProperties = JSON.parse(data.properties);
        form.properties = jsonProperties;

        const importer_properties = Object.keys(form.properties)
          .filter((key) => key.includes("ca_data_importers"))
          .reduce((obj, key) => {
            return Object.assign(obj, {
              [key]: form.properties[key]
            });
          }, {});

        let importerSchemaObj = {
          "required": data.required,
          "properties": importer_properties
        };

        const importer_data = Object.keys(JSON.parse(data.values))
          .filter((key) => key.includes("ca_data_importers"))
          .reduce((obj, key) => {
            return Object.assign(obj, {
              [key]: JSON.parse(data.values)[key]
            });
          }, {});

        setImporterSchema(importerSchemaObj)
        setImporterFormData(importer_data)
      })
    }else{
      getNewImporterForm("http://importui.whirl-i-gig.com:8085/service.php/MetadataImport", data => {
        console.log("getNewImporterForm: ", data);

        let form = { ...data }
        let jsonProperties = JSON.parse(data.properties);
        form.properties = jsonProperties;

        const importer_properties = Object.keys(form.properties)
          .filter((key) => key.includes("ca_data_importers"))
          .reduce((obj, key) => {
            return Object.assign(obj, {
              [key]: form.properties[key]
            });
          }, {});

        let importerSchemaObj = {
          "required": data.required,
          "properties": importer_properties
        };

        const importer_data = Object.keys(JSON.parse(data.values))
          .filter((key) => key.includes("ca_data_importers"))
          .reduce((obj, key) => {
            return Object.assign(obj, {
              [key]: JSON.parse(data.values)[key]
            });
          }, {});

        setImporterSchema(importerSchemaObj)
        setImporterFormData(importer_data)
      })
    }
  }, [importerId]); 

  // const saveImporter = () => {
  //   let name = importerFormData["ca_data_importers.preferred_labels.name"]
  //   let code = importerFormData["ca_data_importers.importer_code"]
  //   let type = importerFormData["ca_data_importers.table_num"]

  //   console.log(name, code, type);

  //   if(importerId){
  //     editImporter(
  //       "http://importui.whirl-i-gig.com:8085/service.php/MetadataImport", 
  //       importerId, 
  //       name, 
  //       settingFormData.setting_inputFormats, 
  //       code, 
  //       "ca_objects", 
  //       type,
  //       [{ "code": "existingRecordPolicy", "value": "skip_on_idno" }], 
  //       data => {
  //         console.log("editImporter: ", data);
  //         setIsSaved(true);
  //         setTimeout(function () {
  //           setIsSaved(false);
  //         }, 3000);
  //         getImportersList();
  //       }
  //     )
  //   }else{
  //     addImporter("http://importui.whirl-i-gig.com:8085/service.php/MetadataImport", 
  //     name, 
  //     ["XLSX"], 
  //     code, 
  //     "ca_objects", 
  //     type, 
  //     [{ "code": "existingRecordPolicy", "value": "skip_on_idno" }],
  //      data => {
  //       console.log("addImporter: ", data);
  //        setIsSaved(true);
  //        setTimeout(function () {
  //          setIsSaved(false);
  //        }, 3000);
  //       getImportersList();
  //     })
  //   }
  // }

  const saveFormData = (formData) => {
    console.log("saveFormData");
    setImporterFormData(formData)
  }

  // console.log("importerSchema: ", importerSchema);
  // console.log("importerFormData: ", importerFormData);

  return (
    <>
    <div className='row border border-secondary py-2 mapping-intro'>

        {(importerSchema) ?
          <Form
            schema={importerSchema}
            formData={importerFormData}
            uiSchema={importerUiSchema}
            onChange={(e) => { saveFormData(e.formData) }}
          >
            {/* <button id="form-submit-button" type="submit" className={isSaved ? "btn btn-success float-left" : "btn btn-secondary float-left"} onClick={() => saveImporter()}>{isSaved? "Saved" : "Save Changes"}</button> */}
          </Form>
          : null
        }
        
        <div className='col p-0 text-right'>
          <MappingSettings />
        </div>
        <div className='col p-0'>
          <button className='btn btn-outline-secondary'>Test data +</button>
        </div>

    </div>
    </>
  )
}

export default MappingIntro


{/* <div className='col'>
        <EasyEdit
          type={Types.TEXT}
          onSave={saveName}
          saveButtonLabel="Save"
          saveButtonStyle="btn btn-secondary btn-sm"
          cancelButtonLabel="Cancel"
          cancelButtonStyle="btn btn-secondary btn-sm"
          attributes={{ name: "name" }}
          placeholder={"MAPPING NAME"}
          value={importerName ? importerName : null}

          allowEdit={editMode}
          hideSaveButton={true}
          hideCancelButton={true}
          saveOnBlur
        />
      </div> */}

{/* <div className='col'>
        <EasyEdit
          type={Types.TEXT}
          onSave={saveCode}
          saveButtonLabel="Save"
          saveButtonStyle="btn btn-secondary btn-sm"
          cancelButtonLabel="Cancel"
          cancelButtonStyle="btn btn-secondary btn-sm"
          attributes={{ code: "code" }}
          placeholder={"[CODE]"}
          value={importerCode ? importerCode : null}

          allowEdit={editMode}
          hideSaveButton={true}
          hideCancelButton={true}
          saveOnBlur
        />
      </div> */}

{/* <div className='col'> 
        <EasyEdit
          type={Types.TEXT}
          onSave={saveType}
          saveButtonLabel="Save"
          saveButtonStyle="btn btn-secondary btn-sm"
          cancelButtonLabel="Cancel"
          cancelButtonStyle="btn btn-secondary btn-sm"
          attributes={{ type: "type" }}
          placeholder={"TYPE"}
          value={importType ? importType : null}
          onChange={saveType}

          allowEdit={editMode}
          hideSaveButton={true}
          hideCancelButton={true}
          saveOnBlur
        />
      </div> */}

{/* {editMode ? 
      <div>
        <button className='btn btn-outline-secondary btn-sm mt-2' onClick={saveImporter}>Save Importer Info</button>
      </div>
    : 
      <div>
        <button className='btn btn-outline-secondary btn-sm mt-2' onClick={toggleEditOn}>Edit Importer Info</button>
      </div> 
    } */}


  // const saveName = (name) => {
  //   console.log("saveName");
  //   setImporterName(name);
  // }
  // const saveCode = (code) => {
  //   console.log("saveCode");
  //   setImporterCode(code);
  // }
  // const saveType = (type) => {
  //   console.log("saveType");
  //   setImportType(type);
  // }
  // const toggleEditOn = () => {
  //   setEditMode(true);
  // }