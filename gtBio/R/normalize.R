# For a reference, see the following links:
# https://github.com/Bioconductor-mirror/affy/blob/master/R/justrma.R#L55
# https://github.com/Bioconductor-mirror/affy/blob/4a732dd80fabbc8e7b008428dfdea57af6bd237b/src/rma2.c#L163
RMA <- function(matrix) {
  # This is equivalent to rma_bg_correct in just.rma
  correctedMatrix <- BackgroundCorrect(states = matrix, outputs = 
    c(corrected = Matrix), should_transpose = FALSE, from_matrix = TRUE)

  # The following code is equivalent to rma_c_call in just.rma
  
  # qnorm_c on perfect match pairs
  normalizedMatrix <- QuantileNormalize(states = correctedMatrix, outputs = 
    c(normalized = Matrix), should_transpose = FALSE)

  # Equivalent to R_subColSummarize_medianpolish_log
  MedianPolish(states = c(normalizedMatrix), outputs = 
    c(polished = Matrix), should_transpose = FALSE)
}

BackgroundCorrect <- function(outputs, states, ...) {
  outputs <- substitute(outputs)
  check.atts(outputs)
  outputs <- convert.atts(outputs)
  gist <- GIST(bio::Background_Correct, ...)
  Transition(gist, outputs, states)
}

QuantileNormalize <- function(outputs, states, ...) {
  outputs <- substitute(outputs)
  check.atts(outputs)
  outputs <- convert.atts(outputs)
  gist <- GIST(bio::Quantile_Normalize, ...)
  Transition(gist, outputs, states)
}

MedianPolish <- function(data) {
  gla <- GLA(bio::Median_Polish)
  inputs <- convert.exprs(quote(c(file_name, ordered_fid, ordered_fsetid, 
    fid, fsetid, intensity)))
  outputs <- convert.atts(quote(c(file_name, intensity, fsetid)))
  Aggregate(data, gla, inputs, outputs)
}