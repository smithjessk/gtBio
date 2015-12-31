<?
function CELFileReader(array $t_args, array $outputs) {
    $className = generate_name('CELFileReader');

    $floatType = lookupType('float');
    $intType = lookupType('int');
    $stringType = lookupType('string');

    $outputs = array_combine(array_keys($outputs), [
     $intType,
     $stringType,
     $intType,
     $floatType
    ]);

    $output_names = [
      'chip_number',
      'chip_type',
      'fid',
      'intensity'
    ];

    // Locally named outputs. Used for ProduceTuple
    $outputs_ = array_combine($output_names, $outputs);

    $identifier = [
        'kind'           => 'GI',
        'name'           => $className,
        'system_headers' => ['string', 'cstdio'],
        'user_headers'   => [],
        'lib_headers'    => ['CELFileReader.h'],
        'libraries'      => ['armadillo'],
        'output'         => $outputs
    ];
?>

class <?=$className?> {
 private:
  // Whether the single tuple for this file has been produced.
  bool finished;

  // Relative to this query
  int _chip_number;

  // Read from the CEL file
  arma::fmat _intensity_matrix;
  arma::fmat _std_dev_matrix;
  arma::Mat<int32_t> _pixels_matrix;
  std::string _chip_type;

  // Used to iterate through the chips' entries, row by row.
  int _fid;

  // file_name must be an absolute path
  void initialize_matrices(std::string file_name) {
    gtBio::CELFileReader in(file_name.c_str());
    gtBio::CELBase::pointer data = in.readFile();
    _intensity_matrix = data->getIntensityMatrix();
    _std_dev_matrix = data->getStdDevMatrix();
    _pixels_matrix = data->getPixelsMatrix();
    //    chip_type = data->get_chip_type();
    _chip_type = "test";
    _chip_number = 100;
  }

 public:
  <?=$className?>(GIStreamProxy& _stream)
    :  finished(false),
    _fid(0) {
   initialize_matrices(_stream.get_file_name());
  }

  bool ProduceTuple(<?=typed_ref_args($outputs_)?>) {
    if (!finished) {
      _fid++;
      int row_index = _fid / _intensity_matrix.n_cols;
      int col_index = _fid % _intensity_matrix.n_cols;
      std::printf("fid = %d, row = %d, col = %d\n", fid, row_index, col_index);
      chip_number = _chip_number;
      chip_type = _chip_type;
      fid = _fid;
      intensity = _intensity_matrix(row_index, col_index);
      if (_fid < _intensity_matrix.n_elem) {
        return true;
      } else {
        finished = true;
        return true;
      }
    } else {
      return false;
    }
  }
};

<?
    return $identifier;
}
?>
