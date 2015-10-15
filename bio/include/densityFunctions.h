#ifndef DENSITY_FUNCTIONS
#define DENSITY_FUNCTIONS

#include <armadillo>
#include <algorithm>
#include <cmath>
#include "pnorm.c"

namespace gtBio {
  /**
   * Compute the standard normal density
   * @param  x Value to compute phi of
   * @return   The standard normal density evaluated at x
   */
  double phi(double x) {
    return dnorm4(x, 0.0, 1.0, 0);
  }

  /**
   * Compute the standard normal distribution function
   * @param  x Value to compute Phi of
   * @return   The standard normal distr. func. evaluated at x
   * @source https://github.com/bmbolstad/preprocessCore/blob/master/src/rma_background4.c#L270
   */
  double Phi(double x) {
    return pnorm5(x, 0.0, 1.0, 1,0);
  }

  /**
   * Returns the max of x
   * @param  x      Pointer to data vector
   * @param  length Number of entries in vector
   * @return        [description]
   */
  double find_max(double *x, int length) {
    double max = x[0];
    for (size_t i = 1; i < length; i++) {
      if (x[i] > max){
        max = x[i];
      }
    }
    return max;
  }

  /**
   * Compute the standard deviation of a data vector
   * @param  x      Data vector
   * @param  length Length of the vector
   * @return        The standard deviation of the vector
   * @source https://github.com/bmbolstad/RMAExpress/blob/master/Preprocess/weightedkerneldensity.c#L408
   */
  double compute_sd(double *x, int length) {
    int i;
    double sum = 0.0, sum2 = 0.0;
    for (i = 0; i < length; i++){
      sum += x[i];
    }
    sum = sum / (double)(length);
    for (i = 0; i < length; i++){
      sum2 += (x[i] - sum) * (x[i] - sum);
    }
    return(sqrt(sum2 / (double) (length-1) ));
  }

  /**
   * Compute the bandwidth for the kernel
   * @param  x      Data vector
   * @param  length Length of x
   * @param  iqr    Interquartile range
   * @return        Return the bandwidth, a smoothing paramter for the kernel
   * @source https://github.com/bmbolstad/RMAExpress/blob/master/Preprocess/weightedkerneldensity.c#L437
   */
  double bandwidth(double *x, int length, double iqr){
    double hi = compute_sd(x, length);
    double lo;
    if (hi > iqr){
      lo = iqr / 1.34;
    } else {
      lo = hi;
    }
    if (lo == 0){
      if (hi !=0){
        lo = hi;
      } else if (fabs(x[1]) != 0){
        lo = fabs(x[1]);
      } else {
        lo = 1.0;
      }
    }
    return (0.9 * lo * pow((double) length, -0.2));
  }

  /**
   * See AS R50 and AS 176  (AS = Applied Statistics). 
   * Idea is to discretize the data. Does not put weights on each observation.
   * @param x     the data
   * @param nx    length of the data
   * @param xlow  minimum value in x dimension
   * @param xhigh maximum value in x dimension
   * @param y     on output will contain discretation scheme of data
   * @param ny    length of y 
   * @source https://github.com/bmbolstad/preprocessCore/blob/master/src/rma_background4.c#L98
   */
  void unweighted_massdist(double *x, int *nx, double *xlow, double *xhigh, 
  double *y, int *ny) {
    double fx, xdelta, xpos;
    int i, ix, ixmax, ixmin;

    ixmin = 0;
    ixmax = *ny - 2;
    xdelta = (*xhigh - *xlow) / (*ny - 1);
    
    for(i = 0; i < *ny ; i++){
      y[i] = 0.0;
    }

    for(i = 0; i < *nx ; i++) {
      if(finite(x[i])) {
        xpos = (x[i] - *xlow) / xdelta;
        ix = (int)floor(xpos);
        fx = xpos - ix;
        if(ixmin <= ix && ix <= ixmax) {
          y[ix] += (1 - fx);
          y[ix + 1] +=  fx;
        } else if(ix == -1) {
          y[0] += fx;
        } else if(ix == ixmax + 1) {
          y[ix] += (1 - fx);
        }
      }
    }
    for(i = 0; i < *ny; i++) {
      y[i] *= (1.0 / (double) (*nx));
    }
  }

  /**
   * Find twiddle factor for the FFT
   * @param N       Length of data series
   * @param i       TODO: Find description
   * @param tf_real On output, contains real part of twiddle factor
   * @param tf_imag On output, contains imaginary part of twiddle factor
   * @source https://github.com/Bioconductor-mirror/preprocessCore/blob/40fbd94748a5ca7723f2ead9e590163eab6ec548/src/weightedkerneldensity.c#L176
   */
  void twiddle(int N, int i, double *tf_real, double *tf_imag){
    double pi = 3.14159265358979323846; 
    if (i ==0){
      *tf_real = 1;
      *tf_imag = 0;
    } else {
      *tf_real = cos(2*pi*(double)i/(double)N);  
      *tf_imag = -sin(2*pi*(double)i/(double)N); 
    }
  }

  /**
   * Twiddle factor in FFT when computing inverse FFT
   * @param N       Length of data series
   * @param i       TODO: Update this
   * @param tf_real On output, contains real part of twiddle factor
   * @param tf_imag On output, contains imaginary part of twiddle factor
   */
  void twiddle2(int N, int i, double *tf_real, double *tf_imag) {
    double pi = 3.14159265358979323846; 
    if (i ==0){
      *tf_real = 1;
      *tf_imag = 0;
     } else {
      *tf_real = cos(2*pi*(double)i/(double)N);  
      *tf_imag = sin(2*pi*(double)i/(double)N); 
    }
  } 

  /**
   * Compute the FFT in place using Decimation in Frequency of a data sequence
   * of length 2^p. NOTE: Result is in reverse bit order.
   * @param f_real Real component of data series
   * @param f_imag Imaginary component of data series
   * @param p      2^p is length of data series.
   * @source https://github.com/bmbolstad/RMAExpress/blob/master/Preprocess/weightedkerneldensity.c#L233
   */
  void fft_dif(double *f_real, double *f_imag, int p) {
    int BaseE, BaseO, i, j, k, Blocks, Points, Points2;
    double even_real, even_imag, odd_real, odd_imag;
    double tf_real, tf_imag;

    Blocks = 1;
    Points = 1 << p;
  
    for (i=0; i < p; i++) {
      Points2 = Points >> 1;
      BaseE = 0;
      for (j=0; j < Blocks; j++) {
        BaseO = BaseE + Points2;
        for (k =0; k < Points2; k++) {
          even_real = f_real[BaseE + k] + f_real[BaseO + k]; 
          even_imag = f_imag[BaseE + k] + f_imag[BaseO + k];  
          twiddle(Points, k, &tf_real, &tf_imag); 
          odd_real = (f_real[BaseE + k] - f_real[BaseO + k]) * tf_real - 
            (f_imag[BaseE + k] - f_imag[BaseO + k]) * tf_imag;
          odd_imag = (f_real[BaseE + k] - f_real[BaseO + k]) * tf_imag + 
            (f_imag[BaseE + k] - f_imag[BaseO + k]) * tf_real; 
          f_real[BaseE + k] = even_real;
          f_imag[BaseE + k] = even_imag;
          f_real[BaseO + k] = odd_real;
          f_imag[BaseO + k] = odd_imag;
        } 
        BaseE = BaseE + Points;
      }                     
      Blocks = Blocks << 1; 
      Points = Points >> 1;
    }
  }

  void fft_ditI(double *f_real, double *f_imag, int p){
    int i,j,k, Blocks, Points, Points2, BaseB, BaseT;
    double top_real, top_imag, bot_real, bot_imag, tf_real, tf_imag;

    Blocks = 1 << (p-1);
    Points = 2;  
    for (i=0; i < p; i++){
      Points2 = Points >> 1;
      BaseT = 0;
      for (j=0; j < Blocks; j++){
        BaseB = BaseT+Points2;
        for (k=0; k < Points2; k++){
          top_real = f_real[BaseT+k];
          top_imag = f_imag[BaseT+k]; 
          twiddle2(Points,k,&tf_real, &tf_imag);
          bot_real = f_real[BaseB+k]*tf_real - f_imag[BaseB+k]*tf_imag;
          bot_imag = f_real[BaseB+k]*tf_imag + f_imag[BaseB+k]*tf_real;
          f_real[BaseT+k] = top_real + bot_real;
          f_imag[BaseT+k] = top_imag + bot_imag;
          f_real[BaseB+k] = top_real - bot_real;   
          f_imag[BaseB+k] = top_imag - bot_imag; 
        }     
        BaseT= BaseT + Points; 
      }
      Blocks = Blocks >> 1;
      Points = Points << 1;
    }
  } 

  /**
   * TODO: Update this
   * @param y     ve
   * @param kords No description found
   * @param n     No description found
   * @source https://github.com/bmbolstad/RMAExpress/blob/master/Preprocess/weightedkerneldensity.c#L323
   */
  void fft_density_convolve(double *y, double *kords, int n) {
    int i;
    // Ugly hack to stop rounding problems
    int nlog2 = (int)(log((double)n)/log(2.0) + 0.5); 
    double *y_imag = (double *)calloc(n,sizeof(double));
    double *kords_imag = (double *)calloc(n,sizeof(double));
    double *conv_real = (double *)calloc(n,sizeof(double));
    double *conv_imag = (double *)calloc(n,sizeof(double));

    fft_dif(y, y_imag, nlog2);
    fft_dif(kords, kords_imag, nlog2);
    
    for (i=0; i < n; i++){
      conv_real[i] = y[i]*kords[i] + y_imag[i]*kords_imag[i];
      conv_imag[i] = y[i]*(-1*kords_imag[i]) + y_imag[i]*kords[i];
    }
    
    fft_ditI(conv_real, conv_imag, nlog2);

    for (i=0; i < n; i++){
      kords[i] = conv_real[i];
    }

    free(conv_real);
    free(conv_imag);
    free(kords_imag);
    free(y_imag);
  }

  /**
   * Linearly interpolate v given x and y.
   * @param  v The value to interpolate
   * @param  x TODO: Update
   * @param  y TODO: Update
   * @param  n TODO: Update
   * @return   TODO: Update
   * @source https://github.com/bmbolstad/RMAExpress/blob/master/Preprocess/weightedkerneldensity.c#L479
   */
  double linear_interpolate_helper(double v, double *x, double *y, int n) {
    int i = 0, j = n - 1;
    if(v < x[i]) return y[0];
    if(v > x[j]) return y[n-1];
   
    /* find the correct interval by bisection */
    int ij;
    while(i < j - 1) { /* x[i] <= v <= x[j] */
      ij = (i + j)/2; /* i+1 <= ij <= j-1 */
      if(v < x[ij]) j = ij;
      else i = ij;
      /* still i < j */
    }
    /* provably have i == j-1 */
    
    /* interpolation */
    if(v == x[j]) return y[j];
    if (v == x[i]) return y[i];
    return y[i] + (y[j] - y[i]) * ((v - x[i]) / (x[j] - x[i]));
  }

  /**
   * Given x and y, interpolate linearly at xout putting the results at yout.
   * @param x      TODO: Update
   * @param y      TODO: Update
   * @param xout   TODO: Update
   * @param yout   TODO: Update
   * @param length TODO: Update
   * @source https://github.com/bmbolstad/RMAExpress/blob/master/Preprocess/weightedkerneldensity.c#L524
   */
  void linear_interpolate(double *x, double *y, double *xout, double *yout, 
  int length) {
    for(int i = 0 ; i < length; i++) {
      yout[i] = linear_interpolate_helper(xout[i], x, y, length);
    }
  }

  /**
   * Computes the kernel density in a memory efficient way
   * @param row        Row j where j = column in question
   * @param nxxx     Number of rows
   * @param output   Memory allocated for dens_y
   * @param output_x Memory allocated for dens_x
   * @param numPoints     Number of points
   * @source https://github.com/bmbolstad/RMAExpress/blob/master/Preprocess/weightedkerneldensity.c#L672
   */
  void kernel_density(double *row, int *nxxx, double *output, 
    double *output_x, int *numPoints){

    int numRows = *nxxx;
    int n = *numPoints;
    int i;
    double low, high, iqr, bw, from, to;
    double *kords = (double *) calloc(2*n, sizeof(double));
    double *buffer = row; 
    double *y = (double *) calloc(2*n, sizeof(double));
    double *xords = (double *) calloc(n, sizeof(double));

    // Sort the row
    std::sort(buffer, buffer + numRows);
    
    // Get the smallest value, largest value, and the IQR
    low  = buffer[0];
    high = buffer[numRows-1];
    iqr =  buffer[(int)(0.75 * numRows + 0.5)] - buffer[(int)(0.25 * numRows + 0.5)];

    // Compute the bandwidth
    bw = bandwidth(row, numRows, iqr);
    
    // Shift the low and high by the bandwidth
    low = low - (7 * bw);
    high = high + (7 * bw);  

    // Set points [0, n] equal to i / (2n - 1)
    for (i = 0; i <= n; i++){
      kords[i] = (double)i/(double)(2*n -1)*2*(high - low);
    }

    // Set the second half of points equal to their opposite (on the opposite 
    // side of the vector)
    for (i = n+1; i < 2*n; i++){
      kords[i] = -kords[2*n - i];
    }

    // Epanechnikov Kernel
    double a = bw * sqrt(5.0);
    for (i = 0; i < 2 * n; i++){
      if (fabs(kords[i]) < a){
        kords[i] = 3.0/(4.0 * a) * 
          (1.0 - (fabs(kords[i]) / a)*  (fabs(kords[i]) / a));
      } else {
        kords[i] = 0.0;
      }
    }

    unweighted_massdist(row, &numRows, &low, &high, y, &n);
    fft_density_convolve(y, kords, 2 * n);

    // Corrections to get into correct output range
    to = high - 4 * bw;  
    from = low + 4 * bw;

    for (i = 0; i < n; i++){
      xords[i] = (double)i/(double)(n -1)*(high - low)  + low;
      output_x[i] = (double)i/(double)(n -1)*(to - from)  + from;
    }

    for (i =0; i < n; i++){
      kords[i] = kords[i] / (2 * n);
    }

    // to get results that agree with R really need to do linear interpolation

    linear_interpolate(xords, kords, output_x, output,n);
    free(xords);
    free(y);
    free(kords);
  }

  /**
   * Find the maximum density in the matrix
   * @param  data   Matrix of dimension rows * cols
   * @param  column Column of interest
   * @return        Maximum density found
   * @source https://github.com/bmbolstad/preprocessCore/blob/master/src/rma_background4.c#L98
   */
  double max_density(double *z, size_t rows, size_t cols, size_t column) {
    size_t i;
    int numPoints = 16384;
    double max_y, max_x;
    double *dens_x = (double*) malloc(numPoints * sizeof(double));
    double *dens_y = (double*) malloc(numPoints * sizeof(double));
    double *x = (double*) malloc(rows * sizeof(double));

    for (i = 0; i < numPoints; i++) {
      dens_x[i] = 0;
      dens_y[i] = 0;
    }

    for (i = 0; i < rows; i++){
      x[i] = z[column * rows +i];
    }
  
    kernel_density(x, (int*) &rows, dens_y, dens_x, &numPoints);
    max_y = find_max(dens_y, 16384);
     
    i = 0;
    do {
      if (dens_y[i] == max_y)
        break;
      i++;
    } while(1);
     
    max_x = dens_x[i];

    free(dens_x);
    free(dens_y);
    free(x);

    return max_x;
  }

  /**
   * Estimate the sigma parameter for PM
   * @param  PM     Perfect Match Matrix
   * @param  PMmax  Mode for PMs for column
   * @param  rows   Number of rows
   * @param  cols   Number of columns
   * @param  column Column of interest
   * @return        Standard deviation for column
   * @source https://github.com/bmbolstad/preprocessCore/blob/master/src/rma_background4.c#L159
   */
  double get_sd(double *PM, double PMmax, int rows, int cols, int column) {
    double sigma;
    double tmpsum = 0.0;
    int numtop = 0;
    for (size_t i = 0; i < rows; i++) {
      if (PM[column*rows + i] < PMmax) {
        tmpsum += (PM[column * rows + i] - PMmax) * 
          (PM[column * rows + i] - PMmax);
        numtop++;
      }
    }
    sigma = sqrt(tmpsum/(numtop -1))*sqrt(2.0)/0.85;
    return sigma;
  }

  /**
   * Estimate the alpha parameter
   * @param  PM     Perfect Match Matrix. This is probably where to start...
   * @param  PMmax  Value of maximum density of PM
   * @param  length Number of entries
   * @return        Alpha parameter 
   */
  double get_alpha(double *PM, double PMmax, int length) {
    for (size_t i = 0; i < length; i++){
      PM[i] = PM[i] - PMmax;
    }
    return 1.0 / max_density(PM, length, 1, 0);
  }

  /**
   * Estimate the parameters for the background.
   * @param PM     Perfect match probes matrix
   * @param param  The parameters. param[0] = alpha, param[1] = mu, 
   *               param[2] = sigma
   * @param rows   Number of rows
   * @param cols   Number of columns
   * @param column Index of the column we're currently operating on
   * @source https://github.com/Bioconductor-mirror/preprocessCore/blob/master/src/rma_background4.c#L216
   */
  void rma_bg_parameters(double *PM, double *param, size_t rows, size_t cols, 
    size_t column) {

    double PMmax, sd, alpha;
    int n_less = 0, n_more = 0;
    double *tmp_less = (double *) calloc(rows, sizeof(double)); // Was Calloc
    double *tmp_more = (double *) calloc(rows, sizeof(double)); // Was Calloc
    size_t i = 0;
    
    PMmax = max_density(PM, rows, cols, column);
    
    for (size_t i = 0; i < rows; i++) {
      if (PM[column*rows + i] < PMmax) {
        tmp_less[n_less] = PM[column*rows + i];
        n_less++;
      }
    }

    PMmax = max_density(tmp_less, n_less, 1, 0);
    sd = get_sd(PM, PMmax, rows, cols, column) * 0.85; 

    for (size_t i = 0; i < rows; i++) {
      if (PM[column*rows + i] > PMmax) {
        tmp_more[n_more] = PM[column*rows + i];
        n_more++;
      }
    }

    /* the 0.85 is to fix up constant in above */
    alpha = get_alpha(tmp_more, PMmax, n_more);
    param[0] = alpha;
    param[1] = PMmax;
    param[2] = sd;
    free(tmp_less);
    free(tmp_more);
  }

  /**
   * Adjust the values in the perfect match probe matrix.
   * @param PM     Matrix of perfect match probes' intensity values
   * @param param  param[0] = alpha, param[1] = mu, param[2] = sigma
   * @param rows   Number of rows
   * @param cols   Number of columns
   * @param column Which column to adjust
   * @source https://github.com/bmbolstad/preprocessCore/blob/master/src/rma_background4.c#L300
   */
  void rma_bg_adjust(double *PM, double *param, size_t rows, size_t cols, size_t column){
    double a;
    for (size_t i = 0; i < rows; i++) {
      a = PM[column*rows + i] - param[1] - param[0]*param[2]*param[2]; 
      PM[column*rows + i] = a + param[2] * phi(a/param[2])/Phi(a/param[2]);
    }
  }
}

#endif // DENSITY_FUNCTIONS