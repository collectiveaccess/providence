import React, { useContext, useEffect, useState, useMemo } from 'react'
import { MappingContext } from '../MappingContext';
import { deleteMapping, editMappings } from '../MappingQueries';
import { SortableHandle } from 'react-sortable-hoc';
import debounce from 'lodash.debounce';
const DragHandle = SortableHandle(() => <span style={{fontSize: "22px", cursor: "pointer"}}>::</span>);

const appData = providenceUIApps.MappingManager.data;

const Mapping = ({ data, line_num, index }) => {

  const { importerId, setImporterId, mappingList, setMappingList, mappingDataList, setMappingDataList, availableBundles } = useContext(MappingContext)
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
    options: [
    	//{ name: "prefix", value: "GOT:" }
    ],
    refineries: [
    	//{ refinery: "entitySplitter", options: [{ name: "delimiter", value: ";" }] }
    ],
    replacement_values: [
      //{ original: "meow", replacement: "arf" },
      //{ original: "blah", replacement: "glurg" }
    ]
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
        options: [
        	//{ name: "prefix", value: "GOT:" }
        ],
        refineries: [
        	//{ refinery: "entitySplitter", options: [{ name: "delimiter", value: ";" }] }
        ],
        replacement_values: [
          //{ original: "meow", replacement: "arf" },
          //{ original: "blah", replacement: "glurg" }
        ]
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
    
    setMappingData(newData)
    let tempList = [...mappingDataList]
    tempList[index] = newData
    setMappingDataList(tempList)
  };
  
  const deleteThisMapping = () => {
    deleteMapping(appData.baseUrl + "/MetadataImport", importerId, mappingId, data => {
      let tempMappingList = [...mappingList]
      tempMappingList.splice(tempMappingList.indexOf(mappingId), 1)
      setMappingList(tempMappingList)
    })
  }
  
  const setCurrentTab = (curr_tab) => {
    setOptionsTab(curr_tab)
  }

  return (
    <div className='row m-2 p-2 border border-secondary align-items-center'>

      <div className='pr-2' style={{ borderRight: "1px solid", borderRightColor: "black" }}>
        <button type="button" className="close" aria-label="Close" onClick={deleteThisMapping}>
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div className='pl-2 mr-3'>
        <p className='mb-0'><strong>{line_num}</strong></p>
      </div>

      <div className='mr-3'>
        <select 
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


      <div className='mr-3'>
        <label className='mb-0 mr-1'>Source: </label>
        {/*<select 
          aria-label="Data Source"
          name="dataSource"
          required
          value={dataSource}
          onChange={handleMappingChange}
        >
          <option value="1">1</option>
          <option value="2">2</option>
        </select>*/}
        <input aria-label="Source" name="dataSource" size="8" defaultValue={dataSource} onChange={handleMappingChange}/>
        
      </div>

      <div className='mr-3'>
        {/* <label className='mb-0 mr-1'>Target: </label> */}
       {/* <select 
          aria-label="Target"
          name="destination"
          required
          value={destination}
          onChange={handleMappingChange}
        >
          {options}
        </select>*/}
         <input aria-label="Target" name="destination" size="20" defaultValue={destination} onChange={handleMappingChange}/>
      </div> 

      <div className='mr-3'>
        <button type="button" className="btn btn-secondary btn-sm" data-toggle="modal" data-target="#exampleModal2">
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

      <div className='mr-4'>
        <input type="text" defaultValue={group} name="group" className="form-control" size="5" placeholder="Group" aria-label="Group" aria-describedby="a group for the mapping"  onChange={handleMappingChange}/>
      </div>
      {/* <div className='mr-3'>
        <button className='btn btn-outline-secondary btn-sm' onClick={saveMapping}>Save</button>
      </div> */}
      <div className='mr-3'>
        <DragHandle />
      </div>
    </div>
  )
}

export default Mapping