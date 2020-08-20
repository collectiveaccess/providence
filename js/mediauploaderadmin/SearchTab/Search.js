import React, { Component } from "react";
import SearchOptions from "./SearchOptions";

class Search extends Component {
  constructor(props) {
    super(props);
  }
  render() {
    return (
      <div>
        <SearchOptions data={this.props.data} handleDateChange={this.props.handleDateChange} dateFilteredData={this.props.dateFilteredData} />
      </div>
    );
  }
}

export default Search;
