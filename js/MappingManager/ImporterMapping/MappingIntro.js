import React, {useContext, useEffect, useState} from 'react'
import { MappingContext } from '../MappingContext';
import { getImportersList, addImporter, getImporterForm, editImporter, getNewImporterForm } from '../MappingQueries';
import Form from "@rjsf/core";
import MappingSettings from './MappingSettings';

const MappingIntro = () => {

  const { importerId, setImporterId, settingFormData, setSettingFormData, importerSchema, setImporterSchema, importerFormData, setImporterFormData } = useContext(MappingContext)

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


  const saveFormData = (formData) => {
    // console.log("saveFormData");
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