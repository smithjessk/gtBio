library(gtBio)

f <- "../demoData/command-console/GSM1134065_GBX.DISC.PCA2.CEL"
data <- ReadCEL(c(f))
x <- View(data, mean = bio::MatrixMean(Intensity))
