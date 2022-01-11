import React, { useContext, useState, useEffect } from 'react';
import { SubmissionsManagerContext } from './SubmissionsManagerContext';
import SubmissionListItem from './SubmissionListItem';
import { getSessionList, setSessionList } from './SubmissionsManagerQueries';

const baseUrl = providenceUIApps.SubmissionsManager.data.baseUrl;

const SubmissionList = (props) => {

  let context = useContext(SubmissionsManagerContext);
  const { sessionList, setSessionList } = useContext(SubmissionsManagerContext);
  const { filterData, setFilterData } = useContext(SubmissionsManagerContext);
  const { setViewMode } = useContext(SubmissionsManagerContext);

  const [ submitted, setSubmitted ] = useState([]);  
  const [ unsubmitted, setUnsubmitted ] = useState([]);

  useEffect(() => {
    getSessionList(baseUrl, context.filterData, function(data){
      setSessionList(data.sessions);
    });
	
  }, [filterData])

  useEffect(() => {
    let data = [...sessionList];

    const current = data.filter(sub => sub.status == 'IN_PROGRESS');
    setUnsubmitted(current);

    const submitted = data.filter(sub => sub.status !== 'IN_PROGRESS');
    setSubmitted(submitted);

  }, [sessionList])

  if(sessionList && (sessionList.length == 0)){
    return(
      <div className='container-fluid' style={{ maxWidth: '85%' }}>
        <div className='row mb-5'>
          <div className='col text-left'>
            <h1>Submissions</h1>
          </div>
          <div className='col text-right'>
          	...
          </div>
        </div>
      </div>
    )
  }else {
    return (
      <div className='container-fluid' style={{maxWidth: '85%'}}>
        {(unsubmitted && (unsubmitted.length > 0))?
          <>
            <div className='row mb-1'>
              <div className='col text-left'>
                {/* <h2>Current Imports</h2> */}
                <h2>Submissions In Progress</h2>
              </div>
            </div>

            <table className="table table-borderless mb-5">
              <thead>
                <tr>
                  <th scope="col">Label</th>
                  <th scope="col">Last Activity</th>
                  <th scope="col">Status</th>
                  <th scope="col">Files</th>
                  <th scope="col">Size</th>
                  <th scope="col">Errors/Warnings</th>
                  <th scope="col">% Done</th>
                  <th scope="col">User</th>
                  <th scope="col"> </th>
                </tr>
              </thead>
              <tbody>
                {unsubmitted.map((item, index) => {
                  return(
                    <SubmissionListItem data={item} key={index} />
                  )
                })}
              </tbody>
            </table>
          </>
        : null}

        {(submitted && (submitted.length > 0)) ? 
          <>
            <div className='row mb-1'>
              <div className='col text-left'>
                <h2>Recent Submissions</h2>
              </div>
            </div> 
            
            <table className="table table-borderless mb-5">
              <thead>
                <tr>
                  <th scope="col">Label</th>
                  <th scope="col">Last Activity</th>
                  <th scope="col">Status</th>
                  <th scope="col">Files</th>
                  <th scope="col">Size</th>
                  <th scope="col">Errors/Warnings</th>
                  <th scope="col">% Done</th>
                  <th scope="col">User</th>
                  <th scope="col"> </th>
                </tr>
              </thead>
              <tbody> 
            
                {submitted.map((item, index) => {
                  return (
                    <SubmissionListItem data={item} key={index} />
                  )
                })}
              </tbody>
            </table>
          </>
        : null}

      </div>
    )
  }
}

export default SubmissionList;