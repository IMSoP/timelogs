<?php

/**
 * Useful and very generic functions that wouldn't be out of place if they were built into PHP
 */

class CWT
{
	/**
	 * coalesce
	 * PHP analogue of the SQL Coalesce() function
	 * @param an arbitrary number of arguments
	 * @return the first argument which is not NULL; If all arguments are NULL, will return NULL
	 *
	 * CWT::coalesce( $a, $b ) is equivalent to ($a === NULL ? $a : $b)
	 * but CWT::coalesce( hard_work(), 'default' ) only has to do hard_work() once
	 * [OTOH, CWT::coalesce( $a, hard_work() ) has to do hard_work() even if the result gets thrown away]
	 */
	public static function coalesce()
	{
		foreach ( func_get_args() as $arg )
		{
			if ( ! is_null($arg) )
			{
				return $arg;
			}
		}
	}
	
	/**
	 * coalesce_empty
	 * PHP analogue of the SQL Coalesce() function, but testing using empty()
	 * @param an arbitrary number of arguments
	 * @return the first argument which is not empty() - i.e. not NULL, false, '', 0, array(), etc
	 * If all arguments are empty, will return NULL
	 *
	 * CWT::coalesce_empty( $a, $b ) is equivalent to ($a ? $a : $b)
	 * but CWT::coalesce_empty( hard_work(), 'default' ) only has to do hard_work() once
	 * [OTOH, CWT::coalesce_empty( $a, hard_work() ) has to do hard_work() even if the result gets thrown away]
	 */
	public static function coalesce_empty()
	{
		foreach ( func_get_args() as $arg )
		{
			if ( ! empty($arg) )
			{
				return $arg;
			}
		}
	}

	function next_available_day(&$offset, &$schedule, $future_time)
	{
		$next_day = CWT::next_day($offset);
		while ($next_day && isset($future_time[$next_day]))
		{
			// Allocate already logged time to the day its been logged to
			foreach ($future_time[$next_day] as $task)
			{
				$schedule[$next_day]['hours_allocated'] += $task['time'];
				$schedule[$next_day]['tasks_allocated'][] = $task['task_id'];
			}
			
			// If we still have some time left for this day, then allow more time to be logged to it
			if ($schedule[$next_day]['hours_allocated'] <= HOURS_PER_DAY)
			{
				break;
			}
			
			$next_day = CWT::next_day($offset);
		}
		
		return $next_day;
	}
	
	/**
	 * Get the next day that isn't a weekend, and increment offset by 1
	 *
	 * @return Y-m-d
	 * @param int $offset Passed by reference, will be incremented by at least 1
	 */
	function next_day(&$offset)
	{
		do
		{
			$offset++;
		}
		while(
			in_array(gmdate('D', gmmktime() + ($offset * 24 * 60 * 60)), array('Sat', 'Sun'))
			||
			in_array(gmdate('Y-m-d', gmmktime() + ($offset * 24 * 60 * 60)), $GLOBALS['config']['non-work_dates'])
		);
		return gmdate('Y-m-d', gmmktime() + ($offset * 24 * 60 * 60));		
	}

}
