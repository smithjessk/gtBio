/**
 * Note: The structure of these checks has been carefully thought through.
 * For example, if x == mu and sigma == 0, we get the correct answer 1.
 * @source https://svn.r-project.org/R/trunk/src/nmath/pnorm.c
 */

#include <float.h>

#define SIXTEN  16 /* Cutoff allowing exact "*" and "/" */

#define R_D__0  (log_p ? ML_NEGINF : 0.)    /* 0 */
#define R_D__1  (log_p ? 0. : 1.)     /* 1 */
#define R_DT_0  (lower_tail ? R_D__0 : R_D__1)    /* 0 */
#define R_DT_1  (lower_tail ? R_D__1 : R_D__0)    /* 1 */
#define R_D_half (log_p ? -M_LN2 : 0.5)   // 1/2 (lower- or upper tail)

#ifndef M_SQRT_32
#define M_SQRT_32 5.656854249492380195206754896838  /* sqrt(32) */
#endif

#ifndef M_1_SQRT_2PI
#define M_1_SQRT_2PI  0.398942280401432677939946059934  /* 1/sqrt(2pi) */
#endif

#define ISNAN(x) (isnan(x)!=0)

#define ML_POSINF (1.0 / 0.0)
#define ML_NEGINF ((-1.0) / 0.0)
#define ML_NAN    (0.0 / 0.0)

void pnorm_both(double x, double *cum, double *ccum, int i_tail, int log_p)
{
/* i_tail in {0,1,2} means: "lower", "upper", or "both" :
   if(lower) return  *cum := P[X <= x]
   if(upper) return *ccum := P[X >  x] = 1 - P[X <= x]
*/
    const static double a[5] = {
  2.2352520354606839287,
  161.02823106855587881,
  1067.6894854603709582,
  18154.981253343561249,
  0.065682337918207449113
    };
    const static double b[4] = {
  47.20258190468824187,
  976.09855173777669322,
  10260.932208618978205,
  45507.789335026729956
    };
    const static double c[9] = {
  0.39894151208813466764,
  8.8831497943883759412,
  93.506656132177855979,
  597.27027639480026226,
  2494.5375852903726711,
  6848.1904505362823326,
  11602.651437647350124,
  9842.7148383839780218,
  1.0765576773720192317e-8
    };
    const static double d[8] = {
  22.266688044328115691,
  235.38790178262499861,
  1519.377599407554805,
  6485.558298266760755,
  18615.571640885098091,
  34900.952721145977266,
  38912.003286093271411,
  19685.429676859990727
    };
    const static double p[6] = {
  0.21589853405795699,
  0.1274011611602473639,
  0.022235277870649807,
  0.001421619193227893466,
  2.9112874951168792e-5,
  0.02307344176494017303
    };
    const static double q[5] = {
  1.28426009614491121,
  0.468238212480865118,
  0.0659881378689285515,
  0.00378239633202758244,
  7.29751555083966205e-5
    };

    double xden, xnum, temp, del, eps, xsq, y;
#ifdef NO_DENORMS
    double min = DBL_MIN;
#endif
    int i, lower, upper;

#ifdef IEEE_754
    if(ISNAN(x)) { *cum = *ccum = x; return; }
#endif

    /* Consider changing these : */
    eps = DBL_EPSILON * 0.5;

    /* i_tail in {0,1,2} =^= {lower, upper, both} */
    lower = i_tail != 1;
    upper = i_tail != 0;

    y = fabs(x);
    if (y <= 0.67448975) { /* qnorm(3/4) = .6744.... -- earlier had 0.66291 */
  if (y > eps) {
      xsq = x * x;
      xnum = a[4] * xsq;
      xden = xsq;
      for (i = 0; i < 3; ++i) {
    xnum = (xnum + a[i]) * xsq;
    xden = (xden + b[i]) * xsq;
      }
  } else xnum = xden = 0.0;

  temp = x * (xnum + a[3]) / (xden + b[3]);
  if(lower)  *cum = 0.5 + temp;
  if(upper) *ccum = 0.5 - temp;
  if(log_p) {
      if(lower)  *cum = log(*cum);
      if(upper) *ccum = log(*ccum);
  }
    }
    else if (y <= M_SQRT_32) {

  /* Evaluate pnorm for 0.674.. = qnorm(3/4) < |x| <= sqrt(32) ~= 5.657 */

  xnum = c[8] * y;
  xden = y;
  for (i = 0; i < 7; ++i) {
      xnum = (xnum + c[i]) * y;
      xden = (xden + d[i]) * y;
  }
  temp = (xnum + c[7]) / (xden + d[7]);

#define do_del(X)             \
  xsq = trunc(X * SIXTEN) / SIXTEN;       \
  del = (X - xsq) * (X + xsq);          \
  if(log_p) {             \
      *cum = (-xsq * xsq * 0.5) + (-del * 0.5) + log(temp); \
      if((lower && x > 0.) || (upper && x <= 0.))     \
      *ccum = log1p(-exp(-xsq * xsq * 0.5) *    \
        exp(-del * 0.5) * temp);    \
  }               \
  else {                \
      *cum = exp(-xsq * xsq * 0.5) * exp(-del * 0.5) * temp;  \
      *ccum = 1.0 - *cum;           \
  }

#define swap_tail           \
  if (x > 0.) {/* swap  ccum <--> cum */      \
      temp = *cum; if(lower) *cum = *ccum; *ccum = temp;  \
  }

  do_del(y);
  swap_tail;
    }

/* else   |x| > sqrt(32) = 5.657 :
 * the next two case differentiations were really for lower=T, log=F
 * Particularly  *not*  for  log_p !

 * Cody had (-37.5193 < x  &&  x < 8.2924) ; R originally had y < 50
 *
 * Note that we do want symmetry(0), lower/upper -> hence use y
 */
    else if((log_p && y < 1e170) /* avoid underflow below */
  /*  ^^^^^ MM FIXME: can speedup for log_p and much larger |x| !
   * Then, make use of  Abramowitz & Stegun, 26.2.13, something like

   xsq = x*x;

   if(xsq * DBL_EPSILON < 1.)
      del = (1. - (1. - 5./(xsq+6.)) / (xsq+4.)) / (xsq+2.);
   else
      del = 0.;
   *cum = -.5*xsq - M_LN_SQRT_2PI - log(x) + log1p(-del);
   *ccum = log1p(-exp(*cum)); /.* ~ log(1) = 0 *./

   swap_tail;

   [Yes, but xsq might be infinite.]

  */
      || (lower && -37.5193 < x  &&  x < 8.2924)
      || (upper && -8.2924  < x  &&  x < 37.5193)
  ) {

  /* Evaluate pnorm for x in (-37.5, -5.657) union (5.657, 37.5) */
  xsq = 1.0 / (x * x); /* (1./x)*(1./x) might be better */
  xnum = p[5] * xsq;
  xden = xsq;
  for (i = 0; i < 4; ++i) {
      xnum = (xnum + p[i]) * xsq;
      xden = (xden + q[i]) * xsq;
  }
  temp = xsq * (xnum + p[4]) / (xden + q[4]);
  temp = (M_1_SQRT_2PI - temp) / y;

  do_del(x);
  swap_tail;
    } else { /* large x such that probs are 0 or 1 */
  if(x > 0) { *cum = R_D__1; *ccum = R_D__0;  }
  else {          *cum = R_D__0; *ccum = R_D__1;  }
    }


#ifdef NO_DENORMS
    /* do not return "denormalized" -- we do in R */
    if(log_p) {
  if(*cum > -min)  *cum = -0.;
  if(*ccum > -min)*ccum = -0.;
    }
    else {
  if(*cum < min)   *cum = 0.;
  if(*ccum < min) *ccum = 0.;
    }
#endif
    return;
}

double pnorm5(double x, double mu, double sigma, int lower_tail, int log_p) {
  double p, cp;

  #ifdef IEEE_754
    if(ISNAN(x) || ISNAN(mu) || ISNAN(sigma)) return x + mu + sigma;
  #endif

  if(!std::isfinite(x) && mu == x) return ML_NAN; /* x-mu is NaN */
    if (sigma <= 0) {
  if(sigma < 0) return ML_NAN; // TODO: Throw error
  /* sigma = 0 : */
  return (x < mu) ? R_DT_0 : R_DT_1;
    }
    p = (x - mu) / sigma;
    if(!std::isfinite(p))
  return (x < mu) ? R_DT_0 : R_DT_1;
    x = p;

    pnorm_both(x, &p, &cp, (lower_tail ? 0 : 1), log_p);

    return(lower_tail ? p : cp);
}