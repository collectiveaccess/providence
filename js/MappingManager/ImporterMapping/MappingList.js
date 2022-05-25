import React, {useContext, useState, useEffect} from 'react'
import { MappingContext } from '../MappingContext';
import { getListMappings, editMappings, reorderMappings } from '../MappingQueries';

import { SortableContainer, SortableElement, SortableHandle } from 'react-sortable-hoc';
import { arrayMoveImmutable } from 'array-move'; //used for the react-sortable-hoc
import { animateScroll as scroll } from 'react-scroll'

var _ = require('lodash');

import MappingItem from './MappingItem';
import MappingGroup from './MappingGroup';

const appData = providenceUIApps.MappingManager.data;

const SingleMappingContainer = SortableContainer(({ items }) => {
  return (
    <div className='mapping-container'>
      {items.map((value, index) => (
        <SingleMapping key={`item-${index}`} index={index} value={value} />
      ))}
    </div>
  );
});

const SingleMapping = SortableElement(({ value }) => {
  return (<div className="row mapping m-2 p-2 border border-secondary align-items-center" style={{ padding: '5px 5px 5px 0' }}>
    {value}
  </div>)
});

const MappingList = () => {

  const { 
    importerId, setImporterId, 
    mappingListGroups, setMappingListGroups,
    mappingDataList, setMappingDataList, 
    changesMade, setChangesMade
  } = useContext(MappingContext)

  const [ orderedIds, setOrderedIds ] = useState('')

  const [groups, setGroups] = useState([])
  const [mappings, setMappings] = useState([])

  const [groupIdsList, setGroupIdsList] = useState()

  useEffect(() => {
    if (importerId) {
      getImporterMappings();
    }
  }, [importerId]); 

  const getImporterMappings = () => {
    getListMappings(appData.baseUrl + "/MetadataImport", importerId, data => {
      console.log("getListMappings: ", data);
      let tempOrderedIds = []
      let tempMappingDataList = []

      //if the mappings have more than one of the same group ids, create sub array of group
      let tempMappingListGroups = []

      if (data.mappings) {
        data.mappings.map((mapping, index) => {
          tempOrderedIds.push(mapping.id)          
          tempMappingDataList.push(
            {
              id: mapping.id,
              type: mapping.type,
              source: mapping.source,
              destination: mapping.destination,
              group: mapping.group_id,
              options: [],
              refineries: [],
              replacement_values: []
            }
          )
        })

        tempMappingListGroups = _.chain(data.mappings)
          // Group the elements of Array based on `group_id` property
          .groupBy("group_id")
          // `key` is group's name (group_id), `value` is the array of objects
          .map((value, key) => ({ group_id: key, mappings: value }))
          .value()
      }
 
      setOrderedIds(tempOrderedIds)
      setMappingDataList(tempMappingDataList)

      setMappingListGroups(tempMappingListGroups)
    })
  }

  useEffect(() => {
    if (mappingListGroups) {

      let temp_groups = []
      let temp_mappings = []

      let temp_group_ids_list = []

      mappingListGroups.forEach((group, index) => {
        temp_group_ids_list.push(group.group_id)

        let line_num = index + 1;

        if (group.mappings.length > 1) {
          let group_mappings = []          
          group.mappings.map((map, index) =>
            group_mappings.push(<MappingItem data={map} line_num={index + 1} index={index} id={map.id} getImporterMappings={getImporterMappings} />)
          )

          temp_groups.push(<MappingGroup data={group_mappings} group_id={group.group_id} getImporterMappings={getImporterMappings} />)

        } else {
          temp_mappings.push(<MappingItem data={group.mappings[0]} line_num={line_num} index={index} getImporterMappings={getImporterMappings} />)
        }
      });

      setGroups(temp_groups)
      setMappings(temp_mappings)

      setGroupIdsList(temp_group_ids_list)
    }
  }, [mappingListGroups]); 

  const addMapping = () => {
    let mappingData = [{
      type: "MAPPING",
      source: "1",	// TODO: fix
      destination: "ca_objects.preferred_labels",
      options: [],
      refineries: [],
      replacement_values: []
    }]

    editMappings(appData.baseUrl + "/MetadataImport", importerId, mappingData, data => {
      getImporterMappings();
    })

    setChangesMade(true)
    scroll.scrollToBottom();
  }

  const addGroup = () => {

    let new_group_id = (groupIdsList.slice(-1)[0]) + 1

    let mappingData = [
      {
        type: "MAPPING",
        source: "1",	// TODO: fix
        destination: "ca_objects.preferred_labels",
        group: String(new_group_id),
        options: [],
        refineries: [],
        replacement_values: []
      },
      {
        type: "MAPPING",
        source: "1",	// TODO: fix
        destination: "ca_objects.preferred_labels",
        group: String(new_group_id),
        options: [],
        refineries: [],
        replacement_values: []
      }
    ]

    editMappings(appData.baseUrl + "/MetadataImport", importerId, mappingData, data => {
      getImporterMappings();
    })

  }

  //required function for react-sortable-hoc, saves the newly drag-sorted position
  const onSortEnd = ({ oldIndex, newIndex }) => {
    let reorderedIds = arrayMoveImmutable(orderedIds, oldIndex, newIndex);
    let reorderedMappings = arrayMoveImmutable(mappings, oldIndex, newIndex);
      
    setOrderedIds(reorderedIds);
    setMappings(reorderedMappings);

    let new_order = { sorted_ids: String(reorderedIds) }

    reorderMappings(appData.baseUrl + "/MetadataImport", importerId, new_order, data => {
      console.log("reorderMappings: ", data);
    })
  };

  console.log("mappingListGroups: ", mappingListGroups);
  // console.log("groupIdsList: ", groupIdsList);
  // console.log("orderedIds: ", orderedIds);

  if(mappingListGroups){
    return (
      <>
        <div className='row d-flex justify-content-end mt-2 mb-4'>
          <button className='btn btn-secondary mr-2' onClick={() => addGroup()}>Add group +</button>
          <button className='btn btn-secondary' onClick={() => addMapping()}>Add mapping +</button>
        </div>

        <div className='mapping-list-container' style={{marginLeft: "-15px", marginRight: "-15px"}}>
          {groups.map((group, index) => {
            return group
          })}

          <SingleMappingContainer axis='y' items={mappings} onSortEnd={onSortEnd} useDragHandle disableAutoscroll={true} />
        </div>

      </>
    )
  }else{
    return "No mappings defined"
  }
}

export default MappingList