<?
function Median_Polish($t_args, $outputs, $states) {
    $class_name = generate_name('Median_Polish');
    $matrix = array_keys($states)[0];
    $matrix_type = array_values($states)[0];
    $inner_type = $matrix_type->get('type');
    $should_transpose = get_default($t_args, 'should_transpose', False);
    $field_to_access = get_default($t_args, 'field_to_access', '');
    if ($field_to_access != '') {
      $field_to_access = '.' + $field_to_access;
    }
    $output = ['polished_matrix' => lookupType('bio::Variable_Matrix', 
      ['type' => $inner_type])];

    $identifier = [
        'kind'  => 'GIST',
        'name'  => $class_name,
        'system_headers' => ['armadillo', 'algorithm'],
        'libraries'     => ['armadillo'],
        'iterable'      => true,
        'output'        => $output,
        'result_type'   => 'single',
    ];
?>

class ConvergenceGLA {
 public:
  int round_num;
  bool converging_this_round;

  ConvergenceGLA(int num) :
      round_num(num),
      converging_this_round(true) {}

  void AddState(ConvergenceGLA other) {
    converging_this_round = converging_this_round && 
      other.converging_this_round;
  }

  // TODO: Add better converging conditions
  bool ShouldIterate() {
    return round_num < 5;
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
    long start_index; // Which row or column this local scheduler starts at
    long end_index; // Which row/col it ends at. Inclusive bound.
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
      long count;
      if (round_num % 2 == 1) {
        count = matrix.n_rows;
      } else {
        count = matrix.n_cols;
      }
      task.start_index = thread_index * count / num_threads;
      task.end_index = (thread_index + 1) * count / num_threads - 1;
      finished_scheduling = true;
      return ret;
    }
  };

  using WorkUnit = std::pair<LocalScheduler*, cGLA*>;
  using WorkUnits = std::vector<WorkUnit>;

 private:
  // Without a limit on the number of iterations, the process could never end.
  int round_num;
  int num_threads;
  Matrix matrix;

  void RowPolish(Task& task, cGLA& gla) {
    int start = task.start_index;
    int end = task.end_index;
    arma::Col<InnerType> med_val = 
      median(matrix.submat(start, 0, end, matrix.n_cols - 1), 1);
    arma::Col<InnerType> med(matrix.n_cols);
    med.fill(med_val(0, 0));
    matrix.submat(start, 0, end, matrix.n_cols - 1) -= med.t();
  }

  void ColPolish(Task& task, cGLA& gla) {
    int start = task.start_index;
    int end = task.end_index;
    arma::Col<InnerType> med_val = 
      median(matrix.submat(0, start, matrix.n_rows - 1, end), 0);
    arma::Col<InnerType> med(matrix.n_rows);
    med.fill(med_val(0, 0));
    matrix.submat(0, start, matrix.n_rows - 1, end) -= med;
  }

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

  // Advance the round number and distribute work among the threads
  void PrepareRound(WorkUnits& workers, int suggested_num_workers) {
    round_num++;
    arma::uword n_rows = matrix.n_rows;
    arma::uword n_cols = matrix.n_cols;
    int min_dimension = std::min(n_rows, n_cols);
    this->num_threads = std::min(min_dimension, suggested_num_workers);
    std::cout << "Beginning round " << round_num << " with " 
      << this->num_threads << " workers." << std::endl;
    std::pair<LocalScheduler*, cGLA*> worker;
    for (int counter = 0; counter < this->num_threads; counter++) {
      worker = std::make_pair(new LocalScheduler(counter, round_num, 
        this->num_threads, matrix), new cGLA(round_num));
      workers.push_back(worker);
    }
  }

  // If round number is odd, do a row polish. Otherwise, do a column polish.
  void DoStep(Task& task, cGLA& gla) {
    if (round_num % 2 == 1) {
      RowPolish(task, gla);
    } else {
      ColPolish(task, gla);
    }
  }

  void GetResult(<?=typed_ref_args($output)?>) {
    polished_matrix = matrix;
  }
};

<?
    return $identifier;
}
?>
