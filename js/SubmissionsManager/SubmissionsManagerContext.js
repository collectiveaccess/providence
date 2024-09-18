import React, { createContext, useState } from 'react';

const SubmissionsManagerContext = createContext();
const SubmissionsManagerContextProvider = (props) => {

  const [ viewMode, setViewMode ] = useState('submission_list') // values are "submission_list", "submission_detail"
  const [ sessionList, setSessionList ] = useState([]);
  const [ userList, setUserList ] = useState([]);
  const [ filterData, setFilterData ] = useState({});
  const [ sessionKey, setSessionKey ] = useState();
  const [ sessionLabel, setSessionLabel ] = useState();
  const [ sessionSearchUrl, setSessionSearchUrl ] = useState();

  return (
    <SubmissionsManagerContext.Provider 
      value={{ 
        viewMode, setViewMode,
        sessionList, setSessionList,
        userList, setUserList,
        filterData, setFilterData,
        sessionKey, setSessionKey,
        sessionLabel, setSessionLabel,
        sessionSearchUrl, setSessionSearchUrl
      }}
    >
      {props.children}
    </SubmissionsManagerContext.Provider>
  )
}

export { SubmissionsManagerContextProvider, SubmissionsManagerContext }
