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

// Settings from invocation
let maxConcurrentUploads = providenceUIApps.mediauploader.maxConcurrentUploads;
maxConcurrentUploads = ((maxConcurrentUploads === undefined) || (parseInt(maxConcurrentUploads) <= 0)) ? maxConcurrentUploads = 4 : parseInt(maxConcurrentUploads);
let maxFileSize = providenceUIApps.mediauploader.maxFileSize;
maxFileSize = ((maxFileSize === undefined) || (parseInt(maxFileSize) <= 0)) ? maxFileSize = 0 : parseInt(maxFileSize);
let maxFilesPerSession = providenceUIApps.mediauploader.maxFilesPerSession;
maxFilesPerSession = ((maxFilesPerSession === undefined) || (parseInt(maxFilesPerSession) <= 0)) ? maxFilesPerSession = 0 : parseInt(maxFilesPerSession);

// Error types
const ERR_RECOVERABLE = 1;
const ERR_BLOCKING = 2;

class MediaUploader extends React.Component {
    constructor(props) {
        super(props);

        this.statusMessages = {
            idle: '',
            ready: 'Ready to upload',
            start: 'Starting upload',
            upload: 'Uploading files',
            complete: 'Upload complete',
            error: 'Error'
        };
        
        this.errorMessages = {
        	userquota: 'User storage allocation exceeded',
            sessionfilelimit: 'Limit of ' + this.props.maxFilesPerSession + ' files per upload exceeded',
            filesizelimit: fileSize(maxFileSize, {round: 1, base: 10}) + ' file size limit exceeded',
            systemquota: 'System storage allocation exceeded'
        };

        this.state = {
            filesSelected: 0,
            filesUploaded: 0,
            status: this.statusMessages['idle'],	/* Current status code; determined status message to be displayed */
            queue: [],					/* List of pending uploads */
            connections: {},			/* List of open tus upload connections */
            connectionIndex: 0,			/* Index/key for next connection; changes for each allocated connection */	
            error: null,				/* Current symbolic error code (Ex. 'userquota', 'filesizelimit') */
            errorMessage: null,			/* Current error message for display */
            errorType: null,			/* Numeric error type; ERR_RECOVERABLE (1) = Resolvable by user; ERR_BLOCKING (2) = Fatal, don't allow upload */

            recentList: [],

            paused: false,

            sessionKey: null,			/* Unique identifier for current upload session */
            
            storageUsage: '-',
            storageUsageBytes: 0,
            storageAvailable: '-',
            storageAvailableBytes: 0,
            storageFileCount: 0,
            storageDirectoryCount: 0
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
        this.resetQueue = this.resetQueue.bind(this);
        this.checkQueue = this.checkQueue.bind(this);
        this.selectFiles = this.selectFiles.bind(this);
        this.statusMessage = this.statusMessage.bind(this);
        this.setError = this.setError.bind(this);
        this.clearError = this.clearError.bind(this);
        this.checkSession = this.checkSession.bind(this);
        this.numConnections = this.numConnections.bind(this);
        this.getRecentListData = this.getRecentListData.bind(this);

        this.getRecentListData();	// load recent uploads for display
        
    }

    /**
     *  Handler for user file selection event
     *
     * @param Event e
     */
    selectFiles(e) {
    	let queue = [...this.state['queue']];
        if(e.target) {  // From <input type="file" ... />
            queue.push(...e.target.files);
        } else {        // From dropzone
            queue.push(...e);
        }
        queue = queue.filter(f => f.size > 0);

		this.setState({queue: queue});
		
		if (this.checkQueue()) {
			if (queue.length > 0) {
				this.statusMessage('ready');
			}
			this.clearError();
			
			if (this.numConnections() > 0) {
				this.processQueue();
			}
		}
    }

    /**
     * Main loop to create new uploads from user-selected files
     */
    processQueue() {
        let that = this;
        let state = {...this.state};
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
                //console.log("Stopping queue because max connections have been reached", state.connections, state.connections.length);
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

			// Set up tus upload process for file, with handlers for success, error and progress
            let upload = new tus.Upload(file, {
                endpoint: this.props.endpoint + '/tus',
                retryDelays: [0, 1000, 3000, 5000],
                chunkSize: 1024 * 512,      // TODO: make configurable
                metadata: {
                    filename: '.' + file.name + '.part',
                    sessionKey: state.sessionKey,
                    relativePath: relPath
                },
                onBeforeRequest: function (req) {
					var xhr = req.getUnderlyingObject()
					xhr.setRequestHeader('x-session-key', state.sessionKey);
				},
                onError: (error) => {
                    let state = {...that.state};
					// error during transfer
					let error_resp = JSON.parse(error.originalResponse.getBody());
					let error_msg = (error_resp && error_resp.error) ? error_resp.error : 'Unknown error';
					let is_global = (error_resp && error_resp.global) ? error_resp.global : false;
					let error_state = (error_resp && error_resp.state) ? error_resp.state : 'error';
					
					if(is_global) {
						that.statusMessage(error_state);
						state.error = error_msg;
					}
                    console.log("Error:", error_msg);
                    if(state.connections[connectionIndex]) {
                        state.connections[connectionIndex]['status'] = error_msg;
                        state.connections[connectionIndex]['uploadedBytes'] = 0;
                        
                        
						state.connections[connectionIndex].upload.abort();
						delete state['connections'][connectionIndex];
						that.setState(state);
						that.checkSession();// is session over now?
                    }
                },
                onProgress: (uploadedBytes, totalBytes) => {
                    let state = {...that.state};
                    if(state.connections[connectionIndex]) {
                        state.connections[connectionIndex]['totalBytes'] = totalBytes;
                        state.connections[connectionIndex]['uploadedBytes'] = uploadedBytes;
                        that.setState(state);
                    }
                },
                onAfterResponse: function (req, res) {
                    let state = {...that.state};
                    if (res.getHeader("storageAvailableDisplay")) {
						state.storageUsageBytes = res.getHeader("storageUsage");
						state.storageUsage = res.getHeader("storageUsageDisplay");
						state.storageAvailableBytes = res.getHeader("storageAvailable");
						state.storageAvailable = res.getHeader("storageAvailableDisplay");
						state.storageFileCount = res.getHeader("fileCount");
						state.storageDirectoryCount = res.getHeader("directoryCount");
						
						if (state.storageUsageBytes > state.storageAvailableBytes) {
							that.setError('userquota', ERR_BLOCKING);
						}
                    	that.setState(state);
                    }
				},
                onSuccess: () => {
                    let connections = {...that.state.connections};
                    let filesUploaded = that.state.filesUploaded;
                    delete connections[connectionIndex];

                    filesUploaded++;
                    that.setState({connections: connections, filesUploaded: filesUploaded});
                    
                    that.checkSession();
                    if(state.sessionKey) {
                        that.processQueue();
                    }
                }
            });

			// Try to resume upload if possible
            upload.findPreviousUploads().then((previousUploads) => {
              if(previousUploads.length > 0) {
                  let resumable = previousUploads.pop();    // Grab last discontinued upload to resume
                  //console.log('Resuming download: ', resumable);
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
     * Initialize UI with selection and message defaults
     *
     * @return void
     */
    init() {
        let that = this;
        this.setState({sessionKey: null, filesUploaded: 0, filesSelected: 0, connectionIndex: 0});
        this.statusMessage('complete');

        setTimeout(function() {
            that.statusMessage('idle');
        }, 3000);
    }

	/**
     * Close session and reset UI if nothing more queued for upload
     *
     * @return void
     */
    checkSession() {
        let that = this;
        if((Object.keys(this.state.connections).length === 0) && this.state.sessionKey) {
            axios.post(that.props.endpoint + '/complete', {}, {
                params: {
                    key: that.state.sessionKey
                }
            }).then(function(response) {
                // Refresh recent uploads list
                that.getRecentListData();
            });
            that.init();
        }
    }

    /**
     * Start upload process
     *
     * @return void
     */
    start() {
        let that = this;

        if(this.state.paused === true) {
            this.resumeUploads();
            return;
        }

        // Get session key and begin upload
        if(this.state.sessionKey === null) {
            this.statusMessage('start');
            axios.post(this.props.endpoint + '/session', {}, {
                params: {
                    n: this.queueLength(),
                    size: this.queueFilesize()
                }
            }).then(function (response) {
            	if(parseInt(response.data.ok) === 1) {
					let state = {
						sessionKey: response.data.key,
						filesUploaded: 0,
						filesSelected: that.queueLength()
					};
					
					if(response.data.storageAvailableDisplay) {
						state.storageUsage = response.data.storageUsageDisplay;
						state.storageUsageBytes = response.data.storageUsage;
						state.storageAvailable = response.data.storageAvailableDisplay;
						state.storageAvailableBytes = response.data.storageAvailable;
						state.storageFileCount = response.data.fileCount;
						state.storageDirectoryCount = response.data.storageDirectoryCount;
					
						if (state.storageUsageBytes > state.storageAvailableBytes) {
							that.setError('userquota', ERR_BLOCKING);
						}
					}
					that.setState(state);

					that.statusMessage('upload');

					that.processQueue();
				} else {
					that.setError('sessionfilelimit', ERR_RECOVERABLE);
				}
            });
        }
    }

	/**
     * Return number of files queued for upload
     *
     * @return int
     */
    queueLength() {
        return this.state['queue'].length;
    }

	/**
     * Return total size of files queued for upload
     *
     * @return int
     */
    queueFilesize() {
        return this.state['queue'].reduce((acc, cv) => acc + parseInt(cv.size) , 0);
    }

	/**
     * Return number of open upload connections
     *
     * @return int
     */
    numConnections() {
        return Object.keys(this.state.connections).length;
    }

    /**
     * Start upload of specific connection
     *
     * @param int connectionIndex Index/key of connection to start
     * @return bool True if connectionIndex is valid
     */
    startUpload(connectionIndex) {
    	if(!this.state.connections[connectionIndex]) { return false; }
        this.state.connections[connectionIndex].upload.start();
        return true;
    }

    /**
     * Pause upload of specific connection
     *
     * @param int connectionIndex Index/key of connection to pause
     * @return bool True if connectionIndex is valid
     */
    pauseUpload(connectionIndex) {
    	if(!this.state.connections[connectionIndex]) { return false; }
        this.setState({paused: true});
        this.state.connections[connectionIndex].upload.abort();
    }

	/**
     * Resume upload on all open collections
     *
     * @return void
     */
    resumeUploads() {
        this.setState({paused: false});
        for(let connectionIndex in this.state.connections) {
            this.state.connections[connectionIndex].upload.start();
        }
    }

	/**
     * Pause upload of all open collections
     *
     * @return void
     */
    pauseUploads() {
        this.setState({paused: true});
        for(let connectionIndex in this.state.connections) {
            this.state.connections[connectionIndex].upload.abort();
        }
    }

    /**
     * Remove specified item from queue
     *
     * @param int index Index in queue of item to remove
     *
     * @return void
     */
    deleteQueuedUpload(index) {
        let queue = [ ...this.state.queue ];
        queue.splice(index, 1);
        this.setState({queue: queue});
       	this.checkQueue(queue);
    }
    
    /**
     * Remove all items from queue
     *
     * @return void
     */
    resetQueue() {
        this.setState({queue: []});
        this.checkQueue([]);
    }
    
    /**
     * Trigger error states as needed for the current queue state. Two error states 
     * are checked for:
     *
     *	1. sessionfilelimit limits how many files may be added in a single upload session. 
     *		If the queue length + number of open connections is greater than the limit an 
     *		error is triggered here.
     *
     *  2. filesizelimit limits how large any single file in an upload session can be. 
     *		If any file exceeds the configured threshold an error is triggered here.
     *
     * @return bool True if queue is ok, false if error was triggered
     */
    checkQueue(currentQueue=null) {
    	let queue = currentQueue ? currentQueue : this.state.queue;
    	if (this.props.maxFilesPerSession > 0) {
    		if (this.queueLength() + this.numConnections() > this.props.maxFilesPerSession) {
    			this.setError('sessionfilelimit', ERR_BLOCKING);
    			return false;
    		} else {
    			this.clearError('sessionfilelimit');
    		}
    	}
    	if (this.props.maxFileSize > 0) {
    		let q = queue.filter((item) => { return (item.size > this.props.maxFileSize); });
    		if (q.length > 0) {
    			this.setError('filesizelimit', ERR_BLOCKING);
    			return false;
    		} else {
    			this.clearError('filesizelimit');
    		}
    	}
    	return true;
    }

    /**
     * Abort and remove running upload connection
     *
     * @param int connectionIndex Index/key for upload to remove
     *
     * @return string bool True if successful
     */
    deleteUpload(connectionIndex) {
    	if(!this.state.connections[connectionIndex]) { return false; }
        this.state.connections[connectionIndex].upload.abort();
        
        let connections = {...this.state['connections']};
        delete connections[connectionIndex];
        this.setState({connections: connections} );
        this.checkSession();// is session over now?
        this.checkQueue();
    }

	/**
	 * Set status message for display
	 *
	 * @param string stage Upload process status. Can be one of the following:
	 *		 idle, ready, start, upload, complete, error
	 *		
	 * @return string Text of status message to display
	 */
    statusMessage(stage) {
    	let status = '';
        if(this.statusMessages[stage]) {
            status =  this.statusMessages[stage];
        } 
        this.setState({status: status});
        return status;
    }
    
    /**
	 * Set error message and type. Message is displayed to end user, and type is employed
	 * to determine how much of the UI to make responsive due to the error state.
	 *
	 * @param string error Error code. Can be one of the following:
	 *		userquota, sessionfilelimit, filesizelimit, systemquota
	 * @param int type Type of error. Can be one of the following:
	 *		ERR_RECOVERABLE (1) = User can use UI to clear error state
	 *		ERR_BLOCKING (2) = Error state is not user-recoverable; UI is locked
	 *
	 * @return string Error message to display; error if invalid error state is passed.
	 */
    setError(error, type) {
    	let errorMessage = '';
        if(this.errorMessages[error]) {
       		errorMessage = this.errorMessages[error];
       	} else {
       		console.log('Invalid error state', error);
       		return false;
       	}
       	if ((type !== ERR_BLOCKING) && (type !== ERR_RECOVERABLE)) {
       		console.log('Invalid error type', type);
       		return false;
       	}
       	
        this.setState({error: error, errorMessage: errorMessage, errorType: type, status: this.statusMessages['error']});
        return errorMessage;
    }
    
    /**
     * Clear error message. If error parameter is passed, only the specified message will 
     * be cleared if it is present.
     *
     * @param string error Optional error state to clear. If set, only the specified error 
     *		is cleared if present. All other error states will be left in as-is.
     *
     * @return void
     */
    clearError(error=null) {
    	if((error === null) || (this.state.error === error)) {
    		this.setState({error: null, errorMessage: null, errorType: null});
    		this.statusMessage('READY');
    	}
    }

	/**
     * Fetch recent upload list from the server
     * 
     * @return void
     */
    getRecentListData() {
        let that = this;
        axios.post(this.props.endpoint + '/recent', {}, {
            params: {}
        }).then(function(response) {
            let state = {
            	recentList: response.data.recent
            };
            
            if (response.data.storageAvailableDisplay) {
				state.storageUsage = response.data.storageUsageDisplay;
				state.storageUsageBytes = response.data.storageUsage;
				state.storageAvailable = response.data.storageAvailableDisplay;
				state.storageAvailableBytes = response.data.storageAvailable;
				state.storageFileCount = response.data.fileCount;
				state.storageDirectoryCount = response.data.directoryCount;
				
				if (state.storageUsageBytes > state.storageAvailableBytes) {
					that.setError('userquota', ERR_BLOCKING);
				}
			}
            that.setState(state);

        });
    }

    /**
     *
     */
  render() {
  	const storageUsage = this.state.storageUsage;
  	const storageAvailable = this.state.storageAvailable;
  	const storageUsageBytes = this.state.storageUsageBytes;
  	const storageAvailableBytes = this.state.storageAvailableBytes;
  	const fileCount = this.state.storageFileCount;
  	const directoryCount = this.state.storageDirectoryCount;
  	
  	const showStartControl = ((this.queueLength() + this.numConnections()) > 0) && !this.state.error;
  
  	const disableUploads = (this.state.errorType === ERR_BLOCKING);
    return (
        <div>
        	<MediaUploaderStats 
        		storageUsage={storageUsage} 
        		storageAvailable={storageAvailable} 
        		storageFileCount={fileCount} 
        		storageDirectoryCount={directoryCount} 
        		storageUsageBytes={storageUsageBytes} 
        		storageAvailableBytes={storageAvailableBytes}
        	/>
            <div className="row">
              <div className="col-md-11">
                  <div className="row mediaUploaderDropZone">
                      <div className="col-md-6 offset-md-4 mt-3">
                          <Dropzone multiple={true} onDrop={acceptedFiles => {this.selectFiles(acceptedFiles)}} disabled={disableUploads}>
                              {({getRootProps, getInputProps}) => (
                                  <div {...getRootProps()} className='row mediaUploaderDropZoneInput'>
                                      <div className='col-md-2'>
                                          <input {...getInputProps()}/>
                                          <i className="fa fa-plus-circle fa-4x" aria-hidden="true"></i>
                                      </div>
                                      <div className='col-md-7 align-self-center'>
                                          <h4>Drag media here or click to browse</h4>
                                      </div>
                                  </div>
                                  )}
                          </Dropzone>
                      </div>
                  </div>
              </div>
          </div>
          <div className="row">
              <div className="col-md-11 mt-1">
                  <h4 className="mediaUploaderError">{this.state.errorMessage}</h4>
              </div>
          </div>
          <div className="row">
			 <div className="col-md-2">
				 <span className="me-2">{showStartControl ? <Button variant="primary" onClick={this.start}><i className="fa fa-play-circle fa-2x" aria-hidden="true"></i></Button> : ''}</span>
				 <span>{(showStartControl && !this.state.paused) ? <Button variant="outline-secondary" onClick={this.pauseUploads}><i className="fa fa-pause-circle fa-2x" aria-hidden="true"></i></Button> : ''}</span>
			  </div>
			 <div className="col-md-9">
				<h4 className="mediaUploaderStatus float-right">{this.state.status}</h4>
			</div>
          </div>
            <div className="row mt-3">
              <div className="col-md-11">
                 <MediauploaderQueueProgress totalFiles={this.state.filesSelected} filesUploaded={this.state.filesUploaded}/>
              </div>
            </div>
            <div className="row mt-3">
              <div className="col-md-11">
                    <MediauploaderUploadList uploads={this.state.connections} deleteCallback={this.deleteUpload} />
              </div>
            </div>
            <div className="row mt-3">
              <div className="col-md-11">
                  <MediauploaderQueueList queue={this.state.queue} deleteCallback={this.deleteQueuedUpload} resetQueueCallback={this.resetQueue}/>
              </div>
            </div>
            <div className="row mt-3">
                <div className="col-md-11">
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
            items.push(<MediauploaderUploadItem key={i} item={this.props.uploads[i]} index={i} deleteCallback={this.props.deleteCallback}/>);
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
            items.push(<MediauploaderQueueItem key={i} item={this.props.queue[i]} index={i} deleteCallback={this.props.deleteCallback}/>);
        }

        if (items.length > 0) {
            return <div>
                <h2>
                	Queued ({items.length})
                	<Button variant="outline-secondary" className="mediaUploaderQueueItemDelete" onClick={this.props.resetQueueCallback}><i className="fa fa-trash"
                                                                             aria-hidden="true"></i></Button>
                </h2>
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
    
    truncateFileName(f) {
		let l = 25;
		let tl = 10;
		if (f.length > l) {
			return f.substr(0, l-tl) + '...' + f.substr(f.length-tl, f.length);
		}
		return f;
	}

    render() {
        return <div className="row" style={{breakInside: 'avoid'}}>
            <div className="col-md-2">
                <Button variant="outline-secondary" className="mediaUploaderQueueItemDelete" onClick={this.deleteItem}><i className="fa fa-trash"
                                                                             aria-hidden="true"></i></Button>
            </div>
            <div className="col-md-10">
                <a href='#' className='mediaUploaderFilename' title={this.props.item.name}>{this.truncateFileName(this.props.item.name)}</a>
                <br/>{fileSize(this.props.item.size, {round: 1, base: 10})}
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
            items.push(<MediauploaderRecentItem key={i} item={this.props.sessions[i]}/>);
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
        let n = item.num_files; 
        let examplePaths = Object.keys(item.files).slice(0, 3);

        let contentsStr = examplePaths.join(", ");
        if (n > 3) {
            contentsStr += ' and ' + (n - 3) + ' more';
        }

        let status, statusClass, current;
        if (item.cancelled > 0) {
            current = 'Cancelled: ' + item.completed_on;
            status = 'Cancelled';
            statusClass = 'badge badge-danger pull-right';
        } else if (parseInt(item.error_code) > 0) {
            current = 'Error: ' + item.error_display;
            status = 'Error';
            statusClass = 'badge badge-danger pull-right';
        } else if (item.completed_on) {
            current = 'Completed: ' + item.completed_on;
            status = 'Completed';
            statusClass = 'badge badge-success pull-right';
        } else {
            current = "Last activity: " + item.last_activity_on;
            status = 'Incomplete';
            statusClass = 'badge badge-warning pull-right';
        }

        return <div className="col-md-4 mt-4">
                <div className="card mediaUploaderRecentItem">
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

class MediaUploaderStats extends React.Component {
	
    constructor(props) {
        super(props);
    }
    
    /**
     *
     */
  render() {
  	let storageUsage = this.props.storageUsage;
  	let storageAvailable = this.props.storageAvailable;
  	let fileCount = this.props.storageFileCount;
  	let directoryCount = this.props.storageDirectoryCount;
  	
	let storageExceeded = (this.props.storageUsageBytes > this.props.storageAvailableBytes) ? 'Storage allocation exceeded' : '';

	return ReactDOM.createPortal(
		<div className="row">
			<div className="col-md-12">
				{storageExceeded ? (<div className="mediaUploaderStorageHeading">Storage</div>) : null}
				<div className="mediaUploaderStorageError">{storageExceeded}</div>
				<ul className="mediaUploaderInfo">
					<li>Available: {storageAvailable}</li>
					<li>In use: {storageUsage}</li>
					<li>Files: {fileCount}</li>
					<li>Directories: {directoryCount}</li>
				</ul>
			</div>
		</div>,
		document.querySelector('#mediaUploaderStats')
  	);
  }
}

ReactDOM.render(<MediaUploader maxConcurrentConnections={maxConcurrentUploads} maxFileSize={maxFileSize} maxFilesPerSession={maxFilesPerSession} endpoint={endpoint}/>, document.querySelector(selector));
