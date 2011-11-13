<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/backend.css?'.AKEEBASUBS_VERSIONHASH);
JHtml::_('behavior.mootools');

$this->loadHelper('select');
$this->loadHelper('cparams');
$this->loadHelper('format');
?>
<form action="index.php" method="post" name="adminForm">
<input type="hidden" name="option" value="com_akeebasubs" />
<input type="hidden" name="view" value="affpayments" />
<input type="hidden" id="task" name="task" value="browse" />
<input type="hidden" name="hidemainmenu" id="hidemainmenu" value="0" />
<input type="hidden" name="boxchecked" id="boxchecked" value="0" />
<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
<input type="hidden" name="<?php echo JUtility::getToken();?>" value="1" />

<table class="adminlist">
	<thead>
		<tr>
			<th width="8%">
				<?php echo JHTML::_('grid.sort', 'Num', 'akeebasubs_affpayment_id', $this->lists->order_Dir, $this->lists->order) ?>
			</th>
			<th width="16"></th>
			<th>
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_AFFPAYMENT_USER_ID', 'akeebasubs_affiliate_id', $this->lists->order_Dir, $this->lists->order) ?>
			</th>
			<th width="10%">
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_AFFPAYMENT_AMOUNT', 'amount', $this->lists->order_Dir, $this->lists->order) ?>
			</th>
			<th width="15%">
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_AFFPAYMENT_CREATED', 'amount', $this->lists->order_Dir, $this->lists->order) ?>
			</th>
			
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $this->items ) + 1; ?>);" />
			</td>
			<td>
				<input type="text" name="search" id="search"
					value="<?php echo $this->escape($this->getModel()->getState('search',''));?>"
					class="text_area" onchange="document.adminForm.submit();" />
				<button onclick="this.form.submit();">
					<?php echo version_compare(JVERSION, '1.6.0', 'ge') ? JText::_('JSEARCH_FILTER') : JText::_('Go'); ?>
				</button>
				<button onclick="document.adminForm.search.value='';this.form.submit();">
					<?php echo version_compare(JVERSION, '1.6.0', 'ge') ? JText::_('JSEARCH_RESET') : JText::_('Reset'); ?>
				</button>
			</td>
			<td></td>
			<td></td>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="20">
				<?php if($this->pagination->total > 0) echo $this->pagination->getListFooter() ?>
			</td>
		</tr>
	</tfoot>
	<tbody>
		<?php if($count = count($this->items)): ?>
		<?php $i = -1; $m = 0; ?>
		<?php foreach ($this->items as $item) : ?>
		<?php
			$i++; $m = 1-$m;
		?>
		<tr class="<?php echo  'row'.$m; ?>">
			<td align="center">
				<a href="index.php?option=com_akeebasubs&view=affpayments&task=edit&id=<?php echo $item->akeebasubs_affpayment_id ?>">
				<?php echo sprintf('%05u', $item->akeebasubs_affpayment_id); ?>
				</a>
			</td>
			<td align="center">
				<?php echo JHTML::_('grid.id', $i, $item->akeebasubs_affpayment_id); ?>
			</td>
			<td>
					<?php if(AkeebasubsHelperCparams::getParam('gravatar')):?>
						<?php if(JURI::getInstance()->getScheme() == 'http'): ?>
							<img src="http://www.gravatar.com/avatar/<?php echo md5(strtolower($item->email))?>.jpg?s=32&d=mm" align="left" class="gravatar"  />
						<?php else: ?>
							<img src="https://secure.gravatar.com/avatar/<?php echo md5(strtolower($item->email))?>.jpg?s=32&d=mm" align="left" class="gravatar"  />
						<?php endif; ?>
					<?php endif; ?>
					<strong><?php echo $this->escape($item->username)?></strong>
					<span class="small">[<?php echo $item->user_id?>]</span>
					<br/>
					<?php echo $this->escape($item->name)?>
					<br/>
					<?php echo $this->escape($item->email)?>
				</span>
			</td>
			<td align="right">
				<?php echo sprintf('%.02f', $item->amount) ?>
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€') ?>
			</td>
			<td>
				<?php echo AkeebasubsHelperFormat::date($item->created_on) ?>
			</td>
		</tr>
		<?php endforeach; ?>
		<?php else: ?>
		<?php endif; ?>
	</tbody>
</table>

</form>