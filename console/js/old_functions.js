function selectQuestion(id, view, button, textBox, sectionIndex, totalRows){
	var type = $('#'+id).val();
	var options;

	if(type == 1){
		$('#'+view).empty();
		options = '<div class="form-group">';
		options += '<input type="radio" />';
		options += '<input type="text" value="No cumple" class="form-control input25 marginForControls" name="section['+sectionIndex+']['+totalRows+'][options][][optionName]" />';
		options += '<input type="text" class="form-control input60 marginForControls" placeholder="%" value="0" name="section['+sectionIndex+']['+totalRows+'][options][][value]" />';
		options += '<input type="button" value="x" class="marginForControls deleteItem" onclick="javascript:deleteOption(this, \''+view+'\', \''+sectionIndex+'\', \''+totalRows+'\');" />';
		options += '</div>';
		options += '<div class="form-group">';
		options += '<input type="radio" />';
		options += '<input type="text" value="Cumple parcialmente" class="form-control input25 marginForControls" name="section['+sectionIndex+']['+totalRows+'][options][][optionName]" />';
		options += '<input type="text" class="form-control input60 marginForControls" placeholder="%" value="50" name="section['+sectionIndex+']['+totalRows+'][options][][value]" />';
		options += '<input type="button" value="x" class="marginForControls deleteItem" onclick="javascript:deleteOption(this, \''+view+'\', \''+sectionIndex+'\', \''+totalRows+'\');" />';
		options += '</div>';
		options += '<div class="form-group">';
		options += '<input type="radio" />';
		options += '<input type="text" value="Cumple" class="form-control input25 marginForControls" name="section['+sectionIndex+']['+totalRows+'][options][][optionName]" />';
		options += '<input type="text" class="form-control input60 marginForControls" placeholder="%" value="100" name="section['+sectionIndex+']['+totalRows+'][options][][value]" />';
		options += '<input type="button" value="x" class="marginForControls deleteItem" onclick="javascript:deleteOption(this, \''+view+'\', \''+sectionIndex+'\', \''+totalRows+'\');" />';
		options += '</div>';
		options += '<div class="form-group">';
		options += '<input type="radio" />';
		options += '<input type="text" value="No aplica" class="form-control input25 marginForControls" name="section['+sectionIndex+']['+totalRows+'][options][][optionName]" />';
		options += '<input type="text" class="form-control input60 marginForControls" placeholder="%" value="" name="section['+sectionIndex+']['+totalRows+'][options][][value]" />';
		options += '<input type="button" value="x" class="marginForControls deleteItem" onclick="javascript:deleteOption(this, \''+view+'\', \''+sectionIndex+'\', \''+totalRows+'\');" />';
		options += '</div>';
		$('#'+button).removeClass('hide');
		$('#'+button).click(function(){
			addOption(type, view, sectionIndex, totalRows);
		});
		$('#'+textBox).addClass('hide');
	}
	else if(type == 2){
		$('#'+view).empty();
		options = '<div class="form-group">';
		options += '<input type="checkbox" />';
		options += '<input type="text" class="form-control input25 marginForControls" name="section['+sectionIndex+']['+totalRows+'][options][][optionName]" />';
		options += '<input type="text" class="form-control input60 marginForControls" placeholder="%" name="section['+sectionIndex+']['+totalRows+'][options][][value]" />';
		options += '<input type="button" value="x" class="marginForControls deleteItem" onclick="javascript:deleteOption(this, \''+view+'\', \''+sectionIndex+'\', \''+totalRows+'\');" />';
		options += '</div>';
		$('#'+button).removeClass('hide');
		$('#'+button).click(function(){
			addOption(type, view, sectionIndex, totalRows);
		});
		$('#'+textBox).addClass('hide');
	}
	else if(type == 3){
		$('#'+view).empty();
		$('#'+button).addClass('hide');
		$('#'+textBox).removeClass('hide');
	}

	$('#'+view).html(options);
}

function addOption(type, view, sectionIndex, totalRows){
	
	var arrItems = $('#'+view+' > div');
	var nextIndex = arrItems.length;
	var newItem;

	if(type == 1){
		newItem = '<div class="form-group">';
		newItem += '<input type="radio" />';
		newItem += '<input type="text" class="form-control input25 marginForControls" name="section['+sectionIndex+']['+totalRows+'][options][][optionName]" />';
		newItem += '<input type="text" class="form-control input60 marginForControls" placeholder="%" value="" name="section['+sectionIndex+']['+totalRows+'][options][][value]" />';
		newItem += '<input type="button" value="x" class="marginForControls deleteItem" onclick="javascript:deleteOption(this, \''+view+'\', \''+sectionIndex+'\', \''+totalRows+'\');" />';
		newItem += '</div>';
	}
	else if(type == 2){
		newItem = '<div class="form-group">';
		newItem += '<input type="checkbox" />';
		newItem += '<input type="text" class="form-control input25 marginForControls" name="section['+sectionIndex+']['+totalRows+'][options][][optionName]" />';
		newItem += '<input type="text" class="form-control input60 marginForControls" placeholder="%" name="section['+sectionIndex+']['+totalRows+'][options][][value]" />';
		newItem += '<input type="button" value="x" class="marginForControls deleteItem" onclick="javascript:deleteOption(this, \''+view+'\', \''+sectionIndex+'\', \''+totalRows+'\');" />';
		newItem += '</div>';
	}

	$('#'+view).append(newItem);
	
}