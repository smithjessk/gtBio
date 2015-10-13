RMA <- function(data, ...) {
  # For a reference, look at just.rma and rma_c_call in the affy package
  data <- BackgroundCorrect(data, ...)
  # Group normalization (and the model stuff)
  # Median Polish
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