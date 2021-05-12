import React, { useContext, useEffect, useState } from 'react';
import { DataImporterContext } from './DataImporterContext';

const ImportMappingPreview = () => {
  const { importerListItems, setImporterListItems, currentView, setCurrentView } = useContext(DataImporterContext);

  const backToEditor = (e) => {
    setCurrentView('import_mapping_editor');
    e.preventDefault();
  }

  return (
    <div className="container-fluid">
      <div className="row align-items-center mt-3">
        <div className="col-3 p-0">
          <button type="button" className="btn btn-dark" onClick={(e) => backToEditor(e)}>Back</button>
        </div>
      </div>
      Preview Page
    </div>
  )
}

export default ImportMappingPreview
