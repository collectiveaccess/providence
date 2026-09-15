<!DOCTYPE html>
<html>
	<head>
		<style>
			/* 1. Define the page margins to make room for headers and footers */
			@page {
				size: A4;
				margin: 2.5cm 2cm 2.5cm 2cm; /* Top, Right, Bottom, Left */
				
				/* 2. Assign the running elements to specific margin boxes */
				@top-center {
					content: element(pageHeader);
				}
				@bottom-center {
					content: element(pageFooter);
				}
			}
		
			/* 3. Take elements out of the normal flow and make them "running" */
			div.header {
				position: running(pageHeader);
				width: 100%;
				border-bottom: 1px solid #ccc;
				padding-bottom: 5px;
			}
		
			div.footer {
				position: running(pageFooter);
				width: 100%;
				border-top: 1px solid #ccc;
				padding-top: 5px;
				font-size: 9pt;
				color: #555;
			}
		
			/* 4. Implement dynamic page numbering using CSS counters */
			.page-number::after {
				content: counter(page);
			}
			.total-pages::after {
				content: counter(pages);
			}
		
			/* General styling for content */
			body {
				font-family: Arial, sans-serif;
				line-height: 1.6;
			}
			.content {
				margin-top: 20px;
			}
		</style>
	</head>
	<body>
	
