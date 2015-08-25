ReadCEL <- function(paths, outputs, ...) {
  if (missing(outputs)) {
    outputs <- c("Intensity", "StdDev", "Pixels")
  }

  # Expand directories to their lists of files

  alias <- create.alias("read")
  gi <- GI(bio::CELFileReader)
  data <- Input(files = paths, alias = alias, gi = gi, schema = outputs, ...)
  set.class(c(data), c("ReadFile", class(data)))
}