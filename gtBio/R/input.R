ReadCEL <- function(paths, outputs, ...) {
  if (missing(outputs)) {
    outputs <- list(
      Intensity = TYPE(statistics::VariableMatrix, type = TYPE(base::float)), 
      StdDev = TYPE(statistics::VariableMatrix, type = TYPE(base::float)),
      Pixels = TYPE(statistics::VariableMatrix, type = TYPE(base::int)))
  }
  alias <- create.alias("read")
  gi <- GI(bio::CELFileReader)
  data <- Input(files = paths, gi = gi, outputs = outputs, ...)
  set.class(c(data), c("ReadFile", class(data)))
}
