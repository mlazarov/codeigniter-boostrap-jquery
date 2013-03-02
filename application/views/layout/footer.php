<?php
/*
 * Created: 02.03.2013
 * Updated: 02.03.2013
 * Created by: Martin Lazarov
 *
 * Changelog:
 */
?>
<?php

if(!$this->flat){
?>
<div class="container" id="footer">
	<footer>
	        <p>
	        	<a href="/terms/">Terms</a> | <a href="/contact/">Feedback</a> |
	        	Page rendered in {elapsed_time} seconds |
	        	<span id="window-width">w</span>x<span id="window-height">h</span>
	        </p>
	</footer>
</div> <!-- /container -->
<script>
function update_window_size(){
	$('#window-height').text($(window).height());
	$('#window-width').text($(window).width());
}
$(window).resize(update_window_size);
update_window_size();
</script>
<?php } ?>

</body>
</html>
