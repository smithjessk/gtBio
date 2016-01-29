<?
function Median_Polish(array $t_args, array $inputs, array $outputs,
    array $states) {
    $className = generate_name('Median_Polish');
    $inputs_ = array_combine(['file_name', 'ordered_fid', 'ordered_fsetid',
      'fid', 'fsetid', 'intensity'], $inputs);
    $output_types = [lookupType('base::string'), lookupType('base::int'),
      lookupType('base::int')];
    $outputs_ = array_combine(['file_name', 'intensity', 'fsetid'],
      $output_types);
    $outputs = array_combine(array_keys($outputs), $output_types);

    $file_names = $t_args["files"];
    $num_files = sizeof($file_names);

    $sys_headers  = ['armadillo', 'unordered_map'];
    $user_headers = [];
    $lib_headers  = [];
    $libraries    = ['armadillo'];
    $properties   = ['tuples'];
    $extra        = [];
    $identifier = [
        'kind'              => 'GLA',
        'name'              => $className,
        'system_headers'    => $sys_headers,
        'user_headers'      => $user_headers,
        'lib_headers'       => $lib_headers,
        'libraries'         => $libraries,
        'iterable'          => false,
        'input'             => $inputs,
        'output'            => $outputs,
        'result_type'       => 'multi',
        'properties'        => $properties,
        'extra'             => $extra,
    ];
?>

extern "C" void median_polish_no_copy(double *data, size_t rows, size_t cols,
                                      double *results, double *resultsSE);

class <?=$className?> {
 private:
  int num_probes_encountered;
  int num_produced;
  int fsetid;
  // Map of file name to column probeset_matrix
  std::vector<std::string> file_names;
  std::vector<float> intensities;

  void resize_matrix(int num_rows) {
    int old_num_rows = probeset_matrix.n_rows;
    probeset_matrix = arma::resize(probeset_matrix, num_rows, <?=$num_files?>);
    for (size_t index = old_num_rows; index < num_rows; index++) {
      probeset_matrix.row(index).fill(0);
    }
  }

  void init_file_names() {
    int column = 0;
    <?  foreach ($file_names as &$file_name) { ?>
      file_names.push_back("<?=$file_name?>");
      column++;
    <?  } ?>
  }

  int get_column_index(std::string file_name) {
    for (size_t index = 0; index < file_name.size(); index++) {
      if (file_name == file_names.at(index)) {
        return index;
      }
    }
    return -1;
  }

 public:
  // Columns are files, rows are probes in this probeset
  arma::Mat<float> probeset_matrix;

  <?=$className?>()
    : probeset_matrix(50, <?=$num_files?>),
    num_probes_encountered(0),
    num_produced(0),
    intensities(<?=$num_files?>) {
      init_file_names();
      probeset_matrix.fill(0);
  }

  void AddItem(<?=const_typed_ref_args($inputs_)?>) {
    this->fsetid = fsetid;
    num_probes_encountered++;
    if (num_probes_encountered > probeset_matrix.n_rows) {
      resize_matrix(1.2 * probeset_matrix.n_rows);
    }
    int col_index = get_column_index(file_name.ToString());
    probeset_matrix(ordered_fsetid, col_index) = intensity;
  }

  void AddState(<?=$className?> &other) {
    this->probeset_matrix += other.probeset_matrix;
  }

  void Finalize() {
    resize_matrix(num_probes_encountered);
    probeset_matrix = log2(probeset_matrix);
    arma::mat temp_mat = arma::conv_to<arma::mat>::from(probeset_matrix);
    double *results = (double *) malloc(sizeof(double) * <?=$num_files?>);
    double *resultsSE = (double *) malloc(sizeof(double) * <?=$num_files?>);
    median_polish_no_copy(temp_mat.memptr(), num_probes_encountered, 
      <?=$num_files?>, results, resultsSE);
    intensities.assign(results, results + sizeof(double) * <?=$num_files?>);
    std::cout << "<?=$className?> finished" << std::endl;
  }

  bool GetNextResult(<?=typed_ref_args($outputs_)?>) const {
    if (num_produced == <?=$num_files?>) {
      return false;
    }
    file_name = file_names.at(num_produced);
    intensity = intensities.at(num_produced);
    fsetid = this->fsetid;
    return true;
  }
};

<?
    return $identifier;
}
?>
