MedianPolish <- function(outputs, states, ...) {
  outputs <- substitute(outputs)
  check.atts(outputs)
  outputs <- convert.atts(outputs)
  gist <- GIST(bio::Median_Polish)
  Transition(gist, outputs, states)
}