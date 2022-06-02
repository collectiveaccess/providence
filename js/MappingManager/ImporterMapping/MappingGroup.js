import React, { useContext, useEffect, useState } from 'react'
import { MappingContext } from '../MappingContext';
import { getListMappings, editMappings, reorderMappings, deleteMapping } from '../MappingQueries';
import { SortableContainer, SortableElement, SortableHandle } from 'react-sortable-hoc';
import { arrayMoveImmutable } from 'array-move'; //used for the react-sortable-hoc

const appData = providenceUIApps.MappingManager.data;

// const DragHandle = SortableHandle(() => <span style={{fontSize: "30px", cursor: "pointer"}}>::</span>);
const DragHandle = SortableHandle(() => <span style={{ fontSize: "40px", cursor: "pointer" }}>⇳</span>);

//The container holding the mapping items of a specific group
const GroupContainer = SortableContainer(({ items }) => {
  return (
    <div className='mapping-group-container'>
      {items.map((value, index) => (
        <Mapping key={`item-${index}`} index={index} value={value} />
      ))}
    </div>
  );
});

//A Mapping Item
const Mapping = SortableElement(({ value }) => {
  return (
  <div className="row mapping m-2 p-1 border border-secondary align-items-center" style={{ padding: '0px' }}>
    {value}
  </div>
  )
});

//Mapping group it self is a sortable element
const MappingGroup = SortableElement(({data, group_id, getImporterMappings}) => {

  const {importerId, setImporterId } = useContext(MappingContext)

  const [orderedIds, setOrderedIds] = useState('')
  const [groupMappings, setGroupMappings] = useState([])

  useEffect(() => {
    if(data){
      setGroupMappings(data)
      let tempOrderedIds = []

      data.forEach(element => {
        tempOrderedIds.push(element.props.id)
      });

      setOrderedIds(tempOrderedIds)
    }
  }, [data])

  // Required function for react-sortable-hoc, saves the newly drag-sorted position
  const onSortEnd = ({ oldIndex, newIndex }) => {
    let reorderedIds = arrayMoveImmutable(orderedIds, oldIndex, newIndex);
    let reorderedGroupMappings = arrayMoveImmutable(groupMappings, oldIndex, newIndex);

    setOrderedIds(reorderedIds);
    setGroupMappings(reorderedGroupMappings);

    let new_order = { sorted_ids: String(reorderedIds) }

    reorderMappings(appData.baseUrl + "/MetadataImport", importerId, new_order, data => {
      //console.log("reorderMappings: ", data);
      // TODO: report errors
    })
  };

  const addMapping = () => {
    let mappingData = [{
      type: "MAPPING",
      source: "",	// leave blank
      destination: "",
      group: "" + group_id,
      options: [],
      refineries: [],
      replacement_values: []
    }]

    editMappings(appData.baseUrl + "/MetadataImport", importerId, mappingData, data => {
      getImporterMappings()
    })

    setChangesMade(true);
  } 

  return (
    <div className='container group-container border p-0 pb-1 mb-4' style={{ boxShadow: "0px 0px 2px 2px lightgray inset"}}>
      <div className='row m-0 my-2 '>
        <div className='p-0 px-2 d-flex align-items-center'>
          <button className='btn btn-sm d-block'><DragHandle /></button>
        </div>

        <div className='' style={{width: "700px"}}>
          <div className='row m-0 d-flex align-items-center'>
            <button className='btn btn-secondary btn-sm d-block m-0 ml-2' onClick={() => addMapping()}>+ Mapping</button>
            <div className='ml-2 mt-1'><strong>Group: {group_id}</strong></div>
          </div>
          <GroupContainer axis='y' items={groupMappings} onSortEnd={onSortEnd} useDragHandle disableAutoscroll={true} />
        </div>
      </div>
    </div>
  )
});

export default MappingGroup