import React, { useContext, useEffect, useState } from 'react';

import { DataImporterContext } from './DataImporterContext';
import ImportMappingComponentOptions from './ImportMappingComponentOptions';

const ImportMappingComponent = () => {
  const { importerListItems, setImporterListItems, setCurrentView, openOptionsPanel, setOpenOptionsPanel, mappingsList, setMappingsList } = useContext(DataImporterContext);

  const openOptions = (e) => {
    setOpenOptionsPanel(true);
    e.preventDefault();
  }

  const deleteMapping = (e) => {
    let tempList = [...mappingsList];
    tempList.pop(<ImportMappingComponent />)
    setMappingsList(tempList);
    e.preventDefault();
  }

  return (
    <>
      <div className="row align-items-center mt-5 p-1" style={{ backgroundColor: '#f1f1f1' }}>

        <button type="button" className="close" aria-label="Close" onClick={(e) => deleteMapping(e)} style={{ padding: '3px' }}> <span aria-hidden="true">&times;</span> </button>

        <div className='col-2 mr-2'>
          <select className="custom-select">
            <option defaultValue>Type</option>
            <option value="1">Mapping</option>
            <option value="2">Constant</option>
            <option value="3">SKIP</option>
          </select>
        </div>

        <div className='col-2 mr-2'>
          <select className="custom-select">
            <option defaultValue>Source</option>
            <option value="1">One</option>
            <option value="2">Two</option>
            <option value="3">Three</option>
          </select>
        </div>

        <div className='col-2 mr-2'>
          <select className="custom-select">
            <option defaultValue>Target</option>
            <option value="1">One</option>
            <option value="2">Two</option>
            <option value="3">Three</option>
          </select>
        </div>

        <div className='col-1 mr-2'>
          <button type="button" className="btn btn-secondary btn-sm" style={{ padding: '3px' }} onClick={(e) => openOptions(e)}>+ Options</button>
        </div>

        <div className='col-1 mr-2'>
          <button type="button" className="btn btn-secondary btn-sm" style={{ padding: '3px' }}>+ Notes</button>
        </div>

      </div>

      {(openOptionsPanel)? <ImportMappingComponentOptions /> : null}
    </>
  )
}

export default ImportMappingComponent
