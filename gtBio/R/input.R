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
  ReadCSV(path, c(ordered_fid = base::int, fid = base::int, 
    fsetid = base::int), header = TRUE)
}

BuildMatrix <- function(celFiles, infoFile) {
  data <- ReadCEL(celFiles)
  info <- ReadPMInfoFile(infoFile)
  numFids <- Count(ReadPMInfoFile(infoFile))
  joined <- Join(data, fid, info, fid)
  builder <- GLA(bio::Build_Matrix, files = list(celFiles))
  Aggregate(joined, builder, convert.exprs(quote(c(file_name, 
    ordered_fid, fid, intensity))), c("Matrix"), states = numFids)
}