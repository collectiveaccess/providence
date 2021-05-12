import React, { useContext, useEffect, useState } from 'react';

import { DataImporterContext } from './DataImporterContext';

const ImportMappingComponentOptions = () => {

  const { importerListItems, setImporterListItems, setCurrentView, openOptionsPanel, setOpenOptionsPanel } = useContext(DataImporterContext);

  const saveOptions = (e) => {
    setOpenOptionsPanel(false);
    e.preventDefault();
  }

  return (
    <div className="row pt-2" style={{ backgroundColor: '#f1f1f1' }}>
      <ul className="nav nav-tabs">
        <li className="nav-item">
          <a className="nav-link active" href="#">Options</a>
        </li>
        <li className="nav-item">
          <a className="nav-link" href="#">Refinery</a>
        </li>
        <li className="nav-item">
          <a className="nav-link" href="#">Replacement Values</a>
        </li>
      </ul>
      <button type="button" className="btn btn-secondary btn-sm" style={{ padding: '3px' }} onClick={(e) => saveOptions(e)}>Save</button>
    </div>
  )
}

export default ImportMappingComponentOptions
