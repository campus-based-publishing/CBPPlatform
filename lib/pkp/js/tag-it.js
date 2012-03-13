/*
 * jQuery Tag-it plugin
 *
 * By Levy Carneiro Jr (http://levycarneiro.com/)
 *
 * Customized by Matthew Crider, PKP (http://pkp.sfu.ca), 2010
 */

(function($) {

	$.fn.tagit = function(options) {

		var el = this;
		var id = el.attr('id');

		// MC: const declarations are mozilla-only--Declare as var
		var BACKSPACE		= 8;
		var ENTER			= 13;
		var SPACE			= 32;
		var COMMA			= 44;

		// add the tagit CSS class.
		el.addClass("tagit");


		// create the input field.
		// MC: Allow for unique IDs (so multiple tag-it instances can live on the same page
		var html_input_field = "<li class=\"tagit-new\"><input class=\"tagit-input\" id=\""+id+"KeywordInput\" type=\"text\" /></li>\n";
		el.html (html_input_field);

		//tag_input		= el.children(".tagit-new").children(".tagit-input");
		var tag_input = $("#"+id+"KeywordInput");

		// Add the existing keywords
		// MC 'For each' is not browser-safe. Use jQuery's .each method
		var currentTags = options.currentTags;
		$.each(currentTags, function() {
			create_choice(this, true);
		});

		$(this).click(function(e){
			if (e.target.tagName == 'A') {
				// Removes a tag when the little 'x' is clicked.
				// Event is binded to the UL, otherwise a new tag (LI > A) wouldn't have this event attached to it.
				$(e.target).parent().remove();
			}
			else {
				// Sets the focus() to the input field, if the user clicks anywhere inside the UL.
				// This is needed because the input field needs to be of a small size.
				//tag_input.focus();
			}
		});

		tag_input.keypress(function(event){
			if (event.which == BACKSPACE) {
				if (tag_input.val() == "") {
					// When backspace is pressed, the last tag is deleted.
					$(el).children(".tagit-choice:last").remove();
				}
			}
			// Comma/Space/Enter are all valid delimiters for new tags.
			// MC: Allow for multi-word keywords (removed space as delimiter)
			else if (event.which == COMMA || event.which == ENTER) {
				event.preventDefault();

				var typed = tag_input.val();
				typed = typed.replace(/,+$/,"");
				typed = typed.trim();

				if (typed != "") {
					if (is_new (typed)) {
						create_choice (typed);
					}
					// Cleaning the input.
					tag_input.val("");
				}
			}
		});

		// MC Need to unescape the data going into the autocomplete widget
		var autoCompleteSource = new Array();
		$.each(options.availableTags, function() {
			autoCompleteSource.push(unescapeHTML(this.toString()));
		});
		tag_input.autocomplete({
			source: autoCompleteSource,
			select: function(event,ui){
				if (is_new (ui.item.value)) {
					create_choice (ui.item.value);
				}
				// Cleaning the input.
				tag_input.val("");

				// Preventing the tag input to be update with the chosen value.
				return false;
			}
		});

		function is_new (value){
			var is_new = true;
			tag_input.parents("ul").children(".tagit-choice").each(function(i){
				n = $(this).children("input").val();
				if (value == n) {
					is_new = false;
				}
			})
			return is_new;
		}
		function create_choice (value, loadingList){
			if(loadingList == true) value = unescapeHTML(value.toString());  // Unescape HTML encodings (e.g. &lt;)
			value = unescape(value);	// Unescape JS encodings (e.g. %3E;)
			var el = "";
			el  = "<li class=\"tagit-choice\">\n";
			el += escapeHTML(value.toString()) + "\n";
			el += "<a class=\"close\">x</a>\n";
			el += "<input type=\"hidden\" class=\"keywordValue\" style=\"display:none;\" value=\""+urlEncode(value)+"\" name=\""+id+"Keywords[]\">\n";
			el += "</li>\n";
			var li_search_tags = tag_input.parent();
			$(el).insertBefore (li_search_tags);
			tag_input.val("");
		}
	};

	String.prototype.trim = function() {
		return this.replace(/^\s+|\s+$/g,"");
	};

})(jQuery);
