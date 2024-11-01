/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 0);
/******/ })
/************************************************************************/
/******/ ({

/***/ "./dev/js/admin.js":
/*!*************************!*\
  !*** ./dev/js/admin.js ***!
  \*************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("jQuery(document).ready(function ($) {\r\n    if ($('#feedback_form').length) {\r\n        $('#feedback_send').on('click', function(){\r\n            $('#feedback_sent, #feedback_not_sent').hide();\r\n            let data = {\r\n                'action': 'wopc_send_feedback',\r\n                'nonce': $('#wopc_nonce_send_feedback').val(),\r\n                'email': $('#feedback_email').val(),\r\n                'name': $('#feedback_name').val(),\r\n                'note': $('#feedback_note').val(),\r\n                'message': $('#feedback_message').val(),\r\n            };\r\n            jQuery.post(wopc_ajax_object.ajax_url, data, function (response) {\r\n                var res = JSON.parse(response);\r\n                if ('done' === res.status) {\r\n                    $('#feedback_sent').show();\r\n                }\r\n                else {\r\n                    $('#feedback_not_sent').show();\r\n                    alert(res.response);\r\n                }\r\n            });\r\n            return false;\r\n        });\r\n    }\r\n\r\n    if ($('#wopc_campaign_meta').length) {\r\n        // PAGE LOAD\r\n        wopc_code_preview();\r\n        wopc_how_many();\r\n\r\n        // ON EVENTS FUNCTIONS\r\n        $('#code_pattern').on('blur', function () {\r\n            wopc_code_preview();\r\n        });\r\n        $('html').on('change','#wopc_meta_campaign, #wopc_div_code_how_many', function () {\r\n            wopc_how_many();\r\n        });\r\n\r\n        // MODULES /\r\n        // AUTOCOMPLETE PRODUCTS\r\n        var cache_products = {};\r\n        $('.wopc_sutocomplete').each(function () {\r\n            let el = $(this);\r\n            let el_name = $(el).attr('data-attr');\r\n            $(this).autocomplete({\r\n                source: function (request, response) {\r\n                    let term = request.term;\r\n                    if (term in cache_products) {\r\n                        response(cache_products[term]);\r\n                        return;\r\n                    }\r\n\r\n                    $.ajax({\r\n                        url: wopc_ajax_object.ajax_url,\r\n                        dataType: \"json\",\r\n                        method: 'post',\r\n                        data: {\r\n                            action: $(el).attr('data-action_ajax'),\r\n                            nonce: $('#wopc_nonce_' + el_name).val(),\r\n                            term: term,\r\n                            name: el_name\r\n                        },\r\n                        success: function (data) {\r\n                            if ('done' == data.status) {\r\n                                cache_products[term] = data.items;\r\n                                response(data.items);\r\n                            }\r\n                            else {\r\n                                console.log(data.response);\r\n                            }\r\n                        }\r\n                    });\r\n                },\r\n                minLength: 1,\r\n                select: function (event, ui) {\r\n                    let item_id = ui.item.id;\r\n                    let item_name = ui.item.value;\r\n                    let found = false;\r\n                    let values = $('#' + el_name).val();\r\n                    values = values.split(',');\r\n                    for (let index in values) {\r\n                        if (values[index] == ui.item.id) {\r\n                            found = true;\r\n                        }\r\n                    }\r\n\r\n                    if (false == found) {\r\n                        // ADD TO HIDDEN INPUT\r\n                        values.push(item_id);\r\n                        values = values.join(',');\r\n                        $('#' + el_name).val(values);\r\n\r\n                        // ADD TO PRODUCT LIST\r\n                        let html_li = '<li><button type=\"button\" id=\"product_li-' + item_id + '\" data-attr=\"' + item_id + '\" class=\"ntdelbutton\" title=\"' + replace_in_translation(wopc_admin_translation.remove_product, item_name) + '\"><span class=\"remove-tag-icon\" aria-hidden=\"true\"></span></button>&nbsp;' + item_name + '</li>';\r\n                        $('#products_list').append(html_li);\r\n                    }\r\n\r\n                    $(el).val('');\r\n                    return false;\r\n                }\r\n            });\r\n\r\n            $('html').on('click', '#' + el_name +'_list button', function () {\r\n                let id = $(this).attr('data-attr');\r\n                let value = $('#' + el_name).val();\r\n                if ('' != id) {\r\n                    value = value.split(',');\r\n                    value = value.filter(item => item !== id);\r\n                    $(this).closest('li').remove();\r\n                }\r\n                $('#' + el_name).val(value);\r\n            });\r\n        });\r\n    }\r\n});\r\n\r\nfunction replace_in_translation(string, replace) {\r\n    return string.replace(/%s/g, replace);\r\n}\r\n\r\nfunction wopc_how_many() {\r\n    $ = jQuery;\r\n    let value_select = $('#code_how_many').val();\r\n\r\n    $('#wopc_div_code_fixed , #wopc_div_code_variable').hide();\r\n    if ('fixed_codes' == value_select) {\r\n        $('#wopc_div_code_fixed').show();\r\n    }\r\n    else if ('variable_codes' == value_select) {\r\n        $('#wopc_div_code_variable').show();\r\n    }\r\n}\r\n\r\nfunction wopc_code_preview() {\r\n    $ = jQuery;\r\n    let value_pattern = $('#wopc_campaign_meta input#code_pattern').val();\r\n    let data = {\r\n        'action': 'wopc_preview',\r\n        'nonce': $('#wopc_nonce_preview').val(),\r\n        'pattern': value_pattern,\r\n        'campaign' : $('#post_ID').val(),\r\n    };\r\n    jQuery.post(wopc_ajax_object.ajax_url, data, function (response) {\r\n        var res = JSON.parse(response);\r\n        if ('done' === res.status) {\r\n            $('#wopc_pattern_preview').html(res.response);\r\n        }\r\n        else {\r\n            alert(res.response);\r\n        }\r\n    });\r\n}\n\n//# sourceURL=webpack:///./dev/js/admin.js?");

/***/ }),

/***/ "./dev/scss/admin.scss":
/*!*****************************!*\
  !*** ./dev/scss/admin.scss ***!
  \*****************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

eval("// extracted by mini-css-extract-plugin\n\n//# sourceURL=webpack:///./dev/scss/admin.scss?");

/***/ }),

/***/ 0:
/*!*****************************************************!*\
  !*** multi ./dev/js/admin.js ./dev/scss/admin.scss ***!
  \*****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

eval("__webpack_require__(/*! ./dev/js/admin.js */\"./dev/js/admin.js\");\nmodule.exports = __webpack_require__(/*! ./dev/scss/admin.scss */\"./dev/scss/admin.scss\");\n\n\n//# sourceURL=webpack:///multi_./dev/js/admin.js_./dev/scss/admin.scss?");

/***/ })

/******/ });