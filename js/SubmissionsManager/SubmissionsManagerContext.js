import React, { createContext, useState } from 'react';

const SubmissionsManagerContext = createContext();
const SubmissionsManagerContextProvider = (props) => {

  const [ viewMode, setViewMode ] = useState('submission_list') // values are "submission_list", "submission_detail"
  const [ sessionList, setSessionList ] = useState([]);
  const [ userList, setUserList ] = useState([]);
  const [ filterData, setFilterData] = useState({});

  return (
    <SubmissionsManagerContext.Provider 
      value={{ 
        viewMode, setViewMode,
        sessionList, setSessionList,
        userList, setUserList,
        filterData, setFilterData
    }}>
        {props.children}
    </SubmissionsManagerContext.Provider>
  )
}

export { SubmissionsManagerContextProvider, SubmissionsManagerContext }
