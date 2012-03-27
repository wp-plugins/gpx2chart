/* Tick formatters for Flot by Alex Chalupka
 * http://alexchalupka.com - lupka31@gmail.com - @lupka */
 
 

/* ------------------- No Decimals ------------------- */
/* Function: hides ticks that are not whole numbers    */

function tf_no_decimals(val, axis) {
	var floatm = parseFloat(val);
	if(floatm % 1 == 0)
		return parseInt(floatm);
	else
		return '';
}
		
		
		
/* ------------------- NBA Season Year ------------------- */
/* Function: based on start year (e.g. 2011) adds 2nd half (e.g., 2011-12) */
/* NOTE: This also performs the function of tf_no_decimals */

function tf_nba_season_year(val, axis) {
	var floatm = parseFloat(val);
	if(floatm % 1 == 0)
	{
		var start_year = parseInt(floatm);
		return start_year + '-' + (start_year+1).toString().substring(2,4);
	}
	else
		return '';
}     
 
