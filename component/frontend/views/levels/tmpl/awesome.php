<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

$this->loadHelper('cparams');
$this->loadHelper('modules');
$this->loadHelper('format');
$this->loadHelper('message');
require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/image.php';

// Take display VAT into account
$showVat = AkeebasubsHelperCparams::getParam('showvat', 0);
/** @var AkeebasubsModelTaxhelper $taxModel */
$taxModel = F0FModel::getTmpInstance('Taxhelper', 'AkeebasubsModel');
$taxParams = $taxModel->getTaxDefiningParameters();
// Take the various inclusions into account
$includesignup = AkeebasubsHelperCparams::getParam('includesignup', 2);
$includediscount = AkeebasubsHelperCparams::getParam('includediscount', 0);
// Only consider discounts if it's a logged in user
$user = JFactory::getUser();
$includediscount = ($includediscount && !$user->guest) ? true : false;
?>

<div id="akeebasubs" class="levels awesome">

<?php echo AkeebasubsHelperModules::loadposition('akeebasubscriptionslistheader')?>

<?php $max = count($this->items); ?>

<div class="akeebasubs-awesome">
	<div class="columns columns-<?php echo $max?>">
		<?php $i = 0; foreach($this->items as $level): $i++?>
		<?php
			$signupFee = 0;

			if (!in_array($level->akeebasubs_level_id, $this->subIDs) && ($includesignup != 0))
			{
				$signupFee = (float)$level->signupfee;
			}

			$vatRule = $taxModel->getTaxRule($level->akeebasubs_level_id, $taxParams['country'], $taxParams['state'], $taxParams['city'], $taxParams['vies']);
			$vatMultiplier = (100 + (float)$vatRule->taxrate) / 100;

			if ($includediscount)
			{
				/** @var AkeebasubsModelSubscribes $subscribesModel */
				$subscribesModel = F0FModel::getTmpInstance('Subscribes', 'AkeebasubsModel')->savestate(false);
				$subscribesModel->setState('id', $level->akeebasubs_level_id);
				$subValidation = $subscribesModel->validatePrice(true);
				$discount = $subValidation->discount;
				$levelPrice = $level->price - $discount;

				$formatedPriceD = sprintf('%1.02F', $level->price);
				$dotposD = strpos($formatedPriceD, '.');
				$price_integerD = substr($formatedPriceD,0,$dotposD);
				$price_fractionalD = substr($formatedPriceD,$dotposD+1);
			}
			else
			{
				$discount = 0;
				$levelPrice = $level->price;
			}

			if ($includesignup == 1)
			{
				if (($levelPrice + $signupFee) < 0)
				{
					$levelPrice = -$signupFee;
				}

				$formatedPrice = sprintf('%1.02F', ($levelPrice + $signupFee) * $vatMultiplier);
				$levelPrice += $signupFee;
			}
			else
			{
				if ($levelPrice < 0)
				{
					$levelPrice = 0;
				}

				$formatedPrice = sprintf('%1.02F', ($levelPrice) * $vatMultiplier);
			}

			$dotpos = strpos($formatedPrice, '.');
			$price_integer = substr($formatedPrice,0,$dotpos);
			$price_fractional = substr($formatedPrice,$dotpos+1);

			$formatedPriceSU = sprintf('%1.02F', $signupFee * $vatMultiplier);
			$dotposSU = strpos($formatedPriceSU, '.');
			$price_integerSU = substr($formatedPriceSU,0,$dotposSU);
			$price_fractionalSU = substr($formatedPriceSU,$dotposSU+1);
		?>
		<div class="akeebasubs-awesome-column akeebasubs-level-<?php echo $level->akeebasubs_level_id ?>">
			<div class="column-<?php echo $i == 1 ? 'first' : ($i == $max ? 'last' : 'middle')?>">
				<div class="akeebasubs-awesome-header">
					<div class="akeebasubs-awesome-level">
						<a href="<?php echo JRoute::_('index.php?option=com_akeebasubs&view=level&layout=default&format=html&slug='.$level->slug)?>" class="akeebasubs-awesome-level-link">
							<?php echo $this->escape($level->title)?>
						</a>
					</div>
					<div class="akeebasubs-awesome-price">
						<?php if(AkeebasubsHelperCparams::getParam('renderasfree', 0) && ($levelPrice < 0.01)):?>
						<?php echo JText::_('COM_AKEEBASUBS_LEVEL_LBL_FREE') ?>
						<?php else: ?>
						<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?><span class="akeebasubs-awesome-price-currency"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span><?php endif; ?><span class="akeebasubs-awesome-price-integer"><?php echo $price_integer ?><?php if((int)$price_fractional > 0): ?></span><span class="akeebasubs-awesome-price-separator">.</span><span class="akeebasubs-awesome-price-decimal"><?php echo $price_fractional ?></span><?php endif; ?><?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?><span class="akeebasubs-awesome-price-currency"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span><?php endif; ?>
						<?php endif; ?>
					</div>
					<?php if ((float)$vatRule->taxrate > 0.01): ?>
					<div class="akeebasubs-awesome-taxnotice">
						<?php if ($levelPrice > 0.01): ?>
						<?php echo JText::sprintf('COM_AKEEBASUBS_LEVELS_INCLUDESVAT', (float)$vatRule->taxrate); ?>
						<?php endif; ?>
					</div>
					<?php endif; ?>
					<?php if ($includediscount == 2): ?>
					<div class="akeebasubs-awesome-prediscount">
						<?php if(abs($discount) >= 0.01): ?>
						<span class="akeebasubs-awesome-prediscount-label">
						<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_PREDISCOUNT'); ?>
						</span>
						<s>
						<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?><span class="akeebasubs-awesome-price-currency"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span><?php endif; ?><span class="akeebasubs-awesome-price-integer"><?php echo $price_integerD ?></span><?php if((int)$price_fractionalD > 0): ?><span class="akeebasubs-awesome-price-separator">.</span><span class="akeebasubs-awesome-price-decimal"><?php echo $price_fractionalD ?></span><?php endif; ?><?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?><span class="akeebasubs-awesome-price-currency"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span><?php endif; ?>
						</s>
						<?php endif; ?>
					</div>
					<?php endif; ?>
					<?php if ($includesignup == 2): ?>
					<div class="akeebasubs-awesome-signup">
						<?php if(abs($signupFee) >= 0.01): ?>
						<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_SIGNUPFEE_LIST'); ?>
						<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?><span class="akeebasubs-awesome-price-currency"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span><?php endif; ?><span class="akeebasubs-awesome-price-integer"><?php echo $price_integerSU ?></span><?php if((int)$price_fractionalSU > 0): ?><span class="akeebasubs-awesome-price-separator">.</span><span class="akeebasubs-awesome-price-decimal"><?php echo $price_fractionalSU ?></span><?php endif; ?><?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?><span class="akeebasubs-awesome-price-currency"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span><?php endif; ?>
						<?php endif; ?>
					</div>
					<?php endif; ?>
				</div>
				<div class="akeebasubs-awesome-body">
					<div class="akeebasubs-awesome-image">
						<img src="<?php echo AkeebasubsHelperImage::getURL($level->image)?>" />
					</div>
					<div class="akeebasubs-awesome-description">
						<?php echo JHTML::_('content.prepare', AkeebasubsHelperMessage::processLanguage($level->description) );?>
					</div>
				</div>
				<div class="akeebasubs-awesome-footer">
					<td class="akeebasubs-awesome-subscribe">
						<button
							class="btn btn-inverse btn-default"
							onclick="window.location='<?php echo JRoute::_('index.php?option=com_akeebasubs&view=level&slug='.$level->slug.'&format=html&layout=default')?>'">
							<?php echo JText::_('COM_AKEEBASUBS_LEVELS_SUBSCRIBE')?>
						</button>
					</td>
				</div>
			</div>
		</div>
		<?php endforeach; ?>
		<div class="level-clear"></div>
	</div>
</div>

<?php echo AkeebasubsHelperModules::loadposition('akeebasubscriptionslistfooter')?>
</div>