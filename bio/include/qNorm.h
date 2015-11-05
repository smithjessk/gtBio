#ifndef QUANTILE_NORM
#define QUANTILE_NORM

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
   * Sort a particular column in ascending order.
   * @param data      The data whose column will be sorted in-place.
   * @param indices   After this function is done, entry (i, col_index) will 
   *                  hold the original entry (i, col_index) before any sorting
   *                  was done.
   * @param col_index The column to sort.
   */
  void reversible_column_sort(arma::mat &data, arma::umat &indices, 
    long col_index) {
    for (size_t i = 0; i < data.n_rows; i++) {
      indices(i, col_index) = i;
    }
    arma::vec data_col(data.colptr(col_index), data.n_rows, false);
    arma::uvec indices_col(indices.colptr(col_index), data.n_rows, false);
    quicksort(data_col, indices_col, 0, data.n_rows - 1);
  }

  /**
   * Unsort a particular column given the pre-sort indices.
   * @param data      Reference to the data to unsort.
   * @param indices   Reference to the indices as returned by 
   *                  reversible_column_sort.
   * @param col_index The column to sort.
   */
  void rearrange_column(arma::mat &data, arma::umat &indices, long col_index) {
    arma::mat copy_of_data(data.memptr(), data.n_rows, data.n_cols);
    for (size_t i = 0; i < indices.n_rows; i++) {
      uint sorted_index = indices(i, col_index);
      data(i, col_index) = copy_of_data(sorted_index, col_index);
    }
  }
} // namespace gtBio

#endif // QUANTILE_NORM