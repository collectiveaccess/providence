import React, { useContext } from 'react'
import { MappingContext } from './MappingContext';
import MappingIntro from './ImporterMapping/MappingIntro'
import MappingList from './ImporterMapping/MappingList'

const ImporterMapping = () => {
  const { id, currentView, setCurrentView } = useContext(MappingContext)

  const viewImporterList = (e) => {
    setCurrentView("importers_list")
    e.preventDefault()
  }

  return (
    <div>
      <div className='row justify-content-start my-2'>
        <button className='btn btn-secondary btn-sm inline-block' onClick={(e) => viewImporterList(e)}>
          <span className="material-icons">arrow_back</span> 
          {/* <p className='m-0 align-self-center'>View Importer List</p> style={{fontSize: "18px"}} */}
        </button>
      </div>

      <MappingIntro />
      <div className='d-flex justify-content-end my-2'>
        <button className='btn btn-secondary btn-sm'>Preview import +</button>
      </div>
      <MappingList />
    </div>
  )
}

export default ImporterMapping