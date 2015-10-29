#include <armadillo>
#include <math.h>

namespace gtBio {
  /**
   * Perform quicksort on data while also swapping the appropriate values in 
   * indices
   * @param data    Column vector to sort in place
   * @param indices Indices to swap
   * @param left    Leftmost index of the partition
   * @param right   Rightmost index of the partition
   */
  void quicksort(arma::vec &data, arma::uvec &indices, int left, int right) {
    int i = left, j = right;
    int temp;
    int pivot = data((left + right) / 2);

    while (i <= j) {
      while (data(i) < pivot) {
        i++;
      }
      while (data(j) > pivot){
        j--;
      }
      if (i <= j) {
        temp = data(i);
        data(i) = data(j);
        data(j) = temp;
        temp = indices(i);
        indices(i) = indices(j);
        indices(j) = temp;
        i++;
        j--;
      }
    };

    if (left < j) {
      quicksort(data, indices, left, j);
    }
    if (i < right) {
      quicksort(data, indices, i, right);
    }
  }

  /**
   * Sort each column of data in ascending order. Return a matrix where entry
   * (i, j) holds the original index of entry (i, j) in column j before any 
   * sorting was done.
   * @param  data Matrix that will be sorted in-place
   * @return      
   */
  arma::umat reversible_sort(arma::mat &data) {
    arma::umat indices(data.n_rows, data.n_cols);
    for (size_t i = 0; i < data.n_rows; i++) {
      for (size_t j = 0; j < data.n_cols; j++) {
        indices(i, j) = i;  
      }
    }
    for (size_t j = 0; j < data.n_cols; j++) {
      arma::vec data_col(data.colptr(j), data.n_rows, false);
      arma::uvec indices_col(indices.colptr(j), data.n_rows, false);
      quicksort(data_col, indices_col, 0, data.n_rows - 1);
    }
    return indices;
  }

  /**
   * Unsort data given the previous indices.
   * @param data    Reference to the data to be unsorted.
   * @param indices Indices as returned by reversible_sort
   */
  void rearrange(arma::mat &data, arma::umat &indices) {
    arma::mat copy_of_data(data.memptr(), data.n_rows, data.n_cols);
    for (size_t i = 0; i < indices.n_rows; i++) {
      for (size_t j = 0; j < indices.n_cols; j++) {
        uint index = indices(i, j);
        data(i, j) = copy_of_data(index, j);
      }
    }
  }

  /**
   * Perform quantile normalization as described in Bolstad 2001:
   * http://bmbolstad.com/stuff/qnorm.pdf
   * @param  data Each dataset is a column
   * @return      The normalized data set
   */
  arma::mat quantile_normalize(arma::mat data) {
    double fill_value = 1 / sqrt(data.n_cols);
    arma::mat diagonal = arma::vec(data.n_cols);
    diagonal.fill(fill_value);
    arma::umat indices = reversible_sort(data);
    data = data * diagonal * diagonal.t() / accu(square(diagonal));
    rearrange(data, indices);
    return data;
  }
}