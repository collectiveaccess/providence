import React, { Component } from "react";
import SearchOptions from "./SearchOptions";

class Search extends Component {
  constructor(props) {
    super(props);
  }
  render() {
    return (
      <div>
        <SearchOptions data={this.props.data} 
        	handleSearchParams={this.props.handleSearchParams} 
        	filteredData={this.props.filteredData} 
        	users={this.props.users} 
        	endpoint={this.props.endpoint}
        />
      </div>
    );
  }
}

export default Search;
