import { Control as e, DomEvent as t, FeatureGroup as n, Marker as r } from "leaflet";
// src/lib/requestUrl.ts
function i(e) {
	let t = typeof location < "u" ? location.href : "http://localhost/", { protocol: n } = new URL(e, t);
	if (n !== "http:" && n !== "https:") throw Error(`A provider url has to be http or https, this one is ${n}`);
	return e;
}
// src/domUtils.ts
function a(e, t = "", n, r = {}) {
	let i = document.createElement(e);
	return t && (i.className = t), Object.keys(r).forEach((e) => {
		if (typeof r[e] == "function") {
			let t = e.indexOf("on") === 0 ? e.substr(2).toLowerCase() : e;
			i.addEventListener(t, r[e]);
		} else e === "html" ? i.innerHTML = r[e] : e === "text" ? i.innerText = r[e] : i.setAttribute(e, r[e]);
	}), n && n.appendChild(i), i;
}
function o(e) {
	e.preventDefault(), e.stopPropagation();
}
function s(e, t) {
	let n = i(e), r = a("script", null, document.body);
	return r.setAttribute("type", "text/javascript"), new Promise((i, a) => {
		let o = () => {
			r.remove(), delete window[t];
		};
		window[t] = (e) => {
			o(), i(e);
		}, r.addEventListener("error", () => {
			o(), a(/* @__PURE__ */ Error(`Request failed: ${e.split("?")[0]}`));
		}), r.setAttribute("src", n);
	});
}
var c = (...e) => e.filter(Boolean).join(" ").trim();
function l(e, t) {
	e && e.classList && (Array.isArray(t) ? t : [t]).forEach((t) => {
		e.classList.contains(t) || e.classList.add(t);
	});
}
function u(e, t) {
	e && e.classList && (Array.isArray(t) ? t : [t]).forEach((t) => {
		e.classList.contains(t) && e.classList.remove(t);
	});
}
function d(e, t, n) {
	u(e, t), l(e, n);
}
var f = {
	"&": "&amp;",
	"<": "&lt;",
	">": "&gt;",
	"\"": "&quot;",
	"'": "&#39;"
};
function p(e) {
	return String(e).replace(/[&<>"']/g, (e) => f[e]);
}
var m = [
	13,
	27,
	40,
	38,
	37,
	39
];
// \0@oxc-project+runtime@0.156.0/helpers/esm/typeof.js
function h(e) {
	"@babel/helpers - typeof";
	return h = typeof Symbol == "function" && typeof Symbol.iterator == "symbol" ? function(e) {
		return typeof e;
	} : function(e) {
		return e && typeof Symbol == "function" && e.constructor === Symbol && e !== Symbol.prototype ? "symbol" : typeof e;
	}, h(e);
}
// \0@oxc-project+runtime@0.156.0/helpers/esm/toPrimitive.js
function ee(e, t) {
	if (h(e) != "object" || !e) return e;
	var n = e[Symbol.toPrimitive];
	if (n !== void 0) {
		var r = n.call(e, t || "default");
		if (h(r) != "object") return r;
		throw TypeError("@@toPrimitive must return a primitive value.");
	}
	return (t === "string" ? String : Number)(e);
}
// \0@oxc-project+runtime@0.156.0/helpers/esm/toPropertyKey.js
function te(e) {
	var t = ee(e, "string");
	return h(t) == "symbol" ? t : t + "";
}
// \0@oxc-project+runtime@0.156.0/helpers/esm/defineProperty.js
function g(e, t, n) {
	return (t = te(t)) in e ? Object.defineProperty(e, t, {
		value: n,
		enumerable: !0,
		configurable: !0,
		writable: !0
	}) : e[t] = n, e;
}
// \0@oxc-project+runtime@0.156.0/helpers/esm/asyncToGenerator.js
function _(e, t, n, r, i, a, o) {
	try {
		var s = e[a](o), c = s.value;
	} catch (e) {
		n(e);
		return;
	}
	s.done ? t(c) : Promise.resolve(c).then(r, i);
}
function v(e) {
	return function() {
		var t = this, n = arguments;
		return new Promise(function(r, i) {
			var a = e.apply(t, n);
			function o(e) {
				_(a, r, i, o, s, "next", e);
			}
			function s(e) {
				_(a, r, i, o, s, "throw", e);
			}
			o(void 0);
		});
	};
}
// src/SearchElement.ts
var y = class {
	constructor({ handleSubmit: e, searchLabel: t, classNames: n = {} }) {
		g(this, "container", void 0), g(this, "form", void 0), g(this, "input", void 0), g(this, "handleSubmit", void 0), g(this, "hasError", !1), this.container = a("div", c("geosearch", n.container)), this.form = a("form", c("", n.form), this.container, {
			autocomplete: "none",
			onClick: o,
			onDblClick: o,
			touchStart: o,
			touchEnd: o
		}), this.input = a("input", c("glass", n.input), this.form, {
			type: "text",
			placeholder: t || "search",
			name: "geosearch",
			onInput: () => this.onInput(),
			onKeyUp: (e) => this.onKeyUp(e),
			onKeyPress: (e) => this.onKeyPress(e),
			onFocus: () => this.onFocus(),
			onBlur: () => this.onBlur(),
			onClick: () => {
				this.input.focus(), this.input.dispatchEvent(new Event("focus"));
			}
		}), this.handleSubmit = e;
	}
	onFocus() {
		l(this.form, "active");
	}
	onBlur() {
		u(this.form, "active");
	}
	onSubmit(e) {
		var t = this;
		return v(function* () {
			o(e), d(t.container, "error", "pending");
			try {
				yield t.handleSubmit({ query: t.input.value });
			} catch (e) {
				throw l(t.container, "error"), t.hasError = !0, e;
			} finally {
				u(t.container, "pending");
			}
		})();
	}
	onInput() {
		this.hasError && (u(this.container, "error"), this.hasError = !1);
	}
	onKeyUp(e) {
		e.keyCode === 27 && (u(this.container, ["pending", "active"]), this.input.value = "", document.body.focus(), document.body.blur());
	}
	onKeyPress(e) {
		e.keyCode === 13 && this.onSubmit(e);
	}
	setQuery(e) {
		this.input.value = e;
	}
}, ne = 0, b = class {
	constructor({ handleClick: e, classNames: t = {}, notFoundMessage: n, input: r, label: i }) {
		g(this, "handleClick", void 0), g(this, "selected", -1), g(this, "results", []), g(this, "container", void 0), g(this, "resultItem", void 0), g(this, "notFoundMessage", void 0), g(this, "notFoundClassName", void 0), g(this, "input", void 0), g(this, "id", void 0), g(this, "onClick", (e) => {
			var t;
			if (typeof this.handleClick != "function") return;
			let n = (t = e.target) == null ? void 0 : t.closest("[data-key]");
			if (!n || !this.container.contains(n)) return;
			let r = Number(n.getAttribute("data-key"));
			this.handleClick({ result: this.results[r] });
		}), this.handleClick = e, this.notFoundClassName = t.notfound, this.id = `geosearch-results-${ne += 1}`, this.setNotFoundMessage(n), this.container = a("div", c("results", t.resultlist), void 0, {
			id: this.id,
			role: "listbox"
		}), i && this.container.setAttribute("aria-label", i), this.container.addEventListener("click", this.onClick, !0), this.input = r, r && (r.setAttribute("role", "combobox"), r.setAttribute("aria-autocomplete", "list"), r.setAttribute("aria-controls", this.id), r.setAttribute("aria-expanded", "false")), this.resultItem = a("div", c(t.item));
	}
	render(e = [], t) {
		if (this.clear(), e.forEach((e, n) => {
			let r = this.resultItem.cloneNode(!0);
			r.setAttribute("data-key", `${n}`), r.id = `${this.id}-${n}`, r.setAttribute("role", "option"), r.setAttribute("aria-selected", "false"), r.innerHTML = t({ result: e }), this.container.appendChild(r);
		}), e.length > 0) {
			var n;
			l(this.container.parentElement, "open"), l(this.container, "active"), (n = this.input) == null || n.setAttribute("aria-expanded", "true");
		} else if (this.notFoundMessage) {
			var r;
			this.container.appendChild(this.notFoundMessage), l(this.container.parentElement, "open"), (r = this.input) == null || r.setAttribute("aria-expanded", "true");
		}
		this.results = e;
	}
	setNotFoundMessage(e) {
		if (e) this.notFoundMessage || (this.notFoundMessage = a("div", c(this.notFoundClassName), void 0, {
			role: "option",
			"aria-disabled": "true"
		})), this.notFoundMessage.innerHTML = e;
		else {
			var t;
			(t = this.notFoundMessage) == null || t.remove(), this.notFoundMessage = void 0;
		}
	}
	select(e) {
		Array.from(this.container.children).forEach((t, n) => {
			n === e ? l(t, "active") : u(t, "active"), t.getAttribute("role") === "option" && t.id && t.setAttribute("aria-selected", String(n === e));
		});
		let t = this.results[e] ? this.container.children[e] : void 0;
		if (t) {
			var n;
			(n = this.input) == null || n.setAttribute("aria-activedescendant", t.id);
		} else {
			var r;
			(r = this.input) == null || r.removeAttribute("aria-activedescendant");
		}
		return this.selected = e, this.results[e];
	}
	count() {
		return this.results ? this.results.length : 0;
	}
	clear() {
		var e, t;
		for (this.selected = -1; this.container.lastChild;) this.container.removeChild(this.container.lastChild);
		u(this.container.parentElement, "open"), u(this.container, "active"), (e = this.input) == null || e.setAttribute("aria-expanded", "false"), (t = this.input) == null || t.removeAttribute("aria-activedescendant");
	}
};
// src/lib/debounce.ts
function re(e, t = 250, n = !1) {
	let r;
	return (...i) => {
		r && clearTimeout(r), r = setTimeout(() => {
			r = null, n || e(...i);
		}, t), n && !r && e(...i);
	};
}
// \0@oxc-project+runtime@0.156.0/helpers/esm/objectSpread2.js
function x(e, t) {
	var n = Object.keys(e);
	if (Object.getOwnPropertySymbols) {
		var r = Object.getOwnPropertySymbols(e);
		t && (r = r.filter(function(t) {
			return Object.getOwnPropertyDescriptor(e, t).enumerable;
		})), n.push.apply(n, r);
	}
	return n;
}
function S(e) {
	for (var t = 1; t < arguments.length; t++) {
		var n = arguments[t] == null ? {} : arguments[t];
		t % 2 ? x(Object(n), !0).forEach(function(t) {
			g(e, t, n[t]);
		}) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(n)) : x(Object(n)).forEach(function(t) {
			Object.defineProperty(e, t, Object.getOwnPropertyDescriptor(n, t));
		});
	}
	return e;
}
// src/SearchControl.ts
var C = {
	container: "leaflet-bar leaflet-control leaflet-control-geosearch",
	button: "leaflet-bar-part leaflet-bar-part-single",
	resetButton: "reset",
	msgbox: "leaflet-bar message",
	form: "",
	input: "",
	resultlist: "",
	item: "",
	notfound: "leaflet-bar-notfound",
	providerSelect: ""
}, w = {
	position: "topleft",
	style: "button",
	showMarker: !0,
	showPopup: !1,
	markerPopup: !0,
	resultListPosition: "below",
	theme: "light",
	popupOptions: {},
	popupFormat: ({ result: e }) => p(e.label),
	resultFormat: ({ result: e }) => p(e.label),
	marker: { draggable: !1 },
	maxMarkers: 1,
	maxSuggestions: 5,
	retainZoomLevel: !1,
	animateZoom: !0,
	flyTo: !1,
	searchLabel: "Enter address",
	clearSearchLabel: "Clear search",
	notFoundMessage: "",
	messageHideDelay: 3e3,
	zoomLevel: 18,
	zoomToBounds: !0,
	fitBoundsOptions: {},
	reverseOnDragEnd: !1,
	classNames: C,
	autoComplete: !0,
	autoCompleteDelay: 250,
	autoClose: !1,
	keepResult: !1,
	updateMap: !0,
	resetButton: "×",
	searchIcon: "",
	requeryOnSelect: !1,
	minQueryLength: 1,
	cacheSize: 0,
	showResultListOnSubmit: !1,
	showProviderSelect: !0,
	allProvidersLabel: "All",
	providerSelectLabel: "Search with",
	fallback: !1
};
function T(e) {
	let t = {};
	return e && Object.keys(e).forEach((n) => {
		e[n] !== void 0 && (t[n] = e[n]);
	}), t;
}
function ie(e) {
	if (!Array.isArray(e) || e.length !== 2) return null;
	let t = e.map((e) => Array.isArray(e) ? e.map(Number) : []);
	return t.every((e) => e.length === 2 && e.every(Number.isFinite)) ? t : null;
}
function ae([[e, t], [n, r]]) {
	return [(e + n) / 2, (t + r) / 2];
}
function E(e) {
	return !!e && Number.isFinite(e.x) && Number.isFinite(e.y);
}
var D = "Leaflet must be loaded before instantiating the GeoSearch control", oe = {
	options: w,
	classNames: C,
	setup(r) {
		if (!e) throw Error(D);
		let i = T(r), o = i.providers && i.providers.length ? i.providers : i.provider ? [{
			label: "",
			provider: i.provider
		}] : [];
		if (!o.length) throw Error("Provider is missing from options");
		this.options = S(S(S({}, w), i), {}, {
			provider: o[0].provider,
			marker: S(S({}, w.marker), T(i.marker))
		}), this.choices = o.map(({ label: e, provider: t }, n) => ({
			index: n,
			label: e,
			provider: t
		})), this.sources = /* @__PURE__ */ new WeakMap(), this.selection = this.choices.length > 1 && this.options.allProvidersLabel !== !1 ? "all" : 0, this.classNames = S(S({}, this.classNames), T(i.classNames)), this.markers = new n(), this.classNames.container += ` leaflet-geosearch-${this.options.style}`;
		let { resultListPosition: s, position: u, style: d, theme: f } = this.options;
		if (this.modifierClasses = [], (s === "above" || s === "auto" && d !== "bar" && String(u).indexOf("bottom") === 0) && this.modifierClasses.push("leaflet-geosearch-results-above"), (f === "dark" || f === "auto") && this.modifierClasses.push(`leaflet-geosearch-theme-${f}`), this.classNames.container = c(this.classNames.container, ...this.modifierClasses), this.searchElement = new y({
			searchLabel: this.options.searchLabel,
			classNames: {
				container: this.classNames.container,
				form: this.classNames.form,
				input: this.classNames.input
			},
			handleSubmit: (e) => this.onSubmit(e)
		}), this.button = a("a", this.classNames.button, this.searchElement.container, {
			title: this.options.searchLabel,
			"aria-label": this.options.searchLabel,
			href: "#",
			onClick: (e) => this.onClick(e)
		}), this.options.searchIcon && (l(this.button, "leaflet-geosearch-custom-icon"), a("span", "leaflet-geosearch-icon", this.button, {
			html: this.options.searchIcon,
			"aria-hidden": "true"
		})), t.disableClickPropagation(this.button), this.resetButton = a("button", this.classNames.resetButton, this.searchElement.form, {
			text: this.options.resetButton,
			"aria-label": this.options.clearSearchLabel,
			onClick: () => {
				this.searchElement.input.value === "" ? this.close() : this.clearResults(null, !0);
			}
		}), t.disableClickPropagation(this.resetButton), this.choices.length > 1) {
			let { form: e, input: t } = this.searchElement, n = a("div", "geosearch-input");
			e.insertBefore(n, t), this.providerSelect = a("select", c("provider-select", this.classNames.providerSelect), n, {
				"aria-label": this.options.providerSelectLabel,
				onChange: () => {
					let { value: e } = this.providerSelect;
					this.selectProvider(e === "all" ? "all" : Number(e));
				}
			});
			let { allProvidersLabel: r } = this.options;
			(r === !1 ? this.choices : [{
				index: "all",
				label: r
			}, ...this.choices]).forEach(({ index: e, label: t }) => {
				a("option", null, this.providerSelect, {
					value: String(e),
					text: t
				});
			}), this.providerSelect.value = String(this.selection), this.providerSelect.hidden = !this.options.showProviderSelect, n.appendChild(t);
		}
		if (this.options.autoComplete || this.options.showResultListOnSubmit) {
			if (this.resultList = new b({
				handleClick: ({ result: e }) => {
					this.searchElement.input.value = e.label, this.onSubmit({
						query: e.label,
						data: e
					});
				},
				classNames: {
					resultlist: this.classNames.resultlist,
					item: this.classNames.item,
					notfound: this.classNames.notfound
				},
				notFoundMessage: this.options.notFoundMessage,
				input: this.searchElement.input,
				label: this.options.searchLabel
			}), this.searchElement.form.appendChild(this.resultList.container), this.options.autoComplete) {
				let e, t = re((t) => {
					e === this.searchId && this.autoSearch(t);
				}, this.options.autoCompleteDelay), n = (n) => {
					e = this.searchId, t(n);
				};
				this.searchElement.input.addEventListener("keyup", n, !0), this.searchElement.input.addEventListener("input", n, !0);
			}
			this.searchElement.input.addEventListener("input", () => this.resultList.select(-1), !0), this.searchElement.input.addEventListener("keydown", (e) => this.selectResult(e), !0), this.searchElement.input.addEventListener("keydown", (e) => this.clearResults(e, !0), !0);
		}
		this.searchElement.form.addEventListener("click", (e) => {
			e.preventDefault();
		}, !1);
	},
	onAdd(e) {
		let { showMarker: n, style: r } = this.options;
		if (this.map = e, n && this.markers.addTo(e), r === "bar") {
			let t = e.getContainer().querySelector(".leaflet-control-container");
			this.container = a("div", c("leaflet-control-geosearch leaflet-geosearch-bar", ...this.modifierClasses)), this.container.appendChild(this.searchElement.form), t.appendChild(this.container);
		}
		return t.disableClickPropagation(this.searchElement.form), this.searchElement.container;
	},
	onRemove() {
		var e;
		return (e = this.container) == null || e.remove(), this;
	},
	open() {
		let { container: e, input: t } = this.searchElement;
		l(e, "active"), t.focus();
	},
	close() {
		let { container: e } = this.searchElement;
		u(e, "active"), this.clearResults();
	},
	onClick(e) {
		e.preventDefault(), e.stopPropagation();
		let { container: t } = this.searchElement;
		t.classList.contains("active") ? this.close() : this.open();
	},
	selectResult(e) {
		if ([
			13,
			40,
			38
		].indexOf(e.keyCode) === -1) return;
		if (e.preventDefault(), e.keyCode === 13) {
			let e = this.resultList.select(this.resultList.selected);
			this.onSubmit({
				query: this.searchElement.input.value,
				data: e
			});
			return;
		}
		let t = this.resultList.count() - 1;
		if (t < 0) return;
		let { selected: n } = this.resultList, r = e.keyCode === 40 ? n + 1 : n - 1, i = r < 0 ? t : r > t ? 0 : r, a = this.resultList.select(i);
		this.searchElement.input.value = a.label;
	},
	clearResults(e, t = !1) {
		if (e && e.keyCode !== 27) return;
		let { keepResult: n } = this.options;
		(t || !n) && (this.searchElement.input.value = "", this.markers.clearLayers()), this.resultList && (this.cancelSearch(), this.lastQuery = "", this.resultList.clear());
	},
	autoSearch(e) {
		var t = this;
		return v(function* () {
			m.indexOf(e.keyCode) > -1 || (yield t.suggest(t.searchElement.input.value));
		})();
	},
	suggest(e) {
		var t = this;
		return v(function* () {
			if (!e.length || e.length < t.options.minQueryLength) {
				t.cancelSearch(), t.lastQuery = "", t.resultList.clear();
				return;
			}
			if (e === t.lastQuery) return;
			t.cancelSearch(), t.lastQuery = e;
			let n = t.searchId, r = typeof AbortController > "u" ? void 0 : new AbortController();
			t.abortController = r;
			let i = yield t.runSearch({
				query: e,
				signal: r == null ? void 0 : r.signal
			});
			n === t.searchId && (t.abortController = void 0, t.reportErrors(i, e, "autocomplete"), !i.results.length && i.errors.length ? t.lastQuery = "" : t.resultList.render(i.results.slice(0, t.options.maxSuggestions), (e) => t.formatResult(e)));
		})();
	},
	searchProvider(e, t, n = !0) {
		var r = this;
		return v(function* () {
			let { cacheSize: i } = r.options, a = n && i > 0 ? r.cache = r.cache || /* @__PURE__ */ new Map() : null, o = `${e.index}:${t.query}`, s = a == null ? void 0 : a.get(o);
			if (a && s) return a.delete(o), a.set(o, s), s;
			let c = yield e.provider.search(t);
			if (c.forEach((t) => r.sources.set(t, e)), a) for (a.set(o, c); a.size > i;) a.delete(a.keys().next().value);
			return c;
		})();
	},
	runSearch(e, t) {
		var n = this;
		return v(function* () {
			let r = {
				results: [],
				errors: []
			};
			if (!t && n.selection === "all") {
				let t = yield Promise.all(n.choices.map((t) => n.searchProvider(t, e).then((e) => ({
					choice: t,
					results: e,
					error: void 0
				}), (e) => ({
					choice: t,
					results: [],
					error: e
				}))));
				t.forEach(({ choice: e, error: t }) => {
					t !== void 0 && r.errors.push({
						error: t,
						provider: e
					});
				});
				let i = Math.max(...t.map((e) => e.results.length));
				for (let e = 0; e < i; e += 1) t.forEach(({ results: t }) => {
					e < t.length && r.results.push(t[e]);
				});
				return r;
			}
			let i = t || n.choices[n.selection], { fallback: a } = n.options, o = !t && a ? [i, ...n.choices.filter((e) => e !== i)] : [i];
			for (let t of o) {
				try {
					r.results = yield n.searchProvider(t, e, !e.data);
				} catch (n) {
					var s;
					if (r.errors.push({
						error: n,
						provider: t
					}), (s = e.signal) != null && s.aborted) return r;
					continue;
				}
				if (r.results.length || a !== "empty") return r;
			}
			return r;
		})();
	},
	reportErrors({ results: e, errors: t }, n, r) {
		t.forEach(({ error: t, provider: i }, a) => {
			if (a === 0 && !e.length) this.handleError(t, n, r, i);
			else {
				var o;
				(o = this.map) == null || o.fire("geosearch/error", {
					error: t,
					query: n,
					source: r,
					provider: i
				});
			}
		});
	},
	cancelSearch() {
		var e;
		(e = this.abortController) == null || e.abort(), this.abortController = void 0, this.searchId = (this.searchId || 0) + 1;
	},
	onSubmit(e) {
		var t = this;
		return v(function* () {
			var n;
			t.cancelSearch();
			let r = t.searchId;
			(n = t.resultList) == null || n.clear();
			let { requeryOnSelect: i } = t.options;
			if (!i && E(e.data)) {
				t.showResult(e.data, e);
				return;
			}
			let a = yield t.runSearch(e, e.data && t.sources.get(e.data));
			if (r !== t.searchId) return;
			t.reportErrors(a, e.query, "submit");
			let { results: o } = a;
			if (!o.length && a.errors.length) return;
			let { showResultListOnSubmit: s, maxSuggestions: c } = t.options;
			if (s && !e.data && o.length !== 1) t.resultList.render(o.slice(0, c), (e) => t.formatResult(e));
			else if (o.length > 0) {
				let [n] = o, r = {
					query: n.label,
					data: n
				};
				if (!E(n)) {
					e.data || (yield t.onSubmit(r));
					return;
				}
				t.showResult(n, e);
			}
		})();
	},
	formatResult({ result: e }) {
		let t = this.sources.get(e), n = this.options.resultFormat({
			result: e,
			provider: t
		});
		return this.selection === "all" && t ? `<span class="provider">${p(t.label)}</span>${n}` : n;
	},
	handleError(e, t, n, r) {
		var i;
		let { searchElement: a } = this;
		l(a.container, "error"), a.hasError = !0, (i = this.map) == null || i.fire("geosearch/error", {
			error: e,
			query: t,
			source: n,
			provider: r
		});
	},
	selectProvider(e) {
		var t;
		if (!(e === "all" ? this.choices.length > 1 && this.options.allProvidersLabel !== !1 : this.choices[e])) throw Error(`There is no provider ${e} to select`);
		if (e === this.selection || (this.selection = e, this.providerSelect && (this.providerSelect.value = String(e)), (t = this.map) == null || t.fire("geosearch/providerchange", {
			provider: this.getSelectedProvider(),
			providers: this.getProviders()
		}), !this.resultList)) return;
		let n = this.searchElement.input.value;
		this.options.autoComplete ? (this.lastQuery = "", this.suggest(n)) : n && this.resultList.count() > 0 && this.onSubmit({ query: n });
	},
	getSelectedProvider() {
		return this.selection === "all" ? {
			index: "all",
			label: this.options.allProvidersLabel
		} : S({}, this.choices[this.selection]);
	},
	getProviders() {
		return this.choices.map((e) => S({}, e));
	},
	reset() {
		this.clearResults(null, !0), this.searchElement.input.value = "", u(this.searchElement.container, "error"), this.searchElement.hasError = !1;
	},
	setQuery(e) {
		var t;
		this.searchElement.input.value = e, (t = this.resultList) == null || t.select(-1);
	},
	setLabels(e) {
		let t = T(e);
		if (Object.assign(this.options, t), t.searchLabel !== void 0) {
			var n;
			this.searchElement.input.placeholder = t.searchLabel, this.button.title = t.searchLabel, this.button.setAttribute("aria-label", t.searchLabel), (n = this.resultList) == null || n.container.setAttribute("aria-label", t.searchLabel);
		}
		if (t.clearSearchLabel !== void 0 && this.resetButton.setAttribute("aria-label", t.clearSearchLabel), t.notFoundMessage !== void 0) {
			var r;
			(r = this.resultList) == null || r.setNotFoundMessage(t.notFoundMessage);
		}
		t.providerSelectLabel !== void 0 && this.providerSelect && this.providerSelect.setAttribute("aria-label", t.providerSelectLabel);
	},
	setProviderSelectVisible(e) {
		this.options.showProviderSelect = e, this.providerSelect && (this.providerSelect.hidden = !e);
	},
	showResult(e, t) {
		let { autoClose: n, updateMap: r } = this.options, i = this.sources.get(e), a = !1;
		if (this.map.fire("geosearch/beforeshowlocation", {
			location: e,
			query: t,
			provider: i,
			preventDefault: () => {
				a = !0;
			}
		}), !a) {
			let n = this.markers.getLayers();
			n.length >= this.options.maxMarkers && this.markers.removeLayer(n[0]), this.movingMap = r;
			let a = this.addMarker(e, t);
			this.movingMap = !1, r && this.centerMap(e), this.map.fire("geosearch/showlocation", {
				location: e,
				marker: a,
				provider: i
			});
		}
		n && this.closeResults();
	},
	closeResults() {
		var e;
		let { container: t, input: n } = this.searchElement;
		t.classList.contains("active") && u(t, "active"), this.options.keepResult || (n.value = ""), this.cancelSearch(), this.lastQuery = "", (e = this.resultList) == null || e.clear();
	},
	addMarker(e, t) {
		let { marker: n, showPopup: i, popupFormat: a, markerPopup: o, popupOptions: s } = this.options, c = new r([e.y, e.x], n);
		if (o) {
			let n = p(e.label);
			typeof a == "function" && (n = a({
				query: t,
				result: e,
				provider: this.sources.get(e)
			})), c.bindPopup(n, s);
		}
		if (this.markers.addLayer(c), o && i) {
			let e = c.getPopup();
			if (this.movingMap && e != null && e.options.autoPan) {
				e.options.autoPan = !1;
				let t = () => {
					this.map.off("moveend", t), e.options.autoPan = !0, e.isOpen() && e.update();
				};
				this.map.on("moveend", t);
			}
			c.openPopup();
		}
		return n.draggable && c.on("dragend", (t) => {
			this.map.fire("geosearch/marker/dragend", {
				location: c.getLatLng(),
				event: t
			}), this.options.reverseOnDragEnd && this.reverseMarker(c, e);
		}), c;
	},
	reverseMarker(e, t) {
		var n = this;
		return v(function* () {
			var r;
			let { lat: i, lng: a } = e.getLatLng(), o = n.sources.get(t) || n.choices[n.selection === "all" ? 0 : n.selection], s = n.reverseIds = n.reverseIds || /* @__PURE__ */ new WeakMap(), c = (s.get(e) || 0) + 1;
			s.set(e, c);
			let l;
			try {
				if (typeof o.provider.reverse != "function") throw Error("This provider does not support reverse geocoding");
				l = yield o.provider.reverse({
					lat: i,
					lng: a
				});
			} catch (t) {
				if (s.get(e) === c) {
					var u;
					(u = n.map) == null || u.fire("geosearch/error", {
						error: t,
						query: `${i},${a}`,
						source: "reverse",
						provider: o
					});
				}
				return;
			}
			let [d] = l;
			if (s.get(e) !== c || !d) return;
			n.sources.set(d, o);
			let { popupFormat: f, keepResult: p, markerPopup: m } = n.options;
			m && e.setPopupContent(f({
				query: { query: d.label },
				result: d,
				provider: o
			})), p && (n.searchElement.input.value = d.label), (r = n.map) == null || r.fire("geosearch/marker/reverse", {
				location: d,
				marker: e,
				provider: o
			});
		})();
	},
	centerMap(e) {
		let { retainZoomLevel: t, animateZoom: n, zoomToBounds: r, fitBoundsOptions: i, flyTo: a } = this.options, o = S(S({}, typeof a == "object" ? a : {}), {}, { animate: n }), s = ie(e.bounds);
		if (!t && r && s) {
			let e = S(S({}, i), o);
			a ? this.map.flyToBounds(s, e) : this.map.fitBounds(s, e);
			return;
		}
		let c = s && (r || !E(e)) ? ae(s) : E(e) ? [e.y, e.x] : null;
		c && (a ? this.map.flyTo(c, this.getZoom(), o) : this.map.setView(c, this.getZoom(), o));
	},
	getZoom() {
		let { retainZoomLevel: e, zoomLevel: t } = this.options;
		return e ? this.map.getZoom() : t;
	}
}, O;
function se() {
	class t extends e {
		constructor(e) {
			super(), this.setup(e), this.callInitHooks();
		}
	}
	return Object.assign(t.prototype, oe), t.initHooks = !0, t;
}
var k = function(...t) {
	if (!e) throw Error(D);
	return O || (O = se()), new O(...t);
}, A = /* @__PURE__ */ function(e) {
	return e[e.SEARCH = 0] = "SEARCH", e[e.REVERSE = 1] = "REVERSE", e;
}({}), j = class {
	constructor(e = {}) {
		g(this, "options", void 0), this.options = e;
	}
	getParamString(e = {}) {
		let t = S(S({}, this.options.params), e);
		return Object.keys(t).map((e) => `${encodeURIComponent(e)}=${encodeURIComponent(t[e])}`).join("&");
	}
	getUrl(e, t) {
		return `${e}?${this.getParamString(t)}`;
	}
	search(e) {
		var t = this;
		return v(function* () {
			let n = t.endpoint({
				query: e.query,
				type: 0
			}), r = yield M(yield fetch(i(n), { signal: e.signal }));
			return t.parse({ data: r });
		})();
	}
	reverseParams(e) {
		return null;
	}
	reverse({ lat: e, lng: t, signal: n }) {
		var r = this;
		return v(function* () {
			let a = r.reverseParams({
				lat: e,
				lng: t
			});
			if (!a) throw Error("This provider does not support reverse geocoding");
			let o = r.endpoint({
				query: a,
				type: 1
			}), s = yield M(yield fetch(i(o), { signal: n }));
			return r.parse({ data: s });
		})();
	}
};
function M(e) {
	return N.apply(this, arguments);
}
function N() {
	return N = v(function* (e) {
		try {
			return yield e.json();
		} catch (t) {
			throw e.ok ? t : Error(`Request failed with status ${e.status} ${e.statusText}`.trim());
		}
	}), N.apply(this, arguments);
}
// src/providers/amapProvider.ts
var ce = class extends j {
	constructor(...e) {
		super(...e), g(this, "searchUrl", "https://restapi.amap.com/v3/geocode/geo");
	}
	endpoint({ query: e }) {
		let t = typeof e == "string" ? { address: e } : S({}, e);
		return t.output = "JSON", this.getUrl(this.searchUrl, t);
	}
	parse(e) {
		var t;
		return ((t = e.data.geocodes) == null ? [] : t).map((e) => ({
			x: Number(e.location.substring(0, e.location.indexOf(","))),
			y: Number(e.location.substring(e.location.indexOf(",") + 1)),
			label: e.formatted_address,
			bounds: null,
			raw: e
		}));
	}
}, P = 0, le = class extends j {
	constructor(...e) {
		super(...e), g(this, "searchUrl", "https://dev.virtualearth.net/REST/v1/Locations");
	}
	endpoint({ query: e, jsonp: t }) {
		let n = typeof e == "string" ? { q: e } : S({}, e);
		return n.jsonp = t, this.getUrl(this.searchUrl, n);
	}
	parse(e) {
		return e.data.resourceSets.length === 0 ? [] : e.data.resourceSets[0].resources.map((e) => ({
			x: e.point.coordinates[1],
			y: e.point.coordinates[0],
			label: e.address.formattedAddress,
			bounds: [[e.bbox[0], e.bbox[1]], [e.bbox[2], e.bbox[3]]],
			raw: e
		}));
	}
	search({ query: e }) {
		var t = this;
		return v(function* () {
			P += 1;
			let n = `BING_JSONP_CB_${Date.now()}_${P}`, r = yield s(t.endpoint({
				query: e,
				jsonp: n
			}), n);
			return t.parse({ data: r });
		})();
	}
}, ue = class extends j {
	constructor(e = {}) {
		super(e), g(this, "searchUrl", void 0), g(this, "reverseUrl", void 0);
		let t = "https://civildefense.fit.vutbr.cz";
		this.searchUrl = e.searchUrl || `${t}/search`, this.reverseUrl = e.reverseUrl || `${t}/reverse`;
	}
	endpoint({ query: e, type: t }) {
		var n;
		let r = typeof e == "string" ? { q: e } : S({}, e);
		switch (r.format === void 0 && ((n = this.options.params) == null ? void 0 : n.format) === void 0 && (r.format = "json"), t) {
			case A.REVERSE: return this.getUrl(this.reverseUrl, r);
			default: return this.getUrl(this.searchUrl, r);
		}
	}
	reverseParams({ lat: e, lng: t }) {
		return {
			lat: e,
			lon: t
		};
	}
	parse(e) {
		return (Array.isArray(e.data) ? e.data : [e.data]).map((e) => ({
			x: Number(e.lon),
			y: Number(e.lat),
			label: e.display_name,
			bounds: [[parseFloat(e.boundingbox[0]), parseFloat(e.boundingbox[2])], [parseFloat(e.boundingbox[1]), parseFloat(e.boundingbox[3])]],
			raw: e
		}));
	}
}, F = "https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/find", de = "https://geocode-api.arcgis.com/arcgis/rest/services/World/GeocodeServer/find", fe = class extends j {
	constructor(...e) {
		super(...e), g(this, "searchUrl", F);
	}
	endpoint({ query: e }) {
		var t, n, r;
		let i = typeof e == "string" ? { text: e } : S({}, e);
		i.f = "json", i.maxLocations === void 0 && ((t = this.options.params) == null ? void 0 : t.maxLocations) === void 0 && (i.maxLocations = 5);
		let a = ((n = i.token) == null ? (r = this.options.params) != null && r.token : n) && this.searchUrl === F ? de : this.searchUrl;
		return this.getUrl(a, i);
	}
	parse(e) {
		return e.data.locations.map((e) => ({
			x: e.feature.geometry.x,
			y: e.feature.geometry.y,
			label: e.name,
			bounds: [[e.extent.ymin, e.extent.xmin], [e.extent.ymax, e.extent.xmax]],
			raw: e
		}));
	}
}, I = class extends j {
	constructor(e = {}) {
		super(e), g(this, "searchUrl", void 0), g(this, "reverseUrl", void 0);
		let t = "https://data.geopf.fr/geocodage";
		this.searchUrl = e.searchUrl || `${t}/search`, this.reverseUrl = e.reverseUrl || `${t}/reverse`;
	}
	endpoint({ query: e, type: t }) {
		let n = typeof e == "string" ? { q: e } : e;
		switch (t) {
			case A.REVERSE: return this.getUrl(this.reverseUrl, n);
			default: return this.getUrl(this.searchUrl, n);
		}
	}
	reverseParams({ lat: e, lng: t }) {
		return {
			lat: e,
			lon: t
		};
	}
	parse(e) {
		return e.data.features.map((e) => ({
			x: e.geometry.coordinates[0],
			y: e.geometry.coordinates[1],
			label: e.properties.label,
			bounds: null,
			raw: e
		}));
	}
}, L = class extends j {
	constructor(e = {}) {
		super(e), g(this, "searchUrl", void 0), g(this, "reverseUrl", void 0);
		let t = "https://api.geoapify.com/v1/geocode";
		this.searchUrl = e.searchUrl || `${t}/search`, this.reverseUrl = e.reverseUrl || `${t}/reverse`;
	}
	endpoint({ query: e, type: t }) {
		let n = typeof e == "string" ? { text: e } : S({}, e);
		switch (n.format = "json", t) {
			case A.REVERSE: return this.getUrl(this.reverseUrl, n);
			default: return this.getUrl(this.searchUrl, n);
		}
	}
	reverseParams({ lat: e, lng: t }) {
		return {
			lat: e,
			lon: t
		};
	}
	parse(e) {
		return (Array.isArray(e.data.results) ? e.data.results : [e.data.results]).map((e) => {
			let t = null;
			return e.bbox && (t = [[parseFloat(e.bbox.lat1), parseFloat(e.bbox.lon1)], [parseFloat(e.bbox.lat2), parseFloat(e.bbox.lon2)]]), {
				x: Number(e.lon),
				y: Number(e.lat),
				label: e.formatted,
				bounds: t,
				raw: e
			};
		});
	}
}, R = class extends j {
	constructor(e = {}) {
		super(e), g(this, "host", void 0), this.host = e.host || "http://localhost:4000";
	}
	endpoint({ query: e, type: t }) {
		switch (t) {
			case A.REVERSE:
				let t = typeof e == "string" ? {} : e;
				return this.getUrl(`${this.host}/v1/reverse`, t);
			default:
				let n = typeof e == "string" ? { text: e } : e;
				return this.getUrl(`${this.host}/v1/autocomplete`, n);
		}
	}
	reverseParams({ lat: e, lng: t }) {
		return {
			"point.lat": e,
			"point.lon": t
		};
	}
	parse(e) {
		return e.data.features.map((e) => {
			let t = {
				x: e.geometry.coordinates[0],
				y: e.geometry.coordinates[1],
				label: e.properties.label,
				bounds: null,
				raw: e
			};
			return Array.isArray(e.bbox) && e.bbox.length === 4 && (t.bounds = [[e.bbox[1], e.bbox[0]], [e.bbox[3], e.bbox[2]]]), t;
		});
	}
}, z = class extends R {
	constructor(e = {}) {
		e.host = "https://api.geocode.earth", super(e);
	}
}, B = (e) => `The setOptions() function should only be called once. The options passed to the additional call (${JSON.stringify(e)}) will be ignored.`, V = (e) => `The google.maps.importLibrary() function is already defined, and @googlemaps/js-api-loader will use the existing function instead of overwriting it. The options passed to setOptions (${JSON.stringify(e)}) will be ignored.`, pe = (e, t) => `Failed to create Trusted Types policy "${e}": ${t instanceof Error ? t.message : String(t)}.\n\nIf your Content Security Policy uses "require-trusted-types-for 'script'", allow this policy with "trusted-types ${e} google-maps-api-loader google-maps-api#html lit-html". The "google-maps-api-loader", "lit-html", and "google-maps-api#html" policies are required for full Maps JavaScript API execution. Falling back to a string script URL.`, H = "@googlemaps/js-api-loader", U = { createScriptURL: (e) => e }, W;
function me() {
	if (W) return W;
	let e = globalThis.trustedTypes;
	if (!e) return W = U, W;
	try {
		W = e.createPolicy(H, { createScriptURL: (e) => e });
	} catch (e) {
		pe(H, e), W = U;
	}
	return W;
}
function he(e, t) {
	e.src = me().createScriptURL(t);
}
var ge = (e) => {
	var t, n, r, i = "The Google Maps JavaScript API", a = "google", o = "importLibrary", s = document, c = window, l = c[a] || (c[a] = {}), u = l.maps || (l.maps = {}), d = /* @__PURE__ */ new Set(), f = new URLSearchParams(), p = () => t || (t = new Promise(function() {
		var o = v(function* (o, c) {
			var l;
			for (r in yield n = s.createElement("script"), f.set("libraries", [...d] + ""), e) f.set(r.replace(/[A-Z]/g, (e) => "_" + e[0].toLowerCase()), e[r]);
			f.set("callback", a + ".maps.__ib__"), he(n, "https://maps.googleapis.com/maps/api/js?" + f), u.__ib__ = o, n.onerror = () => t = c(Error(i + " could not load.")), n.nonce = ((l = s.querySelector("script[nonce]")) == null ? void 0 : l.nonce) || "", s.head.append(n);
		});
		return function(e, t) {
			return o.apply(this, arguments);
		};
	}()));
	u[o] ? console.warn(i + " only loads once. Ignoring:", e) : u[o] = (e, ...t) => d.add(e) && p().then(() => u[o](e, ...t));
}, G = !1;
function _e(e) {
	G ? B(e) : (e.apiKey && (e.key || (e.key = e.apiKey)), ye(e), G = !0);
}
function ve(e) {
	return K.apply(this, arguments);
}
function K() {
	return K = v(function* (e) {
		var t;
		if (!((t = window) != null && (t = t.google) != null && (t = t.maps) != null && t.importLibrary)) throw Error("google.maps.importLibrary is not installed.");
		return yield google.maps.importLibrary(e);
	}), K.apply(this, arguments);
}
function ye(e) {
	var t;
	let n = !!((t = window.google) != null && (t = t.maps) != null && t.importLibrary);
	n && V(e), n || ge(e);
}
// src/providers/googleProvider.ts
var be = [
	"id",
	"nonce",
	"retries",
	"url"
];
function xe(e) {
	let t = be.filter((t) => e[t] !== void 0);
	t.length > 0 && console.warn(`GoogleProvider: the ${t.join(", ")} option(s) are not supported anymore and are ignored`);
	let n = {
		key: e.apiKey,
		v: e.version,
		language: e.language,
		region: e.region,
		libraries: e.libraries,
		mapIds: e.mapIds,
		authReferrerPolicy: e.authReferrerPolicy,
		channel: e.channel,
		client: e.client,
		solutionChannel: e.solutionChannel
	}, r = {};
	return Object.keys(n).forEach((e) => {
		n[e] !== void 0 && (r[e] = n[e]);
	}), r;
}
var q;
function J() {
	return q || (q = new Promise((e, t) => {
		let n = window, r = n.gm_authFailure;
		n.gm_authFailure = () => {
			r == null || r(), t(/* @__PURE__ */ Error("Google Maps rejected the API key, see the browser console for the reason"));
		};
	}), q.catch(() => {})), q;
}
function Se(e) {
	return new Promise((t, n) => {
		let r = () => n(new DOMException("The search was aborted", "AbortError"));
		e != null && e.aborted && r(), e == null || e.addEventListener("abort", r);
	});
}
function Ce(e) {
	return Y.apply(this, arguments);
}
function Y() {
	return Y = v(function* (e) {
		let t = typeof google < "u" ? google.maps : void 0;
		if (t != null && t.Geocoder) return new t.Geocoder();
		if (t != null && t.importLibrary) {
			let { Geocoder: e } = yield t.importLibrary("geocoding");
			return new e();
		}
		_e(xe(e));
		let { Geocoder: n } = yield ve("geocoding");
		return new n();
	}), Y.apply(this, arguments);
}
var X = class extends j {
	constructor(e) {
		super(e), g(this, "loader", null), g(this, "geocoder", null), typeof window < "u" && (J(), this.loader = Ce(e).then((e) => (this.geocoder = e, e)));
	}
	endpoint(e) {
		throw Error("Method not implemented.");
	}
	parse(e) {
		return e.data.results.map((e) => {
			let { lat: t, lng: n } = e.geometry.location.toJSON(), { east: r, north: i, south: a, west: o } = e.geometry.viewport.toJSON();
			return {
				x: n,
				y: t,
				label: e.formatted_address,
				bounds: [[a, o], [i, r]],
				raw: e
			};
		});
	}
	search(e) {
		var t = this;
		return v(function* () {
			let n = t.geocoder || (yield t.loader);
			if (!n) throw Error("GoogleMaps GeoCoder is not loaded. Are you trying to run this server side?");
			let r = new Promise((t, r) => {
				n.geocode({ address: e.query }, (e, n) => {
					n === "OK" || n === "ZERO_RESULTS" ? t({ results: e || [] }) : r(/* @__PURE__ */ Error(`Google geocoder answered ${n}`));
				});
			}), i = yield Promise.race([
				r,
				J(),
				Se(e.signal)
			]);
			return t.parse({ data: i });
		})();
	}
}, we = class extends j {
	constructor(...e) {
		super(...e), g(this, "searchUrl", "https://maps.googleapis.com/maps/api/geocode/json");
	}
	endpoint({ query: e }) {
		let t = typeof e == "string" ? { address: e } : e;
		return this.getUrl(this.searchUrl, t);
	}
	parse(e) {
		return e.data.results.map((e) => ({
			x: e.geometry.location.lng,
			y: e.geometry.location.lat,
			label: e.formatted_address,
			bounds: [[e.geometry.viewport.southwest.lat, e.geometry.viewport.southwest.lng], [e.geometry.viewport.northeast.lat, e.geometry.viewport.northeast.lng]],
			raw: e
		}));
	}
}, Te = class extends j {
	constructor(...e) {
		super(...e), g(this, "searchUrl", "https://geocode.search.hereapi.com/v1/geocode");
	}
	endpoint({ query: e }) {
		let t = typeof e == "string" ? { q: e } : e;
		return this.getUrl(this.searchUrl, t);
	}
	parse(e) {
		return e.data.items.filter((e) => e.position !== void 0).map((e) => ({
			x: e.position.lng,
			y: e.position.lat,
			label: e.address.label,
			bounds: null,
			raw: e
		}));
	}
}, Z = class extends j {
	constructor(e = {}) {
		super(e), g(this, "searchUrl", void 0), g(this, "reverseUrl", void 0);
		let t = "https://nominatim.openstreetmap.org";
		this.searchUrl = e.searchUrl || `${t}/search`, this.reverseUrl = e.reverseUrl || `${t}/reverse`;
	}
	endpoint({ query: e, type: t }) {
		var n;
		let r = typeof e == "string" ? { q: e } : S({}, e);
		switch (r.format === void 0 && ((n = this.options.params) == null ? void 0 : n.format) === void 0 && (r.format = "json"), t) {
			case A.REVERSE: return this.getUrl(this.reverseUrl, r);
			default: return this.getUrl(this.searchUrl, r);
		}
	}
	reverseParams({ lat: e, lng: t }) {
		return {
			lat: e,
			lon: t
		};
	}
	parse(e) {
		return (Array.isArray(e.data) ? e.data : [e.data]).map((e) => ({
			x: Number(e.lon),
			y: Number(e.lat),
			label: e.display_name,
			bounds: [[parseFloat(e.boundingbox[0]), parseFloat(e.boundingbox[2])], [parseFloat(e.boundingbox[1]), parseFloat(e.boundingbox[3])]],
			raw: e
		}));
	}
}, Ee = class extends Z {
	constructor(e) {
		super(S(S({}, e), {}, {
			searchUrl: "https://locationiq.org/v1/search.php",
			reverseUrl: "https://locationiq.org/v1/reverse.php"
		}));
	}
	parse(e) {
		return e.data.error ? [] : super.parse(e);
	}
}, De = class extends j {
	constructor(e = {}) {
		super(e), g(this, "searchUrl", void 0), this.searchUrl = e.searchUrl || "https://a.tiles.mapbox.com/v4/geocode/mapbox.places/";
	}
	endpoint({ query: e }) {
		let t = encodeURIComponent(String(e));
		return this.getUrl(`${this.searchUrl}${t}.json`);
	}
	parse(e) {
		return (Array.isArray(e.data.features) ? e.data.features : []).map((e) => {
			let t = null;
			return e.bbox && (t = [[parseFloat(e.bbox[1]), parseFloat(e.bbox[0])], [parseFloat(e.bbox[3]), parseFloat(e.bbox[2])]]), {
				x: Number(e.center[0]),
				y: Number(e.center[1]),
				label: e.place_name ? e.place_name : e.text,
				bounds: t,
				raw: e
			};
		});
	}
}, Oe = class extends j {
	constructor(...e) {
		super(...e), g(this, "searchUrl", "https://api.opencagedata.com/geocode/v1/json");
	}
	endpoint({ query: e }) {
		let t = typeof e == "string" ? { q: e } : S({}, e);
		return t.format = "json", this.getUrl(this.searchUrl, t);
	}
	parse(e) {
		return e.data.results.map((e) => {
			let t = null;
			return e.bounds && (t = [[e.bounds.southwest.lat, e.bounds.southwest.lng], [e.bounds.northeast.lat, e.bounds.northeast.lng]]), {
				x: e.geometry.lng,
				y: e.geometry.lat,
				label: e.formatted,
				bounds: t,
				raw: e
			};
		});
	}
	search(e) {
		var t = () => super.search, n = this;
		return v(function* () {
			return e.query.length < 2 ? [] : t().call(n, e);
		})();
	}
}, ke = class extends j {
	constructor(e = { apiKey: "" }) {
		super(e), g(this, "apiKey", void 0), g(this, "host", void 0), this.apiKey = e.apiKey, this.host = e.host || "https://api.opengeocoding.com";
	}
	endpoint({ query: e, type: t }) {
		return t === A.REVERSE ? `${this.host}/v1/reverse?${Q(e)}` : this.getUrl(`${this.host}/v1/autocomplete`, { text: e });
	}
	reverseParams({ lat: e, lng: t }) {
		return {
			lat: e,
			lon: t
		};
	}
	search({ query: e, data: t, signal: n }) {
		var r = this;
		return v(function* () {
			let i = t && !Number.isFinite(t.x) && t.raw.id, a = i ? `${r.host}/v1/retrieve?${Q({ id: i })}` : r.endpoint({
				query: e,
				type: A.SEARCH
			});
			return r.parse({ data: yield r.request(a, n) });
		})();
	}
	reverse({ lat: e, lng: t, signal: n }) {
		var r = this;
		return v(function* () {
			let i = r.reverseParams({
				lat: e,
				lng: t
			}), a = r.endpoint({
				query: i,
				type: A.REVERSE
			});
			return r.parse({ data: yield r.request(a, n) });
		})();
	}
	request(e, t) {
		var n = this;
		return v(function* () {
			let r = yield fetch(i(e), {
				headers: { "x-api-key": n.apiKey },
				signal: t
			}), a = yield M(r);
			if (!r.ok) throw Error(a.message || `Request failed with status ${r.status}`);
			return a;
		})();
	}
	parse({ data: e }) {
		return "suggestions" in e ? (e.suggestions || []).map((e) => $(e.displayName, e.geoCoordinates, e)) : "results" in e ? (e.results || []).map((e) => $(e.address.displayName, e.address.geoCoordinates, e)) : "id" in e ? [$(e.displayName, e.geoCoordinates, e)] : [];
	}
};
function Q(e) {
	return Object.keys(e).map((t) => `${encodeURIComponent(t)}=${encodeURIComponent(e[t])}`).join("&");
}
function $(e, t, n) {
	let r = t && t.boundingBox, i = r ? [[r.minLat, r.minLon], [r.maxLat, r.maxLon]] : null;
	return {
		x: t ? t.lon || 0 : NaN,
		y: t ? t.lat || 0 : NaN,
		label: e,
		bounds: i,
		raw: n
	};
}
// src/providers/pdokNlProvider.ts
var Ae = class extends j {
	constructor(e = {}) {
		super(S(S({}, e), {}, { params: S({
			bq: "type:gemeente^0.5 type:woonplaats^0.5 type:weg^1.0 type:postcode^1.5 type:adres^2.0",
			fl: "id,weergavenaam,centroide_ll",
			rows: 5
		}, e.params) })), g(this, "searchUrl", void 0), g(this, "reverseUrl", void 0);
		let t = "https://api.pdok.nl/bzk/locatieserver/search/v3_1";
		this.searchUrl = `${t}/suggest`, this.reverseUrl = `${t}/reverse`;
	}
	endpoint({ query: e, type: t }) {
		let n = typeof e == "string" ? { q: e } : e;
		switch (t) {
			case A.REVERSE: return this.getUrl(this.reverseUrl, n);
			default: return this.getUrl(this.searchUrl, n);
		}
	}
	reverseParams({ lat: e, lng: t }) {
		return {
			lat: e,
			lon: t
		};
	}
	parse({ data: e }) {
		return e.response.docs.map((t) => {
			var n;
			let r = t.centroide_ll.replace(/POINT\(|\)/g, "").trim().split(" ").map(Number), i = t.weergavenaam, a = (n = e.highlighting) == null || (n = n[t.id]) == null ? void 0 : n.suggest;
			return a && (i = a[0]), {
				x: r[0],
				y: r[1],
				label: t.weergavenaam,
				bounds: null,
				raw: S(S({}, t), {}, { highlight: i })
			};
		});
	}
	search(e) {
		var t = this;
		return v(function* () {
			let n = t.endpoint({ query: e.query }), r = yield fetch(i(n), { signal: e.signal }).then((e) => M(e)).then((n) => {
				if (n.response.numFound < 1 && n.spellcheck.collations.length >= 2) {
					let r = t.endpoint({ query: n.spellcheck.collations[1].collationQuery });
					return fetch(i(r), { signal: e.signal }).then((e) => M(e));
				}
				return n;
			});
			return t.parse({ data: r });
		})();
	}
}, je = class extends j {
	constructor(e = {}) {
		super(e), g(this, "searchUrl", void 0), g(this, "reverseUrl", void 0);
		let t = e.host || "https://photon.komoot.io";
		this.searchUrl = `${t}/api`, this.reverseUrl = `${t}/reverse`;
	}
	endpoint({ query: e, type: t }) {
		let n = typeof e == "string" ? { q: e } : S({}, e);
		switch (t) {
			case A.REVERSE: return this.getUrl(this.reverseUrl, n);
			default: return this.getUrl(this.searchUrl, n);
		}
	}
	reverseParams({ lat: e, lng: t }) {
		return {
			lat: e,
			lon: t
		};
	}
	parse(e) {
		return e.data.features.map((e) => {
			let [t, n] = e.geometry.coordinates, r = e.properties.extent, i = r ? [[r[3], r[0]], [r[1], r[2]]] : null;
			return {
				x: t,
				y: n,
				label: Me(e.properties),
				bounds: i,
				raw: e
			};
		});
	}
};
function Me(e) {
	let t = [e.street, e.housenumber].filter(Boolean).join(" "), n = [e.postcode, e.city].filter(Boolean).join(" "), r = [
		e.name,
		t,
		n,
		e.state,
		e.country
	].filter((e) => !!e);
	return r.filter((e, t) => r.indexOf(e) === t).join(", ");
}
export { ce as AMapProvider, le as BingProvider, ue as CivilDefenseMapProvider, fe as EsriProvider, I as GeoApiFrProvider, k as GeoSearchControl, k as SearchControl, L as GeoapifyProvider, z as GeocodeEarthProvider, X as GoogleProvider, Te as HereProvider, j as JsonProvider, we as LegacyGoogleProvider, Ee as LocationIQProvider, De as MapBoxProvider, Oe as OpenCageProvider, ke as OpenGeocodingProvider, Z as OpenStreetMapProvider, Ae as PdokNlProvider, R as PeliasProvider, je as PhotonProvider, y as SearchElement, p as escapeHtml };

//# sourceMappingURL=geosearch.module.js.map