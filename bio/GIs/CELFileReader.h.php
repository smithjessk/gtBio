<?
function CELFileReader(array $t_args, array $outputs) {
    $className = generate_name('CELFileReader');
    $floatType = lookupType('float');
    $intType = lookupType('int');
    $stringType = lookupType('string');
    $outputs = array_combine(array_keys($outputs), [
        lookupType('statistics::Variable_Matrix', ['type' => $floatType]),
        lookupType('statistics::Variable_Matrix', ['type' => $floatType]),
        lookupType('statistics::Variable_Matrix', ['type' => $intType]),
        lookupType('base::String'),
        lookupType('base::String')
    ]);
    // Locally named outputs. Used for ProduceTuple
    $outputs_ = array_combine(['intensity', 'stddev', 'pixels', 
      'annotation', 'protocol_date'], $outputs);
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
  std::string filePath;

  // Whether the single tuple for this file has been produced.
  bool finished;

 public:
  <?=$className?>(GIStreamProxy& _stream)
      : filePath(_stream.get_file_name()),
        finished(false) {
  }

  bool ProduceTuple(<?=typed_ref_args($outputs_)?>) {
    if (!finished) {
      gtBio::CELFileReader in(filePath.c_str());
      gtBio::CELBase::pointer data = in.readFile();
      intensity = data->GetIntensityMatrix();
      stddev = data->GetStdDevMatrix();
      pixels = data->GetPixelsMatrix();
      annotation = data->GetAnnotation();
      protocol_date = data->GetProtocolDate();
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
