// Copyright 2005, Google Inc.
// All rights reserved.
//
// Redistribution and use in source and binary forms, with or without
// modification, are permitted provided that the following conditions are
// met:
//
//     * Redistributions of source code must retain the above copyright
// notice, this list of conditions and the following disclaimer.
//     * Redistributions in binary form must reproduce the above
// copyright notice, this list of conditions and the following disclaimer
// in the documentation and/or other materials provided with the
// distribution.
//     * Neither the name of Google Inc. nor the names of its
// contributors may be used to endorse or promote products derived from
// this software without specific prior written permission.
//
// THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
// "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
// LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
// A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
// OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
// SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
// LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
// DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
// THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
// (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
// OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

// Don't forget gtest.h, which declares the testing framework.

#include "gtest/gtest.h"

#include "../src/CELBase.h"
#include "../src/CELFileReader.h"

#include <armadillo>

TEST(CELReadTest, CommandConsole) {
  CELFileReader in("demoData/command-console/GSM1134065_GBX.DISC.PCA2.CEL");
  CELBase::pointer data = in.readFile();
  arma::fmat nums = data.get()->getIntensityMatrix();
  EXPECT_EQ(13707, nums(0, 0));
  EXPECT_EQ(178, nums(0, 1));
  EXPECT_EQ(13105, nums(0, 2));
  EXPECT_EQ(146, nums(0, 3));
  EXPECT_EQ(71, nums(0, 4));
}

TEST(CELReadTest, Version4) {
  CELFileReader in("demoData/xda/GSM1134065_GBX.DISC.PCA2.CEL");
  CELBase::pointer data = in.readFile();
  arma::fmat nums = data.get()->getIntensityMatrix();
  EXPECT_EQ(13707, nums(0, 0));
  EXPECT_EQ(178, nums(0, 1));
  EXPECT_EQ(13105, nums(0, 2));
  EXPECT_EQ(146, nums(0, 3));
  EXPECT_EQ(71, nums(0, 4));
}