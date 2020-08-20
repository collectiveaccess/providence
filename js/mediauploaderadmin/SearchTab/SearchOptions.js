import React, { Component } from "react";
import UploadList from "../Upload/UploadList";
import Pagination from "./Pagination";
import "bootstrap/dist/css/bootstrap.css";

class SearchOptions extends Component {
  constructor(props) {
    super(props);
    this.state = {
      selectedUser: this.props.data[0].user.user_name,
      selectedStatus: "COMPLETED",
      selectedDate: "",
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

  //change select options handler
  handleChange(event) {
    const { name, value } = event.target;
    this.setState({ [name]: value });
  }

  //Change Page Handler for pagination
  changePageHandler = (pageNumber) => {
    this.setState({ currentPage: pageNumber });
  };

  //for pagination
  prevPageHandler(currentPage) {
    if (this.state.currentPage > 1) {
      this.setState({
        currentPage: currentPage - 1,
      });
    }
  }

  //for pagination
  nextPageHandler(currentPage, numberOfPages) {
    if (this.state.currentPage !== numberOfPages) {
      this.setState({
        currentPage: currentPage + 1,
      });
    }
  }

  render() {
    //filtered uploads list
    let filteredUploads = this.props.dateFilteredData.filter((upload) => {
      return (
        upload.user.user_name === this.state.selectedUser &&
        upload.status === this.state.selectedStatus
      );
    });

    //Get Current Uploads, for pagination
    const indexOfLastUpload = this.state.currentPage * this.state.uploadsPerPage;
    const indexOfFirstUpload = indexOfLastUpload - this.state.uploadsPerPage;
    const currentUploads = filteredUploads.slice(indexOfFirstUpload, indexOfLastUpload);

    //remove duplicates in list of users
    let uniqueUsers = [
      ...new Set(this.props.data.map((upload) => upload.user.user_name)),
    ];

    return (
      <div>
        <div className="card" style={{ marginTop: "10px", padding: "10px" }}>
          <div className="container">
            <div className="row">
              <form className="header" onSubmit={this.handleSubmit}>
                <div className="form-row" style={{ padding: "5px" }}>
                  <div className="form-group" style={{ marginRight: "10px" }}>
                    <label htmlFor="username" style={{ marginRight: "5px" }}>
                      Users
                    </label>
                    <select
                      value={this.state.selectedUser}
                      onChange={this.handleChange}
                      name="selectedUser"
                    >
                      {uniqueUsers.map((username) => {
                        return (
                          <option value={username} key={username}>
                            {username}
                          </option>
                        );
                      })}
                    </select>
                  </div>

                  <div className="form-group" style={{ marginRight: "10px" }}>
                    <label
                      htmlFor="upload-status"
                      style={{ marginRight: "5px" }}
                    >
                      Upload Status
                    </label>
                    <select
                      value={this.state.selectedStatus}
                      onChange={this.handleChange}
                      name="selectedStatus"
                    >
                      <option>COMPLETED</option>
                      <option>IN_PROGRESS</option>
                      <option>CANCELLED</option>
                    </select>
                  </div>

                  <div className="form-group">
                    <label htmlFor="upload-date" style={{ marginRight: "5px" }}>
                      Upload Date
                    </label>
                    <input
                      type="text"
                      value={this.state.selectedDate}
                      onChange={this.handleChange}
                      name="selectedDate"
                      placeholder="eg. August or 2020"
                    ></input>
                  </div>

                  <div className="form-group" style={{ marginLeft: "10px" }}>
                    <button
                      type="submit"
                      className="btn btn-outline-primary"
                      onClick={() =>
                        this.props.handleDateChange(this.state.selectedDate)
                      }
                    >
                      Submit
                    </button>
                  </div>

                </div>
              </form>
            </div>
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

        <UploadList data={currentUploads} />
      </div>
    );
  }
}

export default SearchOptions;
