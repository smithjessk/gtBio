ReadCEL <- function(files, outputs, ...) {
  if (missing(outputs)) {
    outputs <- c("Intensity", "StdDev", "Pixels")
  }

  alias <- create.alias("read")
  gi <- GI(bio::CELFileReader)
  data <- Input(files = files, alias = alias, gi = gi, schema = outputs, ...)
  set.class(c(data), c("ReadFile", class(data)))
}