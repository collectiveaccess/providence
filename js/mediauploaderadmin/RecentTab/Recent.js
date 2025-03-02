import React, { Component } from "react";
import UploadList from "../Upload/UploadList";

class Recent extends Component {
  constructor(props) {
    super(props);
  }
  render() {
    const recentUploads = this.props.data.slice(0, 10);
    return (
      <div>
        <br />
        <UploadList data={recentUploads} endpoint={this.props.endpoint} />
      </div>
    );
  }
}

export default Recent;
