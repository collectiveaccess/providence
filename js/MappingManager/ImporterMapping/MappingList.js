import React, {useContext, useEffect} from 'react'
import Mapping from './Mapping';
import { MappingContext } from '../MappingContext';
import { getListMappings, editMappings } from '../MappingQueries';

import { SortableContainer, SortableElement, SortableHandle } from 'react-sortable-hoc';
import arrayMove from 'array-move'; //used for the react-sortable-hoc
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

  const { importerId, setImporterId, mappingList, setMappingList} = useContext(MappingContext)

  useEffect(() => {
    if (importerId) {
      getListMappings("http://importui.whirl-i-gig.com:8085/service.php/MetadataImport", importerId, data => {
        console.log("getListMappings: ", data);

        let tempMappingList = [...mappingList];

        if (data.mappings){
          data.mappings.map((mapping, index) => {
            let line_num = index + 1;
            tempMappingList.push(<Mapping data={mapping} line_num={line_num} />)
          })
        }

        setMappingList(tempMappingList);
      })
    }
  }, [importerId]); 


  const addMapping = () => {
    console.log("addMapping");

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

      getListMappings("http://importui.whirl-i-gig.com:8085/service.php/MetadataImport", importerId, data => {
        console.log("getListMappings: ", data);
        let tempMappingList = [];
        if (data.mappings) {
          data.mappings.map((mapping, index) => {
            let line_num = index+1;
            tempMappingList.push(<Mapping data={mapping} line_num={line_num} />)
          })
        }
        setMappingList(tempMappingList);
      })
    })
  } 

  //required function for react-sortable-hoc, saves the newly drag-sorted position
  const onSortEnd = ({ oldIndex, newIndex }) => {
    // setResultList(arrayMove(resultList, oldIndex, newIndex))
    // setResultItems(arrayMove(resultItems, oldIndex, newIndex))
  };

  return (
    <div>
      <ItemList axis='y' items={mappingList} onSortEnd={onSortEnd} useDragHandle />

      <div className='d-flex justify-content-end mt-2'>
        <button className='btn btn-secondary btn-sm' onClick={() => addMapping()}>Add mapping +</button>
      </div>
    </div>
  )
}

export default MappingList