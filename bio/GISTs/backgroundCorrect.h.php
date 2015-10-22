<?
function Background_Correct($t_args, $outputs, $states) {
  $class_name = generate_name('Background_Correct');
  $cgla_name = generate_name('ConvergenceGLA');
  $matrix = array_keys($states)[0];
  $field_to_access = get_default($t_args, 'field_to_access', '');
  #fprintf(STDERR, "field_to_access = ".$field_to_access."\n");
  #fprintf(STDERR, print_r(array_values($states)[0]->output()));
  $matrix_type = array_values($states)[0]->output()[$field_to_access];
  #fprintf(STDERR, "matrix_type = ".$matrix_type."\n");
  $inner_type = $matrix_type->get('type');
  #fprintf(STDERR, "inner_type = ".$inner_type."\n");
  $should_transpose = get_default($t_args, 'should_transpose', False);
  if ($field_to_access != '') {
    $field_to_access = '.' + $field_to_access;
  }
  $output = ['corrected_matrix' => lookupType('statistics::Variable_Matrix', 
      ['type' => $inner_type])];
  
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
      'extras'          => $matrix_type->extras(),
  ];
?>

// Found in preprocessCore
extern void rma_bg_parameters(double *PM, double *param, size_t rows, 
  size_t cols, size_t column);
extern void rma_bg_adjust(double *PM, double *param, size_t rows, size_t cols, 
  size_t column);

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
    long col_index;
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
      // task.col_index = thread_index;
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

 public:
  <?=$class_name?>(<?=const_typed_ref_args($states)?>) {

<? if ($should_transpose) { ?>
    std::cout << "Transposing matrix..." << std::endl;
    <? if ($field_to_access != '') { ?> 
        matrix = std::get<0>(<?=$matrix?>.GetList()[0]).<?=$field_to_access?>.t();
    <? } else { ?>
        matrix = std::get<0>(<?=$matrix?>.GetList()[0]).t();
    <? } ?>
<? } else { ?>
    <? if ($field_to_access != '') { ?> 
        matrix = std::get<0>(<?=$matrix?>.GetList()[0]).<?=$field_to_access?>;
    <? } else { ?>
        matrix = std::get<0>(<?=$matrix?>.GetList()[0]);
    <? } ?>
<? } ?> 
    round_num = 0;
  }

  void PrepareRound(WorkUnits& workers, int suggested_num_workers) {
    round_num++;
    arma::uword n_cols = matrix.n_cols;
    this->num_threads = std::min((int) n_cols, suggested_num_workers);
    std::cout << "Beginning round " << round_num << " with " << this->num_threads
      << " workers." << std::endl;
    std::pair<LocalScheduler*, cGLA*> worker;
    for (int counter = 0; counter < this->num_threads; counter++) {
      worker = std::make_pair(new LocalScheduler(counter, round_num, 
        this->num_threads, matrix), new cGLA());
      workers.push_back(worker);
    }
  }

  // TODO: How to extract a pointer to the data? 
  void DoStep(Task& task, cGLA& gla) {
    matrix = matrix.t();
    double *params = (double *) malloc(3 * sizeof(double));
    rma_bg_parameters((double *) matrix.memptr(), params, matrix.n_rows, 
      matrix.n_cols, (size_t) task.col_index);
    rma_bg_adjust((double *) matrix.memptr(), params, matrix.n_rows, 
      matrix.n_cols, (size_t) task.col_index);
  }

  void GetResult(<?=typed_ref_args($output)?>) {
    <? if ($should_transpose) { ?>
      corrected_matrix = matrix.t();
    <? } else { ?>
      corrected_matrix = matrix;
    <? } ?>
  }

  inline const Matrix& GetMatrix() const {
    <? if ($should_transpose) { ?>
      return matrix.t();
    <? } else { ?>
      return matrix;
    <? } ?>
  }
};

<?
    return $identifier;
}
?>