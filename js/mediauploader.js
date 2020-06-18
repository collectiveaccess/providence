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
        files: null,
        status: "No file selected",
        uploadUrl: null,
        upload: null
    };
    this.startUpload = this.startUpload.bind(this);
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
        state['files'] = e.target.files;
        this.setState(state);

        this.setupUpload();
    }

    setupUpload() {
        const files = this.state.files;
        if (!files) return;

        for(let i in files) {
            if (!files.hasOwnProperty(i)){ continue; }
            const file = files[i];

            console.log("starting", i, file);

            const extension = this.getFileExtension(file.uri);
            const upload = new tus.Upload(file, {
                endpoint: this.props.endpoint,
                retryDelays: [0, 1000, 3000, 5000],
                metadata: {
                    filename: file.name,
                    filetype: this.getMimeType(extension)
                },
                onError: (error) => {
                    this.setState({
                        status: `upload failed ${error}`
                    });
                },
                onProgress: (uploadedBytes, totalBytes) => {
                    this.setState({
                        totalBytes: totalBytes,
                        uploadedBytes: uploadedBytes
                    });
                },
                onSuccess: () => {
                    this.setState({
                        status: "upload finished",
                        uploadUrl: upload.url
                    });
                    console.log("Upload URL:", upload.url);
                }
            });
            upload.findPreviousUploads().then((previousUploads) => {
              if(previousUploads.length > 0) {
                  let resumable = previousUploads.pop();    // Grab last discontinued upload to resume
                  console.log("Resuming download: ", resumable);
                  upload.resumeFromPreviousUpload(resumable);
              }
            });

            // TODO: make possible to upload multiple files
            this.setState({
                status: "upload started",
                uploadedBytes: 0,
                totalBytes: 0,
                uploadUrl: null,
                upload: upload
            });
        }


    }

    startUpload() {
        this.state.upload.start();
    }

    pauseUpload() {
        this.state.upload.abort();
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
                <button onClick={this.startUpload}>Start upload</button>
                <button onClick={this.pauseUpload}>Pause upload</button>
          </div>
          <div className="col-md-4">
              {this.state.uploadedBytes} of {this.state.totalBytes}
          </div>
      </div>
    );
  }
}

ReactDOM.render(<MediaUploader endpoint={endpoint}/>, document.querySelector(selector));
