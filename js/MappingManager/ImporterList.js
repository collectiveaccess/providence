import React, { useContext, useEffect } from 'react';
import { MappingContext } from './MappingContext';
import { confirmAlert } from 'react-confirm-alert';
import 'react-confirm-alert/src/react-confirm-alert.css';
import { getImportersList, deleteImporter, addImporter, editImporter } from './MappingQueries';

const appData = providenceUIApps.MappingManager.data;

const ImporterList = () => {
  const { importerId, setImporterId, importerName, setImporterName, importerCode, setImporterCode, importerList, setImporterList, currentView, setCurrentView } = useContext(MappingContext)

  useEffect(() => {
    getImporterList()
  }, [])
  
  const getImporterList = () => {
    getImportersList(appData.baseUrl + "/MetadataImport", data => {
      console.log("getImporterList: ", data);
      setImporterList(data)
    })
  }
  const viewImporter = (e, importer) => {
    setCurrentView("importer_mappings")
    setImporterId(importer.id)
    setImporterName(importer.name)
    setImporterCode(importer.code)
    e.preventDefault()
  }

  const deleteImporterConfirm = (id, name) => {
    confirmAlert({
      customUI: ({ onClose }) => {
        return (
          <div className='info text-gray'>
            <p>Really delete <em>{name}</em>?</p>
            <div className='btn btn-secondary btn-sm mr-2' onClick={() => {
              deleteImporter(appData.baseUrl + "/MetadataImport", id, data => {
                console.log("deleteImporter: ", data);
                getImporterList();
              });
              onClose();
            }}> Yes </div>
            &nbsp;
            <div className='btn btn-secondary btn-sm' onClick={onClose}>No</div>
          </div>
        );
      }
    });
  }

  const addNewImporter = (e) => {
    setCurrentView("importer_mappings");

    setImporterId()
    setImporterName()
    setImporterCode()

    e.preventDefault()
  }

  if(importerList && importerList.length > 0){
    return (
      <div>

        <h1 className='mb-5'>Importers List</h1>

        <table className="table table-striped">
          <thead>
            <tr>
              <th scope="col">Name</th>
              <th scope="col">Code</th>
              <th scope="col"></th>
            </tr>
          </thead>
          <tbody>
            {importerList.map((importer, index) => {
              return (
                <tr className='mb-2' key={index}>
                  <td className=''>{importer.name}</td>
                  <td className=''> <strong>({importer.code})</strong></td>
                  <td><button className='btn btn-secondary btn-sm mr-2' onClick={(e) => viewImporter(e, importer)}>View</button>
                    <button className='btn btn-secondary btn-sm' onClick={() => deleteImporterConfirm(importer.id, importer.name)}>Delete</button></td>
                </tr>
              )
            })}
          </tbody>
        </table>

        <div className='row justify-content-end mt-5 mr-2'>
          <button className='btn btn-secondary btn-sm' onClick={(e) => addNewImporter(e)}>Add Importer +</button>
        </div>

      </div>
    )
  }else{
    return(
      <div>

        <h1 className='mb-5'>Importers List</h1>

        <h3>Add and importer</h3>

        <div className='row justify-content-end mt-5 mr-2'>
          <button className='btn btn-secondary btn-sm' onClick={(e) => addNewImporter(e)}>Add Importer +</button>
        </div>

      </div>
    )
  }

}

export default ImporterList