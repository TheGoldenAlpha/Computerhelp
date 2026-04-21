/*
  Dropdown Handler for two depending dropdowns
  by jGC
  
  Version: 1.0
*/

function handleDropdownDependency(mainDropdown, dependentDropdown){
  //reference the both selects
  var $mainDd = $("select#"+mainDropdown);
  var $dependentDd = $("select#"+dependentDropdown);
  
  $dependentDd.removeClass();
  $dependentDd.addClass("dependent_dropdown");
  
  updateDropdown($dependentDd, $mainDd.val());
  
  $mainDd.on("change", function(){
    var tmp = $(this).val();
    updateDropdown($dependentDd, tmp);  
  });
}

function updateDropdown($dropdown, relation) {

  //deactiate all options in the dropdown
  $dropdown.find("option").attr("style", "");
  //unset the current value
  $dropdown.val("");

  //disable the dropdown if no relation is specified
  //if(!relation) return $dropdown.prop("disabled", true);

  //find all options to be activated
  var $allRelatedOptions = $dropdown.find("[rel="+relation+"]");
  if($allRelatedOptions.length === 0){
    alert("Es steht keine Auswahl zur Verfügung.");
    return $dropdown.prop("disabled", true);    
  }
  else {
    $dropdown.val($allRelatedOptions.val());
  }
  $allRelatedOptions.show();
  $dropdown.prop("disabled", false);  
}

// Requires hidden input with the same id as the dropdown
function updateOptionDropdown(id) {
  var $hiddenInput= $("input#"+id);
  var $option = $("select#"+id).find("option[value="+$hiddenInput.val()+"]");
  $option.prop("selected", true);
  $hiddenInput.remove();
}