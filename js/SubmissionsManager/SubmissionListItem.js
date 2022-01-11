import React, { useContext, useEffect } from 'react';
import { SubmissionsManagerContext } from './SubmissionsManagerContext';
//import { confirmAlert } from 'react-confirm-alert';
//import 'react-confirm-alert/src/react-confirm-alert.css';

import { getSessionList, deleteSubmission, updateSessionStatus } from './SubmissionsManagerQueries';
const baseUrl = providenceUIApps.SubmissionsManager.data.baseUrl;

const SubmissionListItem = (props) => {
  let context = useContext(SubmissionsManagerContext)
  const {setSessionKey, sessionKey, setSessionList, setViewMode } = useContext(SubmissionsManagerContext);

//   const deleteImportConfirm = () => {
//     deleteImport(baseUrl, props.data.sessionKey, function(data){
//       getSessionList(baseUrl, function (data) {
//         setSessionList(data.sessions);
//       });
//     })
//   }
// 
//   const deleteAlert = (e, callback) => {
//     e.preventDefault();
//     confirmAlert({
//       customUI: ({ onClose }) => {
//         return (
//           <div className='col info text-gray'>
//             <p>Would you like to delete this import?</p>
//             <div className='button' style={{ cursor: "pointer" }} onClick={() => { callback(); onClose(); }}>Yes, Delete It!</div>
// 						&nbsp;
//             <div className='button' style={{ cursor: "pointer" }} onClick={() => { onClose() }}>No</div>
//           </div>
//         );
//       }
//     });
//   }

  const viewSubmission = (e) => {
    setViewMode("submission_detail");
    e.preventDefault();
    setSessionKey(props.data.sessionKey);
  }

  let percentageDone;
  if(props.data.files >= 1){
    let total = props.data.totalBytes/1024;
    let received = props.data.receivedBytes/1024;
    percentageDone = (received/total) * 100;
  } else { 
  	percentageDone = 0;
  }
  
  const setStatus = (status) => {
  	updateSessionStatus(baseUrl, props.data.sessionKey, status, function(data){
      // force reload
      getSessionList(baseUrl, context.filterData, function(data){
		  setSessionList(data.sessions);
		});
    });
  }
  
  const accept = (e) => {
  	setStatus('ACCEPTED');
    e.preventDefault();
  }
  
  const reject = (e) => {
  	setStatus('REJECTED');
    e.preventDefault();
  }

  return (
    <>
      <tr style={{ borderTop: '1px solid lightgrey' }}>
        <td scope="row">{(props.data.status !== 'IN_PROGRESS') ?
        <>
          <a href={props.data.searchUrl}>{props.data.label}</a>
        </>
          : props.data.label}</td>
        <td>{props.data.lastActivityOn}</td>
        <td>{props.data.statusDisplay}</td>
        <td>{props.data.filesImported}/{props.data.files}</td>
        <td>{props.data.totalSize}</td>
        <td>{props.data.errors.length}/{props.data.warnings.length}</td>
        <td>{Math.ceil(percentageDone)}%</td>
        <td>{props.data.user}</td>
        {/*(props.data.status == 'IN_PROGRESS') ?
          <>
            <td><a href='#' type='button' className='btn btn-secondary btn-sm' onClick={(e) => deleteAlert(e, deleteImportConfirm)}>Delete</a></td>
          </>
          : null*/}
          {(props.data.status === 'PROCESSED') ?
        <>
          <td><a href='#' type='button' className='btn btn-secondary btn-sm' onClick={accept}>Accept</a></td>
          <td><a href='#' type='button' className='btn btn-secondary btn-sm' onClick={reject}>Reject</a></td>
        </>
          : null}
      </tr>
    </>
  )
}

export default SubmissionListItem;