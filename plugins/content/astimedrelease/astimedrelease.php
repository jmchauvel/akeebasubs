<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');

// Make sure FOF is loaded, otherwise do not run
if(!defined('FOF_INCLUDED')) {
	include_once JPATH_LIBRARIES.'/fof/include.php';
}
if(!defined('FOF_INCLUDED')) return;

// Do not run if Akeeba Subscriptions is not enabled
jimport('joomla.application.component.helper');
if(!JComponentHelper::isEnabled('com_akeebasubs', true)) return;

class plgContentAstimedrelease extends JPlugin
{
	private $levelMap = array();
	
	private $levelElapsed = array();
	
	private $levelRemaining = array();
	
	/**
	 * Constructor and initialisation
	 * 
	 * @param type $subject
	 * @param type $config
	 */
	public function __construct(&$subject, $config = array()) {
		parent::__construct($subject, $config);
		
		$this->initialiseArrays();
	}
	
	/**
	 * Initialises the subscription arrays
	 */
	private function initialiseArrays()
	{
		// init
		$this->levelMap = array();
		$this->levelElapsed = array();
		$this->levelRemaining = array();
		
		// get level title to ID map
		$levels = FOFModel::getTmpInstance('Levels', 'AkeebasubsModel')
			->getList(true);
		if(!empty($levels)) {
			foreach($levels as $level) {
				$level->title = trim($level->title);
				$level->title = strtoupper($level->title);
				$this->levelMap[$level->title] = $level->akeebasubs_level_id;
			}
		} else {
			return;
		}
		
		// Get the user's subscriptions and calculates how much time has elapsed
		// and how much is remaining on each subscription level. It is smart.
		// If you have subscribed for 2 one-month subscriptions over the last
		// five years the elapsed time in this subscription level is 2 months,
		// not five years!
		$user = JFactory::getUser();
		if($user->guest) return;
		
		$subs = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
			->user_id($user->id)
			->paystate('C')
			->getList(true);
		if(empty($subs) || !count($subs)) return;
		jimport('joomla.utilities.date');
		$levelElapsed = array();
		$levelDuration = array();
		$now = new JDate();
		$now = $now->toUnix();
		foreach($subs as $sub) {
			$up = new JDate($sub->publish_up);
			$up = $up->toUnix();
			$down = new JDate($sub->publish_down);
			$down = $down->toUnix();
			
			$duration = $down - $up;
			if($now < $up) {
				$elapsed = 0;
			} elseif($now >= $down) {
				$elapsed = $duration;
			} else {
				$elapsed = $now - $up;
			}
			
			$levelid = $sub->akeebasubs_level_id;
			
			if(!array_key_exists($levelid, $levelDuration)) {
				$levelDuration[$levelid] = 0;
			}
			if(!array_key_exists($levelid, $levelElapsed)) {
				$levelElapsed[$levelid] = 0;
			}
			
			$levelDuration[$levelid] += $duration;
			$levelElapsed[$levelid] += $elapsed;
		}
		
		foreach($levelDuration as $levelid => $duration) {
			$elapsed = $levelElapsed[$levelid];
			$this->levelRemaining[$levelid] = ceil(($duration - $elapsed) / 86400);
			$this->levelElapsed[$levelid] = floor($elapsed / 86400);
		}
	}

	/**
	 * Gets the level ID out of a level title. If an ID was passed, it simply returns the ID.
	 * If a non-existent subscription level is passed, it returns -1.
	 *
	 * @param $title string|int The subscription level title or ID
	 *
	 * @return int The subscription level ID
	 */
	private function getId($title)
	{
		$title = strtoupper($title);
		$title = trim($title);
		if(array_key_exists($title, $this->levelMap)) {
			// Mapping found
			return($this->levelMap[$title]);
		} elseif( (int)$title == $title ) {
			// Numeric ID passed
			return (int)$title;
		} else {
			// No match!
			return -1;
		}
	}
	
	/**
	 * Checks if a time expression is true. The expression syntax is:
	 * LEVEL[(operand1[, operand2])]
	 * e.g.
	 * LEVEL1 -- do we have any remaining time in level 1
	 * LEVEL1(10) -- have more than 10 days elapsed in LEVEL1
	 * LEVEL1(X, 10) -- have less than 10 days elapsed in LEVEL1
	 * LEVEL1(10, 20) -- have 10 to 20 days elapsed in LEVEL1
	 * LEVEL1(-10) -- do we have MORE than 10 days in LEVEL1
	 * LEVEL1(-5,-10) -- are we in the last 10 to 5 days of LEVEL1
	 * LEVEL1(X, -10) -- do he have LESS than 10 days in LEVEL1 
	 * 
	 * @param string $expr
	 * @return bool
	 */
	private function isTrue($expr)
	{
		$level = ''; $expression = '';
		$paremPos = strrpos($expr, '(');
		if($paremPos !== false) {
			$level = $this->getId(substr($expr, 0, -$paremPos));
			$expression = substr($expr, -$paremPos);
		} else {
			$level = $this->getId($expr);
		}
		
		// No level? No joy.
		if($level <= 0) return false;
		
		// Level not in array? No joy.
		if(!array_key_exists($level, $this->levelElapsed)) return;
		
		// Parse the time expression
		$minConstraint = null;
		$maxConstraint = null;
		
		if(!empty($expression)) {
			$expression = trim($expression,'() ');
			$expression = str_replace(' ','',$expression);
			$exParts = explode(',', $expression);

			if(trim($exParts[0]) == 'X') {
				$minConstraint = null;
			} else {
				$minConstraint = (int)$exParts[0];
			}
			if(count($exParts) > 1) {
				if(trim($exParts[1]) == 'X') {
					$maxConstraint = null;
				} else {
					$maxConstraint = (int)$exParts[1];
				}
			}
		}
		
		$result = true;
		
		if(is_null($minConstraint)) {
			// Do nothing
		} elseif($minConstraint < 0) {
			// Negative min constraint
			$result = $result && ($this->levelRemaining >= -$minConstraint);
		} else {
			// Positive min constraint
			$result = $result && ($this->levelElapsed >= $minConstraint);
		}
		
		if(is_null($maxConstraint)) {
			// Do nothing
		} elseif($maxConstraint < 0) {
			// Negative max constraint
			$result = $result && ($this->levelRemaining <= -$maxConstraint);
		} else {
			// Positive max constraint
			$result = $result && ($this->levelElapsed <= $maxConstraint);
		}
		
		return $result;
	}
	
	/**
	 * preg_match callback to process each match
	 */
	private function process($match)
	{
		$ret = '';
		
		if ($this->analyze($match[1])) {
			$ret = $match[2];
		}
		
		return $ret;
	}
	
	/**
	 * preg_match callback to process each match
	 */
	private function processElapsed($match)
	{
		$ret = '';
		
		return $this->analyzeTime($match[1], true);
		
		return $ret;
	}
	
	/**
	/**
	 * preg_match callback to process each match
	 */
	private function processRemaining($match)
	{
		$ret = '';
		
		return $this->analyzeTime($match[1], false);
		
		return $ret;
	}
	
	/**
	 * Analyzes a filter statement and decides if it's true or not
	 * 
	 * @return boolean
	 */
	private function analyze($statement)
	{
		$ret = false;
		
		if ($statement) {
			// Stupid, stupid crap... ampersands replaced by &amp;...
			$statement = str_replace('&amp;&amp;', '&&', $statement);
			// First, break down to OR statements
			$items = explode("||", trim($statement) );
			for ($i=0; $i<count($items) && !$ret; $i++) {
				// Break down AND statements
				$expression = trim($items[$i]);
				$subitems = explode('&&', $expression);
				$ret = true;
				
				foreach($subitems as $item)
				{
					$item = trim($item);
					$negate = false;
					if(substr($item,0,1) == '!') {
						$negate = true;
						$item = substr($item,1);
						$item = trim($item);
					}
					$expr = trim($item);
					$result = $this->isTrue($expr);
					$ret = $ret && ($negate ? !$result : $result);
				}
			}
		}

		return $ret;
	}
	
	private function analyzeTime($expr, $isElapsed = true)
	{
		$level = ''; $expression = '';
		$paremPos = strrpos($expr, ',');
		if($paremPos !== false) {
			$level = $this->getId(substr($expr, 0, -$paremPos));
			$expression = substr($expr, -$paremPos);
		} else {
			$level = $this->getId($expr);
		}
		
		// No level? No joy.
		if($level <= 0) return 0;
		
		// Level not in array? No joy.
		if(!array_key_exists($level, $this->levelElapsed)) 0;
		
		// Parse the time expression
		$addRemove = 0;
		if(!empty($expression)) {
			$expression = trim($expression,', ');
			$expression = str_replace(' ','',$expression);
			$addRemove = (int)$expression;
		}
		
		$result = $isElapsed ? $this->levelElapsed[$level] : $this->levelRemaining[$level];
		
		$result += $addRemove;
		
		return $result;
	}
	
	public function onContentPrepare($context, &$row, &$params, $page = 0)
	{
		$text = is_object($row) ? $row->text : $row;
		
		if ( JString::strpos( $row->text, 'astimedrelease' ) !== false ) {
			$regex = "#{astimedrelease(.*?)}(.*?){/astimedrelease}#s";
			$text = preg_replace_callback( $regex, array('self', 'process'), $text );
		}
		if ( JString::strpos( $row->text, 'asdayselapsed' ) !== false ) {
			$regex = "#{asdayselapsed(.*?)}#s";
			$text = preg_replace_callback( $regex, array('self', 'processElapsed'), $text );
		}
		if ( JString::strpos( $row->text, 'asdaysremaining' ) !== false ) {
			$regex = "#{asdaysremaining(.*?)}#s";
			$text = preg_replace_callback( $regex, array('self', 'processRemaining'), $text );
		}
		
		if(is_object($row)) {
			$row->text = $text;
		} else {
			$row = $text;
		}
		
		return true;
	}
}