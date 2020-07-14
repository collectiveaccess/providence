'use strict';
import React from 'react';
import ReactDOM from 'react-dom';
import Button from 'react-bootstrap/Button';
import ProgressBar from 'react-bootstrap/ProgressBar';
import fileSize from "filesize";
import Dropzone from "react-dropzone";

const axios = require('axios');
const tus = require("tus-js-client");
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const selector = providenceUIApps.mediauploader.selector;
const endpoint = providenceUIApps.mediauploader.endpoint;

class MediaUploader extends React.Component {

    constructor(props) {
        super(props);

        this.statusMessages = {
            idle: 'Select files to upload',
            ready: 'Ready to upload',
            start: 'Starting upload',
            upload: 'Uploading files',
            complete: 'Upload complete',
            error: 'Error'
        };

        this.state = {
            filesSelected: 0,
            filesUploaded: 0,
            status: this.statusMessages['idle'],
            uploadUrl: null,
            upload: null,
            queue: [],
            connections: {},
            connectionIndex: 0,

            recentList: [],

            paused: false,

            sessionKey: null
        };

        this.init = this.init.bind(this);
        this.start = this.start.bind(this);
        this.processQueue = this.processQueue.bind(this);
        this.startUpload = this.startUpload.bind(this);
        this.pauseUpload = this.pauseUpload.bind(this);
        this.pauseUploads = this.pauseUploads.bind(this);
        this.resumeUploads = this.resumeUploads.bind(this);
        this.deleteUpload = this.deleteUpload.bind(this);
        this.deleteQueuedUpload = this.deleteQueuedUpload.bind(this);
        this.selectFiles = this.selectFiles.bind(this);
        this.statusMessage = this.statusMessage.bind(this);
        this.checkSession = this.checkSession.bind(this);
        this.numConnections = this.numConnections.bind(this);
        this.getRecentListData = this.getRecentListData.bind(this);

        this.getRecentListData();
    }

    /**
     *  Handler for user file selection event
     *
     * @param Event e
     */
    selectFiles(e) {
        let state = this.state;
        console.log(e);
        if(e.target) {  // From <input type="file" ... />
            state['queue'].push(...e.target.files);
        } else {        // From dropzone
            state['queue'].push(...e);
        }
        state['queue'] = state['queue'].filter(f => f.size > 0);

        if (state['queue'].length > 0) {
            this.statusMessage('ready');
        }
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
            let relPath = file.webkitRelativePath ? file.webkitRelativePath : file.path;
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

                    that.checkSession();
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

    init() {
        let state = this.state;
        let that = this;

        this.statusMessage('complete');

        state.sessionKey = null;
        state.filesUploaded = 0;
        state.filesSelected = 0;
        state.connectionIndex = 0;
        this.setState(state);

        setTimeout(function() {
            that.statusMessage('idle');
        }, 3000);
    }

    checkSession() {
        let state = this.state;
        let that = this;
        if((Object.keys(state.connections).length === 0) && state.sessionKey) {
            axios.post(that.props.endpoint + '/complete', {}, {
                params: {
                    key: state.sessionKey
                }
            }).then(function(response) {
                // Refresh recent uploads list
                that.getRecentListData();
            });
            that.init();
        }
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
            this.statusMessage('start');
            axios.post(this.props.endpoint + '/session', {}, {
                params: {
                    n: this.queueLength(),
                    size: this.queueFilesize()
                }
            }).then(function (response) {
                state.sessionKey = response.data.key;
                state.filesUploaded = 0;
                state.filesSelected = that.queueLength();
                that.setState(state);

                that.statusMessage('upload');

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

    numConnections() {
        return Object.keys(this.state.connections).length;
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

    /**
     * Remove item from queue
     *
     * @param index
     */
    deleteQueuedUpload(index) {
        let state = this.state;
        delete state['queue'][index];
        this.setState(state);
    }

    /**
     * Remove running upload
     *
     * @param index
     */
    deleteUpload(connectionIndex) {
        let state = this.state;
        state.connections[connectionIndex].upload.abort();
        delete state['connections'][connectionIndex];
        this.setState(state);
        this.checkSession();// is session over now?
    }

    statusMessage(stage) {
        let state = this.state;
        if(this.statusMessages[stage]) {
            state.status =  this.statusMessages[stage];
        } else {
            state.status = '';
        }
        this.setState(state);
        return state.status;
    }

    getRecentListData() {
        let that = this;
        axios.post(this.props.endpoint + '/recent', {}, {
            params: {}
        }).then(function(response) {
            let state = that.state;
            state.recentList = response.data.recent;
            that.setState(state);

        });
    }

    /**
     *
     */
  render() {
    return (
        <div>
          <div className="row">
              <div className="col-md-10">
                  <h2>{this.state.status}</h2>
              </div>
          </div>
            <div className="row">
              <div className="col-md-4">
                  <div className="row mediaUploaderDropZone">
                      <div className="col-md-8 offset-md-2">
                          <Dropzone multiple={true} onDrop={acceptedFiles => {this.selectFiles(acceptedFiles)}}>
                              {({getRootProps, getInputProps}) => (
                                  <div {...getRootProps()} className='row mediaUploaderDropZoneInput'>
                                      <div className='col-md-1'>
                                          <input {...getInputProps()}/>
                                          <i className="fa fa-plus-circle fa-4x" aria-hidden="true"></i>
                                      </div>
                                      <div className='col-md-6 align-self-center'>
                                          <h4>Add media</h4>
                                      </div>
                                  </div>
                                  )}
                          </Dropzone>
                      </div>
                  </div>
              </div>
              <div className="col-md-4">
                  <div className="row">
                     <div className="col-md-1">
                         {((this.queueLength() + this.numConnections()) > 0) ? <Button variant="primary" onClick={this.start}><i className="fa fa-play-circle fa-2x" aria-hidden="true"></i></Button> : ''}
                     </div>
                      <div className="col-md-1">
                         {(((this.queueLength() + this.numConnections()) > 0) && !this.state.paused) ? <Button variant="outline-secondary" onClick={this.pauseUploads}><i className="fa fa-pause-circle fa-2x" aria-hidden="true"></i></Button> : ''}
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
                 <MediauploaderQueueProgress totalFiles={this.state.filesSelected} filesUploaded={this.state.filesUploaded}/>
              </div>
            </div>
            <div className="row mt-3">
              <div className="col-md-10">
                    <MediauploaderUploadList uploads={this.state.connections} deleteCallback={this.deleteUpload} />
              </div>
            </div>
            <div className="row mt-3">
              <div className="col-md-10">
                  <MediauploaderQueueList queue={this.state.queue} deleteCallback={this.deleteQueuedUpload}/>
              </div>
            </div>
            <div className="row mt-3">
                <div className="col-md-10">
                    <MediauploaderRecentsList sessions={this.state.recentList}/>
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

            let progressLabel = this.props.filesUploaded + ((this.props.filesUploaded == 1) ? ' file' : ' files');
            return <div>
                    <ProgressBar variant="success" now={progressFiles} label={progressLabel} />
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
            items.push(<MediauploaderUploadItem item={this.props.uploads[i]} index={i} deleteCallback={this.props.deleteCallback}/>);
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

        this.deleteItem = this.deleteItem.bind(this);
    }

    deleteItem() {
        this.props.deleteCallback(this.props.index);
    }

    render() {
        let progpercent = (this.props.item.uploadedBytes/this.props.item.totalBytes)*100;
        return <div className='row'>
                <div className='col-md-9'>
                    <ProgressBar style={{height: '20px', width:'100%'}} variant="success" now={progpercent} label={fileSize(this.props.item.uploadedBytes, {round: 1, base: 10})} />
                    {this.props.item.name} ({fileSize(this.props.item.totalBytes, {round: 1, base: 10})})
                </div>
                <div className='col-md-1'>
                    <Button variant="outline-secondary" className="mediaUploaderQueueItemDelete" onClick={this.deleteItem}><i className="fa fa-trash" aria-hidden="true"></i></Button>
                </div>
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
                <div style={ {columnCount: 3} }>
                    {items}
                </div>
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
        return <div className="row" style={{breakInside: 'avoid'}}>
            <div className="col-md-1">
                <Button variant="outline-secondary" className="mediaUploaderQueueItemDelete" onClick={this.deleteItem}><i className="fa fa-trash"
                                                                             aria-hidden="true"></i></Button>
            </div>
            <div className="col-md-3">
                {this.props.item.name}
                ({fileSize(this.props.item.size, {round: 1, base: 10})})
            </div>
        </div>
    }
}

class MediauploaderRecentsList extends React.Component {
    constructor(props) {
        super(props);
    }

    render() {
        let items = [];
        for(let i in this.props.sessions) {
            items.push(<MediauploaderRecentItem item={this.props.sessions[i]}/>);
        }

        if(items.length > 0) {
            return <div>
                <h2>Recent uploads ({items.length})</h2>
                <div className='row'>{items}</div>
            </div>;
        } else {
            return <div></div>;
        }
    }
}

class MediauploaderRecentItem extends React.Component {
    constructor(props) {
        super(props);
    }

    render() {
        let item = this.props.item;
        let n = Object.keys(item.files).length;
        let examplePaths = Object.keys(item.files).slice(0, 3);

        let contentsStr = examplePaths.join(", ");
        if (n > 3) {
            contentsStr += ' and ' + (n - 3) + ' more';
        }

        let status, statusClass, current;
        if (item.completed_on) {
            current = 'Completed: ' + item.completed_on;
            status = 'Completed';
            statusClass = 'badge badge-success pull-right';
        } else {
            current = "Last activity: " + item.last_activity_on;
            status = 'Incomplete';
            statusClass = 'badge badge-warning pull-right';
        }

        return <div className="col-md-4 mt-3">
                <div className="card">
                    <div className="card-body">
                        <div
                            className={statusClass}>{status}</div>
                        <h5 className="card-title">{n == 1 ? '1 file' : n + ' files'} ({fileSize(item.total_bytes)}) </h5>
                        <h6 className="card-subtitle mb-2 text-muted">{contentsStr}</h6>
                        <p className="card-text">
                            Started: {item.created_on}
                            <br/>
                            {current}
                        </p>
                    </div>
                </div>
            </div>
    }
}

ReactDOM.render(<MediaUploader maxConcurrentConnections="4" endpoint={endpoint}/>, document.querySelector(selector));
