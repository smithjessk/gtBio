<?
function Background_Correct($t_args, $outputs, $states) {
  $class_name = generate_name('Background_Correct');
  $cgla_name = generate_name('ConvergenceGLA');
  $matrix = array_keys($states)[0];
  $field_to_access = get_default($t_args, 'field_to_access', '');
  $from_matrix = get_default($t_args, 'from_matrix', False);
  $matrix_grabber = $from_matrix ? 
    $matrix.'.GetMatrix()' : 
    'std::get<0>('.$matrix.'.GetList()[0])';
  $matrix_type = array_values($states)[0]->output()[0];
  if ($field_to_access != '') {
    $matrix_type = $matrix_type[$field_to_access];
  }
  $inner_type = $matrix_type->get('type');
  $should_transpose = get_default($t_args, 'should_transpose', False);
  if ($field_to_access != '') {
    $field_to_access = '.' + $field_to_access;
  }
  $output = array_combine(array_keys($outputs), [
    lookupType('statistics::Variable_Matrix', [
      'type' => lookupType('base::DOUBLE')
    ]
  )]);
  $identifier = [
      'kind' => 'GIST',
      'name' => $class_name,
      'system_headers' => ['armadillo', 'algorithm'],
      'libraries'     => ['armadillo', 'preprocessCore'],
      'user_headers'    => [],
      'iterable' => false,
      'output'          => $output,
      'result_type'     => 'single',
      'properties'      => ['matrix'],
      'extras'          => array_merge($matrix_type->extras(), [
        'type' => lookupType('base::DOUBLE')]),
  ];
?>

// Found in preprocessCore
extern "C" void rma_bg_parameters(double *PM, double *param, size_t rows, 
  size_t cols, size_t column);
extern "C" void rma_bg_adjust(double *PM, double *param, size_t rows, 
  size_t cols, size_t column);

class <?=$cgla_name?> {
 public:
  <?=$cgla_name?>() {}

  void AddState(<?=$cgla_name?> other) {}

  // Required by grokit
  bool ShouldIterate() {
    return false;
  }
};

class <?=$class_name?> {
 public:
  // We don't know what type of matrix we will be passed, so it is best to be
  // type-agnostic.
  using Inner = <?=$inner_type?>;
  using Matrix = <?=$matrix_type?>;
  using cGLA = <?=$cgla_name?>;

  struct Task {
    long start_index; // Which column this local scheduler starts at
    long end_index; // Which column it ends at. Inclusive bound.
  };

  struct LocalScheduler {
    int thread_index;
    bool finished_scheduling;
    int num_threads;
    int &round_num;
    Matrix &matrix;

    LocalScheduler(int index, int &round_num, int num_threads, 
      Matrix &matrix) :
        thread_index(index),
        finished_scheduling(false),
        num_threads(num_threads),
        round_num(round_num),
        matrix(matrix) {}

    bool GetNextTask(Task& task) {
      bool ret = !finished_scheduling;
      task.start_index = thread_index * matrix.n_cols / num_threads;
      task.end_index = (thread_index + 1) * matrix.n_cols / num_threads - 1;
      finished_scheduling = true;
      return ret;
    }
  };

  using WorkUnit = std::pair<LocalScheduler*, cGLA*>;
  using WorkUnits = std::vector<WorkUnit>;

 private:
  int round_num;
  int num_threads;
  Matrix matrix;
  arma::mat matrix_as_doubles;

 public:
  <?=$class_name?>(<?=const_typed_ref_args($states)?>) {
<? if ($should_transpose) { ?>
    std::cout << "Transposing matrix..." << std::endl;
    <? if ($field_to_access != '') { ?> 
        matrix = <?=$matrix_grabber?>.<?=$field_to_access?>.t();
    <? } else { ?>
        matrix = <?=$matrix_grabber?>.t();
    <? } ?>
<? } else { ?>
    <? if ($field_to_access != '') { ?> 
        matrix = <?=$matrix_grabber?>.<?=$field_to_access?>;
    <? } else { ?>
        matrix = <?=$matrix_grabber?>;
    <? } ?>
<? } ?> 
    round_num = 0;
    matrix_as_doubles = arma::conv_to<arma::mat>::from(matrix);
  }

  // TODO: Extract perfect match probes
  void PrepareRound(WorkUnits& workers, int suggested_num_workers) {
    round_num++;
    arma::uword n_cols = matrix.n_cols;
    this->num_threads = 1;
    std::printf("Beginning round %d with %d workers.\n", round_num, 
      this->num_threads);
    std::pair<LocalScheduler*, cGLA*> worker;
    for (int counter = 0; counter < this->num_threads; counter++) {
      worker = std::make_pair(new LocalScheduler(counter, round_num, 
        this->num_threads, matrix), new cGLA());
      workers.push_back(worker); 
    }
    // Necessary because Armadillo stores column-by-column but the linked RMA
    // functions expect row-stored values
    matrix = matrix.t(); // Possibly taken care of by Collect?
  }

  // TODO: How to extract a pointer to the data? 
  void DoStep(Task& task, cGLA& gla) {
    double *params = (double *) malloc(3 * sizeof(double));
    rma_bg_parameters(matrix_as_doubles.memptr(), params, 
      matrix_as_doubles.n_rows * matrix_as_doubles.n_rows, 1, 0);
    rma_bg_adjust(matrix_as_doubles.memptr(), params, 
      matrix_as_doubles.n_rows * matrix_as_doubles.n_rows, 1, 0);
  }

  void GetResult(<?=typed_ref_args($output)?>) {
    <? if ($should_transpose) { ?>
      <?=array_keys($outputs)[0]?> = matrix_as_doubles.t();
    <? } else { ?>
      <?=array_keys($outputs)[0]?> = matrix_as_doubles;
    <? } ?>
  }

  inline const arma::mat& GetMatrix() const {
    <? if ($should_transpose) { ?>
      return matrix_as_doubles.t();
    <? } else { ?>
      return matrix_as_doubles;
    <? } ?>
  }
};

<?
    return $identifier;
}
?>
