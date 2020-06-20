'use strict';
import React from 'react';
import ReactDOM from 'react-dom';
const axios = require('axios');
const tus = require("tus-js-client");
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const selector = providenceUIApps.mediauploader.selector;
const endpoint = providenceUIApps.mediauploader.endpoint;

class MediaUploader extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
        uploadedBytes: 0,
        totalBytes: 0,
        status: "No file selected",
        uploadUrl: null,
        upload: null,
        queue: [],
        connections: []
    };
    this.startUpload = this.startUpload.bind(this);
    this.startUploads = this.startUploads.bind(this);
    this.pauseUpload = this.pauseUpload.bind(this);
    this.selectFiles = this.selectFiles.bind(this);
  }

    getFileExtension(uri) {
        const match = /\.([a-zA-Z]+)$/.exec(uri);
        if (match !== null) {
            return match[1];
        }

        return "";
    }

    getMimeType(extension) {
        if (extension === "jpg") return "image/jpeg";
        return `image/${extension}`;
    }

    selectFiles(e) {
        console.log("XX", e.target.files);

        let state = this.state;
        state['queue'].push(...e.target.files);
        this.setState(state);
    }

    setupUpload() {
        let state = this.state;
        let queue = state.queue;
        if (!queue) return;

        const maxConnections = this.props.maxConcurrentConnections;
        if (state.connections.length >= maxConnections) return;

        for(let i in queue) {
            if (!queue.hasOwnProperty(i)){ continue; }
            let file = queue.shift();
            let extension = this.getFileExtension(file.uri);

            let connectionIndex = state.connections.length;

            console.log("starting", connectionIndex, file);
            let upload = new tus.Upload(file, {
                endpoint: this.props.endpoint,
                retryDelays: [0, 1000, 3000, 5000],
                metadata: {
                    filename: file.name,
                    filetype: this.getMimeType(extension)
                },
                onError: (error) => {
                    state.connections[connectionIndex]['status']  = error;
                    this.setState(state);
                    console.log("error!", error);
                },
                onProgress: (uploadedBytes, totalBytes) => {
                    state.connections[connectionIndex]['totalBytes']  = totalBytes;
                    state.connections[connectionIndex]['uploadedBytes']  = uploadedBytes;
                    this.setState(state);
                    console.log(connectionIndex, totalBytes, uploadedBytes);
                },
                onSuccess: () => {
                    state.connections[connectionIndex]['status']  = error;
                    state.connections[connectionIndex]['uploadUrl']  = upload.url;
                    this.setState(state);
                    console.log("Upload URL:", upload.url);
                    this.connections.splice(connectionIndex, 1);
                }
            });

            upload.findPreviousUploads().then((previousUploads) => {
              if(previousUploads.length > 0) {
                  let resumable = previousUploads.pop();    // Grab last discontinued upload to resume
                  console.log("Resuming download: ", resumable);
                  upload.resumeFromPreviousUpload(resumable);
              }
            });
            this.state.connections.push({
                upload: upload,
                status: "starting",
                uploadUrl: null,
                totalBytes: 0,
                uploadedBytes: 0
            });
            upload.start();

            // TODO: make possible to upload multiple files
            this.setState(state);
        }


    }

    startUploads() {
        this.setupUpload();
    }

    startUpload(connectionIndex) {
        this.state.connections[connectionIndex].upload.start();
    }
    pauseUpload(connectionIndex) {
        this.state.connections[connectionIndex].upload.abort();
    }

  render() {
    return (
      <div className="row">
          <div className="col-md-12">
              <h1>{this.state.status}</h1>
          </div>
          <div className="col-md-4">
            <input type="file" name="file" onChange={this.selectFiles} webkitdirectory="1"/>
          </div>
          <div className="col-md-4">
                <button onClick={this.startUploads}>Start upload</button>
          </div>
          <div className="col-md-4">
              {this.state.uploadedBytes} of {this.state.totalBytes}
          </div>
          <div className="col-md-4">
              Queued files: {this.state.queue.length}
          </div>
      </div>
    );
  }
}

ReactDOM.render(<MediaUploader maxConcurrentConnections="4" endpoint={endpoint}/>, document.querySelector(selector));
