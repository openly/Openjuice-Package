<?php
defined('C5_EXECUTE') or die(_("Access Denied."));
echo Loader::helper('concrete/dashboard')->getDashboardPaneHeaderWrapper($form->getTitle(), false, false, false);
?>
	<form method='post' action='<?php echo $target ?>'>
	<div class="ccm-pane-body">
		<?php
			$form->preProcess();
		?>
		<table width='100%' class="ccm-results-list">
			<tr>
				<th colspan='2'><?php echo $form->getSubTitle() ?></td>
			</tr>
		<?php foreach($form->getFields() as $field){ ?>
			<tr>
				<td class='subheader'><?php echo $field->getLabel(); ?></td>
				<td><?php echo $field->getField(); ?></td>
			</tr>
		<?php } ?>
		</table>
	</div>
	<div class='ccm-pane-footer'>
		<?php
			echo $form->buttons();
		?>
	</div>
	</form>
<?php echo Loader::helper('concrete/dashboard')->getDashboardPaneFooterWrapper(false);?>
