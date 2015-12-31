library(gtBio)
library(gtBase)

celFile1 <- "../demoData/command-console/GSM1134065_GBX.DISC.PCA2.CEL"
celFile2 <- "../demoData/command-console/GSM1134066_GBX.DISC.PCA3.CEL"
celFile3 <- "../demoData/xda/GSM1134065_GBX.DISC.PCA2.CEL"
infoFile <- "../scripts/pd.huex.1.0.st.v2.csv"

data <- ReadCEL(c(celFile1, celFile2, celFile3))
info <- ReadPMInfoFile(infoFile)
joined <- Join(data, fid, info, fid)
x <- View(Count(joined))
