import React, { Component } from "react";
import Upload from "./Upload";

class UploadList extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    return (
      <div>
        {this.props.data.map((upload) => {
          return <Upload data={upload} key={upload.session_id} endpoint={this.props.endpoint} />;
        })}
      </div>
    );
  }
}

export default UploadList;
