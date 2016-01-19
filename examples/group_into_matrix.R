library(gtBio)
library(gtBase)

celFile1 <- 
      "/home/jess/git/gtBio/demoData/command-console/GSM1134065_GBX.DISC.PCA2.CEL"
celFile2 <- 
      "/home/jess/git/gtBio/demoData/command-console/GSM1134066_GBX.DISC.PCA3.CEL"
celFile3 <- 
      "/home/jess/git/gtBio/demoData/xda/GSM1134065_GBX.DISC.PCA2.CEL"
colon_cancer_files = c(
                   "/home/jess/colon-cancer-data/10_5N.CEL",
                   "/home/jess/colon-cancer-data/13_7T.CEL",
                   "/home/jess/colon-cancer-data/17_9T.CEL",
                   "/home/jess/colon-cancer-data/2_1N.CEL",
                   "/home/jess/colon-cancer-data/6_3N.CEL",
                   "/home/jess/colon-cancer-data/11_6T.CEL",
                   "/home/jess/colon-cancer-data/14_7N.CEL",
                   "/home/jess/colon-cancer-data/18_9N.CEL",
                   "/home/jess/colon-cancer-data/3_2T.CEL",
                   "/home/jess/colon-cancer-data/7_4T.CEL",
                   "/home/jess/colon-cancer-data/1_1T.CEL",
                   "/home/jess/colon-cancer-data/15_8T.CEL",
                   "/home/jess/colon-cancer-data/19_10T.CEL",
                   "/home/jess/colon-cancer-data/4_2N.CEL",
                   "/home/jess/colon-cancer-data/8_4N.CEL",
                   "/home/jess/colon-cancer-data/12_6N.CEL",
                   "/home/jess/colon-cancer-data/16_8N.CEL",
                   "/home/jess/colon-cancer-data/20_10N.CEL",
                   "/home/jess/colon-cancer-data/5_3T.CEL",
                   "/home/jess/colon-cancer-data/9_5T.CEL"
)

infoFile <- "../scripts/pd.huex.1.0.st.v2.csv"
data <- ReadCEL(c(celFile1))
info <- ReadPMInfoFile(infoFile)
numFids <- Count(ReadPMInfoFile(infoFile))
joined <- Join(data, fid, info, fid)
builder <- GLA(bio::Build_Matrix, files = list(celFile1))
matrix <- Aggregate(joined, builder, convert.exprs(quote(c(file_name, 
      ordered_fid, fid, intensity))), c("Matrix"), states = numFids)
x <- as.object(matrix)

assert(x$content[[1]][[1]]$data[259507] == 2984)