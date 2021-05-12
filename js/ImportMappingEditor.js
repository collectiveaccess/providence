import React, { useContext, useEffect, useState } from 'react';

import { DataImporterContext } from './DataImporterContext';
import ImportMappingComponent from './ImportMappingComponent';

const ImportMappingEditor = () => {
  
  const { importerListItems, setImporterListItems, setCurrentView, mappingsList, setMappingsList } = useContext(DataImporterContext);

  const backToImportList = (e) => {
    setCurrentView('import_mapping_list');
    e.preventDefault();
  }

  const addNewMapping = (e) => {
    console.log('addNewMapping');
    let tempList = [...mappingsList];
    tempList.push(<ImportMappingComponent/>)
    setMappingsList(tempList);
    e.preventDefault();
  }

  const goToPreview = (e) => {
    setCurrentView('import_mapping_preview');
    e.preventDefault();
  }


  return (
    <div className="container-fluid m-0" style={{ width: "90%" }}>

      <div className="row align-items-center mt-3">
        <div className="col-3 p-0">
          <button type="button" className="btn btn-dark" onClick={(e) => backToImportList(e)}>Back</button>
        </div>
        <div className="col-3 p-0 text-center">Mapping Name</div>
        <div className="col-3 p-0 text-center">Mapping Code</div>
        <div className="col-3 p-0 text-right">
          <button type="button" className="btn btn-dark">+ Settings</button>
        </div>
      </div>

      <ImportMappingComponent />

      {mappingsList}

      <div className="row mt-5">
        <div className="col p-0 text-left">
          <button type="button" className="btn btn-dark" onClick={(e) => addNewMapping(e)}>+ Add Mapping</button>
        </div>
      </div>
      <div className="row mt-3">
        <div className="col p-0 text-right">
          <button type="button" className="btn btn-success" onClick={(e) => goToPreview(e)} style={{ padding: "10px 20px", borderRadius: "25px" }}>Preview</button>
        </div>
      </div>

    </div>
  )
}

export default ImportMappingEditor
