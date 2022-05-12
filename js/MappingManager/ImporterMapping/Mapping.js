import React, { useContext, useEffect, useState} from 'react'
import { MappingContext } from '../MappingContext';
import { deleteMapping, editMappings } from '../MappingQueries';
import { SortableHandle } from 'react-sortable-hoc';
const DragHandle = SortableHandle(() => <span style={{fontSize: "22px", cursor: "pointer"}}>::</span>);

const appData = providenceUIApps.MappingManager.data;

const Mapping = ({ data, line_num, index }) => {

  const { importerId, setImporterId, mappingList, setMappingList, mappingDataList, setMappingDataList } = useContext(MappingContext)

  const [ mappingId, setMappingId ] = useState(data && data.id ? data.id : null)
  const [ mappingType, setMappingType ] = useState(data && data.type? data.type : null)
  const [ dataSource, setDataSource ] = useState(data && data.source? data.source : null)
  const [ target, setTarget ] = useState(data && data.destination? data.destination : null)

  const [ optionsTab, setOptionsTab ] = useState("")

  const [ mappingData, setMappingData ] = useState({
    id: mappingId,
    type: mappingType,
    source: dataSource,
    destination: target,
    options: [{ name: "prefix", value: "GOT:" }],
    refineries: [{ refinery: "entitySplitter", options: [{ name: "delimiter", value: ";" }] }],
    replacement_values: [
      { original: "meow", replacement: "arf" },
      { original: "blah", replacement: "glurg" }
    ]
  })
  
  // Change select options handler
  const handleMappingChange = (event) => {
    const { name, value } = event.target;
    if (name == "mappingType") {
      setMappingType(value)
      setMappingData({
        id: mappingId,
        type: value,
        source: dataSource,
        destination: target,
        options: [{ name: "prefix", value: "GOT:" }],
        refineries: [{ refinery: "entitySplitter", options: [{ name: "delimiter", value: ";" }] }],
        replacement_values: [
          { original: "meow", replacement: "arf" },
          { original: "blah", replacement: "glurg" }
        ]
      })

    }
    if (name == "dataSource") {
      setDataSource(value)
    }
    if (name == "target") {
      setTarget(value)
    }

    let tempList = [...mappingDataList]
    // tempList.splice(index, 1, mappingData)
    tempList[index] = mappingData
    console.log("tempList: ", tempList);
    setMappingDataList(tempList)
  }

  // useEffect(() => {
  //   console.log("useeffect triggered");
  //   let tempList = [...mappingDataList]
  //   tempList[index] == mappingData
  //   setMappingDataList(tempList)
  // }, [mappingType, dataSource, target])
  
  const deleteThisMapping = () => {
    deleteMapping(appData.baseUrl + "/MetadataImport", importerId, mappingId, data => {
      console.log("deleteMapping: ", data);
      
      let tempMappingList = [...mappingList]
      tempMappingList.splice(tempMappingList.indexOf(mappingId), 1)
      setMappingList(tempMappingList)
    })
  }
  
  const setCurrentTab = (curr_tab) => {
    setOptionsTab(curr_tab)
  }
  
  console.log("mappingId, type ", mappingId, mappingType, dataSource, target);
  console.log("mappingData: ", mappingData);

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
        <label className='mb-0 mr-1'>Data Source: </label>
        <select 
          aria-label="Data Source"
          name="dataSource"
          required
          value={dataSource}
          onChange={handleMappingChange}
        >
          <option value="1">1</option>
          <option value="2">2</option>
        </select>
      </div>

      <div className='mr-3'>
        {/* <label className='mb-0 mr-1'>Target: </label> */}
        <select 
          aria-label="Target"
          name="target"
          required
          value={target}
          onChange={handleMappingChange}
        >
          <option value={target}>{target}</option>
        </select>
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
        <input type="text" className="form-control" placeholder="Group" aria-label="Group" aria-describedby="a group for the mappings"/>
      </div>
      {/* <div className='mr-3'>
        <button className='btn btn-secondary btn-sm'>Show details +</button>
      </div> */}
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