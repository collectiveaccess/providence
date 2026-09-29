import React, { Component } from "react";
import UploadList from "../Upload/UploadList";
import Pagination from "./Pagination";
import "bootstrap/dist/css/bootstrap.css";

class SearchOptions extends Component {
  constructor(props) {
    super(props);
    this.state = {
      selectedUser: '',
      selectedStatus: '',
      selectedDate: '',
      currentPage: 1,
      uploadsPerPage: 8,
    };
    this.handleSubmit = this.handleSubmit.bind(this);
    this.handleChange = this.handleChange.bind(this);
    this.changePageHandler = this.changePageHandler.bind(this);
    this.prevPageHandler = this.prevPageHandler.bind(this);
    this.nextPageHandler = this.nextPageHandler.bind(this);
  }

  handleSubmit(event) {
    event.preventDefault();
  }

  // Change select options handler
  handleChange(event) {
    const { name, value } = event.target;
    this.setState({ [name]: value });
  }

  // Change page handler for pagination
  changePageHandler = (pageNumber) => {
    this.setState({ currentPage: pageNumber });
  };

  // for pagination
  prevPageHandler(currentPage) {
    if (this.state.currentPage > 1) {
      this.setState({
        currentPage: currentPage - 1,
      });
    }
  }

  // For pagination
  nextPageHandler(currentPage, numberOfPages) {
    if (this.state.currentPage !== numberOfPages) {
      this.setState({
        currentPage: currentPage + 1,
      });
    }
  }

  render() {
    // Filtered uploads list
    let filteredUploads = this.props.filteredData;

    // Get Current Uploads, for pagination
    const indexOfLastUpload = this.state.currentPage * this.state.uploadsPerPage;
    const indexOfFirstUpload = indexOfLastUpload - this.state.uploadsPerPage;
    const currentUploads = filteredUploads.slice(indexOfFirstUpload, indexOfLastUpload);

    return (
      <div>
        <div className="card" style={{ marginTop: "10px", padding: "10px" }}>
          <div className="container">
         <form className="header" onSubmit={this.handleSubmit}>
            <div className="row">
                <div className="col">
                      <div className="input-group">
                        <label htmlFor="username">
                          User
                        </label>
                        <select
                          value={this.state.selectedUser}
                          onChange={this.handleChange}
                          name="selectedUser"
                        >
                        <option value=''>-</option>
                          {this.props.users.map((u) => {
                            return (
                              <option value={u.user_name} key={u.user_name}>
                                {u.fname} {u.lname}
                              </option>
                            );
                          })}
                        </select>
                      </div>
                </div>
                <div className="col">
                      <div className="input-group">
                        <label
                          htmlFor="upload-status"
                          style={{ marginRight: "5px" }}
                        >
                        Status
                        </label>
                        <select
                          value={this.state.selectedStatus}
                          onChange={this.handleChange}
                          name="selectedStatus"
                        >
                          <option value=''>-</option>
                          <option value='COMPLETED'>COMPLETED</option>
                          <option value='IN_PROGRESS'>IN PROGRESS</option>
                          <option value='CANCELLED'>CANCELLED</option>
                          <option value='ERROR'>ERROR</option>
                        </select>
                      </div>
                </div>
                <div className="col">
                      <div className="input-group">
                        <label htmlFor="upload-date">
                        Date
                        </label>
                        <input
                          type="text"
                          value={this.state.selectedDate}
                          onChange={this.handleChange}
                          name="selectedDate"
                          placeholder="eg. August or 2020"
                        ></input>
                      </div>
                </div>
                 <div className="col mt-auto">
                      <div className="input-group">
                        <button
                          type="submit"
                          className="btn btn-primary"
                          onClick={() =>
                            this.props.handleSearchParams(this.state.selectedDate, this.state.selectedStatus, this.state.selectedUser)
                          }
                        >
                          Submit
                        </button>
                      </div>
                    </div>
                </div>
            </form>
          </div>
        </div>

        {filteredUploads.length === 0 ? 'Please enter search parameters' :
          <h6 style={{ marginTop: "10px" }}>
            Found {filteredUploads.length} uploads{" "}
          </h6>
        }

        <Pagination
          uploadsPerPage={this.state.uploadsPerPage}
          totalUploads={filteredUploads.length}
          paginate={this.changePageHandler}
          prevPageHandler={this.prevPageHandler}
          nextPageHandler={this.nextPageHandler}
          currentPage={this.state.currentPage}
          numberOfPages={ Math.ceil(filteredUploads.length / this.state.uploadsPerPage)}
        />

        <UploadList data={currentUploads} endpoint={this.props.endpoint} />
      </div>
    );
  }
}

export default SearchOptions;
