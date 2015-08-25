/******************************************************************************
 **
 ** Copyright (C) 2002 - 2008 B. M. Bolstad
 ** 
 ** Written by: B. M. Bolstad  <bmb@bmbolstad.com>
 ** Implementation dates: 2002-2008
 **
 ** Background: this file is named rma_background4.c reflecting its history
 ** but named distinctly, since it's history is somewhat muddled, and it no longer
 ** provides the full functionality of earlier versions. Instead it stabilizes 
 ** on the most recent implementations.
 **
 *****************************************************************************/

#include "pNorm.h"
#include "dNorm.h"

void rma_bg_parameters(double *PM, double *param, size_t rows, size_t cols, size_t column){

  size_t i = 0;
  double PMmax;

  double sd,alpha;
  int n_less=0,n_more=0;
  double *tmp_less = (double *)Calloc(rows,double);
  double *tmp_more = (double *)Calloc(rows,double);
  
  PMmax = max_density(PM,rows, cols, column);
  
  int index;

  for (i = 0; i < rows; i++) {
    index = (column * rows) + i;
    if (PM[column*rows +i] < PMmax){
      tmp_less[n_less] = PM[column*rows +i];
      n_less++;
    }
  }  

  PMmax = max_density(tmp_less,n_less,1,0);
  sd = get_sd(PM,PMmax,rows,cols,column)*0.85; 

  int index;
  for (i = 0; i < rows; i++) {
    index = (column * rows) + i;
    if (PM[index] > PMmax) {
      tmp_more[n_more] = PM[index];
      n_more++;
    }
  }

  /* the 0.85 is to fix up constant in above */
  alpha = get_alpha(tmp_more,PMmax,n_more);

  param[0] = alpha;
  param[1] = PMmax;
  param[2] = sd;

  Free(tmp_less);
  Free(tmp_more);
}

/**
 * Compute the standard normal distribution function
 * @param  x Value to compute the function of
 * @return   Returns the standard normal distribution function
 */
static double Phi(double x){
  return pnorm5(x, 0.0, 1.0, 1,0);
}

/**
 * Compute the standard normal density.
 * @param  x Value to compute the function of
 * @return   Returns the standard normal density.
 */
static double phi(double x){
  return dnorm4(x, 0.0, 1.0, 0);
}

/**
 * Adjust the values in PM by the appropriate amounts
 * @param PM     Matrix of dim. rows by cols. Represents the perfect match pairs.
 * @param param  param[0] = alpha, param[1] = mu, param[2] = sigma
 * @param rows   Number of rows in PM matrix
 * @param cols   Number of cols in PM matrix
 * @param column Which column we should use
 */
void rma_bg_adjust(double *PM, double *param, size_t rows, size_t cols, size_t column){
  size_t i;
  double a;
  int index;

  for (i = 0; i < rows; i++) {
    index = column * rows + i;
    a = PM[index] - param[1] - (param[0] * param[2] * param[2]); 
    PM[index] = a + param[2] * phi(a / param[2]) / Phi(a / param[2]);
  }
}

/**
 * Performs background correction on PM
 * @param PM   The prime match probes
 * @param rows Number of rows in the matrix
 * @param cols Number of columns in the matrix
 */
void rma_bg_correct(double *PM, size_t rows, size_t cols){
  size_t j;
  double param[3];
  for (j = 0; j < cols; j++){
    rma_bg_parameters(PM, param, rows, cols, j);
    rma_bg_adjust(PM, param, rows, cols, j);
  }
}