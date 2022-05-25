import React, { useContext, useEffect, useState } from 'react'
import { MappingContext } from '../MappingContext';
import { deleteMapping } from '../MappingQueries';

import { SortableHandle } from 'react-sortable-hoc';
import debounce from 'lodash.debounce';
const DragHandle = SortableHandle(() => <span style={{fontSize: "26px", cursor: "pointer", padding: "5px"}}>::</span>);

const appData = providenceUIApps.MappingManager.data;

const MappingItem = ({ data, line_num, index, group_id, getImporterMappings }) => {

  const { importerId, setImporterId, mappingDataList, setMappingDataList, availableBundles, changesMade, setChangesMade } = useContext(MappingContext)
  const [ mappingId, setMappingId ] = useState(null)
  const [ mappingType, setMappingType ] = useState("")
  const [ dataSource, setDataSource ] = useState(null)
  const [ group, setGroup ] = useState(null)
  const [ destination, setDestination ] = useState(null)
  const [ optionsTab, setOptionsTab ] = useState(null)
  
  useEffect(() => {
    setDataSource(data.source);
    setMappingType(data.type);
    setMappingId(data.id);
    setDestination(data.destination);
    setGroup("" + data.group_id);
  }, [data]);


  const [ mappingData, setMappingData ] = useState({
    id: mappingId,
    type: mappingType,
    source: dataSource,
    destination: destination,
    group: group,
    options: [],
    refineries: [],
    replacement_values: []
  })
  
  // Change select options handler
  const handleMappingChange = (event) => {
    const { name, value } = event.target;
    let newData = {
        id: mappingId,
        type: mappingType,
        source: dataSource,
        destination: destination,
        group: group,
        options: [],
        refineries: [],
        replacement_values: []
      };
      
    if (name == "mappingType") {
      setMappingType(value);
      newData['type'] = value;
    }
    if (name == "dataSource") {
      setDataSource(value);
      newData['source'] = value;
    }
    if (name == "destination") {
      setDestination(value);
      newData['destination'] = value;
    }
    
    if (name == "group") {
      setGroup(value);
      newData['group'] = "" + value;
    }

    setChangesMade(true)
    
    setMappingData(newData)
    let tempList = [...mappingDataList]
    tempList[index] = newData
    setMappingDataList(tempList)
  };
  
  const deleteThisMapping = () => {
    deleteMapping(appData.baseUrl + "/MetadataImport", importerId, mappingId, data => {
      console.log("deleteMapping", data);
      let tempMappingDataList = [...mappingDataList]
      tempMappingDataList.splice(tempMappingDataList.indexOf(mappingId), 1)
      setMappingDataList(tempMappingDataList)
      getImporterMappings()
    })
  }
  
  const setCurrentTab = (curr_tab) => {
    setOptionsTab(curr_tab)
  }

  return (
    <>
      <div className='d-flex d-inline px-3'>
        <div className='pr-2'>
          <button type="button" className="close border border-dark rounded px-1" aria-label="Close" onClick={deleteThisMapping}>
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div className='pl-2'>
          <strong>{line_num}</strong>
        </div>
      </div>


      <div className='px-4'>
        <label className='mb-0 d-block'>Mapping Type: </label>
        <select 
          style={{width: "77px", height: "22px"}}
          className='d-block'
          aria-label="mapping type" 
          name="mappingType" 
          required 
          value={mappingType} 
          onChange={handleMappingChange}
        >
          <option value="MAPPING">Mapping</option>
          <option value="SKIP">Skip</option>
          <option value="CONSTANT">Constant</option>
        </select>
      </div>


      <div className='px-4'>
        <label className='mb-0 d-block'>Source: </label>
        <input className='d-block' aria-label="Source" name="dataSource" size="20" defaultValue={dataSource} onChange={handleMappingChange}/>
      </div>


      <div className='px-4'>
        <label className='mb-0 d-block'>Target: </label>
        <input className='d-block' aria-label="Target" name="destination" size="30" defaultValue={destination} onChange={handleMappingChange}/>
      </div> 


      <div className='px-3 pt-4'>
        <button type="button" style={{width: "50px"}} className="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#exampleModal2">
          Options +
        </button>
        <div className="modal fade" id="exampleModal2" tabIndex="-1" aria-labelledby="exampleModalLabel2" aria-hidden="true">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h5 className="modal-title" id="exampleModalLabel2">Options for mapping line</h5>
                <button type="button" className="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div className="modal-body">
                <ul className="nav nav-tabs">
                  <li className="nav-item">
                    <a className={"nav-link settings-tab" + (optionsTab == "settings-tab"? "active" : null)} href="#" onClick={()=>setCurrentTab("settings-tab")}>Settings</a>
                  </li>
                  <li className="nav-item">
                    <a className={"nav-link refineries-tab" + (optionsTab == "refineries-tab" ? "active" : null)} href="#" onClick={()=>setCurrentTab("refineries-tab")}>Refineries</a>
                  </li>
                  <li className="nav-item">
                    <a className={"nav-link replacement-tab" + (optionsTab == "replacement-tab" ? "active" : null)} href="#" onClick={()=>setCurrentTab("replacement-tab")}>Replacement Values</a>
                  </li>
                </ul>
                <div className='tab-box'>
                </div>
              </div>
              <div className="modal-footer">
                <button type="button" className="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" className="btn btn-primary">Save changes</button>
              </div>
            </div>
          </div>
        </div>
      </div>


      {/* <div className='col'>
        <input type="text" defaultValue={group} name="group" className="form-control" size="5" placeholder="Group" aria-label="Group" aria-describedby="a group for the mapping"  onChange={handleMappingChange}/>
      </div> */}


      <div className='p-0 pl-4'>
        <DragHandle />
      </div>
    </>
  )
}

export default MappingItem