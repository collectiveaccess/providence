import React, { useContext, useEffect, useState } from 'react';
import { SubmissionsManagerContext } from './SubmissionsManagerContext';

import { getSession } from './SubmissionsManagerQueries';

const baseUrl = providenceUIApps.SubmissionsManager.data.baseUrl;

const SubmissionInfo = (props) => {
  const { sessionKey, setSessionKey, sessionList, setSessionList, sessionLabel, setSessionLabel,
    sessionSearchUrl, setSessionSearchUrl } = useContext(SubmissionsManagerContext);
  console.log("sessionKey: ", sessionKey);
  const [ previousFilesUploaded, setPreviousFilesUploaded ] = useState([]);
  const [ formData, setFormData ] = useState();
  const [ schemaProperties, setSchemaProperties ] = useState();
  const [ importErrors, setImportErrors ] = useState([]);
  const [ importWarnings, setImportWarnings ] = useState([]);
  const [ formInfo, setFormInfo ] = useState();

  useEffect(() => {
    if(sessionKey){
      getSession(baseUrl, sessionKey, data => {
        console.log("getSession: ", data);
        setSessionList(data.sessions);

        if (data.formData !== "null") {
          let prevFormData = JSON.parse(data.formData);
          // console.log('prev formData: ', prevFormData.data);
          // setFormData(Object.entries(prevFormData));

          let tempFormData = []

          for (const [key, value] of Object.entries(prevFormData.data)) {
            // console.log(`${key}: ${value}`);
            if (typeof value !== 'object' && value !== null && value !== undefined) {
              tempFormData.push(([key, value]))
            }
          }

          // console.log("tempFormData: ", tempFormData);
          setFormData(tempFormData);
        }

        if (data.formInfo !== "null") {
          let prevFormInfo = JSON.parse(data.formInfo);
          console.log('prev formInfo: ', prevFormInfo);

          let tempFormInfo = []

          for (const [key, value] of Object.entries(prevFormInfo)) {
            // console.log(`${key}: ${value}`);
            if (typeof value !== 'object' && value !== null && value !== undefined) {
              tempFormInfo.push(([key, value]))
            }
          }
          setFormInfo(tempFormInfo);
        }

        let prevFiles = []
        if (data.filesUploaded.length > 0) {
          for (let i in data.filesUploaded) {
            prevFiles.unshift(data.filesUploaded[i].name);
          }
        }

        setPreviousFilesUploaded([...prevFiles])

        setImportErrors(data.errors)
        setImportWarnings(data.warnings)
      });
    }

  }, [sessionKey])

  // let prevFiles = [];
  // if (previousFilesUploaded.length > 0) {
  //   for (let i in previousFilesUploaded) {
  //     prevFiles.unshift(previousFilesUploaded[i].name);
  //   }
  // }

  console.log("previousFilesUploaded: ", previousFilesUploaded);
  // console.log("formData: ", formData);
  console.log("formInfo: ", formInfo);


  return (
    <div className='container-fluid' style={{ maxWidth: '80%' }}>
      <button type='button' className='btn btn-light mb-4 ' onClick={(e) => props.setInitialState(e)}>
        <p> <span className="material-icons">arrow_back</span> Submissions</p>
      </button>

      <h2 className="mb-4">
        {sessionSearchUrl !== null ? 
          <a href={sessionSearchUrl}>{sessionLabel}</a>
        : sessionLabel}
      </h2>

      <h2 className="mb-2">Files Uploaded:</h2>
      {(previousFilesUploaded && previousFilesUploaded.length > 100) ?
        <div className="mt-3 overflow-auto" style={{ width: "100%", maxHeight: "200px", boxShadow: "2px 2px 2px 2px #D8D7CE" }}>
          <ul className="mb-0">
            {previousFilesUploaded.slice(0, 100).map((file, index) => {
              return <li className="mb-0" key={index}>{file}</li>
            })}
          </ul>
          <p className="p-1"><strong>and {previousFilesUploaded.length - 100} more</strong></p>
        </div>
        :
        <div className="mt-3 overflow-auto" style={{ width: "100%", maxHeight: "200px", boxShadow: "2px 2px 2px 2px #D8D7CE" }}>
          <ul className="mb-0">
            {previousFilesUploaded.map((file, index) => {
              return <li className="mb-0" key={index}>{file}</li>
            })}
          </ul>
        </div>
      }

      <div className='row mt-5 mb-2'>
        <div className='col text-left'>
          <h2>Import Summary</h2>
        </div>
      </div>

      <table className="table mb-5">
        <tbody>
          {formData? formData.map((field, index) => {
            // console.log("field", field);
            let label = field[0];
            return (
              <tr key={index}>
                <th>{label}</th>
                <td>{field[1]}</td>
              </tr>
            );
          }): null}
        </tbody>
      </table>

      {importErrors.length > 0 ?
        <div className='row mb-2'>
          <div className='col text-left'>
            <h2>Errors</h2>
          </div>
        </div>
      : null}

      {importErrors.length > 0 ?
        <table className="table mb-5">
          <tbody>
            {importErrors ? importErrors.map((file, index) => {
              // console.log("file", file);
              return (
                <tr key={index}>
                  <th>{file.filename}</th>
                  <td dangerouslySetInnerHTML={{ __html: file.message }}></td>
                </tr>
              );
            }) : null}
          </tbody>
        </table>
      : null}

      {importWarnings.length > 0 ?
        <div className='row mb-2'>
          <div className='col text-left'>
            <h2>Warnings</h2>
          </div>
        </div>
      : null}

      {importWarnings.length > 0 ?
        <table className="table mb-5">
          <tbody>
            {importWarnings ? importWarnings.map((file, index) => {
              // console.log("file", file);
              return (
                <tr key={index}>
                  <th>{file.filename}</th>
                  <td dangerouslySetInnerHTML={{ __html: file.message }}></td>
                </tr>
              );
            }) : null}
          </tbody>
        </table>
      : null}

    </div>
  )
}

export default SubmissionInfo
