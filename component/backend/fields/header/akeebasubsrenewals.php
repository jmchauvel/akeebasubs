<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class F0FFormHeaderAkeebasubsrenewals extends F0FFormHeaderFieldselectable
{
	protected function getOptions()
	{
		$options[] = JHTML::_('select.option', 1, JText::_('COM_AKEEBASUBS_RENEWALS_USERSWITHRENEWALS'));
		$options[] = JHTML::_('select.option', -1, JText::_('COM_AKEEBASUBS_RENEWALS_USERSWITHOUTRENEWALS'));

		return $options;
	}
}
