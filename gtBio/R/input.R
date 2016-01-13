ReadCEL <- function(paths, outputs, ...) {
  if (missing(outputs)) {
    outputs <- list(
        file_name = TYPE(base::string),
        chip_type = TYPE(base::string),
        fid = TYPE(base::int),
        intensity = TYPE(base::float))
  }
  alias <- create.alias("read")
  gi <- GI(bio::CELFileReader)
  Input(files = paths, gi = gi, outputs = outputs, ...)
}

ReadPMInfoFile <- function(path) {
  ReadCSV(path, c(fid = base::int, fsetid = base::int), header = TRUE)
}
