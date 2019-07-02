var timer;

$(document).ready(function() {
	$(".result").on("click", function() {
		var id = $(this).attr("data-linkId");
		var url = $(this).attr("href");

		if (!id) {
			alert("data-linkId attribue not found");
		}

		increaseLinkClicks(id, url);

		return false;
	});

	var grid = $(".imageResults");

	grid.on("layoutComplete", function() {
		$(".gridItem img").css("visibility", "visible");
	});

	grid.masonry({
		itemSelector: ".gridItem",
		columWidth: 200,
		gutter: 5,
		//ransitionDuration: 0
		isInitLayout: false
	});

	$("[data-fancybox]").fancybox();
});

function loadImage(src, className) {
	var image = $("<img>");

	image.on("load", function() {
		$("." + className + " a").append(image);
		
		clearTimeout(timer);

		timer = setTimeout(function() {
			$(".imageResults").masonry();
		}, 500);
	});

	image.on("error", function() {
		$("." + className).remove();
		$.post("ajax/setBroken.php", {src: src});
	});

	image.attr("src", src);
}

function increaseLinkClicks(linkId, url) {
	$.post(
		"ajax/updateLinkCount.php",
		{ linkId: linkId})
	.done(function(result) {
		if (result != "") {
			alert(result);
			return;
		}

		window.location.href = url;
	});
}