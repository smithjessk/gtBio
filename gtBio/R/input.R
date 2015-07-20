ReadCEL <- function(files, outputs = c(Intensity, StdDev, Pixels), ...) {
  outputs <- quote(outputs)
  check.atts(outputs)
  outputs <- convert.atts(outputs)

  alias <- create.alias("read")
  gi <- GI(base::CELFileReader)
  Input(files = files, alias = alias, gi = gi, outputs = outputs)
}

# View(ReadCEL("./myfile.CEL"))$content