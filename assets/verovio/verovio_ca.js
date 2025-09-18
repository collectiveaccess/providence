var tk;

function verovio_render(fieldName, targetId) {
	// if targetId is shown, hide it
	if (jQuery('#' + targetId).parent().is(':visible')) {
		jQuery('#' + targetId).parent().hide();
	} else {
		// otherwise, render the score
		tk = new verovio.toolkit();
		var content = jQuery('#' + fieldName).val();
		if (content) {
			
			tk.setOptions({
				pageHeight: 150,
				adjustPageWidth: true,
				pageMarginTop:0,
				pageMarginBottom:0,
				pageMarginLeft:0,
				pageMarginRight:0,
				footer:'none',
				header:'none'
			});
			tk.loadData(content);
			// render the score
			jQuery('#' + targetId).html(tk.renderToSVG());
			jQuery('#' + targetId).parent().show();
		} else {
			jQuery('#' + targetId).hide();
		}
	}
}

function verovio_render_summary() {
	$(".verovio_summary").each(function() {
		if($(this).attr("data-rendered") == "true") {
			return; // already rendered
		}
		let content = $(this).children(".verovio_value").text();
		console.log("content", content);
		if(content) {
			// otherwise, render the score
			tk = new verovio.toolkit();
			tk.setOptions({
				pageHeight: 260,
				adjustPageWidth: true,
				pageMarginTop:0,
				pageMarginBottom:0,
				pageMarginLeft:0,
				pageMarginRight:0,
				footer:'none',
				header:'none'
			});
			tk.loadData(content);
			$(this).children(".verovio_svg").html(tk.renderToSVG());
			$(this).children(".verovio_value").hide();
			$(this).children(".verovio_svg").show();
			$(this).attr("data-rendered", "true");

		}
	});
}

function verovio_download(targetId) {
	var svg_data = document.getElementById(targetId).innerHTML;
	var head = '<svg title="graph" version="1.1" xmlns="http://www.w3.org/2000/svg">';
	var style = '<style>circle {cursor: pointer;stroke-width: 1.5px;}text {font: 10px arial;}path {stroke: DimGrey;stroke-width: 1.5px;}</style>';
	var full_svg = head +  style + svg_data + "</svg>";
	var blob = new Blob([full_svg], {type: "image/svg+xml"});  
	saveAs(blob, "music_score.svg");
}

function verovio_downloadJPG(targetId) {
	/* Source : https://takuti.me/note/javascript-save-svg-as-image/ */

  // fetch SVG-rendered image as a blob object
  const svg = document.querySelector('svg');
  const data = document.getElementById(targetId).innerHTML;
  const svgBlob = new Blob([data], {
    type: 'image/svg+xml;charset=utf-8'
  });

  // convert the blob object to a dedicated URL
  const url = URL.createObjectURL(svgBlob);

  // load the SVG blob to a flesh image object
  const img = new Image();
  img.addEventListener('load', () => {
    // draw the image on an ad-hoc canvas
    const bbox = svg.getBBox();

    const canvas = document.createElement('canvas');
    canvas.width = bbox.width;
    canvas.height = bbox.height;

    const context = canvas.getContext('2d');
    context.drawImage(img, 0, 0, bbox.width, bbox.height);

    URL.revokeObjectURL(url);
	canvas.toBlob((blob) => {
    		saveAs(blob, "music_score.jpg");
		},
		"image/jpeg",
		0.95,
	);
    
  });
  img.src = url;
};