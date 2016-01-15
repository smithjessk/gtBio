library(gtBio)
library(gtBase)

celFile1 <- 
      "/home/jess/git/gtBio/demoData/command-console/GSM1134065_GBX.DISC.PCA2.CEL"
celFile2 <- 
      "/home/jess/git/gtBio/demoData/command-console/GSM1134066_GBX.DISC.PCA3.CEL"
celFile3 <- 
      "/home/jess/git/gtBio/demoData/xda/GSM1134065_GBX.DISC.PCA2.CEL"

colon_cancer_files = c(
                   "~/colon-cancer-data/10_5N.CEL",
                   "~/colon-cancer-data/13_7T.CEL",
                   "~/colon-cancer-data/17_9T.CEL",
                   "~/colon-cancer-data/2_1N.CEL",
                   "~/colon-cancer-data/6_3N.CEL",
                   "~/colon-cancer-data/11_6T.CEL",
                   "~/colon-cancer-data/14_7N.CEL",
                   "~/colon-cancer-data/18_9N.CEL",
                   "~/colon-cancer-data/3_2T.CEL",
                   "~/colon-cancer-data/7_4T.CEL",
                   "~/colon-cancer-data/1_1T.CEL",
                   "~/colon-cancer-data/15_8T.CEL",
                   "~/colon-cancer-data/19_10T.CEL",
                   "~/colon-cancer-data/4_2N.CEL",
                   "~/colon-cancer-data/8_4N.CEL",
                   "~/colon-cancer-data/12_6N.CEL",
                   "~/colon-cancer-data/16_8N.CEL",
                   "~/colon-cancer-data/20_10N.CEL",
                   "~/colon-cancer-data/5_3T.CEL",
                   "~/colon-cancer-data/9_5T.CEL"
)

infoFile <- "../scripts/pd.huex.1.0.st.v2.csv"

data <- ReadCEL(c(celFile1, celFile2, celFile3))
info <- ReadPMInfoFile(infoFile)
numFids <- Count(ReadPMInfoFile(infoFile))
joined <- Join(data, fid, info, fid)

builder <- GLA(bio::Build_Matrix, files = list(celFile1, celFile2, celFile3))
matrix <- Aggregate(joined, builder, convert.exprs(quote(c(file_name, 
      ordered_fid, fid, intensity))), c("Matrix"), states = numFids)

x <- as.object(Count(matrix))

# filtered <- sorted[fid == 2760621 || fid == 562369]
# merged <- Collect(sorted, c("file_name", "intensity"), Matrix, size = 2 * 893078)
# x <- as.data.frame(Count(merged))


                                        # Assertions:
# (fid, fsetid) = (2760621, 2315554), (562369, 2320048)
