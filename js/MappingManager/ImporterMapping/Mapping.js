import React from 'react'

import { SortableHandle } from 'react-sortable-hoc';
const DragHandle = SortableHandle(() => <span>==</span>);

const Mapping = (props) => {
  return (
    <div className='row m-2 p-2 border border-secondary align-items-center'>

      <div className='mr-4'>
        <select aria-label="Default select example">
          <option defaultValue={"Mapping"}>Mapping</option>
          <option value="1">Skip</option>
          <option value="2">Constant</option>
        </select>
      </div>

      <div className='mr-4'>
        <select aria-label="Default select example">
          <option defaultValue={"Data Source"}>Data Source</option>
          <option value="1"></option>
          <option value="2"></option>
        </select>
      </div>

      <div className='mr-4'>
        <select aria-label="Default select example">
          <option defaultValue={"Target"}>Target</option>
          <option value="1"></option>
          <option value="2"></option>
        </select>
      </div> 

      <div className='mr-4'>
        <button type="button" className="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#optionsModal">
          Options +
        </button>

        <div className="modal fade" id="optionsModal" tabIndex="-1" aria-labelledby="optionsModalLabel" aria-hidden="true">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h5 className="modal-title" id="optionsModalLabel">Options for mapping</h5>
                <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div className="modal-body">
                ...
              </div>
              <div className="modal-footer">
                <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" className="btn btn-primary">Save changes</button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className='mr-4'>
          <input type="text" className="form-control" placeholder="Group" aria-label="Group" aria-describedby="a group for the mappings"/>
      </div>
      <div className='mr-4'>
        <button className='btn btn-secondary btn-sm'>Show details +</button>
      </div>
      <div className='mr-4'>
        <DragHandle />
      </div>
    </div>
  )
}

export default Mapping