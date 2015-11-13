ReadCEL <- function(paths, outputs, ...) {
  if (missing(outputs)) {
    outputs <- list(
      Intensity = TYPE(statistics::VariableMatrix, type = TYPE(base::float)), 
      StdDev = TYPE(statistics::VariableMatrix, type = TYPE(base::float)),
      Pixels = TYPE(statistics::VariableMatrix, type = TYPE(base::int))),
      Annotation = TYPE(base::String),
      ProtocolDate = TYPE(base::String)
  }
  alias <- create.alias("read")
  gi <- GI(bio::CELFileReader)
  data <- Input(files = paths, gi = gi, outputs = outputs, ...)
  set.class(c(data), c("ReadFile", class(data)))
}

# A lot of this function is modeled after the extraction of perfect match 
# probes in Oligo. Sources:
# https://github.com/Bioconductor-mirror/oligo/blob/master/R/methods-ExonFeatureSet.R#L49
# https://github.com/Bioconductor-mirror/oligo/blob/321950c77e9c6dc9f9ae86b296cf9215e2cb0f28/R/utils-general.R#L207
# Why is length(featureInfo[["fid"]]) != length(unique(featureInfo[["fid"]]))?
ExtractPMProbes <- function(input) {
  # featureInfo <- stArrayPmInfo(input, target="core") # Sorted by fsetid
  # pmi <- featureInfo[["fid"]] # contains the index of the PM probe
  # pnVec <- as.character(featureInfo[["fsetid"]]) # contains the name of the probe
  # pms <- exprs(input)[pmi, , drop=FALSE]
  # dimnames(pms) <- NULL
  # colnames(pms) <- sampleNames(input)
  # input
}