# Test Report

- Generated: 2026-11-24T02:30:13Z
- Runtime: PHP 8.5.4 on Linux
- Command: `php tests/run.php --report-dir reports/tests`
- Tests: 70
- Passed: 70
- Failed: 0
- Skipped: 0
- Assertions: 5462

| Suite | Test | Result |
| --- | --- | --- |
| ApplicationTest | testAssignPrintsVariantWithoutEchoingUnit | passed |
| ApplicationTest | testAnalyzeJson | passed |
| ApplicationTest | testAnalyzeText | passed |
| ApplicationTest | testSampleSize | passed |
| ApplicationTest | testVersionAndHelp | passed |
| ApplicationTest | testInvalidInvocationsExitWithUsageCode | passed |
| ApplicationTest | testErrorsDoNotReflectRawInput | passed |
| ApplicationTest | testRejectsDuplicateExpectedWeights | passed |
| ApplicationTest | testNumericVariantNamesAreSupported | passed |
| ApplicationTest | testSampleSizeOutOfRangeExitsWithUsageCode | passed |
| ApplicationTest | testRejectsInvalidFormat | passed |
| ChiSquareDistributionTest | testUpperTailMatchesClosedForms | passed |
| ChiSquareDistributionTest | testZeroStatisticHasTailOne | passed |
| ChiSquareDistributionTest | testTailIncreasesWithDegreesOfFreedom | passed |
| ChiSquareDistributionTest | testRejectsInvalidArguments | passed |
| DomainTest | testExperimentComputesTotalWeight | passed |
| DomainTest | testIdentifierRejectsUnsafeValues | passed |
| DomainTest | testVariantRejectsOutOfRangeWeight | passed |
| DomainTest | testExperimentRejectsInvalidVariantSets | passed |
| DomainTest | testObservationValidatesCounts | passed |
| ExperimentAnalyzerTest | testTwoProportionTestMatchesReference | passed |
| ExperimentAnalyzerTest | testWilsonIntervalMatchesReference | passed |
| ExperimentAnalyzerTest | testWilsonBoundsAtExtremes | passed |
| ExperimentAnalyzerTest | testZeroVarianceIsNotSignificant | passed |
| ExperimentAnalyzerTest | testHolmCorrectionCanRemoveSignificance | passed |
| ExperimentAnalyzerTest | testSampleRatioMismatchDetected | passed |
| ExperimentAnalyzerTest | testSampleRatioHonorsExpectedWeights | passed |
| ExperimentAnalyzerTest | testRejectsInvalidExpectedWeights | passed |
| ExperimentAnalyzerTest | testExpectedWeightsMatchNumericLookingNames | passed |
| ExperimentAnalyzerTest | testRejectsInvalidInput | passed |
| HolmCorrectionTest | testAdjustsAndPreservesInputOrder | passed |
| HolmCorrectionTest | testEnforcesMonotonicity | passed |
| HolmCorrectionTest | testCapsAtOne | passed |
| HolmCorrectionTest | testSingleValueIsUnchanged | passed |
| NormalDistributionTest | testUpperTailMatchesReferenceValues | passed |
| NormalDistributionTest | testCdfIsSymmetric | passed |
| NormalDistributionTest | testExtremeTailIsZero | passed |
| NormalDistributionTest | testQuantileMatchesReferenceValues | passed |
| NormalDistributionTest | testQuantileInvertsCdf | passed |
| NormalDistributionTest | testQuantileIsAccurateInUpperTail | passed |
| NormalDistributionTest | testQuantileStaysFiniteBeyondTailCutoff | passed |
| NormalDistributionTest | testQuantileRejectsBoundaries | passed |
| ObservationCsvReaderTest | testReadsValidFile | passed |
| ObservationCsvReaderTest | testAcceptsByteOrderMark | passed |
| ObservationCsvReaderTest | testRejectsMalformedContent | passed |
| ObservationCsvReaderTest | testRejectsTooManyRows | passed |
| ObservationCsvReaderTest | testRejectsOversizedFile | passed |
| ObservationCsvReaderTest | testEnforcesLimitWhenReportedSizeUnderstatesContent | passed |
| ObservationCsvReaderTest | testRejectsDirectoriesAndMissingFiles | passed |
| SampleSizeCalculatorTest | testMatchesReference | passed |
| SampleSizeCalculatorTest | testSmallerEffectNeedsMoreUnits | passed |
| SampleSizeCalculatorTest | testHigherPowerNeedsMoreUnits | passed |
| SampleSizeCalculatorTest | testLowerPowerNeverNeedsMoreUnits | passed |
| SampleSizeCalculatorTest | testRejectsPowerTooLowForAlpha | passed |
| SampleSizeCalculatorTest | testRejectsResultsBeyondSupportedRange | passed |
| SampleSizeCalculatorTest | testRejectsInvalidParameters | passed |
| SchemaTest | testSchemaCreatesAllTables | passed |
| SchemaTest | testSchemaIsIdempotent | passed |
| SchemaTest | testValidLifecycleIsAccepted | passed |
| SchemaTest | testConstraintsRejectInvalidRows | passed |
| SchemaTest | testDeletingExperimentCascades | passed |
| VariantAssignerTest | testBucketMatchesIndependentSha256Vectors | passed |
| VariantAssignerTest | testAssignmentIsDeterministic | passed |
| VariantAssignerTest | testLengthPrefixPreventsBoundaryCollisions | passed |
| VariantAssignerTest | testAllocationMatchesWeights | passed |
| VariantAssignerTest | testExperimentsAreIndependent | passed |
| VariantAssignerTest | testBucketStaysInRange | passed |
| VariantAssignerTest | testRejectsVariantTooSmallToReceiveTraffic | passed |
| VariantAssignerTest | testSmallestSupportedShareReceivesTraffic | passed |
| VariantAssignerTest | testRejectsInvalidUnitIdentifiers | passed |
