<form autocomplete="on" enctype="multipart/form-data" method="post"
action="<?php echo $urlbase . '/tag/add/'?>">
<table>
<tr><td>Tag</td><td>
<input type="text" name="tag"/>
</td></tr>
<tr><td>Admin</td><td>
<input type="text" name="admin"/>
</td></tr>
<tr><td>Email</td><td>
<input type="text" name="email"/>
</td></tr>
</table>
<input type="reset" value="reset" />
<input type="submit" value="submit" />
</form>

