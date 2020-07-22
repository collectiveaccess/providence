'use strict';
import React, { Component } from "react";
import "../node_modules/bootstrap/dist/css/bootstrap.min.css";
import { Tabs, Tab } from "react-bootstrap";
import Recent from "./components/RecentTab/Recent";
import Search from "./components/SearchTab/Search";

const axios = require('axios');
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const selector = providenceUIApps.mediauploaderadmin.selector;
const endpoint = providenceUIApps.mediauploaderadmin.endpoint;

class MediaUploaderAdmin extends Component {
  constructor(props) {
    super(props);
    this.state = {
      activeTab: "recent",
      file_data: [],
      isLoading: true,
    };
    this.handleSelectedTab = this.handleSelectedTab.bind(this);
  }

  componentDidMount() {
    const endpoint = this.props.endpoint;
    axios.get(endpoint).then((response) => {
        this.setState({
          file_data: response.data.data,
          isLoading: false,
        });
        console.log("Loaded service", this.state);
      })
      .catch((error) => {
        if (error.response) {
          /*
           * The request was made and the server responded with a status code
           * that falls out of the range of 2xx
           */
          console.log("Response error");
          console.log(error.response.data);
          console.log(error.response.status);
          console.log(error.response.headers);
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

  render() {
    console.log("Active Tab: " + this.state.activeTab);
    console.log("Data: ");
    console.log(this.state.file_data);
    return (
      <div>

        <div className="container">
          <div className="row">
          <div className="col-sm-10">
          <h1 style={{ textAlign: "center" }}>Media Uploader Admin Console</h1>

          {
            this.state.isLoading === true ?
            <h3>Loading...</h3>
            :
            <Tabs
              activeKey={this.state.activeTab}
              onSelect={this.handleSelectedTab}
            >
              <Tab eventKey="recent" title="Recent">
                <Recent data={this.state.file_data} />
              </Tab>
              <Tab eventKey="search" title="Search">
                <Search data={this.state.file_data} />
              </Tab>
            </Tabs>
          }

          </div>
          </div>


        </div>
      </div>
    );
  }
}

ReactDOM.render(<MediaUploaderAdmin endpoint={endpoint}/>, document.querySelector(selector));
