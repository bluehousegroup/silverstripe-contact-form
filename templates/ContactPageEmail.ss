<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>

<head>
</head>
<body>

<p class="body">
$IntroText
</p>
<p class="body">
<% loop Fields %>
	<% if Values %>
		<strong>$Label</strong>:<br />
		<ul>
			<% loop Values %>
				<li>$Value</li>
			<% end_loop %>
		</ul>
	<% else %>
		<strong>$Label</strong>: $Value <br />
	<% end_if %>
<% end_loop %>
</p>
<small>This email was received from <a href="$Domain">$Domain</a></small>
</body>
</html>
