library(gtBio)

celFile <- "../demoData/command-console/GSM1134065_GBX_DISC.PCA2.CEL"
infoFile <- "../scripts/pd.huex.1.0.st.v2.csv"

data <- ReadCEL(c(celFile))
info <- ReadPMInfoFile(infoFile)
x <- View(info)
