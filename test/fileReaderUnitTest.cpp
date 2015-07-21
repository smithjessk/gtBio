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

#include "../src/CELFileReader.h"

#include <armadillo>

using namespace gtBio;

TEST(CELReadTest, CommandConsole) {
  gtBio::CELFileReader in("demoData/command-console/GSM1134065_GBX.DISC.PCA2.CEL");
  gtBio::CELBase::pointer data = in.readFile();

  arma::fmat intensities = data.get()->getIntensityMatrix();
  EXPECT_EQ(13707, intensities(0, 0));
  EXPECT_EQ(178, intensities(0, 1));
  EXPECT_EQ(13105, intensities(0, 2));
  EXPECT_EQ(146, intensities(0, 3));
  EXPECT_EQ(71, intensities(0, 4));

  arma::fmat devs = data.get()->getStdDevMatrix();
  EXPECT_FLOAT_EQ(1213.2, devs(0, 0));
  EXPECT_FLOAT_EQ(32.3, devs(0, 1));
  EXPECT_FLOAT_EQ(1041.9, devs(0, 2));
  EXPECT_FLOAT_EQ(29, devs(0, 3));
  EXPECT_FLOAT_EQ(9, devs(0, 4));

  arma::Mat<int16_t> pixels = data.get()->getPixelsMatrix();
  EXPECT_EQ(9, pixels(0, 0));
  EXPECT_EQ(9, pixels(0, 1));
  EXPECT_EQ(9, pixels(5, 6));
}

TEST(CELReadTest, Version4) {
  CELFileReader in("demoData/xda/GSM1134065_GBX.DISC.PCA2.CEL");
  CELBase::pointer data = in.readFile();

  arma::fmat intensities = data.get()->getIntensityMatrix();
  EXPECT_EQ(13707, intensities(0, 0));
  EXPECT_EQ(178, intensities(0, 1));
  EXPECT_EQ(13105, intensities(0, 2));
  EXPECT_EQ(146, intensities(0, 3));
  EXPECT_EQ(71, intensities(0, 4));

  arma::fmat devs = data.get()->getStdDevMatrix();
  EXPECT_FLOAT_EQ(1213.2, devs(0, 0));
  EXPECT_FLOAT_EQ(32.3, devs(0, 1));
  EXPECT_FLOAT_EQ(1041.9, devs(0, 2));
  EXPECT_FLOAT_EQ(29, devs(0, 3));
  EXPECT_FLOAT_EQ(9, devs(0, 4));

  arma::Mat<int16_t> pixels = data.get()->getPixelsMatrix();
  EXPECT_EQ(9, pixels(0, 0));
  EXPECT_EQ(9, pixels(0, 1));
  EXPECT_EQ(9, pixels(5, 6));
}