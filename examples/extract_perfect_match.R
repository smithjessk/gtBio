library(gtBio)
library(gtBase)

celFile1 <- "../demoData/command-console/GSM1134065_GBX.DISC.PCA2.CEL"
infoFile <- "../scripts/pd.huex.1.0.st.v2.csv"

data <- ReadCEL(c(celFile1))
info <- ReadPMInfoFile(infoFile)
joined <- Join(data, fid, info, fid)

filtered <- joined[fid == 2760621 || fid == 562369]
x <- as.object(filtered)

if (x$content[[1]][[1]] == 562369) {
  assert(x$content[[1]][[6]] == 2320048)
  assert(x$content[[2]][[6]] == 2315554)
} else {
  assert(x$content[[2]][[6]] == 2320048)
  assert(x$content[[1]][[6]] == 2315554)
}
                                        # Assertions:
# (fid, fsetid) = (2760621, 2315554), (562369, 2320048) 
