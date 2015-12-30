library(gtBio)
library(gtBase)

file <- "../demoData/command-console/GSM1134065_GBX.DISC.PCA2.CEL"
data <- ReadCEL(c(file))
x <- View(Count(data))