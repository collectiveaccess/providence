import React, { useContext, useEffect, useState} from 'react'
import { MappingContext } from '../MappingContext';
import { deleteMapping, editMappings } from '../MappingQueries';

import { SortableHandle } from 'react-sortable-hoc';
const DragHandle = SortableHandle(() => <span style={{fontSize: "22px", cursor: "pointer"}}>::</span>);

const Mapping = ({ data, line_num }) => {

  const { importerId, setImporterId, mappingList, setMappingList, mappingDataList, setMappingDataList } = useContext(MappingContext)

  const [ mappingType, setMappingType ] = useState(data && data.type? data.type : null)
  const [ mappingId, setMappingId ] = useState(data && data.id ? data.id : null)

  const [mappingData, setMappingData] = useState({
    id: mappingId,
    type: mappingType,
    source: "2",
    destination: "ca_objects.preferred_labels",
    options: [{ name: "prefix", value: "GOT:" }],
    refineries: [{ refinery: "entitySplitter", options: [{ name: "delimiter", value: ";" }] }],
    replacement_values: [
      { original: "meow", replacement: "arf" },
      { original: "blah", replacement: "glurg" }
    ]
  })

  useEffect(() => {
    let tempList = [...mappingDataList]
    tempList.push(mappingData)
    setMappingDataList(tempList)
  }, [mappingType])

  const [ optionsTab, setOptionsTab ] = useState("")

  // Change select options handler
  const handleChange = (event) => {
    const { name, value } = event.target;
    if (name == "mappingType") {
      setMappingType(value)
      console.log('handleChange');
    }
  }

  const deleteThisMapping = () => {
    deleteMapping("http://importui.whirl-i-gig.com:8085/service.php/MetadataImport", importerId, mappingId, data => {
      console.log("deleteMapping: ", data);

      let tempMappingList = [...mappingList]
      tempMappingList.splice(tempMappingList.indexOf(mappingId), 1)
      setMappingList(tempMappingList)
    })
  }

  // const saveMapping = () => {
    // let mappingData = [{
    //   id: mappingId,
    //   type: mappingType,
    //   source: "2",
    //   destination: "ca_objects.preferred_labels",
    //   options: [{ name: "prefix", value: "GOT:" }],
    //   refineries: [{ refinery: "entitySplitter", options: [{ name: "delimiter", value: ";" }] }],
    //   replacement_values: [
    //     { original: "meow", replacement: "arf" },
    //     { original: "blah", replacement: "glurg" }
    //   ]
    // }]
  //   console.log("mappingData", mappingData);
  //   editMappings("http://importui.whirl-i-gig.com:8085/service.php/MetadataImport", importerId, mappingData, data => {
  //     console.log("editMappings", data);
  //   })
  // }

  const setCurrentTab = (curr_tab) => {
    setOptionsTab(curr_tab)
  }

  // console.log("mappingId", mappingId, mappingType);
  // console.log(line_num);

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
          onChange={handleChange}
        >
          <option value="MAPPING">Mapping</option>
          <option value="SKIP">Skip</option>
          <option value="CONSTANT">Constant</option>
        </select>
      </div>


      <div className='mr-3'>
        <select aria-label="Default select example">
          <option defaultValue={"Data Source"}>Data Source</option>
          <option value="1"></option>
          <option value="2"></option>
        </select>
      </div>

      <div className='mr-3'>
        <select aria-label="Default select example">
          <option defaultValue={"Target"}>Target</option>
          <option value="1"></option>
          <option value="2"></option>
        </select>
      </div> 

      <div className='mr-3'>
        <button type="button" class="btn btn-secondary btn-sm" data-toggle="modal" data-target="#exampleModal2">
          Options +
        </button>
        <div class="modal fade" id="exampleModal2" tabindex="-1" aria-labelledby="exampleModalLabel2" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel2">Options for mapping line</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <ul class="nav nav-tabs">
                  <li class="nav-item">
                    <a class={"nav-link settings-tab" + (optionsTab == "settings-tab"? "active" : null)} href="#" onClick={()=>setCurrentTab("settings-tab")}>Settings</a>
                  </li>
                  <li class="nav-item">
                    <a class={"nav-link refineries-tab" + (optionsTab == "refineries-tab" ? "active" : null)} href="#" onClick={()=>setCurrentTab("refineries-tab")}>Refineries</a>
                  </li>
                  <li class="nav-item">
                    <a class={"nav-link replacement-tab" + (optionsTab == "replacement-tab" ? "active" : null)} href="#" onClick={()=>setCurrentTab("replacement-tab")}>Replacement Values</a>
                  </li>
                </ul>
                <div className='tab-box'>

                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
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