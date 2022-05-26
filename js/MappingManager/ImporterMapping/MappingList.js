import React, {useContext, useState, useEffect} from 'react'
import { MappingContext } from '../MappingContext';
import { getListMappings, editMappings, reorderMappings, reorderGroups } from '../MappingQueries';

import { SortableContainer, SortableElement, SortableHandle } from 'react-sortable-hoc';
import { arrayMoveImmutable } from 'array-move'; //used for the react-sortable-hoc
import { animateScroll as scroll } from 'react-scroll'
import MappingItem from './MappingItem';
import MappingGroup from './MappingGroup';

var _ = require('lodash');


const appData = providenceUIApps.MappingManager.data;

// The container holding the mapping items of a specific group
const GroupListContainer = SortableContainer(({ items }) => {
  return (
      <div className='group-list-container' style={{marginLeft: "-15px", marginRight: "-15px"}}>
          {items.map((group, index) => {
            return group
          })}
		</div>
  );
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
  
  useEffect(() => {
    if (importerId) {
      getImporterMappings();
    }
  }, [importerId]); 

  const getImporterMappings = () => {
    getListMappings(appData.baseUrl + "/MetadataImport", importerId, data => {
      let tempOrderedIds = []
      let tempMappingDataList = []

      // If the mappings have more than one of the same group ids, create sub array of group
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
              group: "" + mapping.group_id,
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
          .map((value, key) => ({ group_id: parseInt(key), mappings: value }))
          .value()
          
        let x = [];
        let seen = {};
        for(let i in data.mappings) {
        	if(seen[data.mappings[i]['group_id']]) { continue; }
        	for(let j in tempMappingListGroups) {
        		if(tempMappingListGroups[j]['group_id'] == data.mappings[i]['group_id']) {
        			x.push(tempMappingListGroups[j]);
        			seen[data.mappings[i]['group_id']] = true;
        		}
        	}	
        }
        tempMappingListGroups = x;
      }
 
      setOrderedIds(tempOrderedIds)
      setMappingDataList(tempMappingDataList)

      setMappingListGroups(tempMappingListGroups)
    })
  }

  useEffect(() => {
    if (mappingListGroups) {

      let temp_groups = []

      let temp_group_ids_list = []

      mappingListGroups.forEach((group, index) => {
		temp_group_ids_list.push(group.group_id)

		let line_num = index + 1;

		let group_mappings = []          
			group.mappings.map((map, index) =>
			group_mappings.push(<MappingItem data={map} line_num={index + 1} index={index} id={map.id} getImporterMappings={getImporterMappings} />)
		)

		temp_groups.push(<MappingGroup data={group_mappings} index={index} group_id={group.group_id} getImporterMappings={getImporterMappings} />)

      });

      setGroups(temp_groups)
    }
  }, [mappingListGroups]); 

  const addGroup = () => {
    let mappingData = [
      {
        type: "MAPPING",
        source: "",	// leave empty for user to specify
        destination: "",
        group: null,
        options: [],
        refineries: [],
        replacement_values: []
      }
    ]

    editMappings(appData.baseUrl + "/MetadataImport", importerId, mappingData, data => {
      getImporterMappings();
    })

    scroll.scrollToBottom();
  }

  //required function for react-sortable-hoc, saves the newly drag-sorted position
  const onSortEnd = ({ oldIndex, newIndex }) => {
    let reorderedGroups = arrayMoveImmutable(groups, oldIndex, newIndex);
      
    setGroups(reorderedGroups);
    
    let reorderedIds = reorderedGroups.map((group, index) => {
    	return group.props.group_id
    });

    let new_order = { sorted_ids: String(reorderedIds) }

    reorderGroups(appData.baseUrl + "/MetadataImport", importerId, new_order, data => {
      //console.log("reorderGroups: ", data);
      // TODO: report errors
    })
  };

  if(mappingListGroups){
    return (
    <>
    	<div className='row d-flex justify-content-end mt-2 mb-4 h-25'>
          <button className='btn btn-secondary mr-2' onClick={() => addGroup()}>+ Group</button>
        </div>
      <GroupListContainer axis='y' items={groups} onSortEnd={onSortEnd} useDragHandle disableAutoscroll={true}/>
      </>
    )
  }else{
    return "No mappings defined"
  }
}
export default MappingList