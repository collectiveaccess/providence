var page = require('webpage').create(),
    system = require('system');

if (system.args.length < 3) {
    console.log('Usage: rasterise.js URL filename [paperwidth*paperheight|paperformat] [orientation] [marginTop] [marginRight] [marginBottom] [marginLeft]');
    phantom.exit(1);
} else {
    var address = system.args[1];
    var output = system.args[2];
    
    var m;
    if (system.args.length < 6) { m = "0mm"; }
    if (system.args.length == 6) { m = system.args[5]; }
    if (system.args.length > 6) { m = { "top": system.args[5], "right": system.args[6], "bottom": system.args[7], "left": system.args[8] }; }
    
   // page.viewportSize = { width: 600, height: 600 };
    page.paperSize = {
        format: system.args[3],
        orientation: system.args[4],
        margin: m
    };
    page.open(address, function (status) {
        if (status !== 'success') {
            console.log('Unable to load the address!');
        } else {
            if (page.evaluate(function(){return typeof PhantomJSPrinting == "object";})) {
                paperSize = page.paperSize;
                paperSize.header.height = page.evaluate(function() {
                    return PhantomJSPrinting.header.height;
                });
                paperSize.header.contents = phantom.callback(function(pageNum, numPages) {
                    return page.evaluate(function(pageNum, numPages){return PhantomJSPrinting.header.contents(pageNum, numPages);}, pageNum, numPages);
                });
                paperSize.footer.height = page.evaluate(function() {
                    return PhantomJSPrinting.footer.height;
                });
                paperSize.footer.contents = phantom.callback(function(pageNum, numPages) {
                    return page.evaluate(function(pageNum, numPages){return PhantomJSPrinting.footer.contents(pageNum, numPages);}, pageNum, numPages);
                });
                page.paperSize = paperSize;
            }
            window.setTimeout(function () {
                page.render(output);
                phantom.exit();
            }, 200);
        }
    });
}