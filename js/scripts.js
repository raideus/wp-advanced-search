/******************************************************************
Global Site Scripts 
******************************************************************/


/* ----------------------------------------------------------

:: Modernizr Tests

We can use Modernizr.load to load certain scripts &
libraries only when they're needed.

-------------------------------------------------------------*/


/* Support for Responsive Designs */
Modernizr.load([{

	    // Test if browser supports media queries
	    test : Modernizr.mq('only all'),
	    // If not, load Respond.js
	    nope : ['js/respond.js']

}]); 

/* Support CSS Selectors in IE 6-8 */
/*Modernizr.load([{

		// Test for border-radius support (effectively tests for IE 6-8)
	    test : Modernizr.borderradius,
	    // Load Selectivizr to enable CSS selectors in IE 6-8
	    nope : ['js/selectivizr.min.js']

}]);*/


/* ----------------------------------------------------------

:: jQuery Scripts

Set up jQuery scripts needed on all site pages.  

-------------------------------------------------------------*/

jQuery(document).ready(function($) {

	//jQuery Scripts here

});