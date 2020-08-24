"use strict";
import React, { Component } from "react";
import "../node_modules/bootstrap/dist/css/bootstrap.min.css";
import { Tabs, Tab } from "react-bootstrap";
import Recent from "./mediauploaderadmin/RecentTab/Recent";
import Search from "./mediauploaderadmin/SearchTab/Search";

const axios = require("axios");
axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

const selector = providenceUIApps.mediauploaderadmin.selector;
const endpoint = providenceUIApps.mediauploaderadmin.endpoint;

class MediaUploaderAdmin extends Component {
  constructor(props) {
    super(props);
    this.state = {
      activeTab: "recent",
      responseData: [],
      users: [],
      isLoading: true,
      filteredData: [],
    };
    this.handleSelectedTab = this.handleSelectedTab.bind(this);
    this.handleSearchParams = this.handleSearchParams.bind(this);
  }

  componentDidMount() {
    axios
      .get(endpoint + '/logdata')
      .then((response) => {
        this.setState({
          responseData: response.data.data,
          users: response.data.userList,
          isLoading: false,
        });
        //console.log("Loaded service", this.state);
        //console.log("endpoint", endpoint);
      })
      .catch((error) => {
        if (error.response) {
          /*
           * The request was made and the server responded with a status code
           * that falls out of the range of 2xx
           */
          console.log("Response error");
        } else if (error.request) {
          /*
           * The request was made but no response was received, `error.request`
           * is an instance of XMLHttpRequest in the browser and an instance
           * of http.ClientRequest in Node.js
           */
          console.log("Request was made but no response was received");
          console.log(error.request);
        } else {
          // Something happened in setting up the request and triggered an Error
          console.log("Error", error.message);
        }

        console.log(error.config);
      });
  }

  handleSelectedTab(selectedTab) {
    // The active tab must be set into the state so that
    // the Tabs component knows about the change and re-renders.
    this.setState({
      activeTab: selectedTab,
    });
  }

  handleSearchParams(date, status, user) {
    axios.post(endpoint + '/logdata', {}, {
                params: {
                    date: date,
                    status: status,
                    user: user
                }
            }).then((response) => {
            	console.log(response);
      this.setState({
        filteredData: response.data.data,
      });
    });
  }

  render() {
    return (
      <div>
        <div className="container">
          <div className="row">
            <div className="col-sm-11">
              {this.state.isLoading === true ? (
                <h3>Loading...</h3>
              ) : (
                <Tabs
                  activeKey={this.state.activeTab}
                  onSelect={this.handleSelectedTab}
                >
                  <Tab eventKey="recent" title="Recent uploads">
                    <Recent 
                      data={this.state.responseData} 
                      endpoint={endpoint}
                	/>
                  </Tab>
                  <Tab eventKey="search" title="Search">
                    <Search
                      data={this.state.responseData}
                      handleSearchParams={this.handleSearchParams}
                      filteredData={this.state.filteredData}
                      users={this.state.users}
                      endpoint={endpoint}
                    />
                  </Tab>
                </Tabs>
              )}
            </div>
          </div>
        </div>
      </div>
    );
  }
}

ReactDOM.render(
  <MediaUploaderAdmin endpoint={endpoint} />,
  document.querySelector(selector)
);
