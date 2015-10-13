// https://github.com/bmbolstad/preprocessCore/blob/master/src/rma_background4.c#L444

<?
function Background_Correct(array $t_args, array $inputs, array $outputs) {
  $className = generate_name('Background_Correct');

  $identifier = [
      'kind' => 'GLA',
      'name' => $className,
      'iterable' => true,
      'input'           => $inputs,
      'output'          => $outputs,
      // 'generated_state' => $constantState,
      'result_type'     => 'single',
      'user_headers'    => ['densityFunctions.h']
  ];
?>

class <?=$className?> {
 private:

 public:
  void AddItem(<?=const_typed_ref_args($inputs)?>) {}

  void AddState(const <?=$className?& other) {
    // Update the reference to the state with each column in other that has
    // been updated (other.updatedColumns()) ?
    // Or, don't even call this? Or change all of this to a GIST?
  }

  bool ShouldIterate(<?=$constantState?>& state) {
    return false;
  }
}

<?
  return $identifier;
}
?>