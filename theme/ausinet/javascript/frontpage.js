(function($) {

	// Myenrolled course slick slider intialized.
	$('.enrolled-course .slider').slick(
		{
			slidesToShow: 3,
			slidesToScroll: 1,
			swipeToSlide: true,
			responsive: [
	            {
	                breakpoint: 900,
	                settings: {
	                    slidesToShow: 2	                    	                    
	                }
	            },
	            {
	                breakpoint: 600,
	                settings: {
	                    slidesToShow: 1	                    
	                }
	            },
	        ],
		}
	);
}) (jQuery)