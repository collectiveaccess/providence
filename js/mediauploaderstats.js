'use strict';
import React from 'react';
import ReactDOM from 'react-dom';

const axios = require('axios');
const tus = require("tus-js-client");
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const selector = providenceUIApps.mediauploaderstats.selector;
const endpoint = providenceUIApps.mediauploaderstats.endpoint;

class MediaUploaderStats extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
            storageUsage: '-',
            storageUsageBytes: 0,
            storageAvailable: '-',
            storageAvailableBytes: 0,
            storageFileCount: 0,
            storageDirectoryCount: 0
        };

        this.load = this.load.bind(this);
    }
    
    load() {
    	let that = this;
    	let state = this.state;
    	
    	axios.post(this.props.endpoint + '/storage', {}, {
                params: {}
            }).then(function (response) {
                if(response.data.storageAvailableDisplay) {
					state.storageUsage = response.data.storageUsageDisplay;
					state.storageUsageBytes = response.data.storageUsage;
					state.storageAvailable = response.data.storageAvailableDisplay;
					state.storageAvailableBytes = response.data.storageAvailable;
					state.storageFileCount = response.data.fileCount;
					state.storageDirectoryCount = response.data.storageDirectoryCount;
				}
                that.setState(state);
            });
    }
    
    componentDidMount() {
    	this.load();
    }
    
    /**
     *
     */
  render() {
  	let storageUsage = this.state.storageUsage;
  	let storageAvailable = this.state.storageAvailable;
  	let fileCount = this.state.storageFileCount;
  	let directoryCount = this.state.storageDirectoryCount;
  	
    return (
		<div className="row">
			<div className="col-md-12">
				<MediaUploaderInfo storageUsage={storageUsage} storageAvailable={storageAvailable} 
				storageUsageBytes={this.state.storageUsageBytes} storageAvailableBytes={this.state.storageAvailableBytes}
				fileCount={fileCount} directoryCount={directoryCount}/>
			</div>
		</div>
    );
  }
}

class MediaUploaderInfo extends React.Component {
    render() {
		let storage_exceeded = (this.props.storageUsageBytes > this.props.storageAvailableBytes) ? "Storage allocation exceeded" : "";
		return <div className="mediaUploaderInfo">
				<h2>Storage</h2>
				<h4 className="mediaUploaderStorageError">{storage_exceeded}</h4>
				<ul className="mediaUploaderInfo">
					<li>Available: {this.props.storageAvailable}</li>
					<li>In use: {this.props.storageUsage}</li>
					<li>Files: {this.props.fileCount}</li>
					<li>Directories: {this.props.directoryCount}</li>
				</ul>
			</div>;
    }
}


ReactDOM.render(<MediaUploaderStats endpoint={endpoint}/>, document.querySelector(selector));
