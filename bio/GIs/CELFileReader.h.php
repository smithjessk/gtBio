<?
function CELFileReader(array $t_args, array $outputs) {
    $className = generate_name('CELFileReader');

    $fType = lookupType('float');
    $sType = lookupType('smallint');

    $outputs = array_combine(array_keys($outputs),
        [lookupType('bio::Variable_Matrix', ['type' => $fType]),
         lookupType('bio::Variable_Matrix', ['type' => $fType]),
         lookupType('bio::Variable_Matrix', ['type' => $sType])]);

    // Locally named outputs. Used for ProduceTuple
    $outputs_ = array_combine(['intensity', 'stddev', 'pixels'], $outputs);

    $identifier = [
        'kind'           => 'GI',
        'name'           => $className,
        'system_headers' => ['string'],
        'user_headers'   => [],
        'lib_headers'    => ['CELFileReader.h'],
        'libraries'      => ['armadillo'],
        'output'         => $outputs
    ];
?>

class <?=$className?> {
 private:
  // Absolute file path of the CEL File to be read.
  std::string fileName;

  // Whether the single tuple for this file has been produced.
  bool finished;

 public:
  <?=$className?>(GIStreamProxy& _stream)
      : fileName(_stream.get_file_name()),
        finished(false) {
  }

  bool ProduceTuple(<?=typed_ref_args($outputs_)?>) {
    if (!finished) {
      gtBio::CELFileReader in(fileName.c_str());
      gtBio::CELBase::pointer data = in.readFile();
      intensity = data->getIntensityMatrix();
      stddev = data->getStdDevMatrix();
      pixels = data->getPixelsMatrix();
      return finished = true;
    } else {
      return false;
    }
  }
};

<?
    return $identifier;
}
?>
