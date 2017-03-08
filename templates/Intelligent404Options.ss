<h3><%t Intelligent404.OptionsHeader "Were you looking for one of the following?" %></h3>
<% if $Pages %>
    <ul class="404options">
    	<% loop $Pages %>
    		<li>
    			<a href="$Link">
    				<strong>$MenuTitle</strong> -
    				<i>$Link</i>
    			</a>
    		</li>
    	<% end_loop %>
    </ul>
<% end_if %>
