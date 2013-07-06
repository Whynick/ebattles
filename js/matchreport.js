function SwitchSelected(id)
{
	var select = document.getElementById('rank'+id);
	nbr_ranks = select.length
	new_rank_txt = select.options[select.selectedIndex].text

	for (k = 1; k <= nbr_ranks; k++)
	{
		old_rank_found=0
		for (j = 1; j <= nbr_ranks; j++)
		{
			var select = document.getElementById('rank'+j);
			rank_txt = select.options[select.selectedIndex].text
			if (rank_txt == 'Team #'+k) {old_rank_found=1}
		}
		if (old_rank_found==0) {old_rank = k}
	}

	for (j = 1; j <= nbr_ranks; j++)
	{
		if (j!=id)
		{
			var select = document.getElementById('rank'+j);
			rank_txt = select.options[select.selectedIndex].text
			if (rank_txt == new_rank_txt) {select.selectedIndex=old_rank-1}
		}
	}
}

// Forms
function initDatePicker() {
	$('.timepicker').datetimepicker({
	ampm: true,
	timeFormat: 'hh:mm TT',
	stepHour: 1,
	stepMinute: 10,
	minDate: 0
	});
}

function clearDate(frm)
{
	document.getElementById("f_date").value = ""
}