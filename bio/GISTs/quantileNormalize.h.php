<?
function Quantile_Normalize($t_args, $outputs, $states) {
    $class_name = generate_name('Quantile_Normalize');
    $cgla_name = generate_name('ConvergenceGLA');
    $matrix = array_keys($states)[0];
    $field_to_access = get_default($t_args, 'field_to_access', '');
    if ($field_to_access != '') {
      $field_to_access = '.' + $field_to_access;
    }
    $file_names = $t_args['files'];
    $matrix_type = array_values($states)[0];
    $inner_type = $matrix_type->get('type');
    $should_transpose = get_default($t_args, 'should_transpose', False);
    $output_names = ['file_name', 'ordered_fid', 'intensity'];
    $output_types = [lookupType('base::string'), lookupType('base::int'), 
      lookupType('base::float')];
    $outputs_ = array_combine($output_names, $output_types);
    $identifier = [
        'kind'            => 'GIST',
        'name'            => $class_name,
        'system_headers'  => ['armadillo', 'algorithm'],
        'libraries'       => ['armadillo'],
        'iterable'        => true,
        'output'          => $output,
        'result_type'     => 'fragment',
        'extras'          => [],
    ];
?>

class <?=$cgla_name?> {
 public:
  int round_num;

  <?=$cgla_name?>(int num) :
      round_num(num) {}

  void AddState(<?=$cgla_name?> other) {}

  bool ShouldIterate() {
    return round_num < 3;
  }
};

class <?=$class_name?> {
 public:
  // We don't know what type of matrix we will be passed, so it is best to be
  // type-agnostic.
  using Inner = <?=$inner_type?>;
  using Matrix = arma::Mat<Inner>;
  using cGLA = <?=$cgla_name?>;

  struct Task {
    long start_index; // Which row or column this local scheduler starts at
    long end_index; // Which row or column it ends at. Inclusive bound.
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
      long count = (round_num % 2 == 1) ? matrix.n_cols : matrix.n_rows;
      task.start_index = thread_index * count / num_threads;
      task.end_index = (thread_index + 1) * count / num_threads - 1;
      finished_scheduling = true;
      return ret;
    }
  };

  using WorkUnit = std::pair<LocalScheduler*, cGLA*>;
  using WorkUnits = std::vector<WorkUnit>;

 private:
  int round_num;
  int num_threads;
  int num_produced;
  Matrix matrix;
  arma::umat indices;
  arma::mat copy_of_data; // Updated after the second round
  std::vector<string> file_names;

  /**
   * Sort a particular column in ascending order.
   * @param data      The data whose column will be sorted in-place.
   * @param indices   After this function is done, entry (i, col_
   index) will
   *                  hold the original entry (i, col_index) before any sorting
   *                  was done.
   * @param col_index The column to sort.
   */
  void reversible_column_sort(arma::mat &data, arma::umat &indices, 
    long col_index) {
    indices.col(col_index) = sort_index(data.col(col_index));
    data.col(col_index) = sort(data.col(col_index));
  }

  /**
   * Unsort a particular column given the pre-sort indices.
   * @param data      Reference to the data to unsort.
   * @param indices   Reference to the indices as returned by
   *                  reversible_column_sort.
   * @param col_index The column to sort.
   */
  void rearrange_column(arma::mat &data, arma::umat &indices, long col_index) {
    for (size_t i = 0; i < indices.n_rows; i++) {
      uint sorted_index = indices(i, col_index);
      data(sorted_index, col_index) = copy_of_data(i, col_index);
    }
  }

  void init_col_names() {
    int column = 0;
    <?  foreach ($file_names as &$file_name) { ?>
      file_names["<?=$file_name?>"] = column;
      column++;
    <?  } ?>
  }

 public:
  <?=$class_name?>(<?=const_typed_ref_args($states)?>) {
    matrix = <?=$matrix?>.GetMatrix();
    round_num = 0;
    num_produced = 0;
    indices = arma::umat(matrix.n_rows, matrix.n_cols);
    init_col_names();
  }

  void PrepareRound(WorkUnits& workers, int suggested_num_workers) {
    round_num++;
    if (round_num == 3) {
      copy_of_data = arma::mat(matrix.memptr(), matrix.n_rows, matrix.n_cols);
    }
    if (round_num % 2 == 1) {
      int n_cols = matrix.n_cols;
      this->num_threads = std::min(n_cols, suggested_num_workers);
    } else {
      int n_rows = matrix.n_rows;
      this->num_threads = std::min(n_rows, suggested_num_workers);
    }
    std::printf("Beginning round %d with %d workers.\n", round_num,
      this->num_threads);
    std::pair<LocalScheduler*, cGLA*> worker;
    for (int counter = 0; counter < this->num_threads; counter++) {
      worker = std::make_pair(new LocalScheduler(counter, round_num,
        this->num_threads, matrix), new cGLA(round_num));
      workers.push_back(worker);
    }
  }

  // In the first round, sort each column. Store the updated indices in the
  // appropriate column of indices.
  // In the second round, update each row with the appropriate value.
  // In the third round, rearrange each column.
  void DoStep(Task& task, cGLA& gla) {
    for (long index = task.start_index; index <= task.end_index; index++) {
      if (round_num == 1) {
        reversible_column_sort(matrix, indices, index);
      } else if (round_num == 2) {
        matrix.row(index).fill(accu(matrix.row(index)) / matrix.n_cols);
      } else if (round_num == 3) {
        rearrange_column(matrix, indices, index);
      }
    }
  }

  bool GetNextResult(<?=typed_ref_args($outputs_)?>) {
    if (num_produced == matrix.n_elem) {
      return false;
    }
    int row_index = num_produced / matrix.n_rows;
    int col_index = num_produced % matrix.n_rows;
    num_produced++;
    file_name = file_names.at(col_index);
    ordered_fid = row_index;
    intensity = matrix(row_index, col_index);
    return true;
  }
};

<?
    return $identifier;
}
?>
