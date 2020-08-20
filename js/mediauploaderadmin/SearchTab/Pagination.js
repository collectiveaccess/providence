import React, { Component } from "react";

class Pagination extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    const pageNumbers = [];

    for (let i = 1; i <= Math.ceil(this.props.totalUploads / this.props.uploadsPerPage); i++) {
      pageNumbers.push(i);
    }

    return (
      <div>
      <nav>
        <ul className="pagination">

        {this.props.totalUploads === 0 ? ' ' :
        <li className={this.props.currentPage === 1 ? "page-item disabled " : "page-item"} id='previous'>
          <a className="page-link" aria-label="Previous" onClick={() => this.props.prevPageHandler(this.props.currentPage)}>
            <span aria-hidden="true">&laquo;</span>
            <span className="sr-only">Previous</span>
          </a>
        </li>
        }

          {pageNumbers.map((number) => {
            return (
              <li key={number} className={this.props.currentPage === number ? "page-item active " : "page-item"}>
                <a onClick={() => this.props.paginate(number)} className="page-link" href="#">
                  {number}
                </a>
              </li>
            );
          })}

        {this.props.totalUploads === 0 ? ' ' :
        <li className={this.props.currentPage === this.props.numberOfPages ? "page-item disabled " : "page-item"} id='next'>
          <a className="page-link" aria-label="Next" onClick={() => this.props.nextPageHandler(this.props.currentPage, this.props.numberOfPages)}>
            <span aria-hidden="true">&raquo;</span>
            <span className="sr-only">Next</span>
          </a>
        </li>
        }

        </ul>
      </nav>
      </div>
    );
  }
}

export default Pagination;
