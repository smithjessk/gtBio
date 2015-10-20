<?
function Background_Correct($t_args, $outputs, $states) {
  $class_name = generate_name('Background_Correct');
  $matrix = array_keys($states)[0];
  $matrix_type = array_values($states)[0];
  $inner_type = $matrix_type->get('type');
  $should_transpose = get_default($t_args, 'should_transpose', False);
  $field_to_access = get_default($t_args, 'field_to_access', '');
  if ($field_to_access != '') {
    $field_to_access = '.' + $field_to_access;
  }
  $output = ['corrected_matrix' => lookupType('bio::Variable_Matrix', 
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
  ];
?>

// Found in preprocessCore
extern void rma_bg_parameters(double *PM, double *param, size_t rows, 
  size_t cols, size_t column);
extern void rma_bg_adjust(double *PM, double *param, size_t rows, size_t cols, 
  size_t column);

class ConvergenceGLA {
 public:
  ConvergenceGLA() {}

  void AddState(ConvergenceGLA other) {}

  // Required by grokit
  bool ShouldIterate() {
    return false;
  }
};

class <?=$class_name?> {
 public:
  // We don't know what type of matrix we will be passed, so it is best to be
  // type-agnostic.
  using InnerType = <?=$inner_type?>;
  using Matrix = <?=$matrix_type?>::Matrix;
  using cGLA = ConvergenceGLA;

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
        matrix = <?=$matrix?>.GetMatrix()<?=$field_to_access?>.t();
<? } else { ?>
        matrix = <?=$matrix?>.GetMatrix()<?=$field_to_access?>;
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
    corrected_matrix = matrix.t();
  }
};

<?
    return $identifier;
}
?>