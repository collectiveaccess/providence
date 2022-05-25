import React, { useContext, useEffect, useState } from 'react'
import { MappingContext } from '../MappingContext';
import { getListMappings, editMappings, reorderMappings, deleteMapping } from '../MappingQueries';

import { SortableContainer, SortableElement, SortableHandle } from 'react-sortable-hoc';

import { arrayMoveImmutable } from 'array-move'; //used for the react-sortable-hoc
import { animateScroll as scroll } from 'react-scroll'

const appData = providenceUIApps.MappingManager.data;

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

const Mapping = SortableElement(({ value }) => {
  return (
  <div className="row mapping m-2 p-2 border border-secondary align-items-center" style={{ padding: '5px 5px 5px 0' }}>
    {value}
  </div>
  )
});

const MappingGroup = ({data, group_id, getImporterMappings}) => {

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

  //required function for react-sortable-hoc, saves the newly drag-sorted position
  const onSortEnd = ({ oldIndex, newIndex }) => {
    let reorderedIds = arrayMoveImmutable(orderedIds, oldIndex, newIndex);
    let reorderedGroupMappings = arrayMoveImmutable(groupMappings, oldIndex, newIndex);

    setOrderedIds(reorderedIds);
    setGroupMappings(reorderedGroupMappings);

    let new_order = { sorted_ids: String(reorderedIds) }

    reorderMappings(appData.baseUrl + "/MetadataImport", importerId, new_order, data => {
      console.log("reorderMappings: ", data);
    })
  };

  const getMappings = () =>{
    getImporterMappings()
  }

  const addMapping = () => {
    let mappingData = [{
      type: "MAPPING",
      source: "1",	// TODO: fix
      destination: "ca_objects.preferred_labels",
      group: group_id,
      options: [],
      refineries: [],
      replacement_values: []
    }]

    editMappings(appData.baseUrl + "/MetadataImport", importerId, mappingData, data => {
      console.log("editMappings");
      getImporterMappings()
    })

    // setChangesMade(true)
    // scroll.scrollToBottom();
  } 

  // console.log("groupMappings: ", groupMappings);
  return (
    <div className='container group-container border p-0 mb-4' style={{ boxShadow: "0px 0px 2px 2px lightgray inset"}}>
      <div className='row m-0 my-3 d-flex justify-content-between'>
        <h2 className='ml-2'><strong>Group: {group_id}</strong></h2>
        <button className='btn btn-secondary btn-sm mr-2' onClick={() => addMapping()}>Add Group Mapping +</button>
      </div>
      <GroupContainer axis='y' items={groupMappings} onSortEnd={onSortEnd} useDragHandle disableAutoscroll={true} />
    </div>
  )
}

export default MappingGroup