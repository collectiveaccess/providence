import React, {useContext, useEffect, useState} from 'react'
import EasyEdit, { Types } from 'react-easy-edit'; 
import { MappingContext } from '../MappingContext';
import { getImportersList, addImporter, getImporterForm, editImporter } from '../MappingQueries';
import Form from "@rjsf/core";

const MappingIntro = () => {

  const { importerId, setImporterId, importerName, setImporterName, importerCode, setImporterCode, importType, setImportType } = useContext(MappingContext)

  const [editMode, setEditMode] = useState(false)
  const [showErrorMessage, setShowErrorMessage] = useState(false)
  
  const [settingSchema, setSettingSchema] = useState({})
  const [settingFormData, setSettingFormData] = useState({})

  const [importerSchema, setImporterSchema] = useState({})
  const [importerFormData, setImporterFormData] = useState({})
  const [uiSchema, setUiSchema] = useState({})

  useEffect(() => {
    if(importerId){
      getImporterForm("http://importui.whirl-i-gig.com:8085/service.php/MetadataImport", importerId, data => {
        console.log("getImporterForm: ", data);
  
        let form = { ...data }
        let jsonProperties = JSON.parse(data.properties);
        form.properties = jsonProperties;

        const settings_properties = Object.keys(form.properties)
          .filter((key) => key.includes("setting"))
          .reduce((obj, key) => {
            return Object.assign(obj, {
              [key]: form.properties[key]
            });
          }, {});

        // console.log("settings_properties: ", settings_properties);

        const importer_properties = Object.keys(form.properties)
          .filter((key) => key.includes("ca_data_importers"))
          .reduce((obj, key) => {
            return Object.assign(obj, {
              [key]: form.properties[key]
            });
          }, {});

        // console.log("importer_properties: ", importer_properties);
  
        let settingSchemaObj = {
          "title": data.title,
          "required": data.required,
          "properties": settings_properties
        };

        let importerSchemaObj = {
          // "title": data.title,
          "required": data.required,
          "properties": importer_properties
        };
        
        const settings_data = Object.keys(JSON.parse(data.values))
          .filter((key) => key.includes("setting"))
          .reduce((obj, key) => {
            return Object.assign(obj, {
              [key]: JSON.parse(data.values)[key]
            });
          }, {});

        // console.log("settings_data: ", settings_data);

        const importer_data = Object.keys(JSON.parse(data.values))
          .filter((key) => key.includes("ca_data_importers"))
          .reduce((obj, key) => {
            return Object.assign(obj, {
              [key]: JSON.parse(data.values)[key]
            });
          }, {});

        // console.log("importer_data: ", importer_data);
  
        // console.log("schemaObj: ", schemaObj);
        setSettingSchema(settingSchemaObj);
        setSettingFormData(settings_data)
        
        setImporterSchema(importerSchemaObj)
        setImporterFormData(importer_data)


        var element = document.getElementById("root");
        if (element){
          element.classList.add("row");
        }

        const uiClassNames = {
          "ca_data_importers.importer_code": {
            classNames: "col"
          },
          "ca_data_importers.preferred_labels.name": {
            classNames: "col"
          },
          "ca_data_importers.table_num": {
            classNames: "col"
          }
        };
        setUiSchema(uiClassNames)


      })
    }
  }, [importerId]); // Only re-run the effect if count changes

  const saveImporter = () => {
    if(importerCode && importerName && importType != null ){
      addImporter("http://importui.whirl-i-gig.com:8085/service.php/MetadataImport", importerName, ["XLSX"], importerCode, "ca_objects", importType, [{ "code": "existingRecordPolicy", "value": "skip_on_idno" }], data => {
        console.log("addImporter: ", data);
        getImportersList();
      })
    }else{
      setShowErrorMessage(true)
    }
    setEditMode(false)
  }

  const saveSettings = () => {
    editImporter("http://importui.whirl-i-gig.com:8085/service.php/MetadataImport", importerId, importerName, formData[setting_inputFormats], importerCode, "ca_objects", importType, [{ "code": "existingRecordPolicy", "value": "skip_on_idno" }], data => {
      console.log("editImporter: ", data);
      getImportersList();
    })
  }

  const saveName = (name) => {
    console.log("saveName");
    setImporterName(name);
  }
  const saveCode = (code) => {
    console.log("saveCode");
    setImporterCode(code);
  }
  const saveType = (type) => {
    console.log("saveType");
    setImportType(type);
  }
  const toggleEditOn = () => {
    setEditMode(true);
  }

  // console.log("importerName: ", importerName);
  // console.log("importerCode: ", importerCode);
  // console.log("importType: ", importType);
  // console.log("editMode: ", editMode);

  console.log("settingSchema: ", settingSchema);
  console.log("settingFormData: ", settingFormData);

  console.log("importerSchema: ", importerSchema);
  console.log("importerFormData: ", importerFormData);


  return (
    <>
    <div className='row border border-secondary py-2'>
      
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

        {(importerSchema) ?
          <Form
            schema={importerSchema}
            formData={importerFormData}
            uiSchema={uiSchema}
          >
            <button id="form-submit-button" type="submit" className="btn btn-primary">Save changes</button>
          </Form>
          : null
        }
        

      <div className='col'>
        {/* <button className='btn btn-outline-secondary btn-sm mr-2'>Settings +</button> */}

          {/* <!-- Button trigger modal --> */}
          <button type="button" className="btn btn-outline-secondary btn-sm mr-2" data-toggle="modal" data-target="#exampleModal">
            Settings +
          </button>

          {/* <!-- Modal --> */}
          <div className="modal fade" id="exampleModal" tabIndex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div className="modal-dialog modal-dialog-centered modal-dialog-scrollable">
              <div className="modal-content" style={{ maxHeight: "700px", width: "500px" }}>
                <div className="modal-header">
                  <h5 className="modal-title" id="exampleModalLabel">Importer Settings</h5>
                  <button type="button" className="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <div className="modal-body">
                  {(settingSchema) ?
                    <Form 
                      schema={settingSchema}
                      formData={settingFormData}
                      // onChange={console.log("changed")}
                      // onSubmit={console.log("submitted")}
                      // onError={console.log("errors")}
                    >
                      <button id="form-submit-button" type="submit" className="btn btn-primary ml-auto">Save changes</button>
                    </Form>
                    : null
                  }
                </div>
              </div>
            </div>
          </div>

        <button className='btn btn-outline-secondary btn-sm'>Test data +</button>
      </div>

    </div>
    {editMode ? 
      <div>
        <button className='btn btn-outline-secondary btn-sm mt-2' onClick={saveImporter}>Save Importer Info</button>
      </div>
    : 
      <div>
        <button className='btn btn-outline-secondary btn-sm mt-2' onClick={toggleEditOn}>Edit Importer Info</button>
      </div> 
    }
    </>
  )
}

export default MappingIntro