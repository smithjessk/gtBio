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
  void quicksort(arma::vec &data, arma::vec &indices, int left, int right) {
    int i = left, j = right;
    int temp;
    int pivot = data((left + right) / 2);

    // Partition
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

    // Recursion
    if (left < j) {
      quickSort(arr, left, j);
    }
    if (i < right) {
      quickSort(arr, i, right);
    }
  }

  /**
   * Sort each column of data in ascending order. Return a matrix where entry
   * (i, j) holds the original index of entry (i, j) in column j before any 
   * sorting was done.
   * @param  data Matrix that will be sorted in-place
   * @return      
   */
  arma::mat reversible_sort(arma::mat &data) {
    arma::mat indices(data.n_rows, data.n_cols);
    arma::vec init_indices(data.n_rows);
    for (size_t i = 0; i < data.n_rows; i++) {
      init_indices(i) = i;
    }
    for (size_t j = 0; j < data.n_cols; j++) {
      indices(j) = init_indices;
    }
    for (size_t j = 0; j < data.n_cols; j++) {
      quicksort(data(j), indices(j), 0, data.n_rows);
    }
    return indices;
  }

  /**
   * Unsort data given the previous indices.
   * @param data    Reference to the data to be unsorted.
   * @param indices Indices as returned by reversible_sort
   */
  void rearrange(arma::mat &data, arma::mat &indices) {
    arma::mat copy_of_data(data.memptr(), data.n_rows, data.n_cols);
    for (size_t i = 0; i < data.n_rows; i++) {
      for (size_t j = 0; j < data.n_cols; j++) {
        data(i, indices(i, j)) = copy_of_data(i, j);
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
    arma::mat indices = reversible_sort(data);
    data = data * square(diagonal);
    rearrange(data, indices);
    return data;
  }
}