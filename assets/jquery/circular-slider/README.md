[circular-slider](http://www.toolitup.com/circular-slider.html) - A jQuery Plugin
===============
Circular slider is a jQuery plugin which helps to slide range of values and images. It supports half(top/bottom/up/down) and full circle shapes.
#####[Demo Page](http://www.toolitup.com/circular-slider.html)
[![Gitter](https://badges.gitter.im/Join Chat.svg)](https://gitter.im/princejwesley/circular-slider)


![circular slider](http://www.toolitup.com/assets/images/circular-slider-demo.png)

## Getting Started

### Download the latest code


[Fork](https://github.com/princejwesley/circular-slider) this repository or download js/css files from  `dist` directory.

### Including it on your page

Include jQuery and this plugin on a page.

```html
<link rel="stylesheet" href="circular-slider.min.css" />
<script src="jquery.js"></script>
<script src="circular-slider.min.js"></script>
```

### Basic Usage
```html
<div id="slider"></div>
```
```javascript
var slider = $('#slider').CircularSlider();

```
### Properties
#### radius
> `radius` of the circle in *px*.

#### innerCircleRatio
> ratio of inner circle area(used to display current sliding value). ratio can be in the range between 0.10 and 0.90 (open interval).

#### min
> `min` value of the slider.

#### max
> `max` value of the slider.

#### value
> Initial value of the slider.

#### clockwise
> direction of the sliding value.

#### labelSuffix
> label to be used as suffix along with current sliding `value`.

#### labelPrefix
> label to be used as prefix along with current sliding `value`.

#### shape
> `shape` of the slider. Supported shapes are:

1. `'circle'` (*default*)
2. `'half circle'`
3. `'half circle left'`
4. `'half circle right'`
5. `'half circle bottom'`

### touch
> `touch` support. (default: *true*)

### animate
>linear `animation` support. (dafault: *true*)

### animateDuration
> Animation duration in milliseconds. (default: 360ms)

### selectable
> text selection enabled or not. (default: *false*)

### handleDist
> Distance between handle and shape center in percentage(default: 100)

## Functions
### function setValue(value)
> Set current `value` of the slider.

### function getValue()
> Get current `value` of the slider.

### function setRange(min, max)
> Set sliding range of the slider. `min` is a minimum value and `max` is a maximum sliding value. If the current `value` is not in range, `min` will be set as current `value`

## Callback
### function slide(ui, value)
> `slide` callback is triggered whenever there is a change in sliding value.

Parameters:
```
ui -  dom element
value - Current sliding value
```

### function formLabel(value, prefix, suffix)
> `formLabel` callback is triggered with current sliding value, prefix and suffix.
If it is defined, it will be used as a source for inner circle area.

Parameters:
```
value - Current sliding value
prefix - prefix configured
suffix - suffix configured
```

### Default Settings
Slider with default values
```javascript
var slider = $('#slider').CircularSlider({
    radius: 75,
    innerCircleRatio: '0.5',
    handleDist: 100,
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
});
```

#### CSS classes
Use the below css classes for customization
> `.jcs-panel` - circular slider panel

> `.jcs` - Slider area

> `.jcs-value` - Inner circle area

> `.jcs-indicator` - Slide indicator ball

### Advanced Usage ([image carousel](http://www.toolitup.com/circular-slider.html))
```javascript
var imageSlider = $('#slider-customized').CircularSlider({
	min : 0,
	max: 359,
	radius: 100,
	innerCircleRatio : .7,
	formLabel : function(value, prefix, suffix) {
		return '<img src="assets/images/baby'+ parseInt(value / 26)+'.png"></img>';
	},
	slide : function(ui, value) {
		var colors = ['deeppink', 'seagreen', 'deepskyblue', 'coral', 'cadetblue', 'olive', 'chocolate',
        	'yellowgreen', 'cornflowerblue', 'slategrey', 'salmon', 'brown', 'darkgoldenrod', 'dimgrey'];
		var color = colors[parseInt(value / 26)];
		ui.find('.jcs').css({'border-color' : color });
		ui.find('.jcs-indicator').css({'background' : color });
	}
});

```


## License
This plugin is licensed under the [MIT license](https://github.com/princejwesley/circular-slider/blob/master/LICENSE).

Copyright (c) 2014 [Prince John Wesley](http://www.toolitup.com)
