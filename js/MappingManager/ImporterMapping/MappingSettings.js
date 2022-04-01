import React from 'react'

const MappingSettings = () => {
  return (
    <div className='col'>
      {/* <button className='btn btn-outline-secondary btn-sm mr-2'>Settings +</button> */}

      {/* <!-- Button trigger modal --> */}
      <button type="button" className="btn btn-outline-secondary btn-sm mr-2" data-toggle="modal" data-target="#exampleModal">
        Settings +
      </button>

      {/* <!-- Modal --> */}
      <div className="modal fade" id="exampleModal" tabIndex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div className="modal-dialog modal-dialog-centered modal-dialog-scrollable">
          <div className="modal-content" style={{ maxHeight: "700px", width: "500px" }}>
            <div className="modal-header">
              <h5 className="modal-title" id="exampleModalLabel">Importer Settings</h5>
              <button type="button" className="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div className="modal-body">
              {(schema) ?
                <Form
                  schema={schema}
                  formData={formData}
                  onChange={console.log("changed")}
                  onSubmit={console.log("submitted")}
                  onError={console.log("errors")}
                >
                  <button id="form-submit-button" type="submit" className="btn btn-primary">Save changes</button>
                </Form>
                : null
              }
              {/* <button type="button" className="btn btn-secondary" data-dismiss="modal">Close</button> */}
            </div>
          </div>
        </div>
      </div>

      <button className='btn btn-outline-secondary btn-sm'>Test data +</button>
    </div>
  )
}

export default MappingSettings