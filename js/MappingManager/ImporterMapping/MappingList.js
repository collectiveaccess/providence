import React, {useContext} from 'react'
import Mapping from './Mapping';
import { MappingContext } from '../MappingContext';

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

  const { importerName, setImporterName,
    importerCode, setImporterCode, mappingList, setMappingList } = useContext(MappingContext)

  const addMapping = () => {
    console.log("addMapping");
    let tempMappingList = [...mappingList];
    tempMappingList.push(<Mapping />)
    setMappingList(tempMappingList);
  } 

  //required function for react-sortable-hoc, saves the newly drag-sorted position
  const onSortEnd = ({ oldIndex, newIndex }) => {
    // setResultList(arrayMove(resultList, oldIndex, newIndex))
    // setResultItems(arrayMove(resultItems, oldIndex, newIndex))
  };

  return (
    <div>
      <ItemList axis='y' items={mappingList} onSortEnd={onSortEnd} useDragHandle />

      {/* {mappingList && mappingList.length > 0 ?
        mappingList.map((mapping, index) => {
          console.log("mapping", mapping);
          return (
            <div key={index*2+1}>
              {mapping}
            </div>
          )
        })
      : null} */}

      <div className='d-flex justify-content-end mt-2'>
        <button className='btn btn-secondary btn-sm' onClick={() => addMapping()}>Add mapping +</button>
      </div>
    </div>
  )
}

export default MappingList