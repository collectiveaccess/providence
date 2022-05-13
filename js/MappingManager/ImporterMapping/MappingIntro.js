import React, {useContext, useEffect, useState} from 'react'
import { MappingContext } from '../MappingContext';
import { getImportersList, addImporter, getImporterForm, editImporter, getNewImporterForm , getAvailableBundles} from '../MappingQueries';
import Form from "@rjsf/core";
import MappingSettings from './MappingSettings';

const appData = providenceUIApps.MappingManager.data;

const MappingIntro = () => {

  const { importerId, setImporterId, settingFormData, setSettingFormData, importerSchema, setImporterSchema, importerFormData, setImporterFormData, availableBundles, setAvailableBundles } = useContext(MappingContext)

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
        
        /// LOAD POSSIBLE targets (may not need this)
        // getAvailableBundles(appData.baseUrl + "/Schema", "ca_objects", data => {
//         	console.log("SET BUNDLES", data['bundles']);
//         	setAvailableBundles(data['bundles']);
//         });

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
    setImporterFormData(formData)
  }

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
            <button id="form-submit-button" style={{display: 'none'}} type="submit" className={"btn btn-secondary float-left"}>"Save Changes"</button>
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