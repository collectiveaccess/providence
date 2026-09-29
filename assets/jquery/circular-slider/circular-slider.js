/*! The MIT License (MIT)

Copyright (c) 2014 Prince John Wesley <princejohnwesley@gmail.com>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

**/
(function($, undefined) {

    "use strict";
    $.fn.CircularSlider = function(options) {

        var slider = this;

        if (slider.find('div.jcs-panel').length !== 0) throw "Already Created!!!";

        var drawIndicatorBall = function(jcsComponents, radius) {
            if(jcsComponents.jcsIndicator.width() && jcsComponents.jcsIndicator.height())
                return;

            jcsComponents.jcsIndicator.css({
                'width': (radius / 5) + "px",
                'height': (radius / 5) + "px",
            });
        };

        var shapes = {
            "Circle": {
                drawShape: function(jcsComponents, radius) {
                    var d = radius * 2;
                    var rpx = d + "px";
                    var jcs = jcsComponents.jcs;
                    var jcsValue = jcsComponents.jcsValue;
                    var jcsPanel = jcsComponents.jcsPanel;

                    jcs.css({
                        'width': rpx,
                        'height': rpx,
                        'border-radius': rpx
                    });

                    var pd = d + (radius / 10);

                    jcsPanel.css({
                        'border-width': (radius / 10) + 'px',
                        'border-radius': pd + 'px',
                    });

                    var outerArea = (jcs.outerWidth() - jcs.innerWidth()) + (jcsValue.outerWidth() - jcsValue.innerWidth());
                    var iRadius = settings.innerCircleRatio * radius;
                    var corner = radius - iRadius - outerArea / 2;
                    jcsValue.css({
                        'width': (iRadius * 2) + "px",
                        'height': (iRadius * 2) + "px",
                        'font-size': iRadius / 2 + "px",
                        'top': corner + "px",
                        'left': corner + "px",
                    });
                },
                getCenter: function(jcsPosition, jcsRadius) {
                    return {
                        x: jcsPosition.left + jcsRadius,
                        y: jcsPosition.top + jcsRadius,
                        r: jcsRadius
                    };
                },
                deg2Val: function(deg) {
                    if (deg < 0 || deg > 359)
                        throw "Invalid angle " + deg;

                    deg = (deg + 90) % 360;
                    return Math.round(deg * (range / 360.0)) + settings.min;
                },
                val2Deg: function(value) {
                    if (value < settings.min || value > settings.max)
                        throw "Invalid range " + value;

                    var nth = value - settings.min;

                    return (Math.round(nth * (360.0 / range)) - 90) % 360;
                },
            },
            "Half Circle": {
                drawShape: function(jcsComponents, radius) {
                    var d = radius * 2;
                    var jcs = jcsComponents.jcs;
                    var jcsValue = jcsComponents.jcsValue;
                    var jcsPanel = jcsComponents.jcsPanel;


                    jcs.css({
                        'width': d + "px",
                        'height': radius + "px",
                        'border-radius': d + "px " + d + "px 0 0",
                        'border-bottom': 'none'
                    });

                    var pd = d + (radius / 10);

                    jcsPanel.css({
                        'border-width': (radius / 10) + 'px',
                        'border-radius': pd + "px " + pd + "px 0 0",
                        'border-bottom': 'none'
                    });


                    var outerArea = (jcs.outerWidth() - jcs.innerWidth()) + (jcsValue.outerWidth() - jcsValue.innerWidth());
                    var iRadius = settings.innerCircleRatio * radius;
                    var corner = radius - iRadius - outerArea / 2;
                    jcsValue.css({
                        'width': (iRadius * 2) + "px",
                        'height': (iRadius * 2) + "px",
                        'font-size': iRadius / 2 + "px",
                        'top': corner + "px",
                        'left': corner + "px",
                    });
                },
                getCenter: function(jcsPosition, jcsRadius) {
                    return {
                        x: jcsPosition.left + jcsRadius,
                        y: jcsPosition.top + jcsRadius,
                        r: jcsRadius
                    };
                },
                deg2Val: function(deg) {
                    if (deg < 0 || deg > 359)
                        throw "Invalid angle " + deg;

                    deg = (deg + 180) % 360;
                    return Math.round(deg * (range / 180.0)) + settings.min;
                },
                val2Deg: function(value) {
                    if (value < settings.min || value > settings.max)
                        throw "Invalid range " + value;

                    var nth = value - settings.min;

                    return (Math.round(nth * (180.0 / range)) - 180) % 360;
                },
            },
            "Half Circle Left": {
                drawShape: function(jcsComponents, radius) {
                    var d = radius * 2;
                    var jcs = jcsComponents.jcs;
                    var jcsValue = jcsComponents.jcsValue;
                    var jcsPanel = jcsComponents.jcsPanel;

                    jcs.css({
                        'height': d + "px",
                        'width': radius + "px",
                        'border-radius': d + "px 0 0 " + d + "px",
                        'border-right': 'none'
                    });

                    var pd = d + (radius / 10);

                    jcsPanel.css({
                        'border-width': (radius / 10) + 'px',
                        'border-radius': pd + "px 0 0" + pd + "px",
                        'border-right': 'none'
                    });

                    var outerArea = (jcs.outerWidth() - jcs.innerWidth()) + (jcsValue.outerWidth() - jcsValue.innerWidth());
                    var iRadius = settings.innerCircleRatio * radius;
                    var corner = radius - iRadius - outerArea / 2;
                    jcsValue.css({
                        'width': (iRadius * 2) + "px",
                        'height': (iRadius * 2) + "px",
                        'font-size': iRadius / 2 + "px",
                        'top': corner + "px",
                        'left': corner + "px",
                    });
                },
                getCenter: function(jcsPosition, jcsRadius) {
                    return {
                        x: jcsPosition.left + jcsRadius * 2,
                        y: jcsPosition.top + jcsRadius * 2,
                        r: jcsRadius * 2
                    };
                },
                deg2Val: function(deg) {
                    if (deg < 0 || deg > 359)
                        throw "Invalid angle " + deg;

                    deg = (deg - 90) % 360;
                    return Math.round(deg * (range / 180.0)) + settings.min;
                },
                val2Deg: function(value) {
                    if (value < settings.min || value > settings.max)
                        throw "Invalid range " + value;

                    var nth = value - settings.min;

                    return (Math.round(nth * (180.0 / range)) + 90) % 360;
                },
            },

            "Half Circle Right": {
                drawShape: function(jcsComponents, radius) {
                    var d = radius * 2;
                    var jcs = jcsComponents.jcs;
                    var jcsValue = jcsComponents.jcsValue;
                    var jcsPanel = jcsComponents.jcsPanel;

                    jcs.css({
                        'height': d + "px",
                        'width': radius + "px",
                        'border-radius': "0 " + d + "px " + d + "px 0",
                        'border-left': 'none'
                    });

                    var pd = d + (radius / 10);

                    jcsPanel.css({
                        'border-width': (radius / 10) + 'px',
                        'border-radius': "0 " + pd + "px" + pd + "px 0",
                        'border-left': 'none'
                    });

                    var outerArea = (jcs.outerWidth() - jcs.innerWidth()) + (jcsValue.outerWidth() - jcsValue.innerWidth());
                    var iRadius = settings.innerCircleRatio * radius;
                    var corner = radius - iRadius - outerArea / 2;
                    jcsValue.css({
                        'width': (iRadius * 2) + "px",
                        'height': (iRadius * 2) + "px",
                        'font-size': iRadius / 2 + "px",
                        'top': corner + "px",
                        'left': -corner + "px",
                    });
                },
                getCenter: function(jcsPosition, jcsRadius) {
                    return {
                        x: jcsPosition.left,
                        y: jcsPosition.top + jcsRadius * 2,
                        r: jcsRadius * 2
                    };
                },
                deg2Val: function(deg) {
                    if (deg < 0 || deg > 359)
                        throw "Invalid angle " + deg;

                    deg = (deg + 90) % 360;
                    return Math.round(deg * (range / 180.0)) + settings.min;
                },
                val2Deg: function(value) {
                    if (value < settings.min || value > settings.max)
                        throw "Invalid range " + value;

                    var nth = value - settings.min;

                    return (Math.round(nth * (180.0 / range)) - 90) % 360;
                },
            },
            "Half Circle Bottom": {
                drawShape: function(jcsComponents, radius) {
                    var d = radius * 2;
                    var jcs = jcsComponents.jcs;
                    var jcsValue = jcsComponents.jcsValue;
                    var jcsPanel = jcsComponents.jcsPanel;

                    jcs.css({
                        'width': d + "px",
                        'height': radius + "px",
                        'border-radius': "0 0 " + d + "px " + d + "px",
                        'border-top': 'none'
                    });

                    var pd = d + (radius / 10);

                    jcsPanel.css({
                        'border-width': (radius / 10) + 'px',
                        'border-radius': "0 0 " + pd + "px " + pd + "px",
                        'border-top': 'none'
                    });

                    var outerArea = (jcs.outerWidth() - jcs.innerWidth()) + (jcsValue.outerWidth() - jcsValue.innerWidth());
                    var iRadius = settings.innerCircleRatio * radius;
                    var corner = radius - iRadius - outerArea / 2;
                    jcsValue.css({
                        'width': (iRadius * 2) + "px",
                        'height': (iRadius * 2) + "px",
                        'font-size': iRadius / 2 + "px",
                        'top': -corner + "px",
                        'left': corner + "px",
                    });
                },
                getCenter: function(jcsPosition, jcsRadius) {
                    return {
                        x: jcsPosition.left + jcsRadius,
                        y: jcsPosition.top,
                        r: jcsRadius
                    };
                },
                deg2Val: function(deg) {
                    if (deg < 0 || deg > 359)
                        throw "Invalid angle " + deg;

                    return Math.round(deg * (range / 180.0)) + settings.min;
                },
                val2Deg: function(value) {
                    if (value < settings.min || value > settings.max)
                        throw "Invalid range " + value;

                    var nth = value - settings.min;

                    return Math.round(nth * (180.0 / range));
                },
            }
        };

        var defaults = {
            radius: 75,
            innerCircleRatio: '0.5',
            handleDist : 100,
            min: 0,
            max: 359,
            value: 0,
            clockwise: true,
            labelSuffix: "",
            labelPrefix: "",
            shape: "Circle",
            touch: true,
            animate: true,
            animateDuration : 360,
            selectable: false,
            slide: function(ui, value) {},
            formLabel: undefined
        };

        var settings = $.extend({}, defaults, options);

        var validateSettings = function() {

            if ((settings.min | 0) !== settings.min) throw "Invalid min value : " + settings.min;
            if ((settings.max | 0) !== settings.max) throw "Invalid max value : " + settings.max;
            if ((settings.value | 0) !== settings.value) throw "Invalid initial value : " + settings.value;
            if (settings.max < settings.min) throw "Invalid range : " + settings.max + "<" + settings.min;

            if (settings.value < settings.min) settings.value = settings.min;
            if (settings.value > settings.max) settings.value = settings.max;

            if (!settings.labelSuffix) settings.labelSuffix = defaults.labelSuffix;
            if (!settings.labelPrefix) settings.labelPrefix = defaults.labelPrefix;
            if (settings.formLabel && !$.isFunction(settings.formLabel)) settings.formLabel = defaults.formLabel;

            if (!settings.shape) settings.shape = defaults.shape;
            if (!shapes[settings.shape]) throw "Invalid shape : " + settings.shape;

            if (!settings.innerCircleRatio || settings.innerCircleRatio < 0.1 || settings.innerCircleRatio > 0.9)
                throw "Invalid innerCircleRatio. Expected: between 0.1 and 0.9, Found: " + settings.innerCircleRatio;

            if (settings.animate && ((settings.animateDuration | 0) !== settings.animateDuration ||
                settings.animateDuration < 0))
                throw "Invalid animate duration(in ms) : " + settings.animateDuration;

            if (((settings.handleDist | 0) !== settings.handleDist) || settings.handleDist <= 0 || settings.handleDist > 100)
                settings.handleDist = defaults.handleDist;
        };

        validateSettings();

        var range = settings.max - settings.min + 1;

        var jcsPanel = $('<div class="jcs-panel"><div class="jcs"><span class="jcs-value"></span></div><div class="jcs-indicator"> </div></div>');
        jcsPanel.appendTo(slider);

        var radius = Math.abs(parseInt(settings.radius)) || defaults.radius;
        var jcs = jcsPanel.find('div.jcs');
        var jcsIndicator = jcsPanel.find('div.jcs-indicator');
        var jcsValue = jcsPanel.find('span.jcs-value');

        var jcsComponents = {
            'jcs': jcs,
            'jcsPanel': jcsPanel,
            'jcsIndicator': jcsIndicator,
            'jcsValue': jcsValue
        };

        //draw circles
        shapes[settings.shape].drawShape(jcsComponents, radius);
        drawIndicatorBall(jcsComponents, radius);

        var jcsPosition = jcs.position();
        var jcsOuterArea = jcs.outerWidth() - jcs.innerWidth();
        var jcsBallOuterArea = jcsIndicator.outerWidth() - jcsIndicator.innerWidth();

        var jcsRadius = (jcs.width() + jcsOuterArea) / 2;
        var jcsBallRadius = (jcsIndicator.width() + jcsBallOuterArea) / 2;
        var jcsCenter = shapes[settings.shape].getCenter(jcsPosition, jcsRadius);


        // event binding
        var mouseDown = false;
        var onAnimate = false;


        var translate = function(e) {

            var cursor = {
                x: e.offsetX || e.originalEvent.layerX,
                y: e.offsetY || e.originalEvent.layerY
            };

            var dx = cursor.x - jcsCenter.x;
            var dy = cursor.y - jcsCenter.y;

            var rad = Math.atan2(dy, dx);
            var deg = rad * 180.0 / Math.PI;
            var d360 = (parseInt(deg < 0 ? 360.0 + deg : deg)) % 360;

            // change coordinate

            var x = jcsCenter.x + jcsCenter.r * Math.cos(rad) - jcsBallRadius;
            var y = jcsCenter.y + jcsCenter.r * Math.sin(rad) - jcsBallRadius;

            var sd360 = (shapes[settings.shape].val2Deg(settings.value) + 360) % 360;

            if(sd360 === d360) return;

            var distance = Math.min((d360 + 360 - sd360) % 360, (sd360 + 360 - d360) % 360);
            if(!distance) distance = 180;

            var clockwise = ((d360 + 360 - sd360) % 360) === distance;
            var r = settings.animateDuration / distance;
            var delay = 4;
            var unitDeg = 1;

            if(r >= 4) {
                delay = parseInt(r);
            } else if (r >= 1){
                unitDeg = parseInt(r) * 4;
            } else {
                unitDeg = (4 / r);
            }

            //linear animation
            // TODO: add easing effect
            var next = sd360;
            var count = parseInt(distance / unitDeg);

            onAnimate = true;
            var animate = function() {
                next = next + (clockwise ? unitDeg : -unitDeg);
                next = (next + 360) % 360;
                if(--count <= 0) {
                    clearInterval(timer);
                    onAnimate = false;
                    next = d360;
                }
                setValue(shapes[settings.shape].deg2Val(next));
            };

            var timer = window.setInterval(animate, delay);

        };

        var mousemoveHanlder = function(e) {
            e.stopPropagation();

            if (!mouseDown || onAnimate) return;

            var cursor = {
                x: e.offsetX || e.originalEvent.layerX,
                y: e.offsetY || e.originalEvent.layerY
            };

            var dx = cursor.x - jcsCenter.x;
            var dy = cursor.y - jcsCenter.y;

            var rad = Math.atan2(dy, dx);
            var deg = rad * 180.0 / Math.PI;
            var d360 = (parseInt(deg < 0 ? 360.0 + deg : deg)) % 360;

            // change coordinate

            var x = jcsCenter.x + ((jcsCenter.r*settings.handleDist)/100) * Math.cos(rad) - jcsBallRadius;
            var y = jcsCenter.y + ((jcsCenter.r*settings.handleDist)/100) * Math.sin(rad) - jcsBallRadius;


            jcsIndicator.css({
                'top': y + "px",
                'left': x + "px"
            });

            var d2v = shapes[settings.shape].deg2Val(d360);
            var val = settings.clockwise ? d2v : (settings.max - d2v);

            if (val < settings.min) val = settings.min;
            else if (val > settings.max) val = settings.max;

            jcsValue.html(buildLabel(val));
            if (settings.slide && $.isFunction(settings.slide)) settings.slide(slider, val);
        };

        jcs.on('mousedown', function(e) {
            mouseDown = true;
            e.stopPropagation();
        });
        jcs.on('mouseup', function(e) {
            mouseDown = false;
            e.stopPropagation();
        });
        jcs.on('mousemove', mousemoveHanlder);

        jcs.on('click', function(e) {
            e.stopPropagation();
            var cursor = {
                x: e.offsetX || e.originalEvent.layerX,
                y: e.offsetY || e.originalEvent.layerY
            };

            var dx = cursor.x - jcsCenter.x;
            var dy = cursor.y - jcsCenter.y;

            var distance = Math.sqrt(dx * dx + dy * dy);
            if (radius - distance <= jcsOuterArea || distance > radius) {
                if(settings.animate) {
                    translate(e);
                } else {
                    mouseDown = true;
                    mousemoveHanlder(e);
                }
            }
            mouseDown = false;
        });

        jcsPanel.on('click', function(e) {
            jcs.trigger(e);
        });

        jcsPanel.on('mouseup', function(e) {
            jcs.trigger(e);
        });

        jcsPanel.on('mousemove', function(e) {
            jcs.trigger(e);
        });

        jcsPanel.on('mousedown', function(e) {
            jcs.trigger(e);
        });

        var buildLabel = function(value) {
            settings.value = value;
            return settings.formLabel ? settings.formLabel(value, settings.labelPrefix, settings.labelSuffix) : settings.labelPrefix + value + settings.labelSuffix;
        };

        var setValue = function(value) {

            if (((value | 0) !== value)) throw "Invalid input (expected integer) : " + value;

            var val = settings.clockwise ? value : (settings.max - value);

            var d360 = shapes[settings.shape].val2Deg(val);
            var rad = d360 * Math.PI / 180;

            var x = jcsCenter.x + ((jcsCenter.r*settings.handleDist)/100) * Math.cos(rad) - jcsBallRadius;
            var y = jcsCenter.y + ((jcsCenter.r*settings.handleDist)/100) * Math.sin(rad) - jcsBallRadius;

            jcsIndicator.css('top', y + "px");
            jcsIndicator.css('left', x + "px");
            jcsValue.html(buildLabel(value));

            if (settings.slide && $.isFunction(settings.slide)) settings.slide(slider, val);

        };

        var getValue = function() {
            return settings.value;
        };


        var getSupportedShapes = function() {
            return Object.keys();
        };

        var setRange = function(min, max) {
            if ((min | 0) !== min) throw "Invalid min value : " + min;
            if ((max | 0) !== max) throw "Invalid max value : " + max;
            if (max < min) throw "Min range should be less than max";

            settings.min = min;
            settings.max = max;
            range = settings.max - settings.min + 1;

            var value = settings.value;
            if(value < min || value > max)  value = min;
            setValue(value);
        };

        var lastTouchType = '';
        var touchHandler = function(e) {
            var touches = e.changedTouches;

            // Ignore multi-touch
            if (touches.length > 1) return;

            var touch = touches[0];
            var target = $(touch.target);

            if(!target.hasClass('jcs')) return;

            var offset = target.offset();
            var width = target.width();
            var height = target.height();

            var clientX = touch.clientX;
            var clientY = touch.clientY;

            if( clientX < offset.left || clientX > width + offset.left ||
                clientY < offset.top  || clientY > height + offset.top)
                return;

            var events = ["touchstart", "touchmove", "touchend"];
            var mouseEvents = ["mousedown", "mousemove", "mouseup"];
            var ev = events.indexOf(e.type);

            if (ev === -1) return;

            var type = mouseEvents[ev];
            if (e.type === events[2] && lastTouchType === events[0]) {
                type = "click";
            }


            var simulatedEvent = document.createEvent("MouseEvent");
            simulatedEvent.initMouseEvent(type, true, true, window, 1,
                touch.screenX, touch.screenY,
                touch.clientX, touch.clientY, false,
                false, false, false, 0, null);
            touch.target.dispatchEvent(simulatedEvent);
            e.preventDefault();
            lastTouchType = e.type;
        };

        // bind touch events to mouse events
        if (settings.touch) {

            document.addEventListener("touchstart", touchHandler, true);
            document.addEventListener("touchmove", touchHandler, true);
            document.addEventListener("touchend", touchHandler, true);
            document.addEventListener("touchcancel", touchHandler, true);

        }

        if (!settings.selectable) jcsPanel.addClass('noselect');

        // default position
        setValue(settings.value || settings.min);

        return $.extend({}, this, {
            "setValue": setValue,
            "getValue": getValue,
            "getSupportedShapes": getSupportedShapes,
            "setRange": setRange,
        });

    };

}(jQuery));
