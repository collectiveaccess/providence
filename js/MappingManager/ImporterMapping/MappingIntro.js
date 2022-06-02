import React, {useContext, useEffect, useState} from 'react'
import { MappingContext } from '../MappingContext';
import { getImportersList, addImporter, getImporterForm, editImporter, getNewImporterForm , getAvailableBundles} from '../MappingQueries';
import Form from "@rjsf/core";
import MappingSettings from './MappingSettings';

const appData = providenceUIApps.MappingManager.data;

const MappingIntro = () => {

  const { importerId, setImporterId, settingFormData, setSettingFormData, importerSchema, setImporterSchema, importerFormData, setImporterFormData, availableBundles, setAvailableBundles, changesMade, setChangesMade, settingSchema, setSettingSchema } = useContext(MappingContext)

  //adds classname to the for group elements to add styles
  const [importerUiSchema, setImporterUiSchema] = useState({
    "ca_data_importers.preferred_labels.name": {
      classNames: "d-block px-2 importer_name"
    },
    "ca_data_importers.importer_code": {
      classNames: "d-block pr-2 importer_code",
    },
    "ca_data_importers.table_num": {
      classNames: "d-block pr-2 table_num"
    }
  })


  useEffect(() => {
    var element = document.getElementById("root");
    if (element) {
      element.classList.add("row");
      element.classList.add("m-0");
    }
  }, [importerSchema, settingSchema])

  useEffect(() => {
    if(importerId){
      getImporterForm(appData.baseUrl + "/MetadataImport", importerId, data => {
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
      getNewImporterForm(appData.baseUrl + "/MetadataImport", data => {
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


  const saveFormData = (formData) => {

    let temp_data, name
    temp_data = {...formData}
    name = temp_data["ca_data_importers.preferred_labels.name"]

    //generates the importer code from the importer name that is input
    if (name) {
      let code = name.toLowerCase().replace(/[.,\/#!$%\^&\*;:{}=\-_`~()'"]/g, "").replace(/ /g, "_")
      temp_data["ca_data_importers.importer_code"] = code
    }

    setImporterFormData(temp_data)
    setChangesMade(true)
  }

  // console.log("importerSchema: ", importerSchema);
  // console.log("importerFormData: ", importerFormData);
  // console.log("importerUiSchema: ", importerUiSchema);

  return (
    <div className='row p-2 my-3 mapping-intro align-items-center'>
      {(importerSchema) ?
        <Form
          className='intro-form form-inline pl-1 pr-3'
          schema={importerSchema}
          formData={importerFormData}
          uiSchema={importerUiSchema}
          onChange={(e) => { saveFormData(e.formData) }}
        >
          <button style={{display: 'none'}} type="submit" className={"btn btn-secondary"}>"Save Changes"</button>
        </Form>
        : null
      }
      
      <div className='px-2 pt-3'>
        <MappingSettings />
      </div>

      <div className='px-2 pt-3'>
        <button className='btn btn-outline-secondary btn-sm'>Test data +</button>
      </div>

      <div className='px-2 pt-3'>
        <button className='btn btn-outline-secondary btn-sm'>Preview +</button>
      </div>

    </div>
  )
}

export default MappingIntro