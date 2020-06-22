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
            status: "No files selected",
            uploadUrl: null,
            upload: null,
            queue: [],
            connections: {},
            connectionIndex: 0,

            sessionKey: null
        };
        this.start = this.start.bind(this);
        this.processQueue = this.processQueue.bind(this);
        this.startUpload = this.startUpload.bind(this);
        this.pauseUpload = this.pauseUpload.bind(this);
        this.selectFiles = this.selectFiles.bind(this);
    }

    /**
     *  Handler for user file selection event
     *
     * @param Event e
     */
    selectFiles(e) {
        console.log("Selected files", e.target.files);

        let state = this.state;
        state['queue'].push(...e.target.files);
        this.setState(state);
    }

    /**
     *
     */
    processQueue() {
        let that = this;
        let state = this.state;
        let queue = state.queue;
        if (!queue) return;

        const maxConnections = parseInt(this.props.maxConcurrentConnections);
        if (state.connections.length >= maxConnections) return;
        if (!state.sessionKey) {
            return;
        }
        let i = 0;
        while(queue.length > 0) {
            if (state.connections.length >= maxConnections) {
                return;
            }
            let file = queue.shift();

            let connectionIndex = state.connectionIndex;
            let relPath = file.webkitRelativePath;
            if(relPath) {
                let tmp = relPath.split(/\//);
                tmp.pop();
                relPath  = tmp.join('/');
            }

            let upload = new tus.Upload(file, {
                endpoint: this.props.endpoint + '/tus',
                retryDelays: [0, 1000, 3000, 5000],
                chunkSize: 1024 * 512,      // TODO: make configurable
                metadata: {
                    filename: file.name,
                    sessionKey: state.sessionKey,
                    relativePath: relPath
                },
                onError: (error) => {
                    state.connections[connectionIndex]['status']  = error;
                    this.setState(state);
                    console.log('Error: ', error);
                },
                onProgress: (uploadedBytes, totalBytes) => {
                    state.connections[connectionIndex]['totalBytes']  = totalBytes;
                    state.connections[connectionIndex]['uploadedBytes']  = uploadedBytes;
                    this.setState(state);
                },
                onSuccess: () => {
                    //console.log('Completed upload for connection ' + connectionIndex);
                    delete state.connections[connectionIndex];

                    if((Object.keys(state.connections).length === 0) && state.sessionKey) {
                        //console.log("Upload complete for key ", state.sessionKey);
                        axios.post(this.props.endpoint + '/complete', {}, {
                            params: {
                                key: state.sessionKey
                            }
                        });
                        state.sessionKey = null;
                    }

                    this.setState(state);

                    if(state.sessionKey) {
                        that.processQueue();
                    }
                }
            });

            upload.findPreviousUploads().then((previousUploads) => {
              if(previousUploads.length > 0) {
                  let resumable = previousUploads.pop();    // Grab last discontinued upload to resume
                  console.log('Resuming download: ', resumable);
                  upload.resumeFromPreviousUpload(resumable);
              }
            });
            state.connections[connectionIndex] = {
                upload: upload,
                status: "queued",
                uploadUrl: null,
                totalBytes: 0,
                uploadedBytes: 0
            };
            state.connectionIndex++;
            upload.start();

            i++;
        }



        this.setState(state);
    }

    /**
     *
     */
    start() {
        let state = this.state;
        let n = state.queue.length;
        let that = this;

        // Get session key and start upload
        if(state.sessionKey === null) {
            state.status = "Starting session";
            this.setState(state);
            axios.post(this.props.endpoint + '/session', {}, {
                params: {
                    n: this.queueLength(),
                    size: this.queueFilesize()
                }
            }).then(function (response) {
                state.sessionKey = response.data.key;
                that.setState(state);

                that.processQueue();
            });
        }
    }

    queueLength() {
        return this.state['queue'].length;
    }
    queueFilesize() {
        return this.state['queue'].reduce((acc, cv) => acc + parseInt(cv.size) , 0);
    }

    /**
     *
     */
    startUpload(connectionIndex) {
        this.state.connections[connectionIndex].upload.start();
    }

    /**
     *
     */
    pauseUpload(connectionIndex) {
        this.state.connections[connectionIndex].upload.abort();
    }

    /**
     *
     */
  render() {
    return (
      <div className="row">
          <div className="col-md-12">
              <h1>{this.state.status}</h1>
          </div>
          <div className="col-md-4">
            <input type="file" name="file" onChange={this.selectFiles} webkitdirectory="1" mozdirectory="1" multiple="1"/>
          </div>
          <div className="col-md-4">
                <button onClick={this.start}>Start upload</button>
          </div>
          <div className="col-md-4">
              Queued files: {this.state.queue.length}
          </div>
          <div className="col-md-12">
                <MediauploaderUploadList uploads={this.state.connections}/>
          </div>
      </div>
    );
  }
}

class MediauploaderUploadList extends React.Component {
    constructor(props) {
        super(props);
    }
    render() {
        let items = [];
        for(let i in this.props.uploads) {
            items.push(<MediauploaderUploadItem upload={this.props.uploads[i]}/>);
        }

       return <div>
            <h2>Uploads</h2>
           {items}
        </div>
    }
}

class MediauploaderUploadItem extends React.Component {
    constructor(props) {
        super(props);
    }

    render() {
        return <div>
            Item {this.props.upload.status}
            {this.props.upload.totalBytes}/{this.props.upload.uploadedBytes}
        </div>
    }
}

ReactDOM.render(<MediaUploader maxConcurrentConnections="4" endpoint={endpoint}/>, document.querySelector(selector));
