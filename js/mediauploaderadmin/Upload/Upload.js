import React, { Component } from "react";
import ProgressBar from "react-bootstrap/ProgressBar";
import "bootstrap/dist/css/bootstrap.css";

class Upload extends Component {
  constructor(props) {
    super(props);
    this.state = {};
  }

  render() {
    const progress = [];
    for (const [key, value] of Object.entries(this.props.data.files)) {
      progress.push(value.progressInBytes);
    }

    let progressBar = null;
    const arrSum = (arr) => arr.reduce((a, b) => a + b, 0);
    let progressSum = arrSum(progress) / 1000;
    let progressPecentage =
      (progressSum / (this.props.data.total_bytes / 1000)) * 100;
    if (this.props.data.status !== "COMPLETED") {
      progressBar = (
        <div style={{ marginTop: "10px" }}>
          <ProgressBar
            now={progressPecentage}
            label={`${Math.ceil(progressPecentage)}%`}
          />
        </div>
      );
    }

    return (
      <div
        className="card"
        key={this.props.data.session_key}
        style={{ marginBottom: "15px", boxShadow: "1px 2px #888888" }}
      >
        <div className="card-body">
          <div className="container">
            <div
              className="row"
              style={{ justifyContent: "space-between", marginBottom: "8px" }}
            >
              <div className="col">
                {this.props.data.num_files !== "1" ? (
                    <h5 className="card-title">
                      {this.props.data.num_files} files
                    </h5>
                  ) : (
                    <h5 className="card-title">
                      {this.props.data.num_files} file
                    </h5>
                  )}
              </div>

              <div className="col">
                <h5 className="card-title">{this.props.data.user.user_name}</h5>
              </div>

              <div className="col" style={{ borderRadius: "25px" }}>
                {this.props.data.status === "IN_PROGRESS" ? (
                  <div className="badge badge-warning">
                    <h6>{this.props.data.status}</h6>
                  </div>
                ) : (
                  <div className="badge badge-success">
                    <h6>{this.props.data.status}</h6>
                  </div>
                )}
              </div>

              <div className="col">
                {this.props.data.status === "IN_PROGRESS" ? (
                  <button type="button" className="btn btn-danger">
                    x
                  </button>
                ) : (
                  ""
                )}
              </div>
            </div>
          </div>

          <div className="card-subtitle mb-2 text-muted">
            <h6>
              {(() => {
                switch (this.props.data.num_files) {
                  case "1":
                    return "File: " + Object.keys(this.props.data.files)[0];
                  case "2":
                    return (
                      "Files: " +
                      Object.keys(this.props.data.files)[0] +
                      ", " +
                      Object.keys(this.props.data.files)[1]
                    );
                  case "3":
                    return (
                      "Files: " +
                      Object.keys(this.props.data.files)[0] +
                      ", " +
                      Object.keys(this.props.data.files)[1] +
                      ", " +
                      Object.keys(this.props.data.files)[2]
                    );
                  default:
                    return (
                      "Files: " +
                      Object.keys(this.props.data.files)[0] +
                      ", " +
                      Object.keys(this.props.data.files)[1] +
                      ", " +
                      Object.keys(this.props.data.files)[2] +
                      " and " +
                      (this.props.data.num_files - 3) +
                      " more"
                    );
                }
              })()}
            </h6>
          </div>

          <div>
            {this.props.data.status === "COMPLETED" ? (
              <p className="card-text">
                Started on: {this.props.data.created_on}
                <br></br>
                Completed on: {this.props.data.completed_on}
              </p>
            ) : (
              <p className="card-text">
                Started on: {this.props.data.created_on}
              </p>
            )}
          </div>

          {this.props.data.status !== "COMPLETED" ? progressBar : ""}
        </div>
      </div>
    );
  }
}

export default Upload;
