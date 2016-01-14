<?
function BuildMatrix(array $t_args, array $inputs, array $outputs)
{
  $className = generate_name('BuildMatrix');
  $inputs_ = array_combine(['tuple'], $inputs);
  $tuple  = $inputs_['tuple'];
  $file_names = $t_args['files'];
  $num_files = count($file_names);
  $sys_headers  = ['armadillo', 'map'];
  $user_headers = [];
  $lib_headers  = [];
  $libraries    = ['armadillo'];
  $properties   = ['matrix', 'tuples'];
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
    'result_type'       => 'single',
    'finalize_as_state' => true,
    'properties'        => $properties,
    'extra'             => $extra,
  ];
?>

class <?=$className?>;

class <?=$className?> {
 public:
  using Inner = <?=$type?>;

 private:
  arma::fmat entries;
  std::vector<std::vector<bool>> filed;
  std::vector<int> fids; // Index = the row in entries that holds the fid
  std::vector<std::string> file_names;
  
  void init_matrices() {
    entries.fill(0);

  }

  void init_col_names() {
<?  foreach ($file_names as $file_name) { ?>
      file_names.push_back(<?=$file_name?>);
  }

  void resize() {
    entries.resize(fid_to_row.size() + 1, <?=$num_files?>);
    filled.resize(fid_to_row.size() + 1, <?=$num_files?>);
    filled.push_back(new std::vector<bool>(<?=$num_files?>));
    for (size_t j = 0; j < <?=$num_files?>; j++) {
      entries.at(fid_to_row.size(), j) = 0;
      filled.at(fid_to_row.size()).at(j) = false;
    }
  }

  // Update the entries matrix and also mark that we filled out this spot
  void set(int row_index, int col_index, float intensity) {
    entries(row_index, col_index) = intensity;
    filled.at(row_index).at(col_index) = true;
  }

  // Returns the new row index for this fid
  int append_fid(int fid) {
    resize();
    fids.push_back(fid);
    return get_row(fid);
  }

  // Take every set entry that the other GLA set and incorporate it into this 
  // GLA.
  // This method does so for a particular row. 
  void update_entries_for_row(arma::fmat &other_entries, 
    std::vector<std::vector<bool> other_filled, int other_row, int fid) {
    for (size_t col = 0; col < file_names.size(); col++) {
      bool other_filled_entry = other_filled.at(other_row).at(col);
      if (!other_filled_entry) {
        continue;
      }
      int other_intensity = other_entries(other_row, col);
      int my_row = get_row(fid);
      if (my_row == -1) {
        my_row = append_fid(fid);
      }
      set(my_row, col_index, other_intensity);
    }
  }

 public:
  <?=$className?>()
    : entries(0, <?=$num_files?>),
      filled(0, <?=$num_files?>),
      fids(0),
      file_names(0) {
        init_col_names();
    }

  int get_row(int fid) {
    for (size_t i = 0; i < fids.size(); i++) {
      if (fids.get(i) == fid) {
        return i;
      }
    }
    return -1;
  }

  int get_col(std::string file_name) {
    for (size_t i = 0; i < file_names.size(); i++) {
      if (file_names.at(i) == file_name) {
        return i;
      }
    }
    return -1;
  }

  std::vector<int> &get_fids() {
    return fids;
  }

  arma::fmat &get_entries() {
    return entries;
  }

  std::vector<std::vector<bool>> &get_filled() {
    returnh filled;
  }

  // if the fid was already entered for one column, use that row in the 
  // appropriate column. Else append it as a new row.
  void AddItem(<?const_typed_ref_args($inputs)?>) {
    int row_index = get_row(fid),
      col_index = get_col(file_name);
    if (row_index == -1) {
      row_index = append_fid(fid);
    }
    set(row_index, col_index, intensity);
  }

  // For every entry that other has set, take that value and put it in our 
  // entries matrix.
  void AddState(<?=$className?> &other) {
    std::vector<int> other_fids = other.get_fids();
    arma::fmat other_entries = other.get_entries();
    std::vector<std::vector<bool>> other_filled = other.get_filled();
    for (size_t row = 0; row < other_fids.size(); row++) {
      int fid = other_fids.at(row);
      update_entries_for_row(other_entries, other_filled, row, fid);
    }
  }

  void FinalizeState() {
    std::cout << "<?=$className?> finished" << endl;
  }

  const Mat<Inner>& GetMatrix() const {
    return entries;
  }

  const vector<Tuple>& GetTuples() const {
    return extra;
  }

  long GetCount() const {
    return count;
  }
}

<?
    return $identifier;
}
?>
