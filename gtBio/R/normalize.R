# For a reference, see the following links:
# https://github.com/Bioconductor-mirror/affy/blob/master/R/justrma.R#L55
# https://github.com/Bioconductor-mirror/affy/blob/4a732dd80fabbc8e7b008428dfdea57af6bd237b/src/rma2.c#L163
RMA <- function(files) {
  data <- ReadCEL(c(files))
  state <- Gather(data)

  # This is equivalent to rma_bg_correct in just.rma
  correctedMatrix <- BackgroundCorrect(states = state, outputs = 
    c(corrected = Matrix), shouldTranspose = FALSE, 
    field_to_access = "Q1__Intensity")

  # The following code are equivalent to rma_c_call in just.rma
  
  # qnorm_c on perfect match pairs

  # Equivalent to R_subColSummarize_medianpolish_log
  MedianPolish(states = correctedMatrix, outputs = 
    c(normalized = Matrix), shouldTranspose = FALSE)
}

BackgroundCorrect <- function(outputs, states, ...) {
  outputs <- substitute(outputs)
  check.atts(outputs)
  outputs <- convert.atts(outputs)
  gist <- GIST(bio::Background_Correct, ...)
  Transition(gist, outputs, states)
}

MedianPolish <- function(outputs, states, ...) {
  outputs <- substitute(outputs)
  check.atts(outputs)
  outputs <- convert.atts(outputs)
  gist <- GIST(bio::Median_Polish, ...)
  Transition(gist, outputs, states)
}