import React, { useContext, useEffect, useState } from 'react';
import { SubmissionsManagerContext } from './SubmissionsManagerContext';

import { getSessionFilterValues } from './SubmissionsManagerQueries';
const baseUrl = providenceUIApps.SubmissionsManager.data.baseUrl;

const SubmissionManagerFilterPanel = (props) => {
  const { filterData, setFilterData } = useContext(SubmissionsManagerContext);
  const [userList, setUserList] = useState([]);
  const [statusList, setStatusList] = useState([]);
  const [filterFormData, setFilterFormData] = useState({});

   useEffect(() => {
    getSessionFilterValues(baseUrl, function(data){
    	setUserList(data.users);
    	setStatusList(data.statuses);
    });

  }, [])
  
  
  const handleSubmit = (e) => {
  	setFilterData({...filterFormData});
  	
  	e.preventDefault();
  };
  
  const handleChange = (e) => {
  	let d = {...filterFormData};
  	d[e.target.name] = e.target.value;
  	setFilterFormData(d);
  	e.preventDefault();
  };
  
  return (
    <div>
        <div className="card" style={{ marginTop: "10px", padding: "10px" }}>
          <div className="container">
         <form className="header" onSubmit={handleSubmit}>
            <div className="row">
                <div className="col">
                      <div className="input-group">
                        <label htmlFor="username">
                          User
                        </label>
                        <select
                          onChange={handleChange}
                          name="user"
                        >
                        <option value=''>-</option>
                          {userList ? userList.map((u) => {
                            return (
                              <option value={u.user_id} key={u.user_id}>
                                {u.fname} {u.lname}
                              </option>
                            );
                          }) : ''}
                        </select>
                      </div>
                </div>
                <div className="col">
                      <div className="input-group">
                        <label
                          htmlFor="upload-status"
                          style={{ marginRight: "5px" }}
                        >
                        Status
                        </label>
                        <select
                          onChange={handleChange}
                          name="status"
                        >
                          <option value=''>-</option>
                          {statusList ? statusList.map((s) => {
                            return (
                              <option value={s} key={s}>
                                {s}
                              </option>
                            );
                          }) : ''}
                        </select>
                      </div>
                </div>
                <div className="col">
                      <div className="input-group">
                        <label htmlFor="upload-date">
                        Date
                        </label>
                        <input
                          type="text"
                          onChange={handleChange}
                          name="date"
                          placeholder="eg. August or 2020"
                        ></input>
                      </div>
                </div>
                 <div className="col mt-auto">
                      <div className="input-group">
                        <button
                          type="submit"
                          className="btn btn-primary"
                          onClick={() =>
                            handleSubmit
                          }
                        >
                          Submit
                        </button>
                      </div>
                    </div>
                </div>
            </form>
          </div>
        </div>
    </div>
  )
}

export default SubmissionManagerFilterPanel;