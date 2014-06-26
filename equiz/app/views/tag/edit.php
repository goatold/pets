<form autocomplete="on" enctype="multipart/form-data" method="post"
action="<?php echo $urlbase .'/tag/edit/?tag='. $vargs['tag']?>">
<table>
<tr><td>Tag</td><td>
<?php echo $vargs['tag'];?>
</td></tr>
<tr><td>Admin</td><td>
<input type="text" name="admin" value="<?php echo $vargs['fields']['admin'];?>"/>
</td></tr>
<tr><td>Email</td><td>
<input type="text" name="email" value="<?php echo $vargs['fields']['email'];?>"/>
</td></tr>
</table>
<input type="reset" value="reset" />
<input type="submit" value="submit" />
</form>
