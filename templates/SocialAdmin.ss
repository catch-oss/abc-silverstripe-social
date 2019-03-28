<!DOCTYPE html>
<html>
	<head>
	    <title>Insert a Social Post</title>
	    <% require javascript("silverstripe/admin: thirdparty/jquery/jquery.min.js") %>
        <% require javascript("silverstripe/admin: thirdparty/tinymce/plugins/compat3x/tiny_mce_popup.js") %>
        <% require javascript("azt3k/abc-silverstripe-social: js/popup.js") %>
        <% require css("azt3k/abc-silverstripe-social: css/popup.css") %>
	</head>
	<body>
		<form>
			<div>
				<label for="url">Post URL</label>
				<input type="text" name="url" id="url">
				<button>OK</button>
			</div>
			<div id="preview"></div>
		</form>
	</body>
</html>
