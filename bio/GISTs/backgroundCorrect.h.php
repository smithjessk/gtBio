<?
function Background_Correct(array $t_args, array $inputs, array $outputs) {
  $className = generate_name('Background_Correct');
  $matrix = array_keys($states)[0];
  $matrixType = array_values($states)[0];
  $innerType = $matrixType->get('type');
  $shouldTranspose = get_default($t_args, 'shouldTranspose', False);
  $fieldToAccess = get_default($t_args, 'fieldToAccess', '');
  if ($fieldToAccess != '') {
    $fieldToAccess = '.' + $fieldToAccess;
  }
  $output = ['corrected_matrix' => lookupType('bio::Variable_Matrix', 
      ['type' => $innerType])];
  
  $identifier = [
      'kind' => 'GIST',
      'name' => $className,
      'system_headers' => ['armadillo', 'algorithm'],
      'libraries'     => ['armadillo'],
      'user_headers'    => ['densityFunctions.h'],
      'iterable' => true,
      'output'          => $output,
      'result_type'     => 'single',
  ];
?>

class ConvergenceGLA {
 public:
  int roundNum;

  ConvergenceGLA(int num) :
      roundNum(num) {}

  void AddState(ConvergenceGLA other) {}

  // TODO: Add better converging conditions
  bool ShouldIterate() {
    return roundNum < 2;
  }
};

class <?=$className?> {
 public:
  // We don't know what type of matrix we will be passed, so it is best to be
  // type-agnostic.
  using InnerType = <?=$innerType?>;
  using Matrix = <?=$matrixType?>::Matrix;
  using cGLA = ConvergenceGLA;

  struct Task {
    long colIndex;
  };

  struct LocalScheduler {
    int threadIndex;
    bool finishedScheduling;
    int numThreads;
    int &roundNum;
    Matrix &matrix;

    LocalScheduler(int index, int &roundNum, int numThreads, Matrix &matrix) :
        threadIndex(index),
        finishedScheduling(false),
        numThreads(numThreads),
        roundNum(roundNum),
        matrix(matrix) {}

    bool GetNextTask(Task& task) {
      bool ret = !finishedScheduling;
      finishedScheduling = true;
      return ret;
    }
  };

  using WorkUnit = std::pair<LocalScheduler*, cGLA*>;
  using WorkUnits = std::vector<WorkUnit>;

 private:
  int roundNum;
  int numThreads;
  Matrix matrix;

 public:
  <?=$className?>(<?=const_typed_ref_args($states)?>) {

<? if ($shouldTranspose) { ?>
        std::cout << "Transposing matrix..." << std::endl;
        matrix = <?=$matrix?>.GetMatrix()<?=$fieldToAccess?>.t();
<? } else { ?>
        matrix = <?=$matrix?>.GetMatrix()<?=$fieldToAccess?>;
<? } ?> 
        roundNum = 0;
  }

  void PrepareRound(WorkUnits& workers, int numThreads) {
    roundNum++;
    this->numThreads = matrix.n_cols;
    std::cout << "Beginning round " << roundNum << " with " << this->numThreads
      << " workers." << std::endl;
    std::pair<LocalScheduler*, cGLA*> worker;
    for (int counter = 0; counter < this->numThreads; counter++) {
      worker = std::make_pair(new LocalScheduler(counter, roundNum, 
        this->numThreads, matrix), new cGLA(roundNum));
      workers.push_back(worker);
    }
  }

  // If round number is odd, do a row polish. Otherwise, do a column polish.
  void DoStep(Task& task, cGLA& gla) {
    // Here go the equivalent calls to rma_bg_correct and rma_bg_adjust
    // Note that this should mirror the code here 
    // https://github.com/Bioconductor-mirror/preprocessCore/blob/master/src/rma_background4.c#L444
  }

  void GetResult(<?=typed_ref_args($output)?>) {
    corrected_matrix = matrix;
  }
}

<?
    return $identifier;
}
?>