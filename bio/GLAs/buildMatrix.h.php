<?
function BuildMatrix(array $t_args, array $inputs, array $outputs)
{
  $className = generate_name('BuildMatrix');
  $inputs_ = array_combine(['file_name', 'chip_type', 'fid', 'intensity'], 
    $inputs);
  $output_type = [lookupType('statistics::Variable_Matrix', 
    ['type' => lookupType('float')])];
  $outputs_ = array_combine(['matrix'], $output_type);
  $file_names = $t_args['files'];
  $num_files = count($file_names);
  $sys_headers  = ['armadillo', 'unordered_map'];
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
    'output'            => $output_type,
    'result_type'       => 'single',
    'finalize_as_state' => true,
    'properties'        => $properties,
    'extra'             => $extra,
  ];
?>

class <?=$className?>;

class <?=$className?> {
 private:
  arma::fmat entries;
  std::vector<std::vector<bool>> filled;
  std::unordered_map<int, int> fids; // fid to row
  std::unordered_map<std::string, int> file_names; // file_name to column
  int num_fids_processed;
  int max_fid;

  // Update the entries matrix and also mark that we filled out this spot
  void set(int row_index, int col_index, float intensity) {
    entries(row_index, col_index) = intensity;
    filled.at(row_index).at(col_index) = true;
  }

  void init_col_names() {
    int column = 0;
<?  foreach ($file_names as &$file_name) { ?>
      file_names["<?=$file_name?>"] = column;
      column++;
<?  } ?>
  }

  // For each fid we could end up processing, enter a vector that has one entry
  // for each file name. This matrix is the same size as entries.
  void resize_filled() {
    std::vector<bool> new_row(<?=$num_files?>);
    for (size_t j = 0; j < <?=$num_files?>; j++) {
      new_row.at(j) = false;
    }
    filled.resize(max_fid, new_row);
  }

  void resize() {
    entries.resize(max_fid, <?=$num_files?>);
    resize_filled();
    fids.reserve(max_fid);
  }

  // Returns the new row index for this fid
  int append_fid(int fid) {
    if (fid > max_fid) {
      max_fid = fid;
      resize();
    }
    fids[fid] = num_fids_processed;
    num_fids_processed++;
    return num_fids_processed - 1;
  }

  // Take every set entry that the other GLA set and incorporate it into this 
  // GLA.
  // This method does so for a particular row. 
  void update_entries_for_row(arma::fmat &other_entries, 
    std::vector<std::vector<bool>> &other_filled, int other_row, int fid) {
    for (auto it = file_names.begin(); it != file_names.end(); ++it) {
      int col = it->second;
      bool other_filled_entry = other_filled.at(other_row).at(col);
      if (!other_filled_entry) {
        continue;
      }
      int other_intensity = other_entries(other_row, col);
      int my_row = get_row(fid);
      if (my_row == -1) {
        my_row = append_fid(fid);
      }
      set(my_row, col, other_intensity);
    }
  }

 public:
  <?=$className?>()
    : entries(0, <?=$num_files?>),
      fids(0),
      filled(0),
      file_names(0),
      max_fid(0),
      num_fids_processed(0) {
        init_col_names();
    }

  int get_row(int fid) {
    try {
      return fids.at(fid);
    } catch (const std::out_of_range &oor) {
      return -1;
    }
  }

  int get_col(std::string file_name) {
    try {
      return file_names.at(file_name);
    } catch (const std::out_of_range &oor) {
      return -1;
    }
  }

  std::unordered_map<int, int> &get_fids() {
    return fids;
  }

  arma::fmat &get_entries() {
    return entries;
  }

  std::vector<std::vector<bool>> &get_filled() {
    return filled;
  }

  // if the fid was already entered for one column, use that row in the 
  // appropriate column. Else append it as a new row.
  void AddItem(<?=const_typed_ref_args($inputs_)?>) {
    int row_index = get_row(fid),
      col_index = get_col(file_name.ToString());
    if (row_index == -1) {
      row_index = append_fid(fid);
    }
    set(row_index, col_index, intensity);
  }

  // For every entry that other has set, take that value and put it in our 
  // entries matrix.
  void AddState(<?=$className?> &other) {
    std::cout << "Adding state" << std::endl;
    std::unordered_map<int, int> other_fids = other.get_fids(); 
    arma::fmat other_entries = other.get_entries();
    std::vector<std::vector<bool>> other_filled = other.get_filled();
    // iterator is over pairs that signify (fid, row)
    for (auto it = other_fids.begin(); it != other_fids.end(); ++it) {
      int fid = it->first, row = it->second;
      update_entries_for_row(other_entries, other_filled, row, fid);
    }
  }

  void FinalizeState() {
    std::cout << "<?=$className?> finished" << std::endl;
  }

  const arma::Mat<float>& GetMatrix() const {
    return entries;
  }

  void GetResult(<?=typed_ref_args($outputs_)?>) const {
    matrix = entries;
  }
};

<?
    return $identifier;
}
?>
