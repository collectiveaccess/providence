import React, {useContext, useState, useEffect} from 'react'
import Mapping from './Mapping';
import { MappingContext } from '../MappingContext';
import { getListMappings, editMappings, reorderMappings } from '../MappingQueries';
import { SortableContainer, SortableElement, SortableHandle } from 'react-sortable-hoc';
import { arrayMoveImmutable } from 'array-move'; //used for the react-sortable-hoc

const appData = providenceUIApps.MappingManager.data;

const Item = SortableElement(({ value }) => {
  return (<div className="row mapping-item" style={{ padding: '5px 5px 5px 0' }}>
	{value}
  </div>)
});

const ItemList = SortableContainer(({ items }) => {
  return (
    <div className='mapping-list-container'>
      {items.map((value, index) => (
        <Item key={`item-${index}`} index={index} value={value} />
      ))}
    </div>
  );
});

const MappingList = () => {

  const { importerId, setImporterId, mappingList, setMappingList, mappingDataList, setMappingDataList} = useContext(MappingContext)
  const [ orderedIds, setOrderedIds ] = useState('')

  useEffect(() => {
    if (importerId) {
      getImporterMappings();
    }
  }, [importerId]); 

  const getImporterMappings = () => {
  	console.log("GET MAPPINGS FOR ", importerId);
    getListMappings(appData.baseUrl + "/MetadataImport", importerId, data => {
      let tempOrderedIds = []
      let tempMappingList = [];
      let tempMappingDataList = [];

      if (data.mappings) {
        data.mappings.map((mapping, index) => {
          let line_num = index + 1;
          tempOrderedIds.push(mapping.id)
          tempMappingList.push(<Mapping data={mapping} line_num={line_num} index={index} />)
          
          tempMappingDataList.push(
            {
              id: mapping.id,
              type: mapping.type,
              source: mapping.source,
              destination: mapping.destination,
              options: [
              //	{ name: "prefix", value: "GOT:" }
              ],
              refineries: [
              //	{ refinery: "entitySplitter", options: [{ name: "delimiter", value: ";" }] }
              ],
              replacement_values: [
              //	{ original: "meow", replacement: "arf" },
              //	{ original: "blah", replacement: "glurg" }
              ]
            }
          )
        })
      }

      setOrderedIds(tempOrderedIds)
      setMappingList(tempMappingList)
      setMappingDataList(tempMappingDataList)
    })
  }

  const addMapping = () => {
    let mappingData = [{
      type: "MAPPING",
      source: "1",	// TODO: fix
      destination: "ca_objects.preferred_labels",
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
    }]

    editMappings(appData.baseUrl + "/MetadataImport", importerId, mappingData, data => {
      getImporterMappings();
    })
  } 

  //required function for react-sortable-hoc, saves the newly drag-sorted position
  const onSortEnd = ({ oldIndex, newIndex }) => {
    console.log('indexes', oldIndex, newIndex);

	let reorderedIds = arrayMoveImmutable(orderedIds, oldIndex, newIndex);
	let reorderedMappingList = arrayMoveImmutable(mappingList, oldIndex, newIndex);
   	
    setOrderedIds(reorderedIds);
   	setMappingList(reorderedMappingList);
   	console.log("reordered", reorderedMappingList);

    let new_order = { sorted_ids: String(reorderedIds) }

    reorderMappings(appData.baseUrl + "/MetadataImport", importerId, new_order, data => {
      console.log("reorderMappings: ", data);
    })
  };

  if(mappingList){
    return (
      <div>
        <ItemList axis='y' items={mappingList} onSortEnd={onSortEnd} useDragHandle disableAutoscroll={true} />
  
        <div className='d-flex justify-content-end mt-2'>
          <button className='btn btn-secondary btn-sm' onClick={() => addMapping()}>Add mapping +</button>
        </div>
      </div>
    )
  }else{
    return "No mappings defined"
  }
}

export default MappingList