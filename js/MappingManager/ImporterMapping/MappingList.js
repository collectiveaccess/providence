import React, {useContext, useState, useEffect} from 'react'
import Mapping from './Mapping';
import { MappingContext } from '../MappingContext';
import { getListMappings, editMappings, reorderMappings } from '../MappingQueries';

import { SortableContainer, SortableElement, SortableHandle } from 'react-sortable-hoc';
import { arrayMoveMutable } from 'array-move'; //used for the react-sortable-hoc
// const DragHandle = SortableHandle(() => <span>::</span>);

const Item = SortableElement(({ value }) => {
  return (<div className="row mapping-item" style={{ padding: '5px 5px 5px 0' }}>
    {value}
  </div>)
});

const ItemList = SortableContainer(({ items }) => {
  return (
    <div className='mapping-list-container'>
      {items.map((value, index) => (
        <Item key={index} index={index} value={value} />
      ))}
    </div>
  );
});

const MappingList = () => {

  const { importerId, setImporterId, mappingList, setMappingList, mappingDataList, setMappingDataList} = useContext(MappingContext)
  const [orderedIds, setOrderedIds] = useState('')

  useEffect(() => {
    if (importerId) {
      getImporterMappings();
    }
  }, [importerId]); 

  const getImporterMappings = () => {
    getListMappings("http://importui.whirl-i-gig.com:8085/service.php/MetadataImport", importerId, data => {
      console.log("getListMappings: ", data);

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
              source: "2",
              destination: "ca_objects.preferred_labels",
              options: [{ name: "prefix", value: "GOT:" }],
              refineries: [{ refinery: "entitySplitter", options: [{ name: "delimiter", value: ";" }] }],
              replacement_values: [
                { original: "meow", replacement: "arf" },
                { original: "blah", replacement: "glurg" }
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
      source: "2",
      destination: "ca_objects.preferred_labels",
      options: [{ name: "prefix", value: "GOT:" }],
      refineries: [{ refinery: "entitySplitter", options: [{ name: "delimiter", value: ";" }] }],
      replacement_values: [
        { original: "meow", replacement: "arf" },
        { original: "blah", replacement: "glurg" }
      ]
    }]

    editMappings("http://importui.whirl-i-gig.com:8085/service.php/MetadataImport", importerId, mappingData, data => {
      console.log("editMappings", data);
      getImporterMappings();
    })
  } 

  //required function for react-sortable-hoc, saves the newly drag-sorted position
  const onSortEnd = ({ oldIndex, newIndex }) => {
    // console.log("onSortEnd");
    console.log('indexes', oldIndex, newIndex);

    setOrderedIds(arrayMoveMutable(orderedIds, oldIndex, newIndex))
    setMappingList(arrayMoveMutable(mappingList, oldIndex, newIndex))

    let new_order = { sorted_ids: String(orderedIds) }
    console.log("New_order:", new_order);

    reorderMappings("http://importui.whirl-i-gig.com:8085/service.php/MetadataImport", importerId, new_order, data => {
      console.log("reorderMappings: ", data);
      getImporterMappings();
    })
  };

  // console.log("orderedIds: ", orderedIds);
  // console.log("mappingList: ", mappingList);
  console.log("mappingDataList: ", mappingDataList);

  if(mappingList){
    return (
      <div>
        <ItemList axis='y' items={mappingList} onSortEnd={onSortEnd} useDragHandle />
  
        <div className='d-flex justify-content-end mt-2'>
          <button className='btn btn-secondary btn-sm' onClick={() => addMapping()}>Add mapping +</button>
        </div>
      </div>
    )
  }else{
    return "No Mappings Currently"
  }
}

export default MappingList