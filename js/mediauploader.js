'use strict';
import React from 'react';
import ReactDOM from 'react-dom';
import Button from 'react-bootstrap/Button';
import ProgressBar from 'react-bootstrap/ProgressBar';
import fileSize from "filesize";

const axios = require('axios');
const tus = require("tus-js-client");
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const selector = providenceUIApps.mediauploader.selector;
const endpoint = providenceUIApps.mediauploader.endpoint;

class MediaUploader extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            filesUploaded: 0,
            status: "No files selected",
            uploadUrl: null,
            upload: null,
            queue: [],
            connections: {},
            connectionIndex: 0,

            paused: false,

            sessionKey: null
        };
        this.start = this.start.bind(this);
        this.processQueue = this.processQueue.bind(this);
        this.startUpload = this.startUpload.bind(this);
        this.pauseUpload = this.pauseUpload.bind(this);
        this.pauseUploads = this.pauseUploads.bind(this);
        this.resumeUploads = this.resumeUploads.bind(this);
        this.deleteQueuedUpload = this.deleteQueuedUpload.bind(this);
        this.selectFiles = this.selectFiles.bind(this);

        this.fileControl = React.createRef();
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
        state['queue'] = state['queue'].filter(f => f.size > 0);
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
        if (Object.keys(state.connections).length >= maxConnections) {
            return;
        }
        if (!state.sessionKey) {
            return;
        }
        let i = 0;
        while(queue.length > 0) {
            if (Object.keys(state.connections).length >= maxConnections) {
                console.log("Stopping queue because max connections have been reached", state.connections, state.connections.length);
                return;
            }
            let file = queue.shift();
            if(!file) { continue; }

            let connectionIndex = state.connectionIndex;
            let relPath = file.webkitRelativePath;
            if(relPath) {
                let tmp = relPath.split(/\//);
                tmp.pop();
                relPath  = tmp.join('/');
            }

            let upload = new tus.Upload(file, {
                endpoint: this.props.endpoint + '/tus?download=1',
                retryDelays: [0, 1000, 3000, 5000],
                chunkSize: 1024 * 512,      // TODO: make configurable
                metadata: {
                    filename: file.name,
                    sessionKey: state.sessionKey,
                    relativePath: relPath
                },
                onError: (error) => {
                    let state = that.state;
                    if(state.connections[connectionIndex]) {
                        state.connections[connectionIndex]['status'] = 'Error';
                        that.setState(state);
                        console.log('Error: ', error);
                    }
                },
                onProgress: (uploadedBytes, totalBytes) => {
                    let state = that.state;
                    if(state.connections[connectionIndex]) {
                        state.connections[connectionIndex]['totalBytes'] = totalBytes;
                        state.connections[connectionIndex]['uploadedBytes'] = uploadedBytes;
                        that.setState(state);
                    }
                },
                onSuccess: () => {
                    let state = that.state;
                    delete state.connections[connectionIndex];

                    if((Object.keys(state.connections).length === 0) && state.sessionKey) {
                        axios.post(that.props.endpoint + '/complete', {}, {
                            params: {
                                key: state.sessionKey
                            }
                        });
                        state.sessionKey = null;
                        that.fileControl.current.value = '';    // clear file control
                    }
                    state['filesUploaded']++;
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
                uploadUrl: null,
                totalBytes: 0,
                uploadedBytes: 0,
                name: file.name
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
        let that = this;

        if(state.paused === true) {
            this.resumeUploads();
            return;
        }

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
                state.filesUploaded = 0;
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

    resumeUploads() {
        let state = this.state;
        state.paused = false;
        for(let connectionIndex in this.state.connections) {
            state.connections[connectionIndex].upload.start();
        }
        this.setState(state);
    }
    pauseUploads() {
        let state = this.state;
        state.paused = true;
        for(let connectionIndex in this.state.connections) {
            state.connections[connectionIndex].upload.abort();
        }
        this.setState(state);
    }

    deleteQueuedUpload(index) {
        let state = this.state;
        delete state['queue'][index];
        this.setState(state);
    }

    /**
     *
     */
  render() {
    return (
        <div>
          <div className="row">
              <div className="col-md-10">
                  <h1>{this.state.status}</h1>
              </div>
          </div>
            <div className="row">
              <div className="col-md-4">
                <input type="file" name="file" ref={this.fileControl} onChange={this.selectFiles} webkitdirectory="1" mozdirectory="1" multiple="1"/>
              </div>
              <div className="col-md-4">
                  <div className="row">
                     <div className="col-md-1">
                         {(this.queueLength() > 0) ? <Button variant="primary" onClick={this.start}><i className="fa fa-play-circle fa-2x" aria-hidden="true"></i></Button> : ''}
                     </div>
                      <div className="col-md-1">
                         {((this.queueLength() > 0) && !this.state.paused) ? <Button variant="outline-secondary" onClick={this.pauseUploads}><i className="fa fa-pause-circle fa-2x" aria-hidden="true"></i></Button> : ''}
                      </div>
                  </div>
              </div>
            </div>
            <div className="row">
                <div className="col-md-10">
                </div>
             </div>
            <div className="row mt-3">
              <div className="col-md-10">
                 <MediauploaderQueueProgress totalFiles={this.queueLength()} filesUploaded={this.state.filesUploaded}/>
              </div>
            </div>
            <div className="row mt-3">
              <div className="col-md-10">
                    <MediauploaderUploadList uploads={this.state.connections} />
              </div>
            </div>
            <div className="row mt-3">
              <div className="col-md-10">
                  <MediauploaderQueueList queue={this.state.queue} deleteCallback={this.deleteQueuedUpload}/>
              </div>
            </div>
          </div>
    );
  }
}

class MediauploaderQueueProgress extends React.Component {
    render() {
        let progressFiles = 0;
        if ((this.props.totalFiles) > 0) {
            progressFiles = (this.props.filesUploaded/this.props.totalFiles) * 100;

            return <div>
                    <ProgressBar variant="success" now={progressFiles} />
                </div>;
        } else {
            return null;
        }
    }
}

class MediauploaderUploadList extends React.Component {
    constructor(props) {
        super(props);
    }
    render() {
        let items = [];
        for(let i in this.props.uploads) {
            items.push(<MediauploaderUploadItem item={this.props.uploads[i]}/>);
        }
        if(items.length > 0) {
            return <div>
                <h2>Uploading ({items.length})</h2>
                {items}
            </div>;
        } else {
            return <div></div>;
        }
    }
}

class MediauploaderUploadItem extends React.Component {
    constructor(props) {
        super(props);
    }
    render() {
        let progpercent = (this.props.item.uploadedBytes/this.props.item.totalBytes)*100;
        return <div>
            {this.props.item.name}
            <ProgressBar variant="success" now={progpercent} /> {fileSize(this.props.item.uploadedBytes)}/{fileSize(this.props.item.totalBytes)}
        </div>
    }
}

class MediauploaderQueueList extends React.Component {
    constructor(props) {
        super(props);
    }
    render() {
        let items = [];
        for(let i in this.props.queue) {
            items.push(<MediauploaderQueueItem item={this.props.queue[i]} index={i} deleteCallback={this.props.deleteCallback}/>);
        }

        if (items.length > 0) {
            return <div>
                <h2>Queued ({items.length})</h2>
                {items}
            </div>
        } else {
            return <div></div>
        }
    }
}

class MediauploaderQueueItem extends React.Component {
    constructor(props) {
        super(props);

        this.deleteItem = this.deleteItem.bind(this);
    }

    deleteItem() {
        this.props.deleteCallback(this.props.index);
    }

    render() {
        return <div>
            <Button variant="outline-secondary" className="mediaUploaderQueueItemDelete" onClick={this.deleteItem}><i className="fa fa-trash"
                                                                             aria-hidden="true"></i></Button>
            {this.props.item.name} ({fileSize(this.props.item.size)})
        </div>
    }
}
ReactDOM.render(<MediaUploader maxConcurrentConnections="4" endpoint={endpoint}/>, document.querySelector(selector));
