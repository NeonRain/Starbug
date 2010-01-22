<script type="text/javascript">
	function switch_icon(text) {
		if (text == '--') return '&crarr;';
		else return '--';
	}
</script>
<h2>
	<a id="add_uri" class="right round button" href="<?php echo uri("sb/uris/create"); ?>">Create URI</a>
	URIs
</h2>
<table id="uris_table" class="clear lister">
<?php foreach(array("thead", "tfoot") as $t) { ?><?php echo "<$t>"; ?><tr><th class="expand-col"></th><th class="title-col">Title</th><th class="collective-col">Collective</th><th class="date-col">Last Modified</th></tr><?php echo "</$t>"; ?><?php } ?>
<?php
$sb->import("util/dojo");
$sb->import("util/form");
$uris = $sb->query("uris", "action:read");
$kids = array();
foreach($uris as $uri) $kids[$uri['parent']][] = $uri;
function list_uri($row, $kids) { global $sb; global $request; global $dojo; ?>
	<tr id="uris_<?php echo $row['id']; ?>">
		<td class="expand-col"><?php if (!empty($kids[$row['id']])) echo '&crarr;'; ?></td>
		<td class="title-col">
			<a href="<?php echo uri("sb/uris/update/$row[id]"); ?>"><?php echo $row['title']; ?></a>
			<ul class="row-actions">
				<li class="first"><a href="<?php echo uri("sb/uris/update/$row[id]"); ?>">edit</a></li>
				<li><?php $f = new form("uris", "action:delete"); echo $f->open(); echo $f->hidden("id	value:".$row['id']); echo $f->submit("class:link	value:delete"); ?></form></li>
				<li><a href="<?php echo uri($row['path']); ?>">view</a></li>
			</ul>
		</td>
		<td class="collective-col"><?php echo array_search($row['collective'], array_merge(array("everybody" => 0), $request->groups)); ?></td>
		<td class="date-col"><?php echo date("F jS, Y", strtotime($row['modified'])); ?></td>
	</tr>
	<?php
		if (!empty($kids[$row['id']])) { ?>
	<tr id="parent_<?php echo $row['id']; ?>" style="display:none">
		<td colspan="3">
		<table class="lister">
		<?php
			$dojo->toggle("#uris_".$row['id']." .expand-col", "tg_".$row['id'], "parent_".$row['id'], "default:off	add:showFunc:dojo.fx.wipeIn, hideFunc:dojo.fx.wipeOut, duration:300");
			$dojo->attach("#uris_".$row['id']." .expand-col", "sb.replace", "node:evt.target	data:switch_icon(evt.target.innerHTML)");
			foreach($kids[$row['id']] as $uri) list_uri($uri, $kids); 
		?>
		</table>
		</td>
	</tr>
	<?php
		}
	?>
<?php } ?>
<?php foreach($kids[0] as $uri) list_uri($uri, $kids); ?>
</table>
<?php if ($this->uri[1] == "uris") { ?>
<a id="add_uri" class="big left round button" href="<?php echo uri("sb/uris/create"); ?>">Create URI</a>
<?php } ?>