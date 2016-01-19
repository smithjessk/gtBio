library(gtBio)
library(gtBase)

celFile <- 
  "/home/jess/git/gtBio/demoData/command-console/GSM1134065_GBX.DISC.PCA2.CEL"
infoFile <- "/home/jess/git/gtBio/scripts/pd.huex.1.0.st.v2.csv"
matrix <- BuildMatrix(c(celFile), infoFile)
corrected <- RMA(matrix)
x <- View(corrected)
