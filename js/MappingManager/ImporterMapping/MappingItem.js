import React, { useContext, useEffect, useState } from 'react'
import { MappingContext } from '../MappingContext';
import { deleteMapping, getDestinations } from '../MappingQueries';
import { SortableHandle } from 'react-sortable-hoc';
import debounce from 'lodash.debounce';
import ReactTooltip from 'react-tooltip';
// const DragHandle = SortableHandle(() => <span style={{fontSize: "28px", cursor: "pointer", padding: "5px"}}>::</span>);
const DragHandle = SortableHandle(() => <span style={{ fontSize: "30px", cursor: "pointer" }}>⇳</span>);
import { Typeahead } from 'react-bootstrap-typeahead';

const appData = providenceUIApps.MappingManager.data;

const MappingItem = (props) => {
  let { data, line_num, index, group_id, getImporterMappings } = props;

  const { importerId, setImporterId, mappingDataList, setMappingDataList, availableBundles, changesMade, setChangesMade } = useContext(MappingContext)

  const [ mappingId, setMappingId ] = useState(null)
  const [ mappingType, setMappingType ] = useState("")
  const [ group, setGroup ] = useState(null)
  const [ optionsTab, setOptionsTab ] = useState(null)
  
  const [ dataSource, setDataSource ] = useState(null)

  const [ destination, setDestination ] = useState('')
  const [ destinationOptions, setDestinationsOptions ] = useState([])
  const [ destinationSearchVal, setDestinationSearchVal ] = useState("")
  
  useEffect(() => {
  	console.log("set data", data);
    setDataSource(data.source);
    setMappingType(data.type);
    setMappingId(data.id);
    setDestination(data.destination);
    setGroup(data.group_id);
  }, [data]);

  useEffect(() => {
    if(importerId && destinationSearchVal.length > 0){
      getDestinations(appData.baseUrl + "/MetadataImport", importerId, destinationSearchVal, data => {
        console.log("getDestinations: ", data);
        let tempOptions = []
        if(data.matches){
          data.matches.map((option, index) =>{
            tempOptions.push(option.code)
          })
        }
        setDestinationsOptions(tempOptions)
      })
    }
  }, [importerId, destinationSearchVal]);

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
      group: "" + group,
      options: [],
      refineries: [],
      replacement_values: []
    };
    
    if (name == "dataSource") {
      setDataSource(value);
      newData['source'] = value;
    }
    if (name == "mappingType") {
      setMappingType(value);
      newData['type'] = value;
    }
    if (name == "group") {
      setGroup(value);
      newData['group'] = "" + value;
    }

    setChangesMade(true)
    
    setMappingData(newData)
    let tempList = [...mappingDataList]
    for(let idx in tempList) {
    	if(tempList[idx]['id'] == newData['id']) {
    		tempList[idx] = newData;
    		break;
    	}
    }
    setMappingDataList(tempList)
  };

  const handleTypeaheadChange = (name, value) => {
     console.log("handleTypeaheadChange name, value: ", name, value);
	
    let newData = {
      id: mappingId,
      type: mappingType,
      source: dataSource,
      destination: destination,
      group: "" + group,
      options: [],
      refineries: [],
      replacement_values: []
    };

    if (name == "destination") {
      setDestination(value);
      newData['destination'] = value[0];
    }
    
    setMappingData(newData)
    let tempList = [...mappingDataList]
    for (let idx in tempList) {
      if (tempList[idx]['id'] == newData['id']) {
        tempList[idx] = newData;
        break;
      }
    }

    setMappingDataList(tempList)
    setChangesMade(true)
  }
  
  const deleteThisMapping = () => {
    deleteMapping(appData.baseUrl + "/MetadataImport", importerId, mappingId, data => {
      let tempMappingDataList = [...mappingDataList]
      tempMappingDataList.splice(tempMappingDataList.indexOf(mappingId), 1)
      setMappingDataList(tempMappingDataList)
      getImporterMappings()
    })
  }
  
  const setCurrentTab = (curr_tab) => {
    setOptionsTab(curr_tab)
  }

  console.log("destination: ", destination);
  // console.log("dataSource: ", dataSource);

	// let selectedDataSource = dataSource;
	// if(selectedDataSource && !Array.isArray(selectedDataSource)) { selectedDataSource = [selectedDataSource]; }

	let selectedDestination = []
  if(destination){ selectedDestination = destination }
	if(selectedDestination && !Array.isArray(selectedDestination)) { selectedDestination = [selectedDestination]; }
  console.log("selectedDestination", selectedDestination);
  
  return (
    <>
      <div className='d-flex d-inline px-3'>
        <div className='pr-1'>
          <button type="button" className="close border border-dark rounded px-1" aria-label="Close" onClick={deleteThisMapping}>
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div className='pl-1' style={{width: "8px"}}>
          <strong>{line_num}</strong>
        </div>
      </div>

      <div className='px-2'>
        <select 
          style={{width: "77px", height: "22px"}}
          className='d-block'
          aria-label="mapping type" 
          name="mappingType" 
          required 
          value={mappingType} 
          onChange={handleMappingChange}
          multiple={false}
        >
          <option value="MAPPING">Mapping</option>
          <option value="CONSTANT">Constant</option>
          <option value="SKIP">Skip</option>
        </select>
      </div>

      <div className='px-2'>
        <input className='d-block' aria-label="Source" name="dataSource" size="20" defaultValue={dataSource} placeholder="Source" data-tip="Data source" onChange={handleMappingChange} style={{width: "175px"}}/>
      </div>

      <div className='px-2'>
        <Typeahead
          id={`${mappingId}`}
          onChange={(selected) => handleTypeaheadChange("destination", selected)}
          onInputChange={(text) => setDestinationSearchVal(text) }
          options={destinationOptions}
          selected={selectedDestination}
          placeholder={"Target"}
          size={"lg"}
        />
      </div> 

      <div className='px-3'>
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
      
      <div className='text-right'>
        <DragHandle />
      </div>	
      {/* <ReactTooltip delayShow="1000" type="info" effect="solid"/> */}
    </>
  )
}

export default MappingItem