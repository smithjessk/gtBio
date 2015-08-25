#include <armadillo>

#include <math.h>

namespace gtBio {
  /**
   * Performs quantile normalization as described in Bolstad 2001:
   * http://bmbolstad.com/stuff/qnorm.pdf
   * @param  data Each dataset is a column
   * @return      The normalized data set
   */
  arma::mat quantileNormalize(arma::mat data) {
    // Create a digaonal used to normalize on. For more information, see the 
    // diagrams in the above paper.
    double initOfDiagonal = 1 / sqrt(data.n_cols);
    arma::mat diagonal = arma::vec(data.n_cols);
    diagonal.fill(initOfDiagonal);

    // Sort each column (data set) in ascending order
    data = sort(data);

    // Need to keep track of the original index of each element

    arma::mat dataPrime(data.n_rows, data.n_cols);

    dataPrime = dataPrime * diagonal * diagonal.t() / accu(square(diagonal));

    return dataPrime;
  }
}