import React, { useContext, useEffect, useState } from 'react';
import { DataImporterContext } from './DataImporterContext';

const ImportMappingList = () => {
  const { importerListItems, setImporterListItems, currentView, setCurrentView } = useContext(DataImporterContext);

  const newMapping = (e) => {
    setCurrentView('import_mapping_editor');
    e.preventDefault();
  }

  if (importerListItems) {
    return (
      <div className="container-fluid">
        <div className="row mt-3 justify-content-end" style={{ marginRight: '10%' }}>
          <button type="button" className="btn btn-dark" onClick={(e) => newMapping(e)}>+ New</button>
        </div>
        <div className="row justify-content-start">
          <div className="col-6">
            {importerListItems.map((item, index) => {
              return (
                <ul className='list-group list-group-flush' key={index} style={{ listStyle: 'none', border: 'none' }}>
                  <li className='list-group-item'>{item.name}</li>
                </ul>
              )
            })}
          </div>
        </div>
      </div>
    );
  } else {
    return null;
  }
}

export default ImportMappingList
